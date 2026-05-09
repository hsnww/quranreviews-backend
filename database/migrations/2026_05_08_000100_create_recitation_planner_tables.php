<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitation_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->enum('period_type', ['weekly', 'monthly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamps();
        });

        Schema::create('recitation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('recitation_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->tinyInteger('day_of_week');
            $table->enum('prayer_name', ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha']);
            $table->enum('execution_status', ['scheduled', 'completed', 'skipped'])->default('scheduled');
            $table->timestamps();

            $table->index(['user_id', 'date', 'prayer_name']);
        });

        Schema::create('recitation_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('recitation_sessions')->cascadeOnDelete();
            $table->tinyInteger('rakaa_number');
            $table->unsignedInteger('start_surah');
            $table->unsignedInteger('start_ayah');
            $table->unsignedInteger('end_surah');
            $table->unsignedInteger('end_ayah');
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });

        Schema::create('recitation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('recitation_sessions')->nullOnDelete();
            $table->date('date');
            $table->enum('prayer_name', ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha']);
            $table->tinyInteger('rakaa_number');
            $table->unsignedInteger('start_surah');
            $table->unsignedInteger('start_ayah');
            $table->unsignedInteger('end_surah');
            $table->unsignedInteger('end_ayah');
            $table->boolean('is_from_plan')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitation_history');
        Schema::dropIfExists('recitation_segments');
        Schema::dropIfExists('recitation_sessions');
        Schema::dropIfExists('recitation_plans');
    }
};
