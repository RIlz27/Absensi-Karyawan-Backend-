<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $karyawans = [
            ['nip' => '3001', 'name' => 'Putri Ayudia'],
            ['nip' => '3002', 'name' => 'Rizky Ramadhan'],
            ['nip' => '3003', 'name' => 'Siti Aminah'],
            ['nip' => '3004', 'name' => 'Dedi Kurniawan'],
            ['nip' => '3005', 'name' => 'Eka Saputra'],
            ['nip' => '3006', 'name' => 'Dewi Lestari'],
            ['nip' => '3007', 'name' => 'Fajar Sidik'],
            ['nip' => '3008', 'name' => 'Hendra Wijaya'],
            ['nip' => '3009', 'name' => 'Gita Permata'],
            ['nip' => '3010', 'name' => 'Bambang Subiakto'],
        ];

        foreach ($karyawans as $data) {
            User::create([
                'nip'       => $data['nip'],
                'name'      => $data['name'],
                'role'      => 'karyawan', // Semuanya set karyawan
                'kantor_id' => 1,          // Pastikan ID kantor 1 ada di DB lu
                'password'  => Hash::make($data['nip']), // Password = NIP
                'is_active' => true,
            ]);
        }
    }
}