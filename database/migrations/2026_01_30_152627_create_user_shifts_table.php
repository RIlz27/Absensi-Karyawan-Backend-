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
                Schema::create('user_shifts', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('kantor_id')->constrained()->cascadeOnDelete();

                    // Tambahan biar bisa atur hari (Senin, Selasa, dll)
                    $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);

                    $table->timestamps();

                    // Mencegah duplikasi: 1 user gak boleh punya 2 shift di hari yang sama
                    $table->unique(['user_id', 'hari']);
                });
            }

            /**
             * Reverse the migrations.
             */
            public function down(): void
            {
                Schema::dropIfExists('user_shifts');
            }
        };
