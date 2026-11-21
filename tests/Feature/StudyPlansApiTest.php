<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudyPlansApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Surah::query()->firstOrCreate(['id' => 1], ['name' => 'الفاتحة']);
        for ($a = 1; $a <= 7; $a++) {
            QuranVerse::query()->create([
                'sora' => 1, 'ayah' => $a, 'text' => "نص {$a}",
                'page' => 1, 'hizb' => 1, 'qrtr' => 1, 'jozo' => 1,
            ]);
        }

        $this->user = User::factory()->create();
        Student::create(['user_id' => $this->user->id]);
        Sanctum::actingAs($this->user);
    }

    private function payload(string $type, string $startDate, ?string $title = null): array
    {
        $first = QuranVerse::query()->orderBy('id')->first()->id;

        return array_filter([
            'title' => $title,
            'start_date' => $startDate,
            'plan' => [
                ['day_number' => 1, 'date' => $startDate, 'type' => $type, 'from_verse_id' => $first, 'to_verse_id' => $first + 2],
                ['day_number' => 2, 'date' => date('Y-m-d', strtotime($startDate . ' +1 day')), 'type' => $type, 'from_verse_id' => $first + 3, 'to_verse_id' => $first + 6],
            ],
        ], fn ($v) => $v !== null);
    }

    public function test_saving_a_plan_keeps_previous_plans_of_same_type(): void
    {
        $this->postJson('/api/review-plans', $this->payload('new', '2026-11-22', 'البقرة'))
            ->assertCreated()
            ->assertJsonPath('plan.start_date', '2026-11-22')
            ->assertJsonPath('plan.title', 'البقرة')
            ->assertJsonPath('plan.days_count', 2);

        $this->postJson('/api/review-plans', $this->payload('new', '2026-12-06'))->assertCreated();
        $this->postJson('/api/review-plans', $this->payload('review', '2026-11-29'))->assertCreated();

        $this->getJson('/api/review-plans?type=new')
            ->assertOk()
            ->assertJsonCount(2, 'plans')
            ->assertJsonPath('plans.0.start_date', '2026-12-06')
            ->assertJsonPath('plans.0.end_date', '2026-12-07')
            ->assertJsonPath('plans.1.display_title', 'البقرة');

        $this->getJson('/api/review-plans')->assertJsonCount(3, 'plans');
        $this->assertDatabaseCount('review_plans', 6);
    }

    public function test_view_returns_requested_plan_or_latest_of_type(): void
    {
        $firstId = $this->postJson('/api/review-plans', $this->payload('new', '2026-11-22'))->json('plan.id');
        $this->postJson('/api/review-plans', $this->payload('new', '2026-12-06'));

        $this->getJson("/api/review-plans/view?plan_id={$firstId}")
            ->assertOk()
            ->assertJsonPath('plan.id', $firstId)
            ->assertJsonPath('days.0.date', '2026-11-22')
            ->assertJsonCount(2, 'days');

        $this->getJson('/api/review-plans/view?type=new')
            ->assertJsonPath('days.0.date', '2026-12-06');
    }

    public function test_delete_removes_only_that_plan(): void
    {
        $firstId = $this->postJson('/api/review-plans', $this->payload('new', '2026-11-22'))->json('plan.id');
        $secondId = $this->postJson('/api/review-plans', $this->payload('new', '2026-12-06'))->json('plan.id');

        $this->deleteJson("/api/review-plans/{$firstId}")->assertOk();

        $this->getJson('/api/review-plans?type=new')
            ->assertJsonCount(1, 'plans')
            ->assertJsonPath('plans.0.id', $secondId);
        $this->assertDatabaseCount('review_plans', 2);
    }

    public function test_cannot_view_or_delete_another_students_plan(): void
    {
        $planId = $this->postJson('/api/review-plans', $this->payload('new', '2026-11-22'))->json('plan.id');

        $other = User::factory()->create();
        Student::create(['user_id' => $other->id]);
        Sanctum::actingAs($other);

        $this->getJson("/api/review-plans/view?plan_id={$planId}")->assertNotFound();
        $this->deleteJson("/api/review-plans/{$planId}")->assertNotFound();
        $this->patchJson("/api/review-plans/{$planId}", ['title' => 'x'])->assertNotFound();
        $this->assertDatabaseCount('study_plans', 1);
    }

    public function test_rename_plan(): void
    {
        $planId = $this->postJson('/api/review-plans', $this->payload('review', '2026-11-22'))->json('plan.id');

        $this->patchJson("/api/review-plans/{$planId}", ['title' => 'مراجعة جزء عم'])
            ->assertOk()
            ->assertJsonPath('plan.display_title', 'مراجعة جزء عم');
    }
}
