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
        Schema::create('quran_verses', function (Blueprint $table) {
            $table->id();
            $table->integer('sora');
            $table->integer('ayah');
            $table->text('text');
            $table->integer('page');
            $table->integer('hizb');
            $table->integer('qrtr');
            $table->integer('jozo');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_verses');
    }
};
