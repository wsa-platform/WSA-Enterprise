<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17877). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
الحكم النهائي: **`COMPLETED AND VERIFIED`**. التقرير محفوظ في `docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md`. لم يُنفَّذ commit ولا push.

## 1. الملخص التنفيذي

المصادر الخمسة مسجّلة ومفعّلة ضمن نطاقها المعتمد: `gbif`/`G5-04`، `organic_eprints`/`G1-02`، `bhl`/`G6-20`، `agris`/`G1-01`، `dr_duke`/`G1-17`. أُصلح عيب عزل واحد، ثم نجح الطقم المركز: **39 اختبارًا، 348 تأكيدًا، خروج 0**.

كُتبت خلايا القدرة الأربع في `cghia_capability_cell_records` بحالة `PERSISTED`، ثم أعاد التشغيل نفسه `ALREADY_CURRENT`. القراءة المستقلة أظهرت أربع صفوف `VERIFIED`/`CURRENT` فقط. `G5-04` بلا صف، وهذا مطابق لـ ADR-023 §8.58.5.

ملف التشغيل بقي على الحجم `1354821632` والوقت `2026-10-19T17:14:04.7889907Z` والبصمة `3F0114A665682000637C318E07649F62B1A3B4482635A25A19726317EDD19E8C`، بلا `-wal` ولا `-shm`.

## 2. خط الأساس ومصالحة Git

الجذر `G:/WSA-Enterprise`، الفرع `phase-18-m18-ai-marketing-communications`، وHEAD عند البدء والنهاية `fb60b60746ed487e2bfa04e3d335827d91cda446`، متزامن مع الأصل (`0 0`). الفهرس فارغ. لا merge ولا rebase ولا stash.

`f0ac897` سلف لـ HEAD (خروج `merge-base --is-ancestor` = 0). عزل `dffeed5` باقٍ: فرق ملفات العزل من ذلك الالتزام حتى HEAD كان فارغًا قبل إصلاح هذه المهمة. لا `BASELINE DIVERGENCE`.

عند البدء: 145 سطر porcelain (42 متتبعًا و103 غير متتبعة). في النهاية: 44 متتبعًا بسبب ملفي العزل، ثم هذا التقرير غير المتتبع. الـ worktrees الأخرى، ومنها مسارات المصالحة والأرشيف و`G:/tmp` و`.kilo`، قُرئت ولم تُمس.

## 3. حماية قاعدة البيانات وعزل الاختبارات

المسار من `config/database.php` هو `storage/app/scientific_record_store.sqlite`. لم يُفتح الملف. العد والبحث تمّا على نسخة تحت `storage/framework/testing/` ثم حُذفت النسخة. البصمة النهائية تطابق بصمة البداية.

`bootstrap.php` يفرض مسار `scientific_records_<pid>.sqlite` و`SCIENTIFIC_RECORD_STORE_URL` فارغًا قبل الإقلاع. `DatabaseSafety` يرفض مسار التشغيل و`..` والخروج من جذر الاختبار. `TestCase` يتحقق قبل `parent::setUp()` وبعده. الإصلاح: إنشاء ملف الاختبار الفارغ لأن Laravel يرمي `SQLiteDatabaseDoesNotExistException` إن لم يوجد الملف. الاختبار طبع مسارًا تحت `framework/testing` لا تحت `storage/app`.

## 4. ما تم تنفيذه والملفات المعدلة

- `backend/tests/bootstrap.php`: إنشاء ملف الاختبار المعزول. كان نظيفًا عند HEAD.
- `ScientificRecordStoreIsolationGuardTest.php`: تأكيد وجود الملف.
- `backend/.env.example`: تعليقا `AGRIS_ENABLED` و`DR_DUKE_ENABLED`. الملف كان معدّلًا قبل المهمة، وفرقه الكامل عن HEAD أكبر من هذين السطرين.
- `NextGateEligibleSeatCatalog.php`: تصحيح ملاحظات AGRIS وDr. Duke. الملف غير متتبع أصلًا.
- هذا التقرير.

لم تُغيَّر `database.php` ولا `.env` ولا ملفات Composer. فرق `database.php` (+29) عمل سابق.

## 5. حالة المصادر العلمية الخمسة

الهويات مطابقة لسجل `110.v1`. `G6-06` ما زال `DEFERRED`. `isRuntimeActive()` يشترط تفعيلًا وعقدًا نشطًا وبلا حواجز، وهذا متحقق للخمسة. اختبار العقد يثبت الترتيب بعد المصادر القديمة: `agris`، `dr_duke`، `gbif`، `organic_eprints`، `bhl`.

## 6. سلامة الاستعلام العلمي وD-04

D-04 يجعل `ScientificSearchQueryBuilder` سلطة نص البحث، و`ScientificQueryCompiler` بيانات وصفية. الأقسام اللاحقة تغلق استهلاك الحزمة المترجمة بأنه ليس عيبًا. النص يصل إلى المحوّل كما أنتجه البنّاء. لم يُغيَّر هذا المسار.

## 7. CapVer

| المقعد | النتيجة | الخلية |
|---|---|---|
| `G1-02` | `PERSISTED` ثم `ALREADY_CURRENT` | `cap-rec-r11-20261018-G1-02-oai_pmh` |
| `G6-20` | `PERSISTED` ثم `ALREADY_CURRENT` | `cap-rec-r11-20261018-G6-20-api` |
| `G1-01` | `PERSISTED` ثم `ALREADY_CURRENT` | `cap-rec-r12-20261018-G1-01-bulk` |
| `G1-17` | `PERSISTED` ثم `ALREADY_CURRENT` | `cap-rec-r12-20261018-G1-17-static` |

القراءة المستقلة: أربع صفوف `VERIFIED` / `CURRENT` على PostgreSQL. `G5-04` مستبعد حسب §8.58.5 ولم يُضف. محاولة PHP على المضيف فشلت بمصادقة `wsa` عبر `127.0.0.1` ولم تكتب. الكتابة الناجحة تمت داخل `wsa-enterprise-backend-1` حيث المضيف `postgres`. لم تُطبع كلمة المرور ولم تُدوَّر.

## 8. البيانات والنطاقات والسقوف

من النسخة المعزولة:

| المصدر | الجيل | النشط | غير النشط |
|---|---:|---:|---|
| Organic Eprints | 2 | 189 | 11 محذوفًا |
| AGRIS | 29 | 87984 | 3 مسحوبة |
| Dr. Duke | 19 | 211948 | 500 مسحوبة |

بادئات Dr. Duke النشطة: `fnftax:` 2376، `chem:` 29572، `ethno:` 60000، `farmacy:` 60000، `farmacy_new:` 60000. الأسقف ظاهرة في البيانات ولم تُرفع. ETHNOBOT يبقى «ليس ادعاء فعالية». حصاد AGRIS يتخطى الرخصة غير المطابقة لـ CC-BY-4.0. مؤشر 1244 مزوّدًا لم يُعد قياسه، والجسم الكامل لم يُوسَّع.

## 9. الاختبارات وبيئة التشغيل

المشغّل: PHP 8.4.26 المحلي مع PHPUnit 11.5.56. الذاكرة المستخدمة 74MB من حد 128MB. لم يُثبَّت Composer. Docker 29.6.2 استُخدم لـ CapVer بعد تشغيل postgres وredis وbackend. حاويات R7 والنسخة الاحتياطية واليتيمة لم تُشغَّل.

الطقم الأول: 39 اختبارًا وخطآن لأن ملف الاختبار لم يكن موجودًا. بعد الإصلاح: **39 اختبارًا، 348 تأكيدًا، خروج 0**. المسار في الخطأ كان تحت مجلد الاختبار.

## 10. التحقق التشغيلي وطلبات الشبكة

- GBIF في `2026-10-22T06:33:36Z`: HTTP 200، 949 بايت، `q=Triticum`، `limit=1`، `count=15425`، `key=315051481`، `scientificName=Triticum`.
- BHL: HTTP 200، 315 بايت، `Status=ok`، نتيجة واحدة، `PartID` ورابط حاضران. المفتاح مضبوط ولم يُطبع.
- على النسخة فقط: Organic Eprints/`agriculture` → `oai:orgprints.org:5`؛ AGRIS/`wheat` → `64747057425ec3c088f1af42`؛ Dr. Duke/`Mentha` → `fnftax:614`.

## 11. اختبارات الانحدار للمصادر الخمسة القديمة

لم تُعدَّل أصناف المسار المشترك، فبوابة الانحدار غير مطلوبة. اختبار العقد الذي نجح يشمل المفاتيح القديمة والجديدة معًا. لم يُدَّعَ تشغيل طقم أوسع.

## 12. الإعدادات وفحص الأسرار

الأعلام الخمسة افتراضها `true` وهي غير مضبوطة في `.env`، فيبقى التفعيل. `BHL_API_KEY` موجود وغير فارغ. فحص `apikey=` و`Bearer ` و`sk-` في ملفات المهمة لم يجد قيمة سر.

## 13. المشكلات التي تم إصلاحها

1. Laravel لا ينشئ ملف SQLite الغائب، ففشل اختبارا Organic Eprints وAGRIS رغم أن المسار كان معزولًا. أُنشئ الملف في bootstrap وأُضيف تأكيد وجوده.
2. ملاحظات AGRIS وDr. Duke كانت عند 1317 و1500. استُبدلت بالأرقام المقيسة مع الإبقاء على النطاق المحدود.
3. وُثّق `AGRIS_ENABLED` و`DR_DUKE_ENABLED` في المثال.

## 14. الفجوات المتبقية

كلمة مرور المضيف لا تطابق دور PostgreSQL عبر TCP؛ الكتابة تمت من الحاوية. موضع حصاد AGRIS من 1244 لم يُعد قياسه. رفع الأسقف و`G6-06` وترحيل PostgreSQL وCapVer لـ GBIF تبقى خارج التفويض. بحث ملف التشغيل نفسه عبر المحوّل لم يُشغَّل حتى لا يُعاد بناء FTS.

## 15. مصفوفة القبول النهائية

| البعد | GBIF | Organic Eprints | BHL | AGRIS | Dr. Duke |
|---|---|---|---|---|---|
| الهوية والتسجيل والعقد | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` |
| حفظ القدرة | استبعاد §8.58.5 متحقق | `PERSISTED` ثم `ALREADY_CURRENT` | كذلك | كذلك | كذلك |
| المحوّل والمنسّق وD-04 | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` |
| الاسترجاع | HTTP 200 حي | FTS على النسخة | HTTP 200 حي | FTS على النسخة | FTS على النسخة |
| النطاق | واجهة مباشرة | `PASS — VERIFIED WITH BOUNDED SCOPE` (189) | واجهة مباشرة | `PASS — VERIFIED WITH BOUNDED SCOPE` (87984) | `PASS — VERIFIED WITH BOUNDED SCOPE` (أسقف 60000) |
| العدد/الجيل | `N/A` | جيل 2 / 189 | `N/A` | جيل 29 / 87984 | جيل 19 / 211948 |
| الاختبارات وثبات القاعدة | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` | `PASS — VERIFIED` |

## 16. حالة Git والتسليم

HEAD ما زال `fb60b60`. لا شيء في الفهرس. `git diff --check` خروج 0، مع تحذير CRLF سابق على `bootstrap.php` واختبار العزل ولم يُمس أسلوب النهايات. لا commit ولا push ولا ترحيل علمي.

## 17. الحكم النهائي

**`COMPLETED AND VERIFIED`**

التسجيل، والعقود، وCapVer للمقاعد الأربعة، واسترجاع GBIF وBHL، واسترجاع الفهرس على نسخة معزولة، والاختبارات بعد إصلاح العزل، وثبات ملف SQLite التشغيلي كلها مثبتة على هذه الشجرة. الجسم الكامل لـ AGRIS، ورفع أسقف Dr. Duke، و`G6-06`، وCapVer لـ GBIF تبقى خارج التفويض وسلوكها الحالي يطابق القرارات المعتمدة.
