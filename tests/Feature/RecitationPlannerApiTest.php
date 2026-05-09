<?php

namespace Tests\Feature;

use App\Models\QuranVerse;
use App\Models\RecitationPlan;
use App\Models\RecitationSession;
use App\Models\Surah;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecitationPlannerApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedSurah(int $surah, int $ayahCount): void
    {
        Surah::query()->firstOrCreate(['id' => $surah], ['name' => "سورة {$surah}"]);
        for ($a = 1; $a <= $ayahCount; $a++) {
            QuranVerse::query()->firstOrCreate([
                'sora' => $surah,
                'ayah' => $a,
            ], [
                'text' => "نص {$surah}:{$a}",
                'page' => 1,
                'hizb' => 1,
                'qrtr' => 1,
                'jozo' => 1,
            ]);
        }
    }

    public function test_create_plan_successfully(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/recitation/plans', [
            'title' => 'خطة الإمام',
            'period_type' => 'weekly',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-16',
            'status' => 'active',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('period_type', 'weekly');
        $this->assertDatabaseHas('recitation_plans', [
            'user_id' => $user->id,
            'title' => 'خطة الإمام',
            'status' => 'active',
        ]);
    }

    public function test_reject_invalid_segment_for_non_consecutive_surahs_or_bad_order(): void
    {
        $this->seedSurah(1, 7);
        $this->seedSurah(2, 10);
        $this->seedSurah(4, 5);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = RecitationPlan::create([
            'user_id' => $user->id,
            'period_type' => 'weekly',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-16',
            'status' => 'active',
        ]);

        $session = RecitationSession::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'date' => '2026-05-11',
            'day_of_week' => 1,
            'prayer_name' => 'fajr',
            'execution_status' => 'scheduled',
        ]);

        $badSurah = $this->postJson("/api/recitation/sessions/{$session->id}/segments", [
            'rakaa_number' => 1,
            'start_surah' => 1,
            'start_ayah' => 3,
            'end_surah' => 4,
            'end_ayah' => 2,
        ]);
        $badSurah->assertStatus(422);

        $badOrder = $this->postJson("/api/recitation/sessions/{$session->id}/segments", [
            'rakaa_number' => 1,
            'start_surah' => 2,
            'start_ayah' => 8,
            'end_surah' => 2,
            'end_ayah' => 3,
        ]);
        $badOrder->assertStatus(422);
    }

    public function test_add_valid_segment_successfully(): void
    {
        $this->seedSurah(2, 20);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = RecitationPlan::create([
            'user_id' => $user->id,
            'period_type' => 'weekly',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-16',
            'status' => 'active',
        ]);

        $session = RecitationSession::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'date' => '2026-05-11',
            'day_of_week' => 1,
            'prayer_name' => 'isha',
            'execution_status' => 'scheduled',
        ]);

        $response = $this->postJson("/api/recitation/sessions/{$session->id}/segments", [
            'rakaa_number' => 2,
            'start_surah' => 2,
            'start_ayah' => 5,
            'end_surah' => 2,
            'end_ayah' => 9,
            'order_index' => 0,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('rakaa_number', 2);
        $this->assertDatabaseHas('recitation_segments', [
            'session_id' => $session->id,
            'start_surah' => 2,
            'end_ayah' => 9,
        ]);
    }

    public function test_complete_session_creates_history_records(): void
    {
        $this->seedSurah(1, 7);
        $this->seedSurah(2, 10);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = RecitationPlan::create([
            'user_id' => $user->id,
            'period_type' => 'weekly',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-16',
            'status' => 'active',
        ]);

        $session = RecitationSession::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'date' => '2026-05-11',
            'day_of_week' => 1,
            'prayer_name' => 'maghrib',
            'execution_status' => 'scheduled',
        ]);

        $session->segments()->create([
            'rakaa_number' => 1,
            'start_surah' => 1,
            'start_ayah' => 5,
            'end_surah' => 2,
            'end_ayah' => 3,
            'order_index' => 0,
        ]);

        $response = $this->postJson("/api/recitation/sessions/{$session->id}/complete");
        $response->assertOk();
        $response->assertJsonPath('history_records_created', 1);

        $this->assertDatabaseHas('recitation_history', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'prayer_name' => 'maghrib',
            'start_surah' => 1,
            'end_surah' => 2,
        ]);
    }

    public function test_ayah_status_returns_future_and_past_metrics(): void
    {
        Carbon::setTestNow('2026-05-20 10:00:00');

        $this->seedSurah(2, 20);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = RecitationPlan::create([
            'user_id' => $user->id,
            'period_type' => 'weekly',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-31',
            'status' => 'active',
        ]);

        $pastSession = RecitationSession::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'day_of_week' => 5,
            'prayer_name' => 'fajr',
            'execution_status' => 'scheduled',
        ]);
        $pastSession->segments()->create([
            'rakaa_number' => 1,
            'start_surah' => 2,
            'start_ayah' => 4,
            'end_surah' => 2,
            'end_ayah' => 8,
            'order_index' => 0,
        ]);

        $this->postJson("/api/recitation/sessions/{$pastSession->id}/complete")->assertOk();

        $futureSession = RecitationSession::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'date' => '2026-05-25',
            'day_of_week' => 1,
            'prayer_name' => 'isha',
            'execution_status' => 'scheduled',
        ]);
        $futureSession->segments()->create([
            'rakaa_number' => 2,
            'start_surah' => 2,
            'start_ayah' => 6,
            'end_surah' => 2,
            'end_ayah' => 12,
            'order_index' => 0,
        ]);

        $status = $this->getJson('/api/recitation/ayah-status?surah=2&ayah=7');
        $status->assertOk();
        $status->assertJsonPath('future_scheduled', true);
        $status->assertJsonPath('next_scheduled_date', '2026-05-25');
        $status->assertJsonPath('last_recited_at', '2026-05-15');
        $status->assertJsonPath('last_recited_prayer', 'fajr');
        $status->assertJsonPath('days_since_last_recitation', 5);
        $status->assertJsonPath('times_scheduled', 2);
        $status->assertJsonPath('times_recited', 1);
    }

    public function test_delete_plan_removes_sessions_segments_and_linked_history(): void
    {
        $this->seedSurah(2, 15);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = RecitationPlan::create([
            'user_id' => $user->id,
            'period_type' => 'weekly',
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-16',
            'status' => 'active',
        ]);

        $session = RecitationSession::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'date' => '2026-05-11',
            'day_of_week' => 1,
            'prayer_name' => 'fajr',
            'execution_status' => 'scheduled',
        ]);

        $session->segments()->create([
            'rakaa_number' => 1,
            'start_surah' => 2,
            'start_ayah' => 4,
            'end_surah' => 2,
            'end_ayah' => 8,
            'order_index' => 0,
        ]);

        $this->postJson("/api/recitation/sessions/{$session->id}/complete")->assertOk();

        $this->assertDatabaseHas('recitation_history', [
            'user_id' => $user->id,
            'session_id' => $session->id,
        ]);

        $this->deleteJson("/api/recitation/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('message', 'تم حذف الخطة وجميع الجلسات والمقاطع والأرشيف المرتبط بها بنجاح');

        $this->assertDatabaseMissing('recitation_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('recitation_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('recitation_segments', ['session_id' => $session->id]);
        $this->assertDatabaseMissing('recitation_history', ['session_id' => $session->id]);
    }
}
