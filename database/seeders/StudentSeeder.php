<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'name' => 'أحمد خالد الزهراني',
                'email' => 'ahmad.zhr@example.com',
                'phone' => '966512345678',
                'dob' => '2011-02-18',
                'institution' => 'مدرسة التوحيد',
                'memorized_parts' => 10,
                'preferred_review_days' => 7,
                'review_quarters_per_day' => 4,
                'new_memorization_mode' => 'quarter',
            ],
            [
                'name' => 'فهد ناصر الشمري',
                'email' => 'fahad.nsr@example.com',
                'phone' => '966513982014',
                'dob' => '2007-08-03',
                'institution' => 'مركز الفرقان',
                'memorized_parts' => 18,
                'preferred_review_days' => 10,
                'review_quarters_per_day' => 8,
                'new_memorization_mode' => 'half-quarter',
            ],
            [
                'name' => 'عبدالعزيز تركي المطيري',
                'email' => 'aziz.mtr@example.com',
                'phone' => '966598741023',
                'dob' => '2003-06-10',
                'institution' => 'مجمع نور البيان',
                'memorized_parts' => 22,
                'preferred_review_days' => 14,
                'review_quarters_per_day' => 12,
                'new_memorization_mode' => 'quarter-quarter',
            ],
            [
                'name' => 'عبدالله محمد القحطاني',
                'email' => 'abdullah.qh@example.com',
                'phone' => '966554789321',
                'dob' => '2012-01-25',
                'institution' => 'حلقة مسجد الهدى',
                'memorized_parts' => 5,
                'preferred_review_days' => 5,
                'review_quarters_per_day' => 2,
                'new_memorization_mode' => 'quarter',
            ],
            [
                'name' => 'سعود بدر العتيبي',
                'email' => 'saud.bt@example.com',
                'phone' => '966557730945',
                'dob' => '2006-11-17',
                'institution' => 'مجمع الإمام عاصم',
                'memorized_parts' => 9,
                'preferred_review_days' => 7,
                'review_quarters_per_day' => 4,
                'new_memorization_mode' => 'half-quarter',
            ],
            [
                'name' => 'مازن عبدالإله الشهري',
                'email' => 'mazen.sh@example.com',
                'phone' => '966543210987',
                'dob' => '2010-04-09',
                'institution' => 'دار البيان لتحفيظ القرآن',
                'memorized_parts' => 7,
                'preferred_review_days' => 10,
                'review_quarters_per_day' => 3,
                'new_memorization_mode' => 'quarter-quarter',
            ],
            [
                'name' => 'وليد صالح الغامدي',
                'email' => 'waleed.g@example.com',
                'phone' => '966564738291',
                'dob' => '2005-12-28',
                'institution' => 'مدرسة الفرقان',
                'memorized_parts' => 15,
                'preferred_review_days' => 7,
                'review_quarters_per_day' => 6,
                'new_memorization_mode' => 'quarter',
            ],
            [
                'name' => 'ريان أحمد السبيعي',
                'email' => 'rayan.sb@example.com',
                'phone' => '966558921034',
                'dob' => '2009-03-01',
                'institution' => 'مركز زاد',
                'memorized_parts' => 12,
                'preferred_review_days' => 6,
                'review_quarters_per_day' => 5,
                'new_memorization_mode' => 'half-quarter',
            ],
            [
                'name' => 'محمد راشد الحربي',
                'email' => 'm.rashid@example.com',
                'phone' => '966576809123',
                'dob' => '2002-09-14',
                'institution' => 'مدرسة نور الهداية',
                'memorized_parts' => 21,
                'preferred_review_days' => 10,
                'review_quarters_per_day' => 10,
                'new_memorization_mode' => 'quarter-quarter',
            ],
            [
                'name' => 'نواف عبدالمجيد الزبيدي',
                'email' => 'nawaf.zb@example.com',
                'phone' => '966567829410',
                'dob' => '2013-07-06',
                'institution' => 'مركز الرضوان',
                'memorized_parts' => 6,
                'preferred_review_days' => 4,
                'review_quarters_per_day' => 2,
                'new_memorization_mode' => 'quarter',
            ],
        ];

        foreach ($students as $student) {
            Student::create($student);
        }
    }
}
