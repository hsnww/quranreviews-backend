<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $planTitle ?? 'خطة المراجعة والحفظ' }}</title>
    <style>
        body {
            font-family: 'xbriyaz', DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 11pt;
            color: #1f2937;
        }

        /* ===== الترويسة ===== */
        .banner {
            width: 100%;
            border-collapse: collapse;
            background-color: #0f766e;
            color: #ffffff;
        }
        .banner td { padding: 8px 14px; border: none; vertical-align: middle; }
        .banner .title { font-size: 18pt; font-weight: bold; }
        .banner .student { font-size: 11pt; color: #ccfbf1; }
        .banner .range { font-size: 10pt; color: #ccfbf1; text-align: left; }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        .summary td {
            border: none;
            padding: 3px 4px;
            font-size: 9.5pt;
            color: #475569;
        }
        .chip {
            padding: 2px 8px;
            font-size: 9pt;
        }

        /* ===== الجدول ===== */
        table.plan {
            width: 100%;
            border-collapse: collapse;
        }
        table.plan th {
            background-color: #134e4a;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            padding: 5px 4px;
            border: 0.6px solid #134e4a;
            text-align: center;
        }
        table.plan td {
            border: 0.6px solid #d6dde4;
            padding: 3px 5px;
            font-size: 10pt;
            text-align: center;
            vertical-align: middle;
        }
        table.plan tr.day-start td { border-top: 1.4px solid #64748b; }

        .day-num { font-weight: bold; font-size: 12pt; color: #0f766e; }
        .day-cell { background-color: #f1f5f9; }
        .day-cell-weekend { background-color: #f5f3ff; }
        .weekday { font-weight: bold; font-size: 10pt; color: #334155; }
        .date { font-size: 9pt; color: #64748b; }

        .surah { font-weight: bold; color: #0f172a; }
        .ayah-no { color: #0f766e; font-weight: bold; }
        .verse-text {
            text-align: right;
            font-size: 9.5pt;
            color: #475569;
        }

        /* ألوان أنواع الدروس: الخلفية للصف، والوسم لعمود الدرس */
        .row-new td.c { background-color: #f0fdf4; }
        .row-revision td.c { background-color: #fffbeb; }
        .row-weekly td.c { background-color: #faf5ff; }
        .row-review td.c { background-color: #eff6ff; }

        .tag-new { background-color: #16a34a; color: #ffffff; font-weight: bold; }
        .tag-revision { background-color: #d97706; color: #ffffff; font-weight: bold; }
        .tag-weekly { background-color: #7c3aed; color: #ffffff; font-weight: bold; }
        .tag-review { background-color: #2563eb; color: #ffffff; font-weight: bold; }

        .footer {
            width: 100%;
            border-collapse: collapse;
            border-top: 0.8px solid #cbd5e1;
            font-size: 8pt;
            color: #64748b;
        }
        .footer td { border: none; padding-top: 3px; }
    </style>
</head>
<body>
@php
    $plansArray = $plans instanceof \Illuminate\Support\Collection ? $plans->toArray() : (array) $plans;

    // تجميع الصفوف حسب اليوم مع الحفاظ على الترتيب
    $plansByDay = [];
    foreach ($plansArray as $plan) {
        $plansByDay[$plan['day']][] = $plan;
    }

    $kindOf = function (array $p): string {
        if (!empty($p['kind'])) return $p['kind'];
        return match ($p['type'] ?? '') {
            'جديد' => 'new',
            'مراجعة السابق' => 'revision',
            'مراجعة الأسبوع' => 'weekly',
            default => 'review',
        };
    };

    // ملخص: أول وآخر تاريخ، وعدد الأيام، وأعداد الأنواع
    $dates = array_values(array_filter(array_map(fn ($p) => $p['date'] ?? null, $plansArray)));
    $firstDate = $dates[0] ?? null;
    $lastDate = $dates ? end($dates) : null;
    $kindCounts = [];
    foreach ($plansByDay as $dayRows) {
        foreach (array_unique(array_map($kindOf, $dayRows)) as $k) {
            $kindCounts[$k] = ($kindCounts[$k] ?? 0) + 1;
        }
    }
    $tagColors = [
        'new' => '#16a34a',
        'revision' => '#d97706',
        'weekly' => '#7c3aed',
        'review' => '#2563eb',
    ];
    $kindLabels = [
        'new' => 'حفظ جديد',
        'revision' => 'مراجعة السابق',
        'weekly' => 'مراجعة الأسبوع',
        'review' => 'مراجعة بعيدة',
    ];

    $splitDate = function (?string $d): array {
        if (!$d) return [null, null];
        $parts = explode(' ', $d, 2);
        return count($parts) === 2 ? $parts : [null, $d];
    };

    $formatRange = function (array $p): array {
        $sameSurah = ($p['from_sora'] ?? '') === ($p['to_sora'] ?? '');
        return [$sameSurah, $p['from_sora'] ?? '', $p['from_ayah'] ?? '-', $p['to_sora'] ?? '', $p['to_ayah'] ?? '-'];
    };
@endphp

<htmlpagefooter name="planFooter">
    <table class="footer">
        <tr>
            <td style="text-align: right;">{{ $planTitle ?? 'خطة المراجعة والحفظ' }} — {{ $studentName ?? '' }}</td>
            <td style="text-align: center;">{{ config('app.name', 'منصة مراجعة القرآن') }} · quranreview.app</td>
            <td style="text-align: left;">صفحة {PAGENO} من {nbpg} · طُبع {{ \Carbon\Carbon::now()->format('Y/m/d') }}</td>
        </tr>
    </table>
</htmlpagefooter>
<sethtmlpagefooter name="planFooter" value="on" />

{{-- الترويسة --}}
<table class="banner">
    <tr>
        <td>
            <div class="title">{{ $planTitle ?? 'خطة المراجعة والحفظ' }}</div>
            <div class="student">
                الطالب: {{ $studentName ?? '' }}
                @if (!empty($studentInstitution)) &nbsp;·&nbsp; {{ $studentInstitution }} @endif
            </div>
        </td>
        <td class="range">
            @if ($firstDate && $lastDate)
                من {{ $firstDate }}<br>إلى {{ $lastDate }}
            @endif
        </td>
    </tr>
</table>

<table class="summary">
    <tr>
        <td>
            عدد الأيام: <strong>{{ count($plansByDay) }}</strong>
            @foreach ($kindLabels as $k => $label)
                @if (!empty($kindCounts[$k]))
                    &nbsp;&nbsp;<span class="chip tag-{{ $k }}">&nbsp;{{ $label }}: {{ $kindCounts[$k] }}&nbsp;</span>
                @endif
            @endforeach
        </td>
        @if (isset($kindCounts['weekly']))
            <td style="text-align: left;">الحفظ من الأحد إلى الخميس، والجمعة والسبت لمراجعة دروس الأسبوع</td>
        @endif
    </tr>
</table>

<table class="plan" repeat_header="1">
    <thead>
        <tr>
            <th style="width: 6%;">اليوم</th>
            <th style="width: 10%;">التاريخ</th>
            <th style="width: 10%;">الدرس</th>
            <th style="width: 16%;">المقطع</th>
            <th style="width: 29%;">بداية المقطع</th>
            <th style="width: 29%;">نهاية المقطع</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($plansByDay as $day => $dayRows)
            @php
                [$weekday, $dateOnly] = $splitDate($dayRows[0]['date'] ?? null);
                $isWeekend = in_array($weekday, ['الجمعة', 'السبت'], true);
                $dayCellClass = $isWeekend ? 'day-cell-weekend' : 'day-cell';
                $rowspan = count($dayRows);
            @endphp
            @foreach ($dayRows as $i => $plan)
                @php
                    $kind = $kindOf($plan);
                    [$sameSurah, $fromSora, $fromAyah, $toSora, $toAyah] = $formatRange($plan);
                @endphp
                <tr class="row-{{ $kind }} {{ $i === 0 ? 'day-start' : '' }}">
                    @if ($i === 0)
                        <td class="{{ $dayCellClass }}" rowspan="{{ $rowspan }}">
                            <span class="day-num">{{ $day }}</span>
                        </td>
                        <td class="{{ $dayCellClass }}" rowspan="{{ $rowspan }}">
                            @if ($weekday)
                                <div class="weekday">{{ $weekday }}</div>
                            @endif
                            <div class="date">{{ $dateOnly ?? '—' }}</div>
                        </td>
                    @endif
                    <td style="background-color: {{ $tagColors[$kind] ?? '#2563eb' }}; color: #ffffff; font-weight: bold;">{{ $plan['type'] }}</td>
                    <td class="c">
                        @if ($sameSurah)
                            <span class="surah">{{ $fromSora }}</span>
                            <span class="ayah-no">{{ $fromAyah }}@if ($fromAyah !== $toAyah)–{{ $toAyah }}@endif</span>
                        @else
                            <span class="surah">{{ $fromSora }}</span> <span class="ayah-no">{{ $fromAyah }}</span>
                            إلى
                            <span class="surah">{{ $toSora }}</span> <span class="ayah-no">{{ $toAyah }}</span>
                        @endif
                    </td>
                    <td class="c verse-text">{{ $plan['from_text'] }}</td>
                    <td class="c verse-text">{{ $plan['to_text'] }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

</body>
</html>
