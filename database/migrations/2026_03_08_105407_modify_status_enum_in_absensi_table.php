<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE absensi MODIFY COLUMN status ENUM('Hadir', 'Terlambat', 'Tidak Hadir', 'Pulang Cepat', 'Izin', 'Cuti', 'Alfa') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE absensi MODIFY COLUMN status ENUM('Hadir', 'Terlambat', 'Tidak Hadir', 'Pulang Cepat') NOT NULL");
    }
};
