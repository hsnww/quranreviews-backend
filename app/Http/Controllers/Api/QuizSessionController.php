<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranVerse;
use App\Models\QuizSession;
use App\Models\QuizSessionCard;
use App\Services\AyahExcerptService;
use App\Services\QuizCardGeneratorService;
use App\Services\QuizScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizSessionController extends Controller
{
    private const CARD_COUNT_MAX = 50;

    private const VERSES_PER_CARD_MIN = 4;

    private const VERSES_PER_CARD_MAX = 10;

    public function __construct(
        private QuizCardGeneratorService $cardGenerator,
        private AyahExcerptService $ayahExcerptService,
    ) {}

    public function index(Request $request)
    {
        $sessions = QuizSession::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'juz_ids' => 'required|array|min:1',
            'juz_ids.*' => 'integer|between:1,30',
            'card_count' => [
                'required',
                'integer',
                'max:'.self::CARD_COUNT_MAX,
            ],
            'verses_per_card' => 'required|integer|between:'.self::VERSES_PER_CARD_MIN.','.self::VERSES_PER_CARD_MAX,
            'ensure_juz_coverage' => 'sometimes|boolean',
        ], [
            'juz_ids.required' => 'يجب اختيار جزء واحد على الأقل.',
            'juz_ids.min' => 'يجب اختيار جزء واحد على الأقل.',
            'juz_ids.*.between' => 'رقم الجزء يجب أن يكون بين 1 و 30.',
            'card_count.max' => sprintf('عدد البطاقات يجب ألا يزيد عن %d.', self::CARD_COUNT_MAX),
            'verses_per_card.between' => sprintf(
                'عدد الآيات لكل بطاقة يجب أن يكون بين %d و %d.',
                self::VERSES_PER_CARD_MIN,
                self::VERSES_PER_CARD_MAX
            ),
        ]);

        $student = $request->user()->student;
        if ($student === null) {
            return response()->json([
                'message' => 'لا يوجد ملف طالب مرتبط بهذا الحساب.',
            ], 422);
        }

        $juzIds = array_values(array_unique($validated['juz_ids']));
        $minCards = max(5, count($juzIds));

        if ((int) $validated['card_count'] < $minCards) {
            return response()->json([
                'message' => sprintf('عدد البطاقات يجب ألا يقل عن %d لمطابقة عدد الأجزاء المختارة والحد الأدنى المعتمد.', $minCards),
                'errors' => ['card_count' => [sprintf('الحد الأدنى %d.', $minCards)]],
            ], 422);
        }

        foreach ($juzIds as $juz) {
            if (!$this->cardGenerator->juzHasMemorizedAyah($student, $juz)) {
                return response()->json([
                    'message' => sprintf('الجزء %d لا يحتوي على أي آية ضمن المحفوظ المفعّل لهذا الطالب.', $juz),
                ], 422);
            }
        }

        $requested = (int) $validated['card_count'];
        $versesPerCard = (int) $validated['verses_per_card'];
        $ensureCoverage = (bool) ($validated['ensure_juz_coverage'] ?? false);

        $generated = $this->cardGenerator->generate(
            $student,
            $juzIds,
            $requested,
            $versesPerCard,
            $ensureCoverage,
        );

        if ($generated['failure_message'] !== null) {
            return response()->json([
                'message' => $generated['failure_message'],
                'warnings' => $generated['warnings'],
            ], 422);
        }

        $session = DB::transaction(function () use ($request, $juzIds, $generated, $requested, $versesPerCard, $ensureCoverage) {
            $quiz = QuizSession::create([
                'user_id' => $request->user()->id,
                'status' => QuizSession::STATUS_IN_PROGRESS,
                'juz_ids' => $juzIds,
                'verses_per_card' => $versesPerCard,
                'ensure_juz_coverage' => $ensureCoverage,
                'requested_card_count' => $requested,
                'actual_card_count' => count($generated['cards']),
                'score_formula' => QuizScoreCalculator::formulaDescription(),
            ]);

            foreach ($generated['cards'] as $idx => $card) {
                QuizSessionCard::create([
                    'quiz_session_id' => $quiz->id,
                    'order_index' => $idx + 1,
                    'sora_number' => $card['sora_number'],
                    'jozo' => $card['jozo'],
                    'verse_ids' => $card['verse_ids'],
                    'mistake_count' => 0,
                ]);
            }

            return $quiz->load('cards');
        });

        return response()->json($this->serializeSession($session, $generated['warnings']), 201);
    }

    public function show(Request $request, QuizSession $quiz_session)
    {
        $this->authorizeSession($request, $quiz_session);

        return response()->json($this->serializeSession($quiz_session->load('cards')));
    }

    public function updateCard(Request $request, QuizSession $quiz_session, QuizSessionCard $quiz_session_card)
    {
        $this->authorizeSession($request, $quiz_session);

        if ($quiz_session_card->quiz_session_id !== $quiz_session->id) {
            abort(404);
        }

        if ($quiz_session->isCompleted()) {
            return response()->json([
                'message' => 'لا يمكن تعديل بطاقات جلسة منتهية.',
            ], 422);
        }

        $validated = $request->validate([
            'mistake_count' => 'required|integer|min:0',
        ]);

        $quiz_session_card->update([
            'mistake_count' => (int) $validated['mistake_count'],
        ]);

        return response()->json([
            'id' => $quiz_session_card->id,
            'order_index' => $quiz_session_card->order_index,
            'mistake_count' => $quiz_session_card->mistake_count,
        ]);
    }

    public function complete(Request $request, QuizSession $quiz_session)
    {
        $this->authorizeSession($request, $quiz_session);

        if ($quiz_session->isCompleted()) {
            $quiz_session->loadMissing('cards');

            return response()->json([
                'session_id' => $quiz_session->id,
                'status' => $quiz_session->status,
                'score' => $quiz_session->score,
                'total_errors' => $quiz_session->total_errors,
                'score_formula' => $quiz_session->score_formula ?? QuizScoreCalculator::formulaDescription(),
                'cards_count' => $quiz_session->cards->count(),
            ]);
        }

        $quiz_session->load('cards');

        $totalErrors = (int) $quiz_session->cards->sum('mistake_count');
        $score = QuizScoreCalculator::score($totalErrors);
        $formula = QuizScoreCalculator::formulaDescription();

        $quiz_session->update([
            'status' => QuizSession::STATUS_COMPLETED,
            'score' => $score,
            'total_errors' => $totalErrors,
            'score_formula' => $formula,
            'completed_at' => now(),
        ]);

        return response()->json([
            'session_id' => $quiz_session->id,
            'status' => $quiz_session->status,
            'score' => $score,
            'total_errors' => $totalErrors,
            'score_formula' => $formula,
            'cards_count' => $quiz_session->cards->count(),
        ]);
    }

    public function destroy(Request $request, QuizSession $quiz_session)
    {
        $this->authorizeSession($request, $quiz_session);

        $quiz_session->delete();

        return response()->json([
            'message' => 'تم حذف السجل بنجاح',
        ]);
    }

    private function authorizeSession(Request $request, QuizSession $quiz_session): void
    {
        abort_if((int) $quiz_session->user_id !== (int) $request->user()->id, 403);
    }

    /**
     * @param  list<string>|null  $creationWarnings
     */
    private function serializeSession(QuizSession $session, ?array $creationWarnings = null): array
    {
        $session->loadMissing('cards');

        $formula = $session->score_formula ?? QuizScoreCalculator::formulaDescription();

        $cards = $session->cards->map(fn (QuizSessionCard $c) => $this->serializeCard($c));

        $payload = [
            'session_id' => $session->id,
            'status' => $session->status,
            'juz_ids' => $session->juz_ids,
            'verses_per_card' => $session->verses_per_card,
            'ensure_juz_coverage' => (bool) $session->ensure_juz_coverage,
            'requested_card_count' => $session->requested_card_count,
            'actual_card_count' => $session->actual_card_count,
            'score' => $session->score,
            'total_errors' => $session->total_errors,
            'score_formula' => $formula,
            'completed_at' => $session->completed_at,
            'cards' => $cards,
        ];

        if ($creationWarnings !== null && $creationWarnings !== []) {
            $payload['warnings'] = $creationWarnings;
        }

        return $payload;
    }

    private function serializeCard(QuizSessionCard $card): array
    {
        $ids = $card->verse_ids ?? [];
        $verses = QuranVerse::query()
            ->whereIn('id', $ids)
            ->orderBy('ayah')
            ->get(['id', 'sora', 'ayah', 'text', 'jozo']);

        return [
            'id' => $card->id,
            'order_index' => $card->order_index,
            'sora_number' => $card->sora_number,
            'jozo' => $card->jozo !== null ? (int) $card->jozo : (int) ($verses->first()?->jozo ?? 0),
            'verse_count' => count($ids),
            'mistake_count' => (int) $card->mistake_count,
            'first_hint_text' => $this->ayahExcerptService->excerptSmart((string) ($verses->first()?->text ?? '')),
            'verses' => $verses->map(fn (QuranVerse $v) => [
                'verse_number' => (int) $v->ayah,
                'text' => $v->text,
            ])->values()->all(),
        ];
    }
}
