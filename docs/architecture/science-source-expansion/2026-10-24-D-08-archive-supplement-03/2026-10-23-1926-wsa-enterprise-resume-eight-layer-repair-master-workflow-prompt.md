<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18245). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 7:26 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18246; it is archived once. -->

---
# WSA-Enterprise — Resume Eight-Layer Repair Master Workflow

## المشروع

- Repository: `wsa-platform/WSA-Enterprise`
- Local path: `G:\WSA-Enterprise`
- Branch: `phase-18-m18-ai-marketing-communications`
- Latest reported commit: `1e4d7a6`
- Commit message: `docs(research): add current 110-member source register`

## 1. استئناف من نقطة التوقف الصحيحة

اقرأ تعليمات الماستر برومبت المشتركة `MASTER-ENGINEERING-COMMON.md` وملف الجولة الأولى `MASTER-PROMPT-ROUND1.md` كاملين إذا كانا متاحين في بيئة العمل.

إذا لم يكونا موجودين محليًا، فلا تدّعِ قراءتهما. استخدم التعليمات المتاحة في سياق المهمة، واذكر الملفات الناقصة بوضوح.

تحقق فعليًا من:

- مسار المستودع والفرع وHEAD.
- أن الالتزام `1e4d7a6` موجود في تاريخ الفرع، وأن رفعه السابق يمكن التحقق منه.
- حالة Git الحالية، بما فيها staged وunstaged وuntracked.
- جميع التغييرات السابقة والملفات غير المتتبعة.
- حالة قواعد البيانات المحمية وبصماتها حيث يمكن قياسها بأمان.
- أي checkpoint صالح سابق للجولة الأولى.

لا تفترض أن الحالة القديمة ما زالت كما هي.

## 2. التعامل مع تغيّر خط الأساس

كان خط الأساس السابق هو:

`fb60b60746ed487e2bfa04e3d335827d91cda446`

لكن تم لاحقًا إنشاء الالتزام:

`1e4d7a6`

لا تعتبر هذا الاختلاف خطأ تلقائيًا، ولا تتجاهله. تحقّق من أن الالتزام الجديد هو التغيير المتوقع الوحيد في تاريخ Git، ثم سجّل HEAD الفعلي باعتباره خط أساس الاستئناف الجديد لهذه الجولة.

إذا ظهر اختلاف إضافي غير مفسّر، أو كان الفرع أو التاريخ لا يتطابقان مع الأدلة المتاحة، فتوقف قبل تعديل الملفات وقدّم تقريرًا واضحًا.

## 3. نطاق التنفيذ المسموح

بعد نجاح التحقق من خط الأساس، نفّذ **الجولة الأولى فقط: Layers A–D** وفق `MASTER-PROMPT-ROUND1.md`.

تشمل الجولة:

- Layer A — D-08 Archive and Provenance.
- Layer B — Membership Register and Evidence Matrices.
- Layer C — ADR-023 Integrity.
- Layer D — Mission Folder.

لا تعِد تنفيذ إضافة G2-26؛ فقد أُضيف سجل العضوية الحالي ذو 110 مصادر في الالتزام `1e4d7a6`. راجع فقط اتساقه مع العقد المعماري والمصفوفات والأدلة، ولا تعدّل السجلات التاريخية لمجرد توحيد الأعداد.

لا تبدأ Layers E–H في هذه الجولة.

## 4. ضوابط الحماية

- حافظ على كل التغييرات السابقة.
- لا تستخدم `git reset --hard` أو `git clean` أو `git stash` أو أي عملية مدمرة.
- لا تستخدم `git add .` أو `git add -A`.
- لا تنفّذ `commit` أو `push` أو merge أو rebase.
- لا تعدّل قاعدة البيانات التشغيلية `scientific_record_store.sqlite`.
- لا تستخدم قواعد بيانات تشغيلية لاختبارات.
- لا تشغّل الاختبارات قبل إثبات العزل عن قواعد البيانات التشغيلية.
- لا تختلق سجلات أو بصمات أو أدلة أو نتائج اختبار.
- لا تغيّر حالات التحقق للمصادر دون دليل وتعريف معتمد.
- لا تدمج التغييرات المحلية السابقة في ADR-023 دون مراجعة كاملة وفصل واضح للنطاق.

## 5. التنفيذ والتقرير

لا تكتفِ بالخطة إذا أمكن تنفيذ عمل آمن ومبرر. نفّذ الأعمال المسموح بها ضمن الجولة الأولى، وشغّل الفحوص الآمنة المناسبة، وسجّل نتائجها الفعلية.

أنشئ checkpoint للجولة الأولى وفق اتفاقية الماستر برومبت، دون استبدال أي ملف موجود. يجب أن يحتوي على خط الأساس الفعلي، وحالة الطبقات A–D، والملفات المتغيرة، والاختبارات، وبصمات البيانات المحمية، والمخاطر المتبقية، وقائمة ملفات مرشحة للـcommit.

قدّم تقريرًا نهائيًا شاملًا بالعربية يوضح ما نُفّذ وما اختُبر وما بقي غير محسوم.

توقف بعد الجولة الأولى. لا تبدأ الجولة الثانية تلقائيًا، ولا تنفّذ أي commit أو push.
