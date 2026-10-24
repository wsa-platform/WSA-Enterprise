<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17349). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 8:43 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17350; it is archived once. -->

---
# WSA-Enterprise — CapVer Missing Table: Forensic Review Only
## الهدف
تشخيص سبب غياب الجدول:
`cghia_capability_cell_records`
من قاعدة البيانات الأصلية `wsa_enterprise`، بعد نجاح استعادة المشروع بتاريخ 19 أكتوبر 2026.
**هذه مهمة فحص وتحليل فقط. لا تصلح المشكلة ولا تغيّر قاعدة البيانات.**
## الحالة التشغيلية المحمية
الخدمات الحالية تعمل:
- PostgreSQL: `wsa-enterprise-postgres-1`
- Redis: `wsa-enterprise-redis-1`
- Backend: `wsa-enterprise-backend-1`
- Frontend: `wsa-enterprise-frontend-1`
- Nginx: `wsa-enterprise-nginx-1`
- Queue: `wsa-enterprise-queue-1`
- Scheduler: `wsa-enterprise-scheduler-1`
رابط التطبيق:
`http://127.0.0.1:8079/`
لا توقف هذه الخدمات أو تعِد تشغيلها.
## 1. مراجعة المستودع
افحص دون تعديل:
- جميع ملفات migrations التي قد تنشئ `cghia_capability_cell_records`.
- أسماء الجداول القريبة أو السابقة المستخدمة في CapVer.
- نماذج Eloquent والعلاقات والخدمات والمستودعات التي تشير إلى الجدول.
- تسجيل migrations وترتيبها وشروط تنفيذها.
- قرارات ADR والعقود المعمارية المرتبطة بـ CapVer وCapability Cells.
- تاريخ Git للملفات ذات الصلة، إذا كان متاحًا محليًا.
- ما إذا كان الجدول مطلوبًا في بيئة التشغيل الحالية أو في مرحلة مستقبلية فقط.
لا تفترض أن غياب الجدول خطأ قبل مراجعة العقد المعماري ومصدر إنشاء الجدول المتوقع.
## 2. مراجعة قاعدة البيانات — قراءة فقط
باستخدام الاتصال الموجود داخل الحاوية الحالية، نفّذ استعلامات قراءة فقط للتحقق من:
- قاعدة البيانات والمستخدم الفعليين.
- وجود جدول `migrations` وعدد السجلات فيه.
- وجود أي migration مرتبطة بـ CapVer أو `cghia_capability_cell_records`.
- حالة تسجيل migration المسؤولة عن إنشاء الجدول، إن وُجدت.
- أسماء الجداول ذات الصلة الموجودة حاليًا في `public`.
- أي اختلاف واضح بين مخطط قاعدة البيانات الحالية والمخطط الذي تتوقعه ملفات المشروع.
استخدم `SELECT` واستعلامات `information_schema` فقط.
لا تطبع الأسرار أو كلمات المرور أو بيانات المستخدمين الحساسة.
## 3. مقارنة الأدلة
ميّز بوضوح بين:
1. ما يثبته كود المشروع وملفات migrations.
2. ما تثبته قاعدة البيانات الحالية.
3. ما تنص عليه قرارات ADR والعقود المعمارية.
4. ما لا يمكن إثباته من الأدلة المتاحة.
حدّد السيناريو الأقرب بناءً على الأدلة، دون افتراض:
- migration لم تُنفّذ.
- الجدول أُزيل سابقًا.
- الجدول لم يكن مطلوبًا بعد.
- الجدول يُنشأ بطريقة أخرى.
لا تختَر أي سيناريو إلا إذا كانت الأدلة تدعمه.
## 4. ممنوعات إلزامية
- لا تنفّذ أي `php artisan migrate` أو `migrate:status` إذا لم يكن واضحًا أنه لا يغيّر البيانات؛ يُسمح بفحص حالة migrations فقط عبر استعلامات SQL للقراءة.
- لا تنفّذ migrations أو rollback أو seeders.
- لا تنشئ الجدول أو تعدّل المخطط.
- لا تنفّذ `CREATE` أو `ALTER` أو `DROP` أو `TRUNCATE` أو أي DML.
- لا تستخدم restore أو backup import.
- لا تعدّل `.env` أو ملفات المشروع أو إعدادات Docker.
- لا تشغّل أو توقف أو تعِد تشغيل أي حاوية.
- لا تنفّذ Git commit أو push أو reset أو clean.
- لا تبدأ مهمة النسخ الاحتياطية ضمن هذا العمل.
إذا تعذر فحص شيء بأمان، اذكر السبب وتوقف عن ذلك الجزء دون تجاوز القيود.
## 5. التقرير النهائي
قدّم تقريرًا واحدًا بالعربية يحتوي على:
- السبب المثبت أو الاحتمالات المدعومة لغياب الجدول.
- ملفات migrations ذات الصلة ونتائج فحصها.
- حالة تسجيل migration في قاعدة البيانات.
- الأدلة المعمارية من ADR.
- أثر غياب الجدول على التطبيق، وفق الأدلة فقط.
- هل الجدول مطلوب الآن أم لا يزال الأمر غير محسوم.
- الخيارات الممكنة لمعالجة المشكلة مستقبلًا، دون تنفيذ أي منها.
- الملفات التي فُحصت، وأي استعلامات قراءة فقط نُفّذت.
- تأكيد أن الخدمات وقاعدة البيانات بقيت دون تغيير.
**توقف بعد التقرير وانتظر موافقة منفصلة قبل أي تعديل.**
