# تحديث الدومين في فوتر PDF

## التغيير المطبق:
تم تحديث الدومين في فوتر ملفات PDF من `http://quran-review.test` إلى `https://www.quranreview.app/`

## الملفات المحدثة:
- `resources/views/exports/review-plan-pdf.blade.php`

## التغيير:
```php
// قبل التحديث:
<div><a href="{{ config('app.url') }}" style="color: #555; text-decoration: none;">{{ config('app.url') }}</a></div>

// بعد التحديث:
<div><a href="https://www.quranreview.app/" style="color: #555; text-decoration: none;">https://www.quranreview.app/</a></div>
```

## التأثير:
- جميع ملفات PDF المُصدرة ستعرض الآن الدومين الصحيح `https://www.quranreview.app/`
- هذا يشمل:
  - خطط المراجعة (Review Plans)
  - أي PDF آخر يستخدم نفس الـ view

## الاختبار:
1. قم بتسجيل الدخول إلى النظام
2. اذهب إلى صفحة خطط المراجعة
3. اضغط على "تصدير PDF"
4. تحقق من أن فوتر PDF يعرض `https://www.quranreview.app/`

## ملاحظة:
هذا التغيير ثابت ولا يعتمد على متغيرات البيئة، مما يضمن عرض الدومين الصحيح في جميع البيئات (تطوير، إنتاج، اختبار).
