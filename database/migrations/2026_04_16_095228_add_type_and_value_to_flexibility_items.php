<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flexibility_items', function (Blueprint $table) {
            $table->enum('type', ['LATE_EXEMPTION', 'OTHER'])->default('OTHER')->after('item_name');
            $table->integer('value')->default(0)->after('type'); // e.g., minutes of exemption
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flexibility_items', function (Blueprint $table) {
            $table->dropColumn(['type', 'value']);
        });
    }
};
