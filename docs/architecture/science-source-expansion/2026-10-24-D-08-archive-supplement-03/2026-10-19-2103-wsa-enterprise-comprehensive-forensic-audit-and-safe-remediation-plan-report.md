<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17367). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير التدقيق الجنائي الشامل وخطة الإصلاح الآمنة  
**WSA-Enterprise — 2026-10-19 (تشخيص فقط — بلا تغيير دائم)**

---

## A. الملخص التنفيذي

التطبيق الحي على `http://127.0.0.1:8079/` يعمل (health **200**). Laravel داخل Docker يتصل فعليًا بـ **`wsa_enterprise` / `wsa` عبر المضيف الداخلي `postgres`** على الحاوية **`wsa-enterprise-postgres-1`** وVolume **`wsa-enterprise_postgres_data`** (PostgreSQL **16.14**).

الفجوة المركزية المثبتة: **مخطط PostgreSQL متوقف عند 2026-09-21** (55 migration)، بينما المستودع يحمل **6 ملفات أكتوبر** غير مطبّقة على PG، منها Cap Store.  
LocalIndex العلمي يعمل منفصلًا على **SQLite** (~1.35 GB، ~300k سجل) وقد طُبّقت عليه migration العلمية غير المتتبعة.  
لا خطر P0 مثبت على سلامة بيانات التشغيل العام؛ **P1** لمسار CapVer/Cap Store على PostgreSQL.

---

## B. سلامة البيئة (`.env` / الاتصال)

| الفحص | النتيجة | الثقة |
|---|---|---|
| dotenv | 77 سطرًا؛ لا أسطر بلا `=` | CONFIRMED |
| تكرار مفاتيح حرجة (`DB_PASSWORD`…) | لا تكرار لـ DB_* | CONFIRMED |
| تكرار `FAOSTAT_ENABLED` | سطران 73–74 (غير DB) | CONFIRMED |
| `DATABASE_URL` / `DB_URL` نشط | غير موجود كمفتاح فعّال في `.env` | CONFIRMED |
| config cache | `bootstrap/cache/config.php` غير موجود | CONFIRMED |
| `database.php` pgsql | `url` ← `DATABASE_URL` ثم `DB_URL`؛ المضيف من `DB_*` | CONFIRMED |
| Backend فعلي | `DB_HOST=postgres`, URL config **empty**, DB=`wsa_enterprise` | CONFIRMED |
| Host `.env` | `DB_HOST=127.0.0.1` (للمضيف فقط؛ الحاوية تتجاوز بـ `postgres`) | CONFIRMED |
| `DB_PASSWORD` vs `POSTGRES_PASSWORD` (Backend↔Postgres containers) | **MATCH** | CONFIRMED |
| Host `.env` vs container password | نفس مصدر `env_file` عمليًا؛ لم تُطبع قيم | INFERRED / NOT_COMPARABLE كقياس مستقل إضافي |

قيود: لم يُشغَّل `artisan env` كاعتماد وحيد؛ الهوية أُثبتت من داخل الحاوية السليمة.

---

## C. هوية قاعدة البيانات

| العنصر | الدليل |
|---|---|
| حاوية التطبيق | `wsa-enterprise-postgres-1` Running/healthy، `5432:5432` |
| Volume | `wsa-enterprise_postgres_data` (Compose `postgres_data`، منذ 2026-08-09) |
| مرتبط أيضًا بـ | orphan متوقف فقط — خطر إن شُغّل معه |
| هوية الجلسة | `current_database=wsa_enterprise`, `current_user=wsa`, PG **16.14** |
| Laravel داخل backend | نفس القاعدة/المستخدم؛ `CFG_HOST=postgres` |
| جداول public | **148** |
| `migrations` | **55**؛ آخرها `2026_09_21_140000_…` |
| أكتوبر في PG | **0** صفوف |
| Cap Store في PG | **غير موجود** |
| خدمات Windows PG | **none** (لا خدمة `postgresql*`) |

حاويات أخرى متوقفة: R7 / backup-verification / orphan — ليست مسار التطبيق الحي.

---

## D. مصفوفة Migrations أكتوبر

| Migration | Git | في PG `migrations` | ينشئ/يعدّل | اعتماديات | ملاحظات مخاطر |
|---|---|---|---|---|---|
| `2026_10_01_150000_…durable_correlation…` | tracked | لا | `durable_correlation_records` | مستقلة (لا FK) | CREATE فقط؛ idempotent عبر Laravel batch |
| `2026_10_02_160000_…identity_binding…` | tracked | لا | `cghia_identity_binding_records` | مستقلة | كذلك |
| `2026_10_02_230000_…integration_classification…` | tracked | لا | `cghia_integration_classification_records` | مستقلة | كذلك |
| `2026_10_09_140000_…capability_cell…` | tracked (`31a6ee3`) | لا | `cghia_capability_cell_records` + partial UQs | مستقلة عن جداول cghia الأخرى | **مطلوبة لـ CapVer persist** |
| `2026_10_09_190000_…single_active_index…` | tracked | لا | فهرس جزئي على IC | **يتطلب** جدول IC موجودًا؛ يفشل إن وُجدت ACTIVE مكررة | preflight قراءة ثم CREATE INDEX |
| `2026_10_15_220000_…scientific_source…` | **`??` غير متتبعة** | لا في PG | `scientific_source_*` (+ CHECK على pgsql) | مستقلة هيكليًا | **مطبّقة فعلًا على SQLite LocalIndex** |

**ترتيب مقترح عند الموافقة لاحقًا (PG فقط، لا دفعة عمياء):**  
1→2→3→4 (مستقلة نسبيًا) ثم 5 (بعد IC) ؛ **6 لا تُطبَّق على PG الحي** قبل قرار مالك (المخزن العلمي حي على SQLite).

القرص: **63** ملفًا؛ DB: **55** → فجوة **8** تشمل أكتوبر الستة + فرقًا إضافيًا خارج نطاق أكتوبر (لم يُفصَّل كلها هنا).

---

## E. تحليل CapVer

| البند | الحكم |
|---|---|
| سبب الغياب | Migration موجودة وغير مسجّلة/غير منفّذة على Volume الحالي — **CONFIRMED** |
| إزالة لاحقة | لا دليل في `migrations` ولا بقايا جداول cghia — غير مدعوم |
| أثر على الموقع العام | غير مثبت كعطل HTTP — التطبيق يعمل |
| أثر على CapVer | بدون الجدول: مسار المتجر → `STORE_UNAVAILABLE`؛ **لا activation-pass** — CONFIRMED بالكود؛ وحدة Mock **OK 4/38** |
| اختبار حي CapVer على PG | **NOT TESTED** (محظور كتابة/migrate) |
| تطابق نموذج/migration | أعمدة النموذج تطابق `Schema::create` — CONFIRMED بالمراجعة |

---

## F. Scientific Persistence

| Store/File | الجداول الفعلية | المستهلك | دور | مصدر الحقيقة | العقد |
|---|---|---|---|---|---|
| PG `wsa_enterprise` | 148 جدول تطبيق؛ **لا** scientific_source_* / cghia_* | Laravel default `pgsql` | أساسي للمنصة/Cap Store عند تطبيقه | Volume Docker | ADR تشغيل المنصة |
| SQLite `scientific_record_store.sqlite` | `scientific_source_records/states/generations` + FTS + `migrations` | اتصال `scientific_records` (افتراضي sqlite) | LocalIndex / حصاد | الملف ~1.35GB؛ **300635** سجل؛ states=3 | ADR §8.41؛ migration مُسجَّلة داخل SQLite |
| SQLite `mission_artifact_archive/archive.sqlite` | `mission_artifacts`, relationships | أرشيف المهمة | أرشفة | منفصل | `mission_artifact_archive.php` |
| SQLite testing durability | نفس عائلة scientific_* | اختبارات | معزول | صغير | اختبار |

**لا يعوّض** وجود جداول SQLite غياب Cap Store على PostgreSQL.

---

## G. ملفات تحتاج قرار المالك

1. **`backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php` (`??`)**  
   - مطبّقة على SQLite LocalIndex.  
   - غير موجودة في تاريخ git المتتبَّع (`git log --all` فارغ عمليًا للمسار).  
   - قرار: تتبع/إبقاء WIP، وهل يُنشأ مخزن PG موازٍ أم يبقى SQLite.

2. **حزمة WIP الواسعة (~150 short status)** — خارج نطاق إصلاح المخطط؛ تُحفظ كما هي.

3. **تكرار `FAOSTAT_ENABLED` في `.env`** — تنظيف اختياري لاحقًا.

---

## H. النسخ الاحتياطية وتوافق الإصدارات

| المصدر | الحالة |
|---|---|
| `G:\New folder (3)\wsa-render-backup-extracted\2026-09-11T22_27Z\wsa_enterprise_db` | موجود؛ 148 ملفًا؛ ~10.3 MB |
| `pg_restore --list` هذه الجلسة | **تعذّر** — لا `pg_restore` على القرص بعد إزالة Windows PG17 |
| بيانات TOC سابقة لنفس المسار | مصدر PG **18.4**؛ فيه `migrations`؛ **لا** `cghia_capability_*` (سبتمبر قبل migration أكتوبر) — PRIOR |
| استعادة إلى Docker 16.14 | **غير موصى بها مباشرة** دون بيئة معزولة وتحليل توافق 18→16 |
| نسخة أحدث من Volume الحي | **غير موجودة** ضمن هذه المراجعة؛ **لم تُنشأ** نسخة جديدة (محظور) |

**جاهزية نسخة معتمدة قبل أي migrate على الحي: غير مستوفاة بالكامل** (لا بصمة/استعادة معزولة مثبتة الآن).

---

## I. الاختبارات

| الاختبار | العزل | النتيجة |
|---|---|---|
| `CapVerSeatVerificationServiceTest` + `phpunit.xml` (`sqlite :memory:`, `FORBIDDEN_TEST_DATABASES=wsa_enterprise`) | مثبت | **OK 4 tests / 38 assertions** |
| نفس الملف بدون phpunit.xml | غير معزول كما يجب | FFFF (تُجاهل) |
| `CapabilityStorePersistenceTest` (RefreshDatabase) | يستهدف sqlite ذاكرة عبر phpunit | **NOT RUN** (قد يلمس مخططًا اختباريًا؛ يكفي إثبات الوحدة هنا) |
| اختبارات Pgsql live | خطر على الحي | **لم تُشغَّل** |

---

## J. خطة الإصلاح (مرتبة — للتنفيذ بعد موافقات)

0. **بوابة نسخة:** أخذ/التحقق من نسخة معتمدة من Volume `wsa-enterprise_postgres_data` في بيئة معزولة (موافقة منفصلة).  
1. الإبقاء على الخدمات السبع؛ عدم تشغيل orphan/R7/backup.  
2. (اختياري) تنظيف تكرار FAOSTAT في `.env` — موافقة منفصلة.  
3. إثبات ما بعد كل خطوة: `SELECT` لوجود الجدول + صف `migrations`.  
4. تطبيق PG بالترتيب الآمن المقترح:  
   - durable_correlation  
   - identity_binding  
   - integration_classification  
   - **capability_cell (CapVer)**  
   - IC single-active index (بعد نجاح IC)  
5. **عدم** تطبيق scientific migration على PG الحي قبل قرار المالك (SQLite قائم).  
6. بعد Cap Store: `diagnose-persistence` + CapVer مقروء/كتابة بموافقة.  
7. قرار Git بشأن الملف `??` العلمي.  
8. لا `migrate:fresh` / لا restore فوق الحي / لا إنشاء جدول يدوي.

**التراجع:** استعادة Volume من النسخة المعتمدة فقط؛ كل migration CREATE قابلة للعكس نظريًا بـ `down()` لكن العكس على إنتاج يحتاج موافقة ولا يُنفَّذ تلقائيًا.

---

## K. معايير القبول (لكل فجوة)

| الفجوة | إغلاق مقبول |
|---|---|
| Cap Store | الجدول موجود؛ migration مسجّلة؛ CapVer يصل `PERSISTED`/`ALREADY_CURRENT` على مقعد تجريبي مصرّح |
| سلسلة CGHIA أكتوبر | الجداول الثلاثة + فهرس IC موجودة ومسجّلة |
| Scientific | قرار مالك موثّق: SQLite يبقى SoT **أو** مخزن PG مُدار دون ازدواج صامت |
| Backup | نسخة حديثة من Volume الصحيح + `--list` ناجح + تجربة restore معزولة موثّقة |
| `.env` | لا orphan/لا تكرار DB_*؛ اتصال backend ثابت |

---

## L. بوابة الموافقة (كل بند منفصل)

| التغيير | المخاطر | قبل التنفيذ |
|---|---|---|
| أي `artisan migrate` على الحي | تغيير مخطط دائم | نسخة معتمدة + نافذة صيانة |
| migrate الملف العلمي على PG | ازدواج LocalIndex / بيانات متضاربة | قرار معماري SoT |
| إضافة الملف `??` إلى Git | تثبيت WIP | مراجعة مالك |
| restore من Render 18.4→16.14 | فشل/فساد محتمل | بيئة معزولة فقط |
| إيقاف/تشغيل orphan | فساد Volume | ممنوع مع الحي |
| تعديل `.env`/كلمات مرور | انقطاع خدمة | مقارنة MATCH الحالية كافية غالبًا |

---

## M. تأكيد السلامة (هذه المهمة)

| العنصر | بقي دون تغيير؟ |
|---|---|
| مخطط/بيانات PostgreSQL | **نعم** (SELECT فقط) |
| Redis/Volumes | **نعم** (لا flush/لا تعديل) |
| حالة الحاويات | **نعم** (لا start/stop) — ما زالت 7 healthy + 3 متوقفة |
| `.env` | **نعم** (قراءة فقط) |
| ملفات WIP / غير متتبعة | **نعم** (لم تُحذف/تُنقل/تُعدَّل) |
| Git commit/push/reset/clean/stash | **نعم لم يحدث** |
| HTTP `/api/v1/health` | **200** بعد الفحوص |

---

### تصنيف سريع

| ID | الأولوية | الحالة |
|---|---|---|
| Cap Store غائب على PG | **P1** | CONFIRMED |
| بقية migrations أكتوبر CGHIA/correlation غائبة | **P2** | CONFIRMED |
| IC index يعتمد على IC | **P2** | CONFIRMED |
| Scientific migration `??` + SQLite حي | **P2** / قرار مالك | CONFIRMED |
| لا نسخة معتمدة حديثة مثبتة للاستعادة | **P1** قبل أي migrate | CONFIRMED |
| فشل مغلق CapVer | متوقع | CONFIRMED (كود + وحدة) |
| خطر تشغيل orphan | **P0** إن خُولف | مشروط |

**توقفت هنا.** لا إصلاح دائم حتى موافقة صريحة على كل بوابة في القسم L.
