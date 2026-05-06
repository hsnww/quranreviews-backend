<?php

namespace Tests\Unit;

use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\StudentMemorization;
use App\Models\Surah;
use App\Models\User;
use App\Services\QuizCardGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizCardGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_surah_memorization_cannot_fill_four_verse_window(): void
    {
        Surah::create(['id' => 99, 'name' => 'اختبار']);

        $v1 = QuranVerse::create([
            'sora' => 99,
            'ayah' => 1,
            'text' => 'a',
            'page' => 1,
            'hizb' => 1,
            'qrtr' => 1,
            'jozo' => 2,
        ]);
        $v2 = QuranVerse::create([
            'sora' => 99,
            'ayah' => 2,
            'text' => 'b',
            'page' => 1,
            'hizb' => 1,
            'qrtr' => 1,
            'jozo' => 2,
        ]);
        $v3 = QuranVerse::create([
            'sora' => 99,
            'ayah' => 3,
            'text' => 'c',
            'page' => 1,
            'hizb' => 1,
            'qrtr' => 1,
            'jozo' => 2,
        ]);

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $v1->id,
            'to_verse_id' => $v3->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        $service = new QuizCardGeneratorService;
        $result = $service->generate($student, [2], 2, 4, false);

        $this->assertNotNull($result['failure_message']);
        $this->assertSame([], $result['cards']);
    }
}
