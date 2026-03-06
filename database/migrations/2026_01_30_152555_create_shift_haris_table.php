<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_hari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->enum('hari', ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']);
            $table->timestamps();
            $table->unique(['shift_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_hari');
    }
};
