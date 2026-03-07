<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kantors', function (Blueprint $table) {
            $table->integer('toleransi_menit')->default(15)->after('radius_meter');
        });
    }

    public function down()
    {
        Schema::table('kantors', function (Blueprint $table) {
            $table->dropColumn('toleransi_menit');
        });
    }
};