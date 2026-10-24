<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17276). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 6:19 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. -->

---
# WSA-Enterprise — Identify the Original PostgreSQL Container and Volume

افحص Docker الحالي في `G:\WSA-Enterprise`، مع اعتبار الصورة المرفقة دليلًا على وجود أربع حاويات متوقفة وعدم وجود حاويات عاملة.

الحاويات الظاهرة:
- `wsa-r7-store-pg...`
- `wsa-backup-ver...` مع المنفذ `55432:5432`
- `wsa-enterprise-...` بصورة PostgreSQL
- `wsa-enterprise` أو مجموعة خدمات تحمل هذا الاسم

والـ Volumes الظاهرة سابقًا:
- `wsa-enterprise_postgres_data` (185.2 MB)
- `wsa-r7-store-pgdata-20261015` (123.9 MB)
- `wsa-enterprise_backend_vendor` (45 MB)
- `wsa-enterprise_redis_data` (2.6 KB)
- Volume إضافي بمعرّف طويل (73.9 MB)

## المطلوب
1. استخدم Docker CLI المتصل بالـ Engine الحالي لفحص جميع الحاويات المتوقفة وبيانات `docker inspect`.
2. سجّل الاسم الكامل لكل حاوية وصورة PostgreSQL وإصدارها ووقت إنشائها، مع إخفاء الأسرار.
3. استخرج ارتباط كل حاوية بـ Volumes وMounts والمنافذ وCompose project/service labels.
4. افحص ملفات Compose وسجل Git لتحديد أي حاوية تطابق إعدادات المشروع التاريخية.
5. إذا كانت الأدوات تسمح بذلك دون تشغيل الحاوية، افحص سجلات PostgreSQL السابقة المتاحة لتحديد اسم القاعدة ووقت التشغيل أو آخر نشاط، مع عدم عرض الأسرار.
6. حدّد أي حاوية مرتبطة بـ `wsa-enterprise_postgres_data` وأي حاوية مرتبطة بـ `wsa-r7-store-pgdata-20261015`.
7. ميّز بين حاوية قاعدة Laravel الأصلية المحتملة، وحاوية النسخة الاحتياطية، ومخزن R7 الأحدث، مع توضيح الأدلة ومستوى الثقة.
8. إذا أمكن تحديد ارتباطات الـ Volumes، افحص بياناتها الوصفية فقط. لا تعدّل ملفات PostgreSQL الداخلية ولا تفك تركيب أي Volume.

## قيود إلزامية
- لا تشغّل أي حاوية ولا تعِد تشغيلها.
- لا تنفذ Compose up/down.
- لا تنشئ أو تحذف أي Volume أو حاوية.
- لا تنفذ migrations أو restore أو عمليات كتابة.
- لا تغيّر `backend/.env` أو أي ملف مشروع.
- لا تغيّر كلمات المرور أو إعدادات المصادقة.
- لا تنفذ Commit أو Push.
- لا تطبع أي أسرار.
- توقف بعد تقرير الأدلة، ولا تنفذ إصلاحًا.

أصدر تقريرًا عربيًا واحدًا يحدد الروابط الفعلية بين الحاويات والـ Volumes، ويبين أي قاعدة يُرجح أنها قاعدة Laravel الأصلية ولماذا.
