<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuranVerse;

class QuranController extends Controller
{
    public function versesInRange(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if (!$from || !$to || $from > $to) {
            return response()->json(['message' => 'Invalid range'], 422);
        }

        $verses = QuranVerse::whereBetween('id', [$from, $to])
            ->orderBy('id')
            ->get(['id', 'sora', 'ayah', 'text', 'page']);

        return response()->json($verses);
    }

    public function getByQuarter($qrtr)
    {
        $verses = QuranVerse::where('qrtr', $qrtr)
            ->orderBy('id')
            ->select('id', 'sora', 'ayah', 'text')
            ->get();

        if ($verses->isEmpty()) {
            return response()->json(['message' => 'الربع غير موجود'], 404);
        }

        return response()->json($verses);
    }

    public function getBySurah($sora)
    {
        $verses = QuranVerse::where('sora', $sora)
            ->orderBy('id')
            ->select('id', 'sora', 'ayah', 'text')
            ->get();

        if ($verses->isEmpty()) {
            return response()->json(['message' => 'السورة غير موجودة'], 404);
        }

        return response()->json($verses);
    }

    public function getByHizb($hizb)
    {
        $verses = QuranVerse::where('hizb', $hizb)
            ->orderBy('id')
            ->select('id', 'sora', 'ayah', 'text')
            ->get();

        if ($verses->isEmpty()) {
            return response()->json(['message' => 'الحزب غير موجود'], 404);
        }

        return response()->json($verses);
    }

}

