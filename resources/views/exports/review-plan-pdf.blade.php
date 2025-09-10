<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>خطة المراجعة</title>

    <style>

        body {
            font-family: 'xbriyaz', DejaVu Sans, sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 16px;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 4px;
        }

        th {
            background-color: #f0f0f0;
        }

        .verse-text {
            color: #444;
            font-size: 14px;
        }
    </style>

</head>
<body>
<h1 style="text-align: center;">خطة المراجعة والحفظ</h1>
<hr style="border-top: 1px solid #aaa; margin: 0 0 10px 0; width: 50%">

<table style="width: 100%; font-size: 16px; margin-bottom: 10px; border: none;">
    <tr>
        <td style="text-align: right; border: none;">الطالب: {{ $studentName }}</td>
        <td style="text-align: left; border: none;">{{ $studentInstitution }}</td>
    </tr>
</table>



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
    @foreach ($plans as $plan)
        <tr style="background-color: {{ $plan['type'] === 'جديد' ? '#fceaea' : '#e6f4ea' }};">
            <td>{{ $plan['day'] }}</td>
            <td>{{ $plan['from_sora'] }}</td>
            <td>{{ $plan['from_ayah'] }}</td>
            <td class="verse-text">{{ $plan['from_text'] }}</td>
            <td>{{ $plan['to_sora'] }}</td>
            <td>{{ $plan['to_ayah'] }}</td>
            <td class="verse-text">{{ $plan['to_text'] }}</td>
            <td>{{ $plan['type'] }}</td>

        </tr>
    @endforeach
    </tbody>
</table>
<hr style="margin-top: 30px;">

<div style="font-size: 10pt; color: #555; text-align: center; margin-top: 10px;">
    <div>{{ config('app.name') }}</div>
    <div><a href="https://www.quranreview.app/" style="color: #555; text-decoration: none;">https://www.quranreview.app/</a></div>
    <div>تاريخ الطباعة: {{ \Carbon\Carbon::now()->translatedFormat('l d M Y - h:i A') }}</div>
</div>


</body>
</html>
