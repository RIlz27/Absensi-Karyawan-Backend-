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
                'name' => 'Admin Testing',
                'nip' => '12345',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // 2. Buat Kantor
        Kantor::updateOrCreate(
            ['id' => 1],
            [
                'nama' => 'Kantor Pusat',
                'alamat' => 'Jl. Merdeka No. 10, Jakarta',
                'latitude' => -6.826774,
                'longitude' => 107.127078,
                'radius_meter' => 60,
            ]
        );

        // 3. Buat Master Shift
        $shift = Shift::updateOrCreate(
            ['id' => 1], 
            [
                'nama' => 'Office Hour',
                'jam_masuk' => '06:30:00',
                'jam_pulang' => '17:30:00',
                'toleransi_menit' => 15,
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