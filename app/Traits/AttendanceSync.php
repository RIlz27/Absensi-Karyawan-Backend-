<?php

namespace App\Traits;

use App\Models\Absensi;
use App\Models\PointLedger;
use App\Models\PointRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait AttendanceSync {

    /**
     * Real-time Sync for ALFA status
     */
    public function syncAlfaStatus($user)
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $hariInggris = $now->format('l');

        // 1. Cek apakah user punya shift hari ini (dengan prioritas tambahan > biasa)
        $shiftToday = $user->shiftForDay($hariInggris);
        if (!$shiftToday) return;

        // 2. Cek apakah sudah lewat jam pulang
        try {
            // Gunakan Carbon::parse agar lebih fleksibel dibanding createFromFormat
            $jamPulangShift = Carbon::parse($shiftToday->jam_pulang);
        } catch (\Exception $e) {
            // Fallback jika format jam di DB aneh
            return;
        }
        if ($now->toTimeString() <= $jamPulangShift->toTimeString()) {
            return; // Belum waktunya ALFA
        }

        // 3. Cek apakah sudah ada catatan absensi hari ini (Hadir/Izin/Cuti)
        $hasRecord = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->exists();
        if ($hasRecord) return;

        $hasIzin = DB::table('izins')
            ->where('user_id', $user->id)
            ->where('status', 'Approved')
            ->whereDate('tanggal', $today)
            ->exists();
        if ($hasIzin) return;

        $hasCuti = DB::table('cutis')
            ->where('user_id', $user->id)
            ->where('status', 'Approved')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->exists();
        if ($hasCuti) return;

        // 4. Jika semua kriteria terpenuhi -> TANDAI ALFA SEKARANG
        DB::transaction(function () use ($user, $shiftToday, $today) {
            Absensi::create([
                'user_id' => $user->id,
                'shift_id' => $shiftToday->id,
                'kantor_id' => $shiftToday->pivot->kantor_id ?? $user->kantor_id,
                'tanggal' => $today->toDateString(),
                'status' => 'Alfa',
                'metode' => 'Manual',
                'latitude' => 0,
                'longitude' => 0,
            ]);

            // Ambil Penalti Alfa dari Aturan Poin (Gamification)
            $role = $user->role ?? 'karyawan';
            $alfaRule = PointRule::where('condition_value', 'ALFA')
                        ->whereIn('target_role', [$role, 'Semua'])
                        ->first();
                        
            if ($alfaRule) {
                $dendaAlfa = $alfaRule->point_modifier; // Nilai negatif dari DB (ex: -50)
                $saldoSekarang = $user->points ?? 0;
                
                PointLedger::create([
                    'user_id' => $user->id,
                    'transaction_type' => 'PENALTY',
                    'amount' => $dendaAlfa,
                    'current_balance' => $saldoSekarang + $dendaAlfa,
                    'description' => $alfaRule->rule_name . " pada " . $today->toDateString(),
                ]);

                $user->update(['points' => $saldoSekarang + $dendaAlfa]);
            }
        });
    }
}
