<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('verses_per_card')->nullable()->after('juz_ids');
            $table->boolean('ensure_juz_coverage')->default(false)->after('verses_per_card');
        });

        Schema::table('quiz_session_cards', function (Blueprint $table) {
            $table->unsignedTinyInteger('jozo')->nullable()->after('sora_number');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn(['verses_per_card', 'ensure_juz_coverage']);
        });

        Schema::table('quiz_session_cards', function (Blueprint $table) {
            $table->dropColumn('jozo');
        });
    }
};
