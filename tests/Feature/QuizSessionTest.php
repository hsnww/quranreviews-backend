<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Models\QuizSession;
use App\Models\Student;
use App\Models\StudentMemorization;
use App\Models\Surah;
use App\Models\User;
use App\Services\AyahExcerptService;
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
            $this->assertArrayHasKey('first_hint_text', $card);
        }

        $firstCard = $response->json('cards.0');
        $expectedHint = app(AyahExcerptService::class)->excerptSmart($firstCard['verses'][0]['text']);
        $this->assertSame($expectedHint, $firstCard['first_hint_text']);

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
        $complete->assertJsonPath('score', 98.5); // max(0, 100 - 3*0.5)

        $session = QuizSession::findOrFail($sessionId);
        $this->assertTrue($session->isCompleted());
    }

    public function test_created_cards_are_unique_in_same_session(): void
    {
        $verses = $this->seedSurahVerses(1, 'الفاتحة', 1, 20, 8);

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
            'card_count' => 8,
            'verses_per_card' => 4,
            'ensure_juz_coverage' => false,
        ]);

        $response->assertCreated();
        $cards = collect($response->json('cards'));

        $keys = $cards->map(function (array $card): string {
            $first = $card['verses'][0]['verse_number'];
            $last = $card['verses'][count($card['verses']) - 1]['verse_number'];
            return $card['sora_number'].'-'.$first.'-'.$last;
        });

        $this->assertSame($keys->count(), $keys->unique()->count());
    }

    public function test_delete_session_for_owner_succeeds(): void
    {
        $owner = User::factory()->create();
        $session = QuizSession::create([
            'user_id' => $owner->id,
            'status' => QuizSession::STATUS_IN_PROGRESS,
            'juz_ids' => [8],
            'verses_per_card' => 4,
            'ensure_juz_coverage' => false,
            'requested_card_count' => 5,
            'actual_card_count' => 5,
        ]);

        QuizSession::findOrFail($session->id)->cards()->create([
            'order_index' => 1,
            'sora_number' => 1,
            'jozo' => 8,
            'verse_ids' => [1, 2, 3, 4],
            'mistake_count' => 0,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/quiz-sessions/{$session->id}");

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'تم حذف السجل بنجاح']);
        $this->assertDatabaseMissing('quiz_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('quiz_session_cards', ['quiz_session_id' => $session->id]);
    }

    public function test_delete_session_for_other_user_returns_403(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $session = QuizSession::create([
            'user_id' => $owner->id,
            'status' => QuizSession::STATUS_IN_PROGRESS,
            'juz_ids' => [8],
            'verses_per_card' => 4,
            'ensure_juz_coverage' => false,
            'requested_card_count' => 5,
            'actual_card_count' => 5,
        ]);

        Sanctum::actingAs($other);

        $response = $this->deleteJson("/api/quiz-sessions/{$session->id}");

        $response->assertForbidden();
    }

    public function test_delete_non_existing_session_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/quiz-sessions/999999');

        $response->assertNotFound();
    }
}
