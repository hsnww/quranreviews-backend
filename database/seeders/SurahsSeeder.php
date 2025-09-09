<?php

namespace Database\Seeders;

use App\Models\Surah;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class SurahsSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/quran/المصحف.xlsx');

        // جلب كل الشيتات كمصفوفة خام
        $sheets = Excel::toArray([], $path);

        // شيت الفهرس موجود غالبًا في المؤشر [1]
        $rows = $sheets[2];

        // حذف صف العناوين
        unset($rows[0]);

        foreach ($rows as $row) {
            // تجاهل الصفوف التي لا تحتوي على رقم سورة حقيقي
            if (!is_numeric($row[0])) {
                continue;
            }

            Surah::create([
                'id' => $row[0],
                'name' => trim($row[1]),
            ]);
        }
    }
}
