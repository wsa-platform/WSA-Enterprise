<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17521). Message timestamp as recorded in the conversation: Tuesday, Oct 20, 2026, 11:45 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17522; it is archived once. -->

---
# WSA-Enterprise — Second-Pass Commit Readiness Audit
## الهدف
اكتشاف جميع مجموعات الملفات المحلية التي أصبحت جاهزة فعلًا للـ Commit بعد رفع إصلاح الواجهة الأمامية.
Repository: `G:\WSA-Enterprise`
Branch: `phase-18-m18-ai-marketing-communications`
Expected local HEAD:
`3eeba4012d40a3bddf9318470406b8c8000694b7`
Expected remote HEAD:
`8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`
## المرحلة 1 — التحقق من الحالة الحالية
نفّذ:
- `git status --short`
- `git status -sb`
- `git diff --stat`
- `git diff --cached --stat`
- `git diff --check`
- `git log --graph --oneline --decorate -12`
تحقق من الفرع المحلي والبعيد ومن عدم وجود تغييرات staged غير معروفة. لا تعتمد على أعداد التقارير السابقة إذا اختلفت الحالة الحالية.
## المرحلة 2 — جرد وتصنيف الملفات
راجع جميع التغييرات المتعقبة وغير المتعقبة، وصنّفها إلى مجموعات وظيفية متماسكة. افحص خصوصًا:
1. `MissionArtifactArchive`
2. التوثيق والتقارير المعمارية
3. خدمات البحث العلمي و`LocalIndex`
4. عقود Source Registry وCapability وProvenance
5. ملفات الاختبارات والـ fixtures
6. `backend/tests/Pgsql/`
7. `ADR-023-scientific-source-expansion.md`
8. ملفات إعداد المشروع و`composer.lock`
9. الملفات المؤقتة وبيانات E2E
لا تصنّف الملف بأنه جاهز لمجرد أن حجمه صغير أو أن تغييره يبدو مستقلًا؛ تحقق من التبعيات والهدف الوظيفي.
## المرحلة 3 — مراجعة الجاهزية
لكل مجموعة مرشحة:
- راجع الفرق كاملًا.
- تحقق من اكتمال التنفيذ والتبعيات.
- راجع الاختبارات الموجودة ذات الصلة.
- شغّل الاختبارات المسموح بها فقط إذا كان تشغيلها آمنًا.
- لا تشغّل اختبارات يمكن أن تكتب إلى SQLite التشغيلية أو قاعدة PostgreSQL التشغيلية.
- لا تستخدم بيانات اعتماد حقيقية ولا تطبع الأسرار.
- لا تدّعِ نجاح اختبار لم يُشغّل فعليًا.
أي مجموعة تتطلب اختبارًا غير متاح تُصنّف «تحتاج تحققًا»، لا «جاهزة».
## المرحلة 4 — حماية العمل المحلي
ممنوع:
- `git add` أو `git commit` أو `git push`
- `git stash` أو `git reset` أو `git clean` أو `git restore`
- Rebase أو Merge أو Cherry-pick
- حذف أو نقل أو إعادة تسمية أي ملف
- تطبيق migrations أو تغيير قاعدة البيانات
- تعديل ADR-023 أو إصلاح whitespace في هذه الجولة
حافظ على كل WIP كما هو. لا تُدرج ملفات كلمات المرور أو الرموز أو مخرجات E2E المؤقتة في المجموعات المرشحة.
## التقرير النهائي
قدّم تقريرًا عربيًا واحدًا يتضمن:
1. حالة Git الحالية الفعلية والفرق بين المحلي والبعيد.
2. كل مجموعة ملفات، ومساراتها، والغرض منها.
3. تصنيف كل مجموعة:
   - **جاهزة للـ Commit**
   - **تحتاج إصلاحًا**
   - **تحتاج اختبارات أو تحققًا**
   - **مستبعدة من Commit**
4. الأدلة والاختبارات الفعلية لكل مجموعة جاهزة.
5. التبعيات والمخاطر التي تمنع اعتماد المجموعات الأخرى.
6. قائمة دقيقة بأفضل المجموعات التالية للـ Commit، كل مجموعة منفصلة بعنوان Commit مقترح.
7. تأكيد أن هذه الجولة لم تغيّر الملفات ولم تنفذ Commit أو Push.
