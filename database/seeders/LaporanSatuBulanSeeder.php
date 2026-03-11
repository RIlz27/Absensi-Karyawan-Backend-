<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Kantor;
use App\Models\Shift;
use Carbon\Carbon;

class LaporanSatuBulanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil semua karyawan dari seeder sebelumnya
        $users = User::where('role', 'karyawan')->get();

        // 2. Ambil data kantor pertama yang ada di DB lo
        $kantor = Kantor::first();

        // 3. Ambil shift pertama sebagai acuan jam masuk/pulang
        $shift = Shift::first();

        if (!$kantor || !$shift) {
            $this->command->error("Waduh Min, data Kantor atau Shift di DB lo kosong! Isi dulu baru jalankan seeder ini.");
            return;
        }

        $startOfMonth = now()->subMonth()->startOfMonth();
        $endOfMonth = now()->subMonth()->endOfMonth();

        foreach ($users as $user) {
            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {

                if ($date->isWeekend()) continue;

                $chance = rand(1, 100);

                if ($chance <= 85) {
                    // 85% Peluang HADIR
                    $isTerlambat = rand(1, 100) <= 20;
                    $jamMasukDefault = Carbon::parse($shift->jam_masuk);

                    $jamMasuk = $isTerlambat
                        ? $jamMasukDefault->copy()->addMinutes(rand(5, 45))->format('H:i:s')
                        : $jamMasukDefault->copy()->subMinutes(rand(5, 20))->format('H:i:s');

                    Absensi::create([
                        'user_id' => $user->id,
                        'shift_id' => $shift->id,
                        'kantor_id' => $kantor->id,
                        'tanggal' => $date->format('Y-m-d'),
                        'jam_masuk' => $jamMasuk,
                        'jam_pulang' => $shift->jam_pulang,
                        'status' => $isTerlambat ? 'Terlambat' : 'Hadir',
                        'latitude' => '-6.827145',
                        'longitude' => '107.137249',
                    ]);
                } elseif ($chance <= 92) {
                    // 7% Peluang IZIN
                    Izin::create([
                        'user_id'    => $user->id,
                        'tanggal'    => $date->format('Y-m-d'),
                        'jam_mulai'  => '08:00:00', 
                        'jam_selesai' => '17:00:00', 
                        'alasan'     => 'Keperluan mendadak keluarga',
                        'status'     => 'Approved', 
                        'approved_by' => 1,
                        'approved_at' => now(),
                    ]);
                }
            }
        }
        $this->command->info("Data laporan sebulan lalu buat 10 user beres, Min!");
    }
}
