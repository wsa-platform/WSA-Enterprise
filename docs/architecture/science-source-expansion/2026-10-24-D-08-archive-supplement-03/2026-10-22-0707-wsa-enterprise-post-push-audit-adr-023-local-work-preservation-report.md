<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17660). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
النشر على GitHub سليم، وشجرة العمل الأصلية لم تُمس. الخطر الوحيد عند أي مزامنة لاحقة هو تصادم أقسام ADR-023 ذات الأرقام 8.27–8.29.

## A. حالة GitHub
`origin` هو `https://github.com/wsa-platform/WSA-Enterprise.git`. بعد `git fetch` للفرع فقط، `origin/phase-18-m18-ai-marketing-communications` يساوي `168edcc79a8eb377a0d69f9c1ea3ccb979b01670`.

التزام الدمج أبواه:

- الأول: `dffeed5a7ace309eadddffef02b278bfc5e0fffa`
- الثاني: `52e904ea7e037747bf9147ce95baef80965e195e`

`dffeed5` و`168edcc` كلاهما سلف للتاريخ المنشور. الالتزامات الستة الموجودة على البعيد وغير الموجودة في رأس الفرع المحلي هي: `168edcc`، ثم `52e904e`، ثم `8dd6827`، ثم `32eb117`، ثم `fba78bc`، ثم `067fe80`. لم يُنفَّذ Push.

الفرق بين `dffeed5` و`168edcc` ملف واحد فقط: ADR-023، `+247/−0`.

## B. شجرة العمل الأصلية
الفرع `phase-18-m18-ai-marketing-communications`، و`HEAD` قبل الفحص وبعده `dffeed5a7ace309eadddffef02b278bfc5e0fffa`. الحالة `behind 6`. لا عملية دمج أو إعادة قاعدة أو انتقاء. الفهرس فارغ. لا حذف ولا إعادة تسمية.

الجرد: **43** ملفًا معدّلًا غير مرحّل، و**103** مسارات غير متعقبة، والمجموع **146**. القائمة السابقة كانت 43 و102. الزيادة مسار واحد لأن ملفات LocalIndex التي دخلت `dffeed5` لم تعد مجلدًا غير متعقب واحدًا، وبقي ملفان ظاهران منفصلان: `LocalIndexLifecycleTest.php` و`IncrementalBulkIngestDoesNotWithdrawPeersTest.php`. قائمة الملفات المعدّلة الـ43 لم تتغير.

`dffeed5` نفسه هو `fix(tests): isolate scientific record SQLite store`، ويلمس ستة ملفات اختبار فقط.

Worktree `G:\WSA-Enterprise-reconcile-dffeed5` على `temp/reconcile-dffeed5` عند `168edcc` وشجرته نظيفة. بصمة ADR-023 المحلية قبل الفحص وبعده `A5B9B174097BBAE441AFE98646FCF5DBD1141EC61A20B1C22601256338DD21C5`، والحجم `647330`، ووقت التعديل `2026-10-20T19:21:21Z`.

## C. ADR-023
ثلاث نسخ:

| المقارنة | النتيجة |
|---|---|
| شجرة العمل مقابل `dffeed5` | `+3602/−1` |
| `dffeed5` مقابل المنشور `168edcc` | `+247/−0` |
| العناوين | المحلي 98، و`dffeed5` ينتهي عند 8.26، والمنشور 65 عنوانًا وينتهي عند 8.29 |

الموجود محليًا وغير الموجود في `dffeed5` ولا في المنشور هو السلسلة **8.27–8.62**: قرارات المالك، R3–R9، CapVer، واستعادة PostgreSQL، وOption A لمخزن SQLite التشغيلي. هذه سجلات معمارية يجب إبقاؤها.

الموجود في المنشور وغير الموجود محليًا ثلاثة أقسام بأرقام يعيد المحلي استخدامها لمعنى آخر:

- **8.27** المنشور: سجل استمرارية البحث العلمي وتوسعة 109 مصدرًا. المحلي: قبول مشروط لـ 8.24.
- **8.28** المنشور: سياسة أرشفة المحادثات. المحلي: إغلاق سجل القرار وبوابات الخروج.
- **8.29** المنشور: تقرير مصالحة 109 مصدرًا. المحلي: تحقق مرحلي للمرحلة 1.

لا يوجد رقم مكرر داخل النسخة المحلية وحدها، ولا داخل النسخة المنشورة وحدها. التصادم يظهر فقط إذا دُمجت النسختان. لا فجوة رقمية داخل كل نسخة. اختلاف الشرطات في العناوين الأقدم من 8.26 أثر عرض ترميزي؛ الإحصاء `+3602/−1` يعني أن متن `dffeed5` بقي مع إضافة الأقسام اللاحقة.

ADR-021 محليًا `+84/−1`، و`docs/adr/README.md` تسعة أسطر مضافة. هذان ليسا في فرق `dffeed5` مقابل المنشور.

## D. العمل المحلي الذي يجب إبقاؤه
كل ما يلي محلي فقط، ولا نسخة منشورة مقابلة له إلا حيث ذُكر:

- **ADR والسجلات:** ADR-023 وADR-021 و`docs/adr/README.md` و`docs/architecture/science-source-expansion/` و`BASELINE-RECONCILIATION.md` و`REMEDIATION-DEPENDENCY-MAP.md`. الحفظ إلزامي. الدمج اللاحق مع المنشور يتعارض عند 8.27–8.29.
- **Prompts وتقارير المتابعة:** ملفات `GO-*` و`R6`–`R9` وJSON السجلات داخل `science-source-expansion/`. محلية وغير منشورة. لا تُحذف.
- **الكود والاختبارات:** خدمات البحث وLocalIndex وCapVer وOnboarding وMembership وEvaluation وProjection والأوامر والاختبارات والهجرة غير المتعقبة. مترابطة، ولم تُنشر. دمجها فوق `168edcc` ممكن لاحقًا لأنها ليست في فرق النشر، بعد عزل الاختبارات عن SQLite التشغيلية.
- **الإعدادات:** `.gitignore` يكرر `/backups/`. `backend/.env.example` تعليقات فقط. `backend/config/database.php` و`agricultural_intelligence.php` معدّلان. `composer.lock` غير متعقب و`composer.json` غير معدّل. تُراجع قبل أي التزام ولا تُزامَن كما هي.
- **E2E والبيانات الحساسة:** `admin-mobile/e2e-tmp/` و`backend/e2e-tmp/` و`e2e-tmp/`، وفيها ملفات تسجيل دخول ومخرجات. تُستبعد من أي التزام. لم تُطبع القيم.
- **أدوات:** `tools/science-source-expansion/` ثلاث أدوات توليد. تبقى منفصلة عن التقارير التي تنتجها.

## E. التوصية
الخيار **A** هو الأقل مخاطرة الآن: تبقى الشجرة الأصلية كما هي، وأي عمل على التاريخ المنشور يتم في `G:\WSA-Enterprise-reconcile-dffeed5` عند `168edcc`. لا يعيد كتابة التاريخ ولا يلمس ADR المحلي.

الخيار B مناسب لاحقًا للكود بعد تحديد الملفات، لكنه لا يحل تصادم ADR. الخيار C مطلوب قبل نقل نص ADR-023: الإبقاء على 8.27–8.29 المنشورة، ثم إعادة ترقيم السلسلة المحلية 8.27–8.62 لتبدأ بعد 8.29، من دون حذف أي سجل. C يحتاج موافقة منفصلة ولا يُنفَّذ داخل الشجرة المتسخة.

التفويض التالي المقترح: خطة دمج نص ADR فقط داخل worktree نظيف مبني على `168edcc`، مع الإبقاء على الشجرة الأصلية نسخة احتياطية حتى تكتمل المراجعة البشرية للأقسام المعاد ترقيمها.

## F. التحقق النهائي
`HEAD` قبل الفحص وبعده `dffeed5a7ace309eadddffef02b278bfc5e0fffa`. الجرد بقي 146 سطرًا، منها 103 غير متعقبة والفهرس فارغ. ADR-023 لم يتغير. Worktree المصالحة بقي عند `168edcc` ونظيفًا. `git fetch` حدّث مرجع التتبع فقط.

القيد المتبقي: أي `merge` أو `pull` داخل الشجرة الأصلية سيحاول تحديث ADR-023 المتسخ ويخاطر بفقد الأقسام 8.30–8.62 أو الأقسام المنشورة 8.27–8.29.
