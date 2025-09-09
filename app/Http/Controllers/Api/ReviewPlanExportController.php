<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReviewPlanExport;
use App\Http\Controllers\Controller;
use App\Models\ReviewPlan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\QuranVerse;
use Illuminate\Support\Str;
use Mpdf\Mpdf;


class ReviewPlanExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $accessToken = $request->bearerToken() ?? $request->query('token');

        if (!$accessToken) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $tokenModel = PersonalAccessToken::findToken($accessToken);
        $user = $tokenModel?->tokenable;

        if (!$user || !$user->student) {
            return response()->json(['message' => 'User or student not found'], 404);
        }

        $student = $user->student;
        $filename = 'review-plan.xlsx';
        return Excel::download(new ReviewPlanExport($student->id), $filename);
    }

    public function exportPdf(Request $request)
    {

        $accessToken = $request->bearerToken() ?? $request->query('token');

        if (!$accessToken) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($accessToken);
        $user = $tokenModel?->tokenable;

        if (!$user || !$user->student) {
            return response()->json(['message' => 'User or student not found'], 404);
        }

        $student = $user->student;

        $plans = \App\Models\ReviewPlan::where('student_id', $student->id)
            ->orderBy('day_number')
            ->get(['day_number', 'from_verse_id', 'to_verse_id', 'type']);

        $verseIds = $plans->pluck('from_verse_id')->merge($plans->pluck('to_verse_id'))->unique();
        $verses = \App\Models\QuranVerse::with('surah')->whereIn('id', $verseIds)->get()->keyBy('id');

        $detailedPlans = $plans->map(function ($plan) use ($verses) {
            $from = $verses[$plan->from_verse_id] ?? null;
            $to = $verses[$plan->to_verse_id] ?? null;

            return [
                'day' => $plan->day_number,
                'type' => $plan->type === 'new' ? 'جديد' : 'مراجعة', // ✅ مضاف
                'from_sora' => $from?->surah->name ?? 'غير معروف',
                'from_ayah' => $from?->ayah ?? '-',
                'from_text' => $from ? \Str::limit($from->text, 100) : '-',
                'to_sora' => $to?->surah->name ?? 'غير معروف',
                'to_ayah' => $to?->ayah ?? '-',
                'to_text' => $to ? \Str::limit($to->text, 100) : '-',
            ];
        });
        $studentName = $student->user->name ?? 'الطالب';
        $studentInstitution = $student->institution ?? 'حلقة غير معروفة';

        $html = view('exports.review-plan-pdf', [
            'plans' => $detailedPlans,
            'studentName' => $studentName,
            'studentInstitution' => $studentInstitution,
        ])->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'xbriyaz',
            'default_font_size' => 16,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);
        $mpdf->SetDirectionality('rtl');

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="review-plan.pdf"',
        ]);
    }

}
