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

    private function seedSixVerseWindow(): array
    {
        Surah::create(['id' => 1, 'name' => 'الفاتحة']);

        $verses = [];
        for ($i = 1; $i <= 6; $i++) {
            $verses[] = QuranVerse::create([
                'sora' => 1,
                'ayah' => $i,
                'text' => 'كلمة '.$i,
                'page' => 1,
                'hizb' => 1,
                'qrtr' => 1,
                'jozo' => 8,
            ]);
        }

        return $verses;
    }

    public function test_create_quiz_session_returns_cards_within_memorized_and_selected_juz(): void
    {
        $verses = $this->seedSixVerseWindow();

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $verses[0]->id,
            'to_verse_id' => $verses[5]->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8],
            'card_count' => 5,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('session_id', fn ($id) => is_numeric($id));
        $response->assertJsonPath('actual_card_count', 5);
        $response->assertJsonCount(5, 'cards');

        $firstCardVerses = $response->json('cards.0.verses');
        $this->assertGreaterThanOrEqual(4, count($firstCardVerses));
        $this->assertLessThanOrEqual(6, count($firstCardVerses));

        $session = QuizSession::firstOrFail();
        $this->assertSame((int) $user->id, (int) $session->user_id);
        $this->assertSame(QuizSession::STATUS_IN_PROGRESS, $session->status);
    }

    public function test_create_session_rejects_juz_without_memorization(): void
    {
        $this->seedSixVerseWindow();

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => QuranVerse::where('jozo', 8)->first()->id,
            'to_verse_id' => QuranVerse::where('jozo', 8)->orderByDesc('id')->first()->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8, 9],
            'card_count' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'الجزء 9 لا يحتوي على أي آية ضمن المحفوظ المفعّل لهذا الطالب.',
        ]);
    }

    public function test_patch_card_and_complete_session(): void
    {
        $verses = $this->seedSixVerseWindow();

        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id]);

        StudentMemorization::create([
            'student_id' => $student->id,
            'from_verse_id' => $verses[0]->id,
            'to_verse_id' => $verses[5]->id,
            'type' => 'initial',
            'verified' => false,
        ]);

        Sanctum::actingAs($user);

        $create = $this->postJson('/api/quiz-sessions', [
            'juz_ids' => [8],
            'card_count' => 5,
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
