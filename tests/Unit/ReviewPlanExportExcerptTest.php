<?php

namespace Tests\Unit;

use App\Exports\ReviewPlanExport;
use App\Models\QuranVerse;
use App\Models\ReviewPlan;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use App\Services\AyahExcerptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPlanExportExcerptTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_plan_export_uses_shared_ayah_excerpt_service(): void
    {
        Surah::create(['id' => 1, 'name' => 'الفاتحة']);

        $from = QuranVerse::create([
            'sora' => 1,
            'ayah' => 1,
            'text' => 'هذا نص طويل للتأكد من أن التصدير يستخدم نفس منطق القص الموحد',
            'page' => 1,
            'hizb' => 1,
            'qrtr' => 1,
            'jozo' => 1,
        ]);

        $to = QuranVerse::create([
            'sora' => 1,
            'ayah' => 2,
            'text' => 'نص آخر للتأكد من تطبيق نفس القاعدة على عمود إلى',
            'page' => 1,
            'hizb' => 1,
            'qrtr' => 1,
            'jozo' => 1,
        ]);

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        ReviewPlan::create([
            'student_id' => $student->id,
            'day_number' => 1,
            'from_verse_id' => $from->id,
            'to_verse_id' => $to->id,
            'type' => 'review',
        ]);

        $rows = (new ReviewPlanExport($student->id))->collection();
        $firstRow = $rows->first();
        $excerpt = app(AyahExcerptService::class);

        $this->assertSame($excerpt->excerptSmart($from->text), $firstRow['من (نص الآية)']);
        $this->assertSame($excerpt->excerptSmart($to->text), $firstRow['إلى (نص الآية)']);
    }
}
