# التشغيل على Docker

المشروع (الباكند + الفرونت) يعمل بالكامل على Docker، ولا حاجة لتثبيت PHP أو MySQL أو Node على الجهاز.

يجب أن يكون المستودعان متجاورين:

```
~/projects/
├── quran-review-backend    ← ملفات compose هنا
└── quran-review-frontend
```

## الخدمات

| الخدمة      | الوصف                                  | العنوان                       |
|-------------|----------------------------------------|-------------------------------|
| `frontend`  | Next.js                                | http://localhost:3000         |
| `backend`   | Laravel (nginx + php-fpm)، لوحة Filament | http://localhost:8000/admin   |
| `queue`     | `queue:work` (بريد إعادة كلمة المرور)   | —                             |
| `scheduler` | `schedule:work` (حذف التوكنات المنتهية) | —                             |
| `mysql`     | MySQL 8                                | `127.0.0.1:3307` من جهازك     |
| `phpmyadmin`| إدارة القاعدة (التطوير فقط، يدخل كـ root) | http://localhost:8080         |

المتصفح لا يتصل بالباكند مباشرة: الفرونت يمرر الطلبات عبر `/backend-api` إلى `http://backend:8080` داخل شبكة Docker.

## التطوير

```bash
cp .env.example .env     # أول مرة فقط، ثم اضبط APP_KEY و JWT_SECRET
docker compose up -d --build
docker compose exec backend composer install   # أول مرة فقط
docker compose exec backend php artisan key:generate   # إن كان APP_KEY فارغًا
docker compose exec backend php artisan jwt:secret     # إن كان JWT_SECRET فارغًا
docker compose exec backend php artisan migrate
```

الكود مربوط من الجهاز، فتظهر التعديلات فورًا في الباكند (دون إعادة تشغيل) وفي الفرونت (hot reload).

أوامر شائعة:

```bash
docker compose exec backend php artisan <command>
docker compose exec backend composer require <package>
docker compose exec frontend npm install <package>   # ثم: docker compose up -d --build -V frontend
docker compose restart queue        # بعد تعديل Jobs أو Mail
docker compose logs -f backend      # السجلات
docker compose down                 # إيقاف (البيانات تبقى)
```

## الإنتاج

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

- الكود داخل الصور، وOPcache مفعّل، و`APP_DEBUG=false`.
- عند الإقلاع يُنفَّذ تلقائيًا: انتظار القاعدة ثم `migrate --force` و`optimize` و`storage:link`.
- الفرونت مبني `standalone`، ويعمل بمستخدم غير root، بنظام ملفات للقراءة فقط، ودون أي Linux capabilities.
- عنوان الباكند العام الذي يظهر في CSP يُحدَّد وقت البناء عبر `FRONTEND_PUBLIC_API_URL` في `.env`.

## الأداء على Windows: ضع الكود داخل WSL

الكود على `C:\` يمر عبر جسر بطيء بين Windows وLinux. عند القياس كانت صفحة `/admin/login` تستغرق **~6000ms على C:\ مقابل ~90ms داخل WSL**.

1. ثبّت Ubuntu (PowerShell كمسؤول)، ثم أنشئ اسم المستخدم وكلمة المرور عند الطلب:
   ```powershell
   wsl --install -d Ubuntu
   ```
2. Docker Desktop ← Settings ← Resources ← WSL integration ← فعّل **Ubuntu** ← Apply & restart.
3. داخل Ubuntu:
   ```bash
   mkdir -p ~/projects && cd ~/projects
   git clone https://github.com/hsnww/quranreviews-backend.git quran-review-backend
   git clone https://github.com/hsnww/quranreviews-frontend.git quran-review-frontend
   cp "/mnt/c/Laravel projects/quran-review-backend/.env" quran-review-backend/.env
   sed -i 's/^WATCHPACK_POLLING=.*/WATCHPACK_POLLING=false/' quran-review-backend/.env
   cd quran-review-backend
   docker compose up -d --build
   docker compose exec backend composer install
   ```
4. افتح المشروع في VS Code من داخل WSL: `code .` (يتطلب إضافة **WSL** في VS Code).

لا تشغّل النسختين (على C:\ وداخل WSL) في الوقت نفسه، لأن المنافذ نفسها مستخدمة في الاثنتين.
