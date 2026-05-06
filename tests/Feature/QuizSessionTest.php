<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Models\QuizSession;
use App\Models\Student;
use App\Models\StudentMemorization;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizSessionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<QuranVerse>
     */
    private function seedSurahVerses(int $surahId, string $surahName, int $ayahStart, int $ayahEnd, int $jozo): array
    {
        Surah::query()->firstOrCreate(['id' => $surahId], ['name' => $surahName]);

        $verses = [];
        for ($a = $ayahStart; $a <= $ayahEnd; $a++) {
            $verses[] = QuranVerse::create([
                'sora' => $surahId,
                'ayah' => $a,
                'text' => 'كلمة '.$surahId.'_'.$a,
                'page' => 1,
                'hizb' => 1,
                'qrtr' => 1,
                'jozo' => $jozo,
            ]);
        }

        return $verses;
    }

    public function test_create_quiz_session_respects_verses_per_card_and_returns_jozo(): void
    {
        $verses = $this->seedSurahVerses(1, 'الفاتحة', 1, 15, 8);

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $verses[0]->id,
            'to_verse_id' => $verses[count($verses) - 1]->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8],
            'card_count' => 5,
            'verses_per_card' => 4,
            'ensure_juz_coverage' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('session_id', fn ($id) => is_numeric($id));
        $response->assertJsonPath('actual_card_count', 5);
        $response->assertJsonPath('verses_per_card', 4);
        $response->assertJsonCount(5, 'cards');

        foreach ($response->json('cards') as $card) {
            $this->assertSame(4, $card['verse_count']);
            $this->assertCount(4, $card['verses']);
            $this->assertSame(8, $card['jozo']);
        }

        $session = QuizSession::firstOrFail();
        $this->assertSame((int) $user->id, (int) $session->user_id);
        $this->assertSame(QuizSession::STATUS_IN_PROGRESS, $session->status);
        $this->assertSame(4, (int) $session->verses_per_card);
        $this->assertFalse($session->ensure_juz_coverage);
    }

    public function test_create_session_rejects_juz_without_memorization(): void
    {
        $this->seedSurahVerses(1, 'الفاتحة', 1, 15, 8);

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        $ids = QuranVerse::where('jozo', 8)->orderBy('id')->pluck('id');
        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $ids->first(),
            'to_verse_id' => $ids->last(),
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8, 9],
            'card_count' => 5,
            'verses_per_card' => 4,
            'ensure_juz_coverage' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'الجزء 9 لا يحتوي على أي آية ضمن المحفوظ المفعّل لهذا الطالب.',
        ]);
    }

    public function test_ensure_juz_coverage_requires_one_card_per_selected_juz(): void
    {
        $v8 = $this->seedSurahVerses(1, 'الفاتحة', 1, 12, 8);
        $v9 = $this->seedSurahVerses(2, 'البقرة', 1, 12, 9);

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        $memIds = collect($v8)->merge(collect($v9))->pluck('id');
        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $memIds->min(),
            'to_verse_id' => $memIds->max(),
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8, 9],
            'card_count' => 5,
            'verses_per_card' => 4,
            'ensure_juz_coverage' => true,
        ]);

        $response->assertCreated();

        $jozos = collect($response->json('cards'))->pluck('jozo')->unique()->sort()->values()->all();
        $this->assertContains(8, $jozos);
        $this->assertContains(9, $jozos);

        $session = QuizSession::firstOrFail();
        $this->assertTrue($session->ensure_juz_coverage);
    }

    public function test_patch_card_and_complete_session(): void
    {
        $verses = $this->seedSurahVerses(1, 'الفاتحة', 1, 15, 8);

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $verses[0]->id,
            'to_verse_id' => $verses[count($verses) - 1]->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $create = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8],
            'card_count' => 5,
            'verses_per_card' => 4,
            'ensure_juz_coverage' => false,
        ]);

        $create->assertCreated();

        $sessionId = $create->json('session_id');
        $cardId = $create->json('cards.0.id');

        $patch = $this->patchJson("/api/quiz-sessions/{$sessionId}/cards/{$cardId}", [
            'mistake_count' => 3,
        ]);

        $patch->assertOk();
        $patch->assertJsonPath('mistake_count', 3);

        $complete = $this->postJson("/api/quiz-sessions/{$sessionId}/complete");

        $complete->assertOk();
        $complete->assertJsonPath('total_errors', 3);
        $complete->assertJsonPath('score', 94); // max(0, 100 - 3*2)

        $session = QuizSession::findOrFail($sessionId);
        $this->assertTrue($session->isCompleted());
    }
}
