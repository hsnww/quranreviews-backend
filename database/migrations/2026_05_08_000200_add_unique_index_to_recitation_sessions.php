<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recitation_sessions', function (Blueprint $table) {
            $table->unique(
                ['plan_id', 'date', 'prayer_name'],
                'recitation_sessions_plan_date_prayer_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('recitation_sessions', function (Blueprint $table) {
            $table->dropUnique('recitation_sessions_plan_date_prayer_unique');
        });
    }
};
