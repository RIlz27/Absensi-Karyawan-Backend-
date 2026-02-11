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
    public function run()
{
    \App\Models\User::updateOrCreate(
        ['nip' => '12345'], // Cek berdasarkan NIP
        [
            'name' => 'Administrator',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]
    );
}
}