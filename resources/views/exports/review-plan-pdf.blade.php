<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>خطة المراجعة</title>

    <style>
        @page {
            margin: 2cm 1.5cm;
            @top-center {
                content: "خطة المراجعة والحفظ - {{ $studentName }}";
                font-size: 12pt;
                font-weight: bold;
            }
            @bottom-center {
                content: "{{ config('app.name', 'منصة مراجعة القرآن') }} - https://www.quranreview.app/";
                font-size: 9pt;
                color: #555;
            }
            @bottom-right {
                content: "صفحة " counter(page) " من " counter(pages) " - تاريخ الطباعة: " "{{ \Carbon\Carbon::now()->translatedFormat('d/m/Y - h:i A') }}";
                font-size: 8pt;
                color: #666;
            }
        }

        body {
            font-family: 'xbriyaz', DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 16px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-before: always;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
        }

        .student-info {
            width: 100%;
            font-size: 14pt;
            margin-bottom: 15px;
            border: none;
        }

        .student-info td {
            border: none;
            padding: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px 4px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 12pt;
        }

        td {
            font-size: 11pt;
        }

        .verse-text {
            color: #444;
            font-size: 11px;
            text-align: right;
            max-width: 150px;
            word-wrap: break-word;
        }

        .day-cell {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .new-lesson {
            background-color: #fceaea;
        }

        .review-lesson {
            background-color: #e6f4ea;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background-color: #f8f9fa;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            font-size: 9pt;
            color: #555;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }
    </style>

</head>
<body>

    @php
        $plansArray = $plans->toArray();
        
        // تجميع الخطط حسب اليوم
        $plansByDay = [];
        foreach ($plansArray as $plan) {
            $day = $plan['day'];
            if (!isset($plansByDay[$day])) {
                $plansByDay[$day] = [];
            }
            $plansByDay[$day][] = $plan;
        }
        
        // تقسيم الأيام إلى مجموعات من 5 أيام
        $days = array_keys($plansByDay);
        $dayChunks = array_chunk($days, 5);
        $totalPages = count($dayChunks);
    @endphp

    @foreach ($dayChunks as $pageIndex => $dayChunk)
        @if ($pageIndex > 0)
            <div class="page-break"></div>
        @endif

        <div class="header">
            <h1>خطة المراجعة والحفظ</h1>
            <div style="font-size: 12pt; margin-top: 5px;">
                <span>الطالب: {{ $studentName }}</span>
                <span> - </span>
                <span>{{ $studentInstitution }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>اليوم</th>
                    <th>السورة</th>
                    <th>من آية</th>
                    <th>الآية</th>
                    <th>السورة</th>
                    <th>إلى آية</th>
                    <th>الآية</th>
                    <th>الدرس</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dayChunk as $day)
                    @foreach ($plansByDay[$day] as $plan)
                        <tr class="{{ $plan['type'] === 'جديد' ? 'new-lesson' : 'review-lesson' }}">
                            <td class="day-cell">{{ $plan['day'] }}</td>
                            <td>{{ $plan['from_sora'] }}</td>
                            <td>{{ $plan['from_ayah'] }}</td>
                            <td class="verse-text">{{ $plan['from_text'] }}</td>
                            <td>{{ $plan['to_sora'] }}</td>
                            <td>{{ $plan['to_ayah'] }}</td>
                            <td class="verse-text">{{ $plan['to_text'] }}</td>
                            <td>{{ $plan['type'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 20px; font-size: 9pt; color: #666;">
            <div style="margin-bottom: 5px;">{{ config('app.name', 'منصة مراجعة القرآن') }} - https://www.quranreview.app/</div>
            <div>صفحة {{ $pageIndex + 1 }} من {{ $totalPages }} - تاريخ الطباعة: {{ \Carbon\Carbon::now()->translatedFormat('d/m/Y - h:i A') }}</div>
        </div>
    @endforeach

</body>
</html>
