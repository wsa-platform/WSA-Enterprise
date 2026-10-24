<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17482). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
الفحص اكتمل دون أي Commit أو Push أو تعديل على الملفات. منطقة التجهيز بقيت فارغة، ولم يُدرج أي ملف.

## 1. الفرع وHEAD

- الفرع: `phase-18-m18-ai-marketing-communications`
- يتتبع `origin/phase-18-m18-ai-marketing-communications` ومتزامن معه: `0` خلف و`0` أمام
- HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`
- رسالة HEAD: `test(ic): verify PostgreSQL persistence isolation in CI`
- التاريخ: 2026-10-13 01:02:34 +0300

هذه التغييرات المحلية كانت موجودة قبل مهمة التصنيف. قسم ADR `8.62` أُلحق في الجلسة السابقة داخل الملف المعدّل أصلًا، ولا يمكن فصله عن بقية diff دون تعديل الملف.

## 2. الأعداد الفعلية

| الحالة | العدد |
|---|---:|
| أسطر `git status --short` | 151 |
| متعقبة معدّلة ` M` | 45 |
| غير متعقبة `??` | 106 مسارًا |
| ملفات غير متعقبة بعد فك المجلدات | 1438 |
| محذوفة أو معاد تسميتها | 0 |
| في staging | 0 |

`git diff --stat` للملفات المتعقبة: 45 ملفًا، `+5946 / -232`. `git diff --cached` فارغ. `git diff --check` خرج برمز 2 بسبب مسافات بيضاء زائدة، لا بسبب تعارض دمج.

## 3. تصنيف المسارات

مراجعة الأسطر الخمسة والأربعين المتعقبة تمت عبر `name-status` و`diff --stat`، مع قراءة diff كاملة لملفات الإعداد والواجهة الأمامية وبداية ADR-021. ملف ADR-023 حجمه 3603 سطرًا مضافًا، لذلك صُنّف من الإحصاء والمحتوى المعروف للأقسام الأخيرة دون ادعاء قراءة كل سطر. المجلدات غير المتعقبة فُحصت بالنوع والحجم والأسماء الحساسة، لا بسطر لكل ملف من 1438.

| المسار | Git | التصنيف | القرار |
|---|---|---|---|
| `frontend/src/public/scientificUserPresentation.ts` | ` M` | A | مرشح وحيد. يوسّع نوع المدخل ليقبل ملف المحصول بلا رسالة عرض |
| `frontend/src/public/fieldCropCanonicalAnswer.test.ts` | ` M` | A | يرافق الملف السابق. Vitest: 22/22، خروج 0 |
| `.gitignore` | ` M` | C | يضيف `/backups/` مرتين بالنص نفسه. القصد صحيح والشكل غير نظيف. لا يُلتزم قبل إزالة التكرار، ولم أعدّل الملف |
| `backend/.env.example` | ` M` | C | يخلط مفاتيح المخزن العلمي، ومفاتيح أمراض، وFacebook OAuth. لا أسرار فعلية. تعليق SQLite يقول إنه ليس إثبات إنتاج، وهذا يتعارض مع خيار A في ADR 8.62. لا يُفصل بأمان |
| `backend/config/database.php` | ` M` | B | يضيف اتصال `scientific_records` وافتراضه SQLite. مرتبط بالكود غير المتعقب وقرار الترحيل المعلّق |
| `backend/config/agricultural_intelligence.php` | ` M` | B | يفعّل GBIF وOrganic Eprints وBHL وAGRIS وDr. Duke افتراضيًا، ويضيف اتصال LocalIndex. تغيير سلوك تشغيلي غير مفصول |
| `backend/app/Contracts/ScientificSourceAdapterInterface.php` | ` M` | C | إضافة صغيرة تعتمد على العقد غير المتعقب `ScientificSourceAdapterV2Interface.php` |
| `backend/app/Providers/AgriculturalIntelligenceServiceProvider.php` | ` M` | C | يربط مخزن السجلات ومحرك FTS. يعتمد على شجرة `LocalIndex/` غير المتعقبة |
| 18 ملف خدمة بحث/عرض/تحقق تحت `backend/app/Services/Agriculture/` | ` M` | C | توسعة مترابطة للمنسق والمصادر والعرض. لا تُبنى وحدها دون الملفات الجديدة |
| 15 اختبارًا متعقبًا تحت `backend/tests/` | ` M` | B | ترافق توسعة البحث. لم تُعد تشغيلها في هذه المهمة |
| `docs/adr/README.md` | ` M` | E | يربط ADR-022/023 وسجلات JSON غير متعقبة. الالتزام بدونه يكسر الروابط |
| `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` | ` M` | E | إغلاق مرحلة 3 بتاريخ 2026-09-21 مع ادعاءات اختبار حيّة. مسافة بيضاء زائدة في السطر 357. الاختبارات المدعاة لم تُعد هنا |
| `docs/architecture/ADR-023-scientific-source-expansion.md` | ` M` | E | 3603 سطرًا تشمل 8.60–8.62 ومسافات بيضاء وسطرًا فارغًا في نهاية الملف. لا يُفصل 8.62 دون تعديل |
| `backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php` | `??` | F | مستبعد بأمر صريح. غير مطبق على PostgreSQL |
| 10 أوامر `backend/app/Console/Commands/*` | `??` | C | حصاد وفهرسة وأرشيف وتشخيص. تعتمد على الخدمات الجديدة |
| `ScientificSourceAdapterV2Interface.php` والعقد `Search/Contract/` و`Family/` و`Registry/` و`Provenance/` و`LocalIndex/` | `??` | C | نواة المخزن العلمي. مترابطة مع الملفات المعدّلة |
| `Capability/CapVer/` و`Onboarding/` و`Membership/` و`Evaluation/` و`Projection/` | `??` | C | قدرات وعضوية وتقييم. ليست مجموعة واحدة مكتملة |
| `backend/app/Services/Architecture/MissionArtifactArchive/` و`backend/config/mission_artifact_archive.php` | `??` | C | أرشيف مهمة منفصل عن CapVer. اسم `MissionArtifactSecretFilter.php` مرشح أسرار وليس سرًا بحد ذاته |
| `backend/composer.lock` | `??` | G | 315 236 بايت وغير متعقب أصلًا. `composer.json` غير معدّل. لا يُلتزم وحده |
| اختبارات `backend/tests/Unit/...` و`Feature` الجديدة و`Fixtures` و`Support/LocalIndexFixtures.php` | `??` | B | ترافق الكود. `LocalIndexSearchIntegrationTest.php` حاول في الجلسة السابقة الكتابة إلى SQLite التشغيلية وفشل بالقفل. لا يُعاد تشغيله داخل الحاوية |
| `backend/tests/Pgsql/` | `??` | B | مخصصة لـ `phpunit.pgsql.xml`. لم تُشغّل هنا حتى لا تمس `wsa_enterprise` |
| `backend/e2e-tmp/` | `??` | D | 576 ملفًا: مخرجات تشغيل JSON ونصوص PHPUnit. بعضها أكبر من 1 ميغابايت |
| `e2e-tmp/capver_v22.json` | `??` | D | ناتج تشغيل |
| `admin-mobile/e2e-tmp/` | `??` | D | 16 ملفًا. `login-body.json` و`login-out.json` يحتويان حقول كلمات سر أو رموز. لم تُطبع القيم |
| `docs/architecture/science-source-expansion/` | `??` | E | 652 ملفًا: تقارير وأرشيف وJSON كبير، منها R6 بحجم 1.27 ميغابايت وR7 بحجم 1.35 ميغابايت |
| `docs/architecture/BASELINE-RECONCILIATION.md` و`REMEDIATION-DEPENDENCY-MAP.md` | `??` | E | وثائق تسوية غير مربوطة بمجموعة كود مكتملة |
| `tools/science-source-expansion/` | `??` | C | 3 مولّدات تقارير، ليست تطبيق التشغيل |

`RestJsonCredential.php` و`BhlApiEnvelopeAndCredentialTest.php` اسمهما حساس، ومحتواهما عقد/اختبار لا ملف سر. استبعاد المجلد كله مع مجموعة الكود غير المكتمل.

## 4. مجموعات Commit المقترحة

مجموعة واحدة فقط صالحة الآن.

الرسالة المقترحة:

`fix(frontend): accept crop profiles that have no presentation message`

الملفات:

- `frontend/src/public/scientificUserPresentation.ts`
- `frontend/src/public/fieldCropCanonicalAnswer.test.ts`

المجموعات التالية غير جاهزة ولا تُخلط مع هذه المجموعة:

- المخزن العلمي والبحث وCapVer: كود معدّل مع ملفات جديدة، وقرار الترحيل ما زال مستقلًا.
- ADR-021 وADR-023 وREADME: تحتاج مراجعة دقة الادعاءات، وADR-023 لا يُجزأ بأمان.
- `.gitignore`: يحتاج حذف السطر المكرر قبل أي التزام، وهذا تعديل لم أقم به.
- `.env.example`: تغييرات غير مترابطة داخل الملف نفسه.

## 5. ما جُهّز في staging

لا شيء. `git diff --cached` فارغ قبل الفحص وبعده. لم أستخدم `git add`.

## 6. المستبعد مؤقتًا

كل المسارات خارج ملفي الواجهة الأمامية. أسباب الاستبعاد: عمل غير مكتمل، أو اعتماد على ملفات غير مضمّنة، أو بيانات تشغيل، أو أسرار في ملفات تسجيل الدخول، أو ترحيل ممنوع، أو وثائق تدّعي نتائج لم تُعد فحصها هنا، أو ملف قفل اعتماديات بلا تغيير مقابل في `composer.json`.

## 7. الاختبارات التي نُفذت في هذه المهمة

| الأمر | النتيجة |
|---|---|
| `npx vitest run src/public/fieldCropCanonicalAnswer.test.ts` داخل `frontend` | خروج 0، ملف واحد، 22 اختبارًا ناجحًا |
| اختبارات PHPUnit للبحث وLocalIndex وPgsql | `NOT TESTED` في هذه المهمة. لم تُشغّل لأن بعضها يلمس SQLite أو PostgreSQL التشغيليين |

تحذير `localStorage` من Node ظهر أثناء Vitest ولم يفشل الاختبار.

## 8. ما يمنع الالتزام الآمن لبقية العمل

- شجرة البحث العلمي لا تُبنى إذا التزمنا بالملفات المعدّلة دون الجديدة، ولا تُعد مكتملة إذا التزمنا بهما معًا قبل عزل اختبار المنسق عن قاعدة التشغيل.
- ترحيل `2026_10_15_220000` مستبعد بأمر مستقل، بينما الكود يعتمد على مخططه داخل SQLite.
- `git diff --check` يسجل مسافات بيضاء في ADR-021 السطر 357، وفي ADR-023 الأسطر 7948 و7992–7997، وسطرًا فارغًا جديدًا في نهاية ADR-023.
- ملفات `admin-mobile/e2e-tmp/login-*.json` تحمل مادة سرية.
- لا يمكن فصل قسم 8.62 عن بقية ADR-023 دون تعديل الملف، وهذا ممنوع في مرحلة التصنيف.

## 9. الحالة النهائية

`git status --short` ما زال 151 سطرًا: 45 معدّلًا و106 غير متعقب. لا حذف ولا إعادة تسمية ولا staging. لم يُفقد أي WIP.

## 10. التأكيد

لم يُنفَّذ `git commit` ولا `git push` ولا merge ولا rebase ولا reset ولا clean ولا restore ولا stash. لم يُحذف أو يُستبدل أي تعديل.

## 11. الأوامر المقترحة، دون تنفيذ

بعد موافقتك على مجموعة الواجهة فقط:

```powershell
git add -- frontend/src/public/scientificUserPresentation.ts frontend/src/public/fieldCropCanonicalAnswer.test.ts
git diff --cached --name-only
git commit -m "fix(frontend): accept crop profiles that have no presentation message"
```

لا أوامر Commit للمجموعات الأخرى إلى أن يُعتمد نطاق كل مجموعة على حدة.
