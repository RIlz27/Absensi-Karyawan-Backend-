<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //table categori
        Schema::create('assessment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('Karyawan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        //table penilaian
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('evaluatee_id')->constrained('users')->onDelete('cascade');
            $table->date('assessment_date');
            $table->string('period_type')->default('Bulanan');
            $table->string('period_name');
            $table->text('general_notes')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        //tabel Detail
        Schema::create('assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('assessment_categories')->onDelete('cascade');
            $table->decimal('score', 5, 2);
            $table->foreignId('question_id')->nullable()->constrained('assessment_questions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_details');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('assessment_categories');
    }
};
