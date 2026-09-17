<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\QuranVerse;

class QuranController extends Controller
{
    /** نص القرآن ثابت لا يتغيّر، فيُخزَّن في الكاش وفي المتصفح لمدة طويلة. */
    private const CACHE_TTL = 60 * 60 * 24 * 30; // 30 يومًا بالثواني

    public function versesInRange(Request $request)
    {
        $from = filter_var($request->query('from'), FILTER_VALIDATE_INT);
        $to = filter_var($request->query('to'), FILTER_VALIDATE_INT);

        if ($from === false || $to === false || $from <= 0 || $to <= 0 || $from > $to) {
            return response()->json(['message' => 'Invalid range'], 422);
        }

        $verses = Cache::remember(
            "quran:range:{$from}:{$to}",
            self::CACHE_TTL,
            fn () => QuranVerse::whereBetween('id', [$from, $to])
                ->orderBy('id')
                ->get(['id', 'sora', 'ayah', 'text', 'page'])
                ->toArray()
        );

        return $this->cachedJson($verses);
    }

    public function getByQuarter($qrtr)
    {
        return $this->versesByColumn('qrtr', $qrtr, 'الربع غير موجود');
    }

    public function getBySurah($sora)
    {
        return $this->versesByColumn('sora', $sora, 'السورة غير موجودة');
    }

    public function getByHizb($hizb)
    {
        return $this->versesByColumn('hizb', $hizb, 'الحزب غير موجود');
    }

    /** استعلام موحّد لكل التقسيمات (سورة/حزب/ربع) مع كاش لكل قيمة. */
    private function versesByColumn(string $column, $value, string $notFoundMessage): JsonResponse
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false || $value <= 0) {
            return response()->json(['message' => $notFoundMessage], 404);
        }

        $verses = Cache::remember(
            "quran:{$column}:{$value}",
            self::CACHE_TTL,
            fn () => QuranVerse::where($column, $value)
                ->orderBy('id')
                ->get(['id', 'sora', 'ayah', 'text'])
                ->toArray()
        );

        if (empty($verses)) {
            return response()->json(['message' => $notFoundMessage], 404);
        }

        return $this->cachedJson($verses);
    }

    /** private: الاستجابة تُخزَّن في متصفح المستخدم فقط، لا في بروكسي مشترك. */
    private function cachedJson(array $verses): JsonResponse
    {
        return response()->json($verses)
            ->header('Cache-Control', 'private, max-age='.self::CACHE_TTL);
    }
}
