<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17321). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 7:56 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17322; it is archived once. -->

---
# WSA-Enterprise — Restore Nginx and Frontend Safely
## Authorization
أوافق على استعادة خدمتي Nginx وFrontend الأصليتين لمشروع WSA-Enterprise، بهدف إعادة الوصول إلى التطبيق عبر المتصفح باستخدام منفذ المضيف 8079، مع الحفاظ على الحالة الحالية الناجحة للخدمات الأساسية.
الحالة الحالية المؤكدة:
- PostgreSQL: `wsa-enterprise-postgres-1` — Running / Healthy — المنفذ 5432.
- Redis: `wsa-enterprise-redis-1` — Running / Healthy — المنفذ 6379.
- Backend: `wsa-enterprise-backend-1` — Running / Healthy — اتصال PostgreSQL وRedis ناجح.
- Nginx وFrontend: متوقفتان.
- Queue وScheduler وR7 وbackup-verification والحاوية اليتيمة: متوقفة عمدًا.
## Phase 1 — Preflight Inspection
افحص الحاويتين الأصليتين:
- `wsa-enterprise-nginx-1`
- `wsa-enterprise-frontend-1`
تحقق من:
1. وجود الحاويتين وعدم حذفهِما أو استبدالهما.
2. صور Docker والإعدادات الفعلية وMounts والشبكات والمنافذ.
3. أن Nginx يستخدم منفذ المضيف التاريخي 8079، إن كان هذا مثبتًا في إعداد الحاوية الحالية.
4. أن Backend وPostgreSQL وRedis ما زالت تعمل.
5. أن المنفذ 8079 متاح ولا توجد خدمة أخرى تشغله.
6. إعدادات Nginx الفعلية ومسارات ملفات الواجهة والاعتمادات بين الخدمات.
7. سجلات التشغيل السابقة وأي Health Checks متاحة.
لا تطبع الأسرار أو كلمات المرور.
إذا كانت الحاوية مفقودة، أو كانت إعداداتها تختلف عن المتوقع، أو احتاجت إلى إعادة إنشاء أو تعديل ملفات، فتوقف عن التشغيل واعرض النتائج والسبب. لا تحاول الإصلاح تلقائيًا.
## Phase 2 — Restore Frontend
إذا كانت الحاوية الأصلية `wsa-enterprise-frontend-1` موجودة وإعداداتها سليمة وقابلة للتشغيل دون إعادة إنشاء أو تعديل، شغّلها باستخدام:
`docker start wsa-enterprise-frontend-1`
تحقق من:
- بقائها Running.
- عدم وجود أخطاء بناء أو بدء تشغيل حرجة.
- سجلاتها الفعلية.
- إمكانية الوصول إلى الملفات أو الخدمة التي يتوقع Nginx تقديمها، وفق التكوين الحالي.
إذا فشلت، اجمع الأدلة وتوقف عن إصلاحها تلقائيًا. لا تنشئ صورة جديدة ولا تعدّل ملفات الواجهة.
## Phase 3 — Restore Nginx
لا تشغّل Nginx إلا بعد التحقق من إعداداتها ومن جاهزية المتطلبات التي تعتمد عليها.
إذا كانت الحاوية الأصلية `wsa-enterprise-nginx-1` موجودة وإعداداتها سليمة والمنفذ 8079 متاحًا، شغّلها باستخدام:
`docker start wsa-enterprise-nginx-1`
تحقق من:
- بقائها Running.
- ربط المنفذ 8079 كما هو محدد في الإعداد الأصلي.
- نجاح فحص إعدادات Nginx، إذا كانت الأداة متاحة داخل الحاوية.
- عدم وجود أخطاء upstream أو DNS أو اتصال في السجلات.
- إمكانية الوصول محليًا إلى عنوان التطبيق على المنفذ 8079، باستخدام فحص HTTP مناسب.
لا تغيّر ملفات Nginx أو الشبكات أو المنافذ أو إعدادات التطبيق.
## Phase 4 — End-to-End Verification
بعد تشغيل الخدمتين، أعد التحقق من:
- PostgreSQL: Running / Healthy.
- Redis: Running / Healthy.
- Backend: Running / Healthy.
- Frontend: الحالة الفعلية ونتيجة فحص الخدمة.
- Nginx: الحالة الفعلية ونتيجة فحص HTTP.
- المنفذ 8079: عنوان URL وHTTP status الفعلي.
- سجلات الخدمات الجديدة بعد التشغيل.
ميّز بين نجاح HTTP وبين إثبات عمل كل وظائف التطبيق. لا تدّعِ نجاحًا كاملًا إذا كانت الصفحة تُحمّل لكن API أو الأصول الثابتة تفشل.
## Strictly Prohibited
- لا تستخدم `docker compose up/down`.
- لا تستخدم `docker run` ولا تعِد إنشاء أي حاوية.
- لا تشغّل Queue أو Scheduler أو R7 أو backup-verification أو الحاوية اليتيمة.
- لا تنفّذ migrations أو restore أو seeders.
- لا تعدّل `.env` أو ملفات التطبيق أو Nginx أو Compose.
- لا تحذف أو تعدّل أي Volume أو بيانات.
- لا توقف PostgreSQL أو Redis أو Backend ما دامت تعمل.
- لا تنفّذ Git commit أو push أو reset أو clean.
إذا فشلت أي بوابة، توقف عن الإجراء المتأثر، واجمع السجلات والأدلة، ولا تتجاوز المشكلة بإعادة إنشاء الحاويات أو تغيير الإعدادات.
## Final Report
قدّم تقريرًا هندسيًا واحدًا بالعربية يتضمن:
- ما فُحص وما نُفّذ فعلًا.
- الحالة النهائية لكل حاوية.
- نتائج Health Checks.
- نتيجة HTTP الفعلية على المنفذ 8079.
- أخطاء السجلات أو مشكلات API والأصول إن وجدت.
- أي خطوات متبقية، دون الادعاء بأن التطبيق يعمل بالكامل إلا إذا أثبتت الفحوص ذلك.
نفّذ العمل فعليًا ضمن هذا النطاق، وتوقف عند أي حاجة إلى تغيير غير مصرح به.
