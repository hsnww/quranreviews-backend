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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone')->nullable();
            $table->date('dob')->nullable();
            $table->string('institution')->nullable();
            $table->integer('memorized_parts')->default(0);
            $table->integer('preferred_review_days')->default(7);
            $table->integer('review_quarters_per_day')->default(4);
            $table->enum('new_memorization_mode', ['quarter', 'half-quarter', 'quarter-quarter'])->default('quarter');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
