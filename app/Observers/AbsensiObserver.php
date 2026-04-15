<?php

namespace App\Observers;

use App\Models\Absensi;
use App\Models\PointRule;
use App\Models\PointLedger;
use Carbon\Carbon;

class AbsensiObserver
{
    /**
     * Handle the Absensi "created" event.
     * Terpicu secara otomatis setelah record absensi baru berhasil disimpan ke database.
     */
    public function created(Absensi $absensi)
    {
        // 1. Mengambil data User untuk mengidentifikasi Role
        $user = $absensi->user;
        $role = ucfirst(strtolower($user->role ?? 'Pegawai')); 

        // 2. Mengambil semua aturan poin yang secara spesifik berlaku untuk Role tersebut (termasuk "Semua")
        $rules = \App\Models\PointRule::whereIn('target_role', [$role, 'Semua', strtolower($role)])->get();

        // Memastikan data jam_masuk valid sebelum melakukan perhitungan (mencegah null exception)
        if (!$absensi->jam_masuk) return;

        // Ekstraksi format waktu (H:i:s) dari jam_masuk untuk mempermudah komparasi logika
        $waktuAbsen = Carbon::parse($absensi->jam_masuk)->format('H:i:s');

        // 3. Melakukan iterasi untuk mencocokkan waktu absen dengan setiap aturan (PointRule)
        foreach ($rules as $rule) {
            if ($rule->condition_value === 'ALFA') continue; // Hanya diproses oleh cron job
            
            $isMatch = false;

            // Tipe A: Aturan berbasis format Waktu/Jam (Contoh: "06:30:00")
            if (str_contains($rule->condition_value, ':')) {
                $waktuRule = $rule->condition_value;

                switch ($rule->condition_operator) {
                    case '<':
                        $isMatch = $waktuAbsen < $waktuRule; break;
                    case '<=':
                        $isMatch = $waktuAbsen <= $waktuRule; break;
                    case '>':
                        $isMatch = $waktuAbsen > $waktuRule; break;
                    case '>=':
                        $isMatch = $waktuAbsen >= $waktuRule; break;
                }
            } 
            // Tipe B: Aturan berbasis Angka/Durasi (Contoh: Keterlambatan dalam menit)
            else {
                // Implementasi logika perhitungan selisih menit keterlambatan terhadap jadwal shift
                if ($absensi->shift && $absensi->shift->jam_masuk) {
                    
                    // Hitung selisih: Positive if aktual > jadwal (Late), Negative if aktual < jadwal (Early)
                    $selisihMenit = (strtotime($waktuAbsen) - strtotime($absensi->shift->jam_masuk)) / 60;
                    $ruleValue = (int) $rule->condition_value;

                    switch ($rule->condition_operator) {
                        case '<':
                            $isMatch = $selisihMenit < $ruleValue; break;
                        case '<=':
                            $isMatch = $selisihMenit <= $ruleValue; break;
                        case '>':
                            $isMatch = $selisihMenit > $ruleValue; break;
                        case '>=':
                            $isMatch = $selisihMenit >= $ruleValue; break;
                        case '==':
                            $isMatch = $selisihMenit == $ruleValue; break;
                    }
                }
            }

            // 4. Jika kondisi terpenuhi, lakukan pencatatan mutasi poin ke ledger
            if ($isMatch) {
                // Kalkulasi saldo akhir berdasarkan modifier dari aturan yang terpenuhi
                $saldoSekarang = $user->current_points; 
                $saldoBaru = $saldoSekarang + $rule->point_modifier;

                // Mencatat transaksi ke PointLedger sebagai Audit Trail
                PointLedger::create([
                    'user_id' => $user->id,
                    'transaction_type' => $rule->point_modifier > 0 ? 'EARN' : 'PENALTY',
                    'amount' => $rule->point_modifier,
                    'current_balance' => $saldoBaru,
                    'description' => "Penyesuaian otomatis: Memenuhi kriteria '{$rule->rule_name}' pada tanggal {$absensi->tanggal}",
                ]);
            }
        }
    }
}