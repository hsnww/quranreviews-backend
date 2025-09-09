<?php

namespace Database\Seeders;

use App\Models\QuranVerse;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class QuranVersesSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/quran/المصحف.xlsx');

        // قراءة أول شيت كمصفوفة (بدون رؤوس)
        $sheets = Excel::toArray([], $path);

        $rows = $sheets[0]; // أول شيت
//        unset($rows[0]);    // حذف أول صف لأنه رؤوس الأعمدة

        foreach ($rows as $row) {
            // تأكد من وجود القيم وتطابق الترتيب
            QuranVerse::create([
                'qrtr' => $row[0],
                'hizb' => $row[1],
                'jozo' => $row[2],
                'page' => $row[3],
                'sora' => $row[4],
                'ayah' => $row[5],
                'text' => $row[6],
            ]);
        }
    }
}


