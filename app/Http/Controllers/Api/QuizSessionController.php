<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranVerse;
use App\Models\QuizSession;
use App\Models\QuizSessionCard;
use App\Services\QuizCardGeneratorService;
use App\Services\QuizScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizSessionController extends Controller
{
    private const CARD_COUNT_MIN = 5;

    private const CARD_COUNT_MAX = 50;

    public function __construct(
        private QuizCardGeneratorService $cardGenerator,
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
            'card_count' => 'required|integer|between:'.self::CARD_COUNT_MIN.','.self::CARD_COUNT_MAX,
        ], [
            'juz_ids.required' => 'يجب اختيار جزء واحد على الأقل.',
            'juz_ids.min' => 'يجب اختيار جزء واحد على الأقل.',
            'juz_ids.*.between' => 'رقم الجزء يجب أن يكون بين 1 و 30.',
            'card_count.between' => sprintf(
                'عدد البطاقات يجب أن يكون بين %d و %d.',
                self::CARD_COUNT_MIN,
                self::CARD_COUNT_MAX
            ),
        ]);

        $student = $request->user()->student;
        if ($student === null) {
            return response()->json([
                'message' => 'لا يوجد ملف طالب مرتبط بهذا الحساب.',
            ], 422);
        }

        $juzIds = array_values(array_unique($validated['juz_ids']));

        foreach ($juzIds as $juz) {
            if (!$this->cardGenerator->juzHasMemorizedAyah($student, $juz)) {
                return response()->json([
                    'message' => sprintf('الجزء %d لا يحتوي على أي آية ضمن المحفوظ المفعّل لهذا الطالب.', $juz),
                ], 422);
            }
        }

        $requested = (int) $validated['card_count'];
        $generated = $this->cardGenerator->generate($student, $juzIds, $requested);

        if ($generated['actual_count'] === 0) {
            $msg = $generated['warnings'][0] ?? 'تعذّر إنشاء بطاقات لهذا الاختيار.';

            return response()->json([
                'message' => $msg,
                'warnings' => $generated['warnings'],
            ], 422);
        }

        $session = DB::transaction(function () use ($request, $validated, $juzIds, $generated, $requested) {
            $quiz = QuizSession::create([
                'user_id' => $request->user()->id,
                'status' => QuizSession::STATUS_IN_PROGRESS,
                'juz_ids' => $juzIds,
                'requested_card_count' => $requested,
                'actual_card_count' => $generated['actual_count'],
                'score_formula' => QuizScoreCalculator::formulaDescription(),
            ]);

            foreach ($generated['cards'] as $idx => $card) {
                QuizSessionCard::create([
                    'quiz_session_id' => $quiz->id,
                    'order_index' => $idx + 1,
                    'sora_number' => $card['sora_number'],
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
            ->get(['id', 'sora', 'ayah', 'text']);

        return [
            'id' => $card->id,
            'order_index' => $card->order_index,
            'sora_number' => $card->sora_number,
            'verse_count' => count($ids),
            'mistake_count' => (int) $card->mistake_count,
            'verses' => $verses->map(fn (QuranVerse $v) => [
                'verse_number' => (int) $v->ayah,
                'text' => $v->text,
            ])->values()->all(),
        ];
    }
}
