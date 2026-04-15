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
        // 1. Bikin Data Pancingan Aturan Poin
        PointRule::create([
            'rule_name' => 'Tepat Waktu',
            'target_role' => 'Karyawan', // Sesuaiin sama role yang lu pake pas login
            'condition_operator' => '>=',
            'condition_value' => '00:00:00', // Format jam gini biar tiap absen masuk PASTI dapet poin buat ngetes
            'point_modifier' => 50
        ]);

        PointRule::create([
            'rule_name' => 'Datang Kepagian (Rajin)',
            'target_role' => 'Karyawan',
            'condition_operator' => '<',
            'condition_value' => '06:30:00',
            'point_modifier' => 20
        ]);

        // 2. Bikin Data Pancingan Item Toko
        FlexibilityItem::create([
            'item_name' => 'Voucher Telat 15 Menit',
            'point_cost' => 100,
            'stock_limit' => 5
        ]);

        FlexibilityItem::create([
            'item_name' => 'Tukar Kopi di Kantin',
            'point_cost' => 50,
            'stock_limit' => null // Null artinya stoknya unlimited
        ]);
    }
}