<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Absensi;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarkAlfaDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-alfa-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pengecekan Karyawan Alfa Otomatis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $englishDay = $today->format('l');

        $users = User::where('role', 'karyawan')->where('is_active', true)->get();

        $alfaCount = 0;

        foreach ($users as $user) {
            // Check if user has a shift today
            $shiftToday = $user->shifts()->wherePivot('hari', $englishDay)->first();

            if (!$shiftToday) {
                continue; // BUKAN hari kerja dia (Libur)
            }

            // Check if user has Absensi today
            $hasAbsensi = DB::table('absensi')
                ->where('user_id', $user->id)
                ->whereDate('tanggal', $today)
                ->exists();

            if ($hasAbsensi) continue;

            // Check if user has approved Izin today
            $hasIzin = DB::table('izins')
                ->where('user_id', $user->id)
                ->where('status', 'Approved')
                ->whereDate('tanggal', $today)
                ->exists();

            if ($hasIzin) continue;

            // Check if user has approved Cuti today
            $hasCuti = DB::table('cutis')
                ->where('user_id', $user->id)
                ->where('status', 'Approved')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->exists();

            if ($hasCuti) continue;

            // Tandai Alfa karena tidak ada keterangan
            DB::table('absensi')->insert([
                'user_id' => $user->id,
                'shift_id' => $shiftToday->id,
                'kantor_id' => $shiftToday->pivot->kantor_id ?? $user->kantor_id,
                'tanggal' => $today->format('Y-m-d'),
                'jam_masuk' => null,
                'jam_pulang' => null,
                'latitude' => 0,  // Absensi mewajibkan int, Alfa = 0 fallback
                'longitude' => 0, 
                'status' => 'Alfa',
                'metode' => 'Manual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // PILLAR 2: DYNAMIC POINT SYSTEM (Alfa Deduction)
            $newPoints = max(0, $user->points - 20);
            $user->update(['points' => $newPoints]);

            $alfaCount++;
        }

        $this->info("Berhasil memberikan status Alfa ke {$alfaCount} karyawan pada {$today->format('Y-m-d')}.");
    }
}
