<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tambahkan Super Admin yang Pasti Bisa Login
        User::firstOrCreate(
            ['nip' => 'admin'],
            [
                'name'      => 'Super Admin',
                'role'      => 'admin',
                'kantor_id' => 1,
                'password'  => Hash::make('admin'), // Password: admin
                'is_active' => true,
            ]
        );
    }
}