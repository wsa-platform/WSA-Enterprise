<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17233). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير فحص جنائي — النسخة الاحتياطية `2026-09-11T22_27Z`

**نطاق البحث:** `G:\New folder (3)\wsa-render-backup-extracted\2026-09-11T22_27Z`  
**طبيعة العمل:** قراءة فقط · لا استعادة · لا تعديل · لا Docker · لا commit  

---

## 1. الملخص التنفيذي

**نعم — عُثر على مرشح قوي لقاعدة Laravel الرئيسية الأصلية** داخل المجلد الفرعي `wsa_enterprise_db`.

| الحكم | التفاصيل |
|---|---|
| النوع المثبت | نسخة **PostgreSQL directory-format** (`pg_dump -Fd`) |
| اسم القاعدة في الأرشيف | `wsa_enterprise_db` |
| مالك/دور في الأرشيف | `wsa_enterprise_db_user` |
| مصدر الإصدار | **PostgreSQL 18.4** (Debian) — مُفرَّغ بـ `pg_dump` **18.6** |
| الحجم الإجمالي | ≈ **9.78 MB** (148 ملفًا، 10 256 332 بايت) |
| جداول في TOC | 147 جدولًا + 147 `TABLE DATA` |
| Cap Store / LocalIndex العلمي | **غير موجودين** في هذه النسخة |
| هل هي «كل بيانات المشروع اليوم»؟ | **لا** — لقطة تطبيق Laravel بتاريخ ≈ 2026-09-11، قبل جداول CGHIA/LocalIndex الحالية |

---

## 2. جدول الملفات المرشحة

| المسار | النوع | الحجم | الدور المحتمل | الثقة |
|---|---|---:|---|---|
| `...\2026-09-11T22_27Z\wsa_enterprise_db\` (مجلد) | أرشيف `pg_dump` بصيغة DIRECTORY | ~9.78 MB | **قاعدة Laravel الرئيسية** (لقطة) | **عالية جداً** |
| `...\wsa_enterprise_db\toc.dat` | فهرس TOC لـ PGDMP | 672 735 B | بيانات وصفية للأرشيف | مؤكد |
| `...\wsa_enterprise_db\*.dat` (147 ملفًا) | مقاطع بيانات/مخطط الأرشيف | أغلبها صغيرة؛ الأكبر `5757.dat` ≈ 8.90 MB | جداول/بيانات | مؤكد كجزء من الأرشيف |
| ملفات `.sqlite` / `.sql` / `.zip` في هذا المجلد الزمني | — | — | **لا يوجد** ضمن هذا المسار | مؤكد الغياب هنا |

لا ملفات SQLite أو SQL نصي منفصلة داخل `2026-09-11T22_27Z`.

---

## 3. محرك قاعدة البيانات

| الادعاء | الحالة | الدليل |
|---|---|---|
| PostgreSQL custom/directory dump | **مؤكد** | ترويسة `toc.dat`: بايتات `50 47 44 4D 50` = **`PGDMP`** |
| الصيغة | **مؤكد** | `pg_restore --list`: `Format: DIRECTORY` · `Dump Version: 1.16-0` · `Compression: none` |
| إصدار المصدر | **مؤكد** | `Dumped from database version: 18.4 (Debian 18.4-1.pgdg12+1)` |
| أداة التفريغ | **مؤكد** | `Dumped by pg_dump version: 18.6` · وقت الأرشيف `2026-09-11 22:27:27` |
| امتداد `vector` | **مؤكد** | عنصر TOC: `EXTENSION - vector` |
| MySQL / SQLite كمحرك لهذه النسخة | **مرفوض بهذه الأدلة** | لا توقيع SQLite؛ توقيع PGDMP + TOC PostgreSQL |

---

## 4. تحديد قاعدة Laravel الرئيسية

أدلة تطابق تطبيق WSA (وليس LocalIndex):

- اسم قاعدة الأرشيف: `wsa_enterprise_db` (قريب من `wsa_enterprise` في إعداد المشروع الحالي).
- جداول Laravel نمطية في TOC: `migrations`, `users`, `cache`, `jobs`/`failed_jobs`, `sessions`, إلخ.
- جداول مجال زراعي/تشغيلي: `farms`, `crop_*`, `diagnosis_*`, `apiaries`/`hives`, فوترة، إلخ.
- عيّنة بيانات `migrations` (ملف `5625.dat`) تبدأ بأسماء migrations نصية مثل `create_users` — نمط Laravel (قُرئت الترويسة فقط؛ لم تُستورد).
- **لا** جداول: `cghia_capability_cell_records`, `scientific_source_records`, `durable_correlation_records`, ولا مؤشرات `mission_artifact`.

**الفصل المعماري:**

| المخزن | في هذه النسخة؟ |
|---|---|
| Laravel الرئيسية | **نعم (لقطة 2026-09-11)** |
| CapVer / Capability Store | **لا** |
| Scientific LocalIndex | **لا** (موجود اليوم كـ SQLite منفصل في المستودع، خارج هذه النسخة) |
| Mission Artifacts archive | **لا** |

---

## 5. فحص السلامة (ما نُفّذ فعلياً)

| الفحص | النتيجة |
|---|---|
| جرد تكراري للمجلد | 148 ملفًا تحت `wsa_enterprise_db` فقط |
| ترويسة `toc.dat` | `PGDMP` |
| ترويسات عيّنة `.dat` | نص نسخ PostgreSQL (مثل `\N`، أسماء migrations) — بلا طباعة أسرار |
| `pg_restore --list` (فهرسة فقط، بلا استعادة) | TOC ≈ 1604 بندًا؛ نجح على أدوات PostgreSQL 17 |
| `PRAGMA`/SQLite | غير منطبق |
| استيراد / `psql -f` / إنشاء DB | **لم يُنفَّذ** |

قابلية الفحص الآمن الآن: **نعم** (قراءة الأرشيف وفهرسة TOC).  
قابلية الاستعادة لاحقاً: **ممكنة تقنياً** لكن تحتاج بيئة وقرار إصدار (انظر المخاطر).

---

## 6. المقارنة مع المشروع الحالي (`G:\WSA-Enterprise`)

| الجانب | النسخة 2026-09-11 | المشروع الحالي |
|---|---|---|
| محرك التطبيق المقصود | PostgreSQL | `DB_CONNECTION=pgsql` |
| اسم القاعدة | `wsa_enterprise_db` | إعداد `.env`: `wsa_enterprise` |
| دور الأرشيف | `wsa_enterprise_db_user` | إعداد: `wsa` |
| إصدار PG المصدر | **18.4** | محلي مُبلَّغ: **17.11** |
| جداول CGHIA / Cap Store | غائبة | migrations أحدث موجودة في الكود |
| LocalIndex العلمي | غائب من الأرشيف | SQLite ~1.35 GiB على القرص |
| اكتمال لقطة التطبيق | TOC كامل ظاهرياً (147/147 DATA) | غير مُعاد تشغيل الاستعادة للتحقق من سلامة الصفوف |

---

## 7. المخاطر والفجوات

1. **تعارض الإصدار:** التفريغ من PG **18**؛ الاستعادة إلى PG **17** قد تفشل أو تحتاج مسار ترقية — **غير متحقق** حتى تجربة استعادة معزولة.  
2. النسخة **لا تتضمن** LocalIndex ولا Cap Store الحاليين.  
3. لا يوجد `manifest`/`README` بجانب المجلد الزمني يوثق سلسلة Render الكاملة (ضمن هذا المسار فقط).  
4. سلامة كل صف بيانات لم تُثبت بـ restore test (متعمداً).  
5. أكبر ملف بيانات (~8.9 MB) يحتوي إشارات نصية لمعرفة بحثية — لم يُفحَص المحتوى الحساس.

---

## 8. التوصية التالية (واحدة — دون تنفيذ)

على **نسخة معزولة** من PostgreSQL (يفضَّل **≥ 18** أو مسار ترقية موثَّق)، وبعد موافقة صريحة: تجربة `pg_restore` إلى قاعدة فارغة باسم تجريبي **فقط** للتحقق من نجاح الاستعادة وعدد الجداول — دون لمس `.env` الإنتاجي ودون دمج LocalIndex.

---

## سجل موجز

- **فُحص:** المجلد الزمني + `wsa_enterprise_db/*` + ترويسات + `pg_restore --list`.  
- **عُدّل:** لا شيء.  
- **استعادة:** لم تُنفَّذ.
