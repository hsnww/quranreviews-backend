<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // in_progress | completed
            $table->json('juz_ids');
            $table->unsignedSmallInteger('requested_card_count');
            $table->unsignedSmallInteger('actual_card_count')->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedInteger('total_errors')->nullable();
            $table->string('score_formula')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('quiz_session_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $table->unsignedSmallInteger('order_index');
            $table->unsignedSmallInteger('sora_number');
            $table->json('verse_ids');
            $table->unsignedInteger('mistake_count')->default(0);
            $table->timestamps();

            $table->unique(['quiz_session_id', 'order_index']);
            $table->index('quiz_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_session_cards');
        Schema::dropIfExists('quiz_sessions');
    }
};
