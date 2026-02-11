<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // 1.User
        $user = \App\Models\User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Admin Testing',
                'nip' => '12345', // Pastikan NIP unik
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // 2.Kantor ID
        $kantor = \App\Models\Kantor::updateOrCreate(
            ['id' => 1],
            [
                'nama' => 'Kantor Pusat',
                'alamat' => 'Jl. Merdeka No. 10, Jakarta',
                'latitude' => -6.826774,
                'longitude' => 107.127078,
                'radius_meter' => 60,
            ]
        );

        // 3.Master Shift
        $shiftPagi = \App\Models\Shift::updateOrCreate(
            ['nama' => 'Office Hour'],
            [
                'jam_masuk' => '08:00:00',
                'jam_pulang' => '17:00:00',
                'toleransi_menit' => 15,
            ]
        );

        // 4. Plotting ke User (Senin sampai Jumat sekaligus)
        $hariKerja = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($hariKerja as $hari) {
            \App\Models\UserShift::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'hari'    => $hari, // Akan mengisi Monday, Tuesday, dst.
                ],
                [
                    'shift_id'  => $shiftPagi->id,
                    'kantor_id' => $kantor->id,
                ]
            );
        }
    }
}
