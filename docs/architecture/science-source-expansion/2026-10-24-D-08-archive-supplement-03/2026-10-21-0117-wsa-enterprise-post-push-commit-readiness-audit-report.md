<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17597). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
لا توجد مجموعة إضافية جاهزة للالتزام. المستودع الأصلي متأخر عن البعيد بخمسة التزامات، والتغييرات المتبقية مترابطة أو تحتاج إصلاحًا أو عزلًا قبل أي Commit.

## 1. حالة Git الفعلية
المستودع الأصلي `G:\WSA-Enterprise` ما زال على الفرع `phase-18-m18-ai-marketing-communications` عند `f0ac89700b7a822bd83b2cd821a18e493089d786`. بعد `git fetch` صار البعيد `52e904ea7e037747bf9147ce95baef80965e195e`، والحالة `behind 5`. لا توجد عملية دمج أو إعادة قاعدة أو انتقاء، ومنطقة التجهيز فارغة.

الالتزامات الخمسة الموجودة على البعيد وغير الموجودة في `HEAD` الأصلي هي: `52e904e`، ثم `8dd6827`، ثم `32eb117`، ثم `fba78bc`، ثم `067fe80`. الالتزام `f0ac897` موجود أبًا ثانيًا لالتزام الدمج، لذلك محتواه وصل إلى البعيد، لكن مؤشر الفرع الأصلي لم يتحرك.

| Worktree | الفرع | HEAD | الشجرة |
|---|---|---|---|
| `G:\WSA-Enterprise` | `phase-18-m18-ai-marketing-communications` | `f0ac897` | متسخة، `behind 5` |
| `G:\WSA-Enterprise-archive-reconcile` | `temp/archive-reconcile` | `52e904e` | نظيفة ومطابقة للبعيد |
| `G:\WSA-Enterprise-push-isolated` | `temp/frontend-crop-profile-push` | `8dd6827` | نظيفة، `behind 3` |

أي Commit يُنشأ فوق `f0ac897` داخل المجلد الأصلي لن يكون fast-forward إلى البعيد. المسار الآمن لاحقًا هو worktree جديد يبدأ من `52e904e`، لا نقل الفرع الأصلي ولا دمج شجرة العمل المتسخة.

## 2. الأعداد الفعلية
`git status --short` يعرض **145** سطرًا: **43** ملفًا متعقبًا معدّلًا و**102** مدخلًا غير متعقب. عند توسيع الملفات غير المتعقبة يظهر **2041** مسارًا. الفرق المتعقب هو `43 files, +5931/-231`. `git diff --check` خرج **2** بسبب مسافات زائدة في ADR-021 وADR-023 وسطر فارغ في نهاية ADR-023. لا عيوب whitespace في ملفات PHP المعدّلة.

## 3. المجموعات التي رُوجعت
البحث العلمي وLocalIndex والعقود وعائلات المصادر والسجل والأصل والموفّر والإعدادات؛ CapVer مع Onboarding وMembership وProjection وEvaluation؛ مخزن SQLite وملف الهجرة؛ اختبارات PostgreSQL؛ ADR-021 وADR-023 و`docs/adr/README.md` وتقارير التوسعة؛ `.gitignore` و`.env.example` و`composer.lock`؛ مجلدات `e2e-tmp`؛ وأدوات `tools/science-source-expansion/`.

Search وContract وFamily وRegistry وLocalIndex وOnboarding وCapVer حلقة اعتماد واحدة. `ScientificSourceCatalog` يستورد Onboarding، وOnboarding يستورد LocalIndex وFamily وContract، وCapVer يستورد فهارس Onboarding. لا يمكن فصل هذه المجلدات إلى التزامات مستقلة من دون كسر التحميل.

## 4. الجاهز فعلًا
**لا توجد مجموعة READY_FOR_COMMIT ولا READY_AFTER_STAGING_REVIEW.** مجموعة MissionArtifactArchive سبق دفعها، وليست ضمن التغييرات المتبقية.

## 5. ما يحتاج اختبارًا أو إصلاحًا

| المجموعة | الحالة | السبب |
|---|---|---|
| Scientific Search + LocalIndex + Contract + Family + Registry + Provenance + الأوامر + اختباراتها | NEEDS_TESTS | الموفّر يربط أصناف LocalIndex غير المتعقبة، و`config/database.php` و`agricultural_intelligence.php` جزء من السلوك. `ScientificRecordsSqliteDurabilityTest` يفتح اتصال `scientific_records`، ومساره الافتراضي هو SQLite التشغيلية. لم يُعاد تشغيل الاختبار لأن ذلك قد يكتب إلى قاعدة التشغيل. |
| CapVer + Onboarding + Membership + Projection + Evaluation | NEEDS_TESTS | CapVer يعتمد على Onboarding، وOnboarding يعتمد على حزمة البحث. Projection يضيف أربعة ملفات فوق أنواع متعقبة أصلًا. لا توجد نتيجة اختبار حديثة لهذه النسخة. |
| اختبارات `backend/tests/Pgsql/` مع `phpunit.pgsql.xml` | NEEDS_TESTS | الحارس يمنع `wsa_enterprise` ويشترط قاعدة تنتهي بـ `_test` وخادم loopback، والمجموعة تسقط كل الجداول في قاعدة الاختبار. لم تُشغَّل، ولا تُضم قبل إثبات خادم اختبار منفصل. |
| `.gitignore` | NEEDS_FIX | يضيف `/backups/` مرتين بالنص نفسه. |
| `backend/.env.example` | NEEDS_FIX | يجمع تعليقات المتجر العلمي مع مفاتيح الأمراض وFacebook. التعليق يقول إن SQLite ليست إثبات إنتاج، وهذا يتعارض مع قرار Option A. القيم الظاهرة تعليقات فارغة، ولا توجد أسرار فعلية في الفرق. |
| ADR-023 وADR-021 و`docs/adr/README.md` وتقارير `science-source-expansion` | NEEDS_FIX | ADR-023 المحلي يعيد استخدام الأرقام 8.27–8.29 لنص مختلف عن البعيد. الفرق مقابل البعيد `+3696/-172` في ADR-023 وحده، مع whitespace غير نظيف. التقارير التاريخية لا تُخلط مع كود التشغيل. |
| الهجرة `2026_10_15_220000_create_scientific_source_record_store_tables.php` | BLOCKED | تنشئ جداول المتجر وفيها فرع خاص بـ PostgreSQL. Option A يبقي SQLite المخزن التشغيلي، ولا توجد موافقة على نقل البيانات أو تطبيق الهجرة. تبقى خارج كل مجموعة. |
| `backend/composer.lock` | BLOCKED | غير متعقب، و`composer.json` غير معدّل، ولا سجل Git سابق للملف. لا يُضاف وحده. |

## 6. المستبعد
- `admin-mobile/e2e-tmp/`: 16 مسارًا ظاهرًا لـ Git، بينها ملفات تسجيل دخول ومخرجات. المجلد على القرص أكبر لأن بقية ملفاته متجاهلة.
- `backend/e2e-tmp/`: 576 مسارًا، بينها مخرجات JSON ونسخة محلية من `.env.example` وملف يحمل اسم token.
- `e2e-tmp/` في جذر المستودع.
- لا تُطبع القيم. التصنيف: EXCLUDED.

أدوات `tools/science-source-expansion/` ثلاث: مولّد تقارير، تقييم استرجاع غير متصل، ومولّد قطع مهمة. هي أدوات إنتاج تقارير لا تشغيل التطبيق، وتُبقى منفصلة عن تقارير `docs/architecture/science-source-expansion/`. لا اختبارات ظاهرة لها، لذا ليست جاهزة.

## 7. الاختبارات في هذه الجولة
لم يُشغَّل أي اختبار. اختبار MissionArtifactArchive السابق (9 اختبارات، 40 تأكيدًا، خروج 0) مرتبط بالمجموعة المدفوعة، ولم يُعد تشغيله. اختبارات LocalIndex وPostgreSQL والحصاد والأرشفة والهجرة لم تُشغَّل لأنها قد تكتب إلى SQLite أو PostgreSQL التشغيلية.

## 8. المخاطر غير المحسومة
الالتزام على `HEAD` الأصلي يعيد تباعد التاريخ. دمج شجرة العمل كما هي سيصطدم بـ ADR-023 لأن الأرقام 8.27–8.29 مكررة بمعنى مختلف. تشغيل اختبارات المتجر العلمي داخل حاوية التشغيل قد يفتح `storage/app/scientific_record_store.sqlite`. حزمة البحث لا تعمل إذا اُلتزم الموفّر من دون ملفات LocalIndex غير المتعقبة.

## 9. ترتيب الالتزام المقترح
لا يُقترح تنفيذ أي عنوان الآن. الترتيب بعد زوال الموانع:

1. إصلاح `.gitignore` ثم `chore(git): ignore local database backups once`.
2. حزمة البحث والمتجر معًا، من دون الهجرة و`composer.lock`، ثم `feat(research): add the local scientific index and source families`.
3. CapVer وOnboarding وMembership وEvaluation فوق تلك الحزمة، ثم `feat(research): verify source seats against the onboarding catalogs`.
4. توثيق ADR بعد إعادة ترقيم الأقسام المحلية فوق 8.29 البعيد، في التزام منفصل.
5. اختبارات PostgreSQL فقط بعد خادم `*_test` معزول.
6. استبعاد `e2e-tmp` والهجرة و`composer.lock`.

## 10. الجولة التالية
لا تبدأ بCommit. أول عمل هو عزل اختبارات LocalIndex عن SQLite التشغيلية وإثبات نجاحها، لأن هذه الحزمة هي قاعدة كل المجموعات البرمجية المتبقية.

## 11. ما لم يتغير
لم يُعدَّل أي ملف، ولم يُستخدم `git add` أو Commit أو Push أو merge أو rebase أو reset أو clean أو stash. `git fetch` حدّث معلومات التتبع فقط. لم تُغيّر Worktrees ولا قواعد البيانات.
