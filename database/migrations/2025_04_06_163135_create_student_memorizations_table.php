<?php

// database/migrations/xxxx_xx_xx_create_student_memorizations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_memorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_verse_id')->constrained('quran_verses');
            $table->foreignId('to_verse_id')->constrained('quran_verses');
            $table->string('note')->nullable();
            $table->enum('type', ['permanent', 'temporary', 'test'])->default('permanent');
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('student_memorizations');
    }
};

