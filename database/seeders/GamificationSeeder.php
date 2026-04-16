<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PointRule;
use App\Models\FlexibilityItem;

class GamificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. RULE DASAR ABSENSI (Berlaku untuk SEMUA ROLE)
        PointRule::create([
            'rule_name' => 'Datang Lebih Awal / Tepat Waktu',
            'target_role' => 'Semua', 
            'condition_operator' => '<=',
            'condition_value' => '0', // Kurang dari atau sama dengan 0 menit selisih (tepat waktu)
            'point_modifier' => 15
        ]);

        PointRule::create([
            'rule_name' => 'Terlambat (Masuk Poin Minimal)',
            'target_role' => 'Semua',
            'condition_operator' => '>',
            'condition_value' => '0', // Lebihi 0 menit selisih
            'point_modifier' => 5
        ]);

        // 2. RULE HUKUMAN / PENALTI
        PointRule::create([
            'rule_name' => 'Tidak Hadir (ALFA)',
            'target_role' => 'Semua', // Terapkan ke Admin dan Karyawan
            'condition_operator' => '=',
            'condition_value' => 'ALFA',
            'point_modifier' => -50
        ]);

        // 3. ITEM TOKO FLEKSIBILITAS (VOUCHER)
        // Note: Field type & value harus sesuai dengan logika "LATE_EXEMPTION" di backend
        FlexibilityItem::create([
            'item_name' => 'Bebas Terlambat 30 Menit',
            'point_cost' => 250,
            'stock_limit' => null,
            'type' => 'LATE_EXEMPTION',
            'value' => '30'
        ]);

        FlexibilityItem::create([
            'item_name' => 'Tukar Kopi di Kantin',
            'point_cost' => 50,
            'stock_limit' => 10,
            'type' => 'OTHER',
            'value' => 0
        ]);
    }
}