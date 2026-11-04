<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * يميّز صفوف «مراجعة السابق» داخل جدول الحفظ الجديد (type = new)
     * عن صفوف الحفظ الجديد نفسها؛ جدول المراجعة المستقل (type = review) لا يتأثر.
     */
    public function up(): void
    {
        Schema::table('review_plans', function (Blueprint $table) {
            $table->boolean('is_revision')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('review_plans', function (Blueprint $table) {
            $table->dropColumn('is_revision');
        });
    }
};
