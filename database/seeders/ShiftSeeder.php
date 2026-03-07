<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kantor;
use App\Models\Shift;
use App\Models\ShiftHari;

class ShiftSeeder extends Seeder
{
    public function run()
    {
        // 1. Buat User
        User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Airil Jahran',
                'nip' => '0085689927',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // 2. Buat Kantor (Toleransi ada di sini sekarang)
        Kantor::updateOrCreate(
            ['id' => 1],
            [
                'nama' => 'Sandi Jaya',
                'alamat' => 'Jawa Barat, Cianjur, Kel. Muka, Kp. Margaluyu',
                'latitude' => -6.826774,
                'longitude' => 107.127078,
                'radius_meter' => 60,
                'toleransi_menit' => 15,
            ]
        );

        $shift = Shift::updateOrCreate(
            ['id' => 1],
            [
                'nama' => 'Shift Biasa',
                'jam_masuk' => '05:30:00',
                'jam_pulang' => '18:30:00',
            ]
        );

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            ShiftHari::updateOrCreate(
                [
                    'shift_id' => $shift->id,
                    'hari' => $day
                ]
            );
        }
    }
}
