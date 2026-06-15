<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecitationBookmarkRequest;
use App\Http\Requests\StoreRecitationPlanRequest;
use App\Http\Requests\StoreRecitationSegmentRequest;
use App\Http\Requests\UpdateRecitationSegmentRequest;
use App\Http\Requests\UpsertRecitationSessionsRequest;
use App\Models\RecitationBookmark;
use App\Models\RecitationHistory;
use App\Models\RecitationPlan;
use App\Models\RecitationSegment;
use App\Models\RecitationSession;
use Illuminate\Support\Facades\DB;
use App\Services\AyahRecitationStatusService;
use App\Services\RecitationCompletionService;
use App\Services\RecitationPlannerService;
use App\Services\RecitationSegmentValidator;
use App\Services\SurahRecitationSummaryService;
use Illuminate\Http\Request;

class RecitationPlannerController extends Controller
{
    public function __construct(
        private RecitationPlannerService $plannerService,
        private RecitationSegmentValidator $segmentValidator,
        private RecitationCompletionService $completionService,
        private AyahRecitationStatusService $ayahStatusService,
        private SurahRecitationSummaryService $surahSummaryService,
    ) {}

    public function indexPlans(Request $request)
    {
        $query = RecitationPlan::query()
            ->where('user_id', $request->user()->id)
            ->withCount('sessions')
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json(
            $query->paginate((int) $request->query('per_page', 30))
        );
    }

    public function storePlan(StoreRecitationPlanRequest $request)
    {
        $plan = $this->plannerService->createPlan((int) $request->user()->id, $request->validated());

        return response()->json($plan, 201);
    }

    public function showPlan(Request $request, RecitationPlan $plan)
    {
        $this->authorizePlan($request, $plan);

        return response()->json(
            $plan->load(['sessions.segments'])
        );
    }

    /**
     * يحذف الخطة وجلساتها ومقاطعها، وسجلات الأرشيف المرتبطة بتلك الجلسات
     * (جدول التاريخ يستخدم nullOnDelete على الجلسة فيترك سجلات يتيمة بدون هذا الحذف الصريح).
     */
    public function destroyPlan(Request $request, RecitationPlan $plan)
    {
        $this->authorizePlan($request, $plan);

        $userId = (int) $request->user()->id;

        DB::transaction(function () use ($plan, $userId): void {
            $sessionIds = RecitationSession::query()
                ->where('plan_id', $plan->id)
                ->where('user_id', $userId)
                ->pluck('id');

            if ($sessionIds->isNotEmpty()) {
                RecitationHistory::query()
                    ->where('user_id', $userId)
                    ->whereIn('session_id', $sessionIds)
                    ->delete();
            }

            $plan->delete();
        });

        return response()->json([
            'message' => 'تم حذف الخطة وجميع الجلسات والمقاطع والأرشيف المرتبط بها بنجاح',
        ]);
    }

    public function upsertSessions(UpsertRecitationSessionsRequest $request, RecitationPlan $plan)
    {
        $this->authorizePlan($request, $plan);

        $sessions = $this->plannerService->upsertSessions(
            $plan,
            (int) $request->user()->id,
            $request->validated()['sessions']
        );

        return response()->json([
            'message' => 'تم حفظ الجلسات بنجاح',
            'sessions' => collect($sessions)->values(),
        ]);
    }

    public function storeSegment(StoreRecitationSegmentRequest $request, RecitationSession $session)
    {
        $this->authorizeSession($request, $session);

        $payload = $request->validated();
        $this->segmentValidator->validate($payload);

        $segment = $session->segments()->create($payload);

        return response()->json($segment, 201);
    }

    public function updateSegment(UpdateRecitationSegmentRequest $request, RecitationSegment $segment)
    {
        $this->authorizeSegment($request, $segment);

        $payload = $request->validated();
        $this->segmentValidator->validate($payload);

        $segment->update($payload);

        return response()->json($segment->fresh());
    }

    public function destroySegment(Request $request, RecitationSegment $segment)
    {
        $this->authorizeSegment($request, $segment);
        $segment->delete();

        return response()->json([
            'message' => 'تم حذف المقطع بنجاح',
        ]);
    }

    public function completeSession(Request $request, RecitationSession $session)
    {
        $this->authorizeSession($request, $session);
        $completed = $this->completionService->completeSession($session);

        return response()->json([
            'message' => 'تم إكمال الجلسة بنجاح',
            'session' => $completed,
            'history_records_created' => $completed->segments->count(),
        ]);
    }

    public function archive(Request $request)
    {
        $query = RecitationHistory::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->query('to')))
            ->when($request->filled('prayer_name'), fn ($q) => $q->where('prayer_name', $request->query('prayer_name')))
            ->when($request->filled('surah'), function ($q) use ($request) {
                $surah = (int) $request->query('surah');
                $q->where('start_surah', '<=', $surah)
                    ->where('end_surah', '>=', $surah);
            })
            ->latest('date')
            ->latest('id');

        return response()->json($query->paginate((int) $request->query('per_page', 15)));
    }

    public function ayahStatus(Request $request)
    {
        $validated = $request->validate([
            'surah' => ['required', 'integer', 'between:1,114'],
            'ayah' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->ayahStatusService->getStatus(
                (int) $request->user()->id,
                (int) $validated['surah'],
                (int) $validated['ayah']
            )
        );
    }

    public function surahSummary(Request $request)
    {
        $validated = $request->validate([
            'prayer' => ['nullable', 'in:fajr,maghrib,isha'],
        ]);

        return response()->json([
            'data' => $this->surahSummaryService->getSummary(
                (int) $request->user()->id,
                $validated['prayer'] ?? null
            ),
        ]);
    }

    public function indexBookmarks(Request $request)
    {
        $bookmarks = RecitationBookmark::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $bookmarks]);
    }

    public function storeBookmark(StoreRecitationBookmarkRequest $request)
    {
        $payload = $request->validated();
        $this->segmentValidator->validate($payload);

        $bookmark = RecitationBookmark::query()->create([
            ...$payload,
            'user_id' => (int) $request->user()->id,
        ]);

        return response()->json($bookmark, 201);
    }

    public function destroyBookmark(Request $request, RecitationBookmark $bookmark)
    {
        $this->authorizeBookmark($request, $bookmark);
        $bookmark->delete();

        return response()->json([
            'message' => 'تم حذف المقطع المحفوظ بنجاح',
        ]);
    }

    private function authorizePlan(Request $request, RecitationPlan $plan): void
    {
        abort_if((int) $plan->user_id !== (int) $request->user()->id, 403);
    }

    private function authorizeBookmark(Request $request, RecitationBookmark $bookmark): void
    {
        abort_if((int) $bookmark->user_id !== (int) $request->user()->id, 403);
    }

    private function authorizeSession(Request $request, RecitationSession $session): void
    {
        abort_if((int) $session->user_id !== (int) $request->user()->id, 403);
    }

    private function authorizeSegment(Request $request, RecitationSegment $segment): void
    {
        $segment->loadMissing('session');
        $this->authorizeSession($request, $segment->session);
    }
}
