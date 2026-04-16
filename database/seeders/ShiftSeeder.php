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
        // 1. Shift Utama
        $shift1 = Shift::firstOrCreate(
            ['nama' => 'Kerja woi'],
            [
                'jam_masuk'  => '14:36:00',
                'jam_pulang' => '17:00:00',
                'warna'      => '#3B82F6',
            ]
        );

        $hariShift1 = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        foreach ($hariShift1 as $hari) {
            $shift1->hariKerja()->firstOrCreate(['hari' => $hari]);
        }

        // 2. Shift Kamis
        $shift2 = Shift::firstOrCreate(
            ['nama' => 'Shift Kamis (15:40)'],
            [
                'jam_masuk'  => '15:40:00',
                'jam_pulang' => '16:15:00',
                'warna'      => '#EAB308',
            ]
        );
    }
}
