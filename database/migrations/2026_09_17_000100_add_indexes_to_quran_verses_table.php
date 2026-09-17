<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول الآيات يُستعلم عنه بـ sora و hizb و qrtr و page في كل طلب تقريبًا،
     * وبدون فهارس يمسح MySQL الجدول كاملًا (6236 صفًا) في كل مرة.
     */
    public function up(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->index('sora', 'quran_verses_sora_index');
            $table->index('hizb', 'quran_verses_hizb_index');
            $table->index('qrtr', 'quran_verses_qrtr_index');
            $table->index('jozo', 'quran_verses_jozo_index');
            $table->index('page', 'quran_verses_page_index');
            // ترتيب الآيات داخل السورة (getBySurah يرتّب ثم يعرض)
            $table->index(['sora', 'ayah'], 'quran_verses_sora_ayah_index');
        });
    }

    public function down(): void
    {
        Schema::table('quran_verses', function (Blueprint $table) {
            $table->dropIndex('quran_verses_sora_index');
            $table->dropIndex('quran_verses_hizb_index');
            $table->dropIndex('quran_verses_qrtr_index');
            $table->dropIndex('quran_verses_jozo_index');
            $table->dropIndex('quran_verses_page_index');
            $table->dropIndex('quran_verses_sora_ayah_index');
        });
    }
};
