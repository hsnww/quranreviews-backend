<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تاريخ كل يوم في جدول الحفظ/المراجعة البعيدة (الخطط القديمة تبقى بلا تاريخ)،
     * وتمييز صفوف «مراجعة الأسبوع» يومي الجمعة والسبت لدروس أيام الأسبوع.
     */
    public function up(): void
    {
        Schema::table('review_plans', function (Blueprint $table) {
            $table->date('date')->nullable()->after('day_number');
            $table->boolean('is_weekly_review')->default(false)->after('is_revision');
        });
    }

    public function down(): void
    {
        Schema::table('review_plans', function (Blueprint $table) {
            $table->dropColumn(['date', 'is_weekly_review']);
        });
    }
};
