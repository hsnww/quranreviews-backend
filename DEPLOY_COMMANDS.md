# أوامر Git للنشر - الباك إند (Laravel)

## 1. التحقق من الحالة الحالية
```bash
cd "C:\Laravel projects\quran-review-backend"
git status
```

## 2. إضافة الملفات المحدثة
```bash
# إضافة جميع التغييرات
git add .

# أو إضافة ملفات محددة
git add app/Http/Controllers/Api/AuthController.php
git add app/Models/Student.php
git add routes/api.php
git add resources/views/exports/review-plan-pdf.blade.php
git add config/cors.php
```

## 3. إنشاء commit
```bash
git commit -m "feat: إضافة API لتعديل بيانات العضو وإصلاح مشاكل التسجيل

- إضافة دالة updateUser في AuthController
- إضافة مسار PUT /api/user
- إصلاح مشكلة $fillable في مودل Student
- تحديث الدومين في فوتر PDF إلى https://www.quranreview.app/
- إصلاح مشاكل CORS
- تحسين رسائل الخطأ في التسجيل"
```

## 4. دفع التغييرات إلى GitHub
```bash
# الدفع إلى الفرع الرئيسي
git push origin main

# أو إذا كنت تستخدم فرع آخر
git push origin develop
```

## 5. النشر على DigitalOcean (إذا كان لديك إعدادات CI/CD)
```bash
# إذا كان لديك GitHub Actions
git tag v1.2.0
git push origin v1.2.0
```

## 6. أوامر إضافية للخادم (إذا كان النشر يدوي)
```bash
# على الخادم
cd /var/www/quran-review-backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

---

# أوامر Git للنشر - الفرونت إند (Next.js)

## 1. التحقق من الحالة الحالية
```bash
cd "C:\Laravel projects\quran-review-frontend"
git status
```

## 2. إضافة الملفات المحدثة
```bash
# إضافة جميع التغييرات
git add .

# أو إضافة ملفات محددة
git add src/app/(auth)/register/page.tsx
git add ENV_SETUP.md
git add SETUP_INSTRUCTIONS.md
```

## 3. إنشاء commit
```bash
git commit -m "feat: إصلاح مشاكل التسجيل وتحسين واجهة المستخدم

- إصلاح مشكلة password_confirmation في التسجيل
- تحسين رسائل الخطأ والتحقق من البيانات
- إضافة ملفات توثيق للإعداد
- تحسين معالجة الأخطاء في التسجيل"
```

## 4. دفع التغييرات إلى GitHub
```bash
# الدفع إلى الفرع الرئيسي
git push origin main

# أو إذا كنت تستخدم فرع آخر
git push origin develop
```

## 5. النشر على Vercel (إذا كان متصلاً بـ GitHub)
```bash
# Vercel سيتعامل مع النشر تلقائياً عند push
# أو يمكنك النشر يدوياً:
npx vercel --prod
```

## 6. أوامر إضافية للبناء المحلي
```bash
# بناء المشروع للبيئة الإنتاجية
npm run build

# اختبار البناء
npm start
```

---

# أوامر سريعة للنشر السريع

## الباك إند:
```bash
cd "C:\Laravel projects\quran-review-backend"
git add . && git commit -m "feat: إصلاحات التسجيل وتحديث PDF" && git push origin main
```

## الفرونت إند:
```bash
cd "C:\Laravel projects\quran-review-frontend"
git add . && git commit -m "feat: إصلاحات التسجيل وتحسينات UI" && git push origin main
```

---

# ملاحظات مهمة:

1. **تأكد من أن جميع التغييرات تعمل محلياً قبل النشر**
2. **اختبر التسجيل وتعديل البيانات قبل النشر**
3. **تأكد من إعداد متغيرات البيئة على الخادم**
4. **احتفظ بنسخة احتياطية من قاعدة البيانات قبل النشر**
