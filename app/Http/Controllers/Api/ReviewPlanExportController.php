<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReviewPlanExport;
use App\Http\Controllers\Controller;
use App\Models\ReviewPlan;
use App\Services\AyahExcerptService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\QuranVerse;
use Illuminate\Support\Str;
use Mpdf\Mpdf;


class ReviewPlanExportController extends Controller
{
    public function __construct(
        private AyahExcerptService $ayahExcerptService,
    ) {}

    /** نوع الخطة المطلوب تصديرها: review (المراجعة البعيدة) أو new (الحفظ)، وnull للكل */
    private function resolveType(Request $request): ?string
    {
        $type = $request->query('type');
        return in_array($type, ['review', 'new'], true) ? $type : null;
    }

    private function planTitle(?string $type): string
    {
        return match ($type) {
            'review' => 'جدول المراجعة البعيدة',
            'new' => 'خطة الحفظ',
            default => 'خطة المراجعة والحفظ',
        };
    }

    private function fileBaseName(?string $type): string
    {
        return match ($type) {
            'review' => 'distant-review-plan',
            'new' => 'memorization-plan',
            default => 'review-plan',
        };
    }

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
        $type = $this->resolveType($request);
        $filename = $this->fileBaseName($type) . '.xlsx';
        return Excel::download(new ReviewPlanExport($student->id, $type), $filename);
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

        $type = $this->resolveType($request);

        $plans = \App\Models\ReviewPlan::where('student_id', $student->id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('day_number')
            ->orderBy('id')
            ->get(['day_number', 'date', 'from_verse_id', 'to_verse_id', 'type', 'is_revision', 'is_weekly_review']);

        $verseIds = $plans->pluck('from_verse_id')->merge($plans->pluck('to_verse_id'))->unique();
        $verses = \App\Models\QuranVerse::with('surah')->whereIn('id', $verseIds)->get()->keyBy('id');

        $detailedPlans = $plans->map(function ($plan) use ($verses) {
            $from = $verses[$plan->from_verse_id] ?? null;
            $to = $verses[$plan->to_verse_id] ?? null;

            return [
                'day' => $plan->day_number,
                'date' => $plan->dateWithWeekday(),
                'type' => $plan->lessonLabel(),
                'kind' => $plan->lessonKind(),
                'from_sora' => $from?->surah->name ?? 'غير معروف',
                'from_ayah' => $from?->ayah ?? '-',
                'from_text' => $from ? $this->ayahExcerptService->excerptSmart($from->text) : '-',
                'to_sora' => $to?->surah->name ?? 'غير معروف',
                'to_ayah' => $to?->ayah ?? '-',
                'to_text' => $to ? $this->ayahExcerptService->excerptSmart($to->text) : '-',
            ];
        });
        $studentName = $student->user->name ?? 'الطالب';
        $studentInstitution = $student->institution ?? 'حلقة غير معروفة';

        $html = view('exports.review-plan-pdf', [
            'plans' => $detailedPlans,
            'studentName' => $studentName,
            'studentInstitution' => $studentInstitution,
            'planTitle' => $this->planTitle($type),
        ])->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'xbriyaz',
            'default_font_size' => 11,
            'margin_top' => 10,
            'margin_bottom' => 14,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_footer' => 5,
        ]);
        $mpdf->SetTitle($this->planTitle($type) . ' - ' . $studentName);
        $mpdf->SetDirectionality('rtl');

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->fileBaseName($type) . '.pdf"',
        ]);
    }

}
