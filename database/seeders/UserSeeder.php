<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat Admin
        User::create([
            'name'     => 'Administrator',
            'nip'      => '12345', // Ganti email jadi nip
            'password' => Hash::make('password'),
            'role'     => 'admin'
        ]);

        // Buat Karyawan (buat ngetes login sebagai karyawan nanti)
        User::create([
            'name'     => 'Budi Karyawan',
            'nip'      => '54321',
            'password' => Hash::make('password'),
            'role'     => 'karyawan'
        ]);
    }
}