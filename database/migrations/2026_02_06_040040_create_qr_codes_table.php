<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('kantor_id')
                  ->constrained('kantors') 
                  ->cascadeOnDelete();

            $table->enum('type', ['masuk', 'pulang'])->index(); 
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamp('expired_at')->nullable(); // Kasih nullable biar aman pas data entry
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Pastikan foreign key check dimatikan sebentar pas drop biar gak error di MySQL
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('qr_codes');
        Schema::enableForeignKeyConstraints();
    }
};