<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftHariSeeder extends Seeder
{
    public function run(): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($days as $day) {
            \App\Models\ShiftHari::updateOrCreate(
                [
                    'shift_id' => 1,
                    'hari' => (string) $day 
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
