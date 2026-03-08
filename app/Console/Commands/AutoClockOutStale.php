<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Absensi;
use Carbon\Carbon;

class AutoClockOutStale extends Command
{
    protected $signature = 'app:auto-clock-out-stale';
    protected $description = 'Force close attendance sessions that have been open for >16 hours';

    public function handle()
    {
        // Temukan sesi absen yang sudah menggantung terlalu lama (lebih dari 16 jam tanpa checkout)
        // 16 jam dipilih untuk menghindari nabrak dengan orang yang punya night shift panjang atau lembur extrem
        $staleThreshold = Carbon::now()->subHours(16);

        $staleSessions = Absensi::whereNull('jam_pulang')
            ->whereNotNull('jam_masuk')
            ->where('created_at', '<', $staleThreshold)
            ->get();

        $count = 0;

        foreach ($staleSessions as $session) {
            // Jam pulang diforse untuk ditutup di jam sekarang, 
            // ATAU bisa diisi sama dengan jam pulangnya shift (ini lebih aman secara fairness)
            // Tapi untuk log audit, mending kasih note.
            
            $session->update([
                'jam_pulang' => Carbon::now(),
                'status' => 'AUTO-PULANG' // override status biar Admin tau dia ga bener nyecan
            ]);

            $count++;
        }

        $this->info("Successfully auto-clocked out {$count} stale sessions.");
    }
}
