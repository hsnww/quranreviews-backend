<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\StudentMemorization;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentMemorizationChunksFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_chunks_endpoint_filters_by_jozo_when_provided(): void
    {
        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        Surah::create(['id' => 1, 'name' => 'الفاتحة']);

        $jozo8Verse1 = QuranVerse::create([
            'sora' => 1,
            'ayah' => 1,
            'text' => 'v1',
            'page' => 1,
            'hizb' => 16,
            'qrtr' => 31,
            'jozo' => 8,
        ]);
        $jozo8Verse2 = QuranVerse::create([
            'sora' => 1,
            'ayah' => 2,
            'text' => 'v2',
            'page' => 1,
            'hizb' => 16,
            'qrtr' => 31,
            'jozo' => 8,
        ]);

        $jozo9Verse1 = QuranVerse::create([
            'sora' => 1,
            'ayah' => 3,
            'text' => 'v3',
            'page' => 1,
            'hizb' => 18,
            'qrtr' => 35,
            'jozo' => 9,
        ]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $jozo8Verse1->id,
            'to_verse_id' => $jozo8Verse2->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $jozo9Verse1->id,
            'to_verse_id' => $jozo9Verse1->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        $response = $this->getJson('/api/student-memorization/chunks?jozo=8');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.jozo', 8);
    }
}

