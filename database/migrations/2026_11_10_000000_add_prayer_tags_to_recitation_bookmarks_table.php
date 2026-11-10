<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recitation_bookmarks', function (Blueprint $table) {
            // وسوم الصلوات المناسبة للمقطع: مصفوفة من fajr / maghrib / isha
            $table->json('prayer_tags')->nullable()->after('end_ayah');
        });
    }

    public function down(): void
    {
        Schema::table('recitation_bookmarks', function (Blueprint $table) {
            $table->dropColumn('prayer_tags');
        });
    }
};
