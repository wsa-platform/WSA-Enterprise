<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17247). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير اكتشاف قاعدة البيانات الحية — WSA-Enterprise

**التاريخ:** 2026-10-19  
**المسار:** `G:\WSA-Enterprise` · **Remote:** `https://github.com/wsa-platform/WSA-Enterprise.git`  
**HEAD:** `f2025315…` · **الفرع:** `phase-18-m18-ai-marketing-communications`  
**طبيعة العمل:** قراءة فقط · لا استعادة · لا تشغيل Docker · لا commit  

**مرجع مقارنة فقط:**  
`G:\New folder (3)\wsa-render-backup-extracted\2026-09-11T22_27Z\wsa_enterprise_db`

---

## 1. الملخص التنفيذي

**لا توجد قاعدة Laravel رئيسية حية يمكن إثبات اتصال التطبيق بها الآن.**

| البيئة | الحكم |
|---|---|
| إعداد Laravel المحلي | يشير إلى PostgreSQL `127.0.0.1` / `wsa_enterprise` / `wsa` |
| اتصال التطبيق الفعلي | **فشل مصادقة** (`password authentication failed for user "wsa"`) |
| ما يعمل محليًا ببيانات فعلية | **LocalIndex SQLite** (~1.3 GiB) + **Mission Artifacts SQLite** (~6.3 MiB) |
| Docker | CLI موجود؛ **الـ daemon متوقف** — لا فحص حاويات/volumes |
| GitHub | كود + إعدادات + migrations؛ **لا ملفات قاعدة تشغيلية** في البحث المتاح |
| Render | موثّق تاريخيًا كهدف نشر PostgreSQL؛ **لم يُتحقق من الخدمة الحية** |
| آخر لقطة Laravel رئيسية مثبتة | Dump خارجي **2026-09-11** (PostgreSQL 18، ~10 MB) — **نسخة احتياطية وليست حية** |

**الحالات المنطبقة:**  
**D** (الإعداد يشير لقاعدة غير متاحة حاليًا) + مخازن فرعية حية محليًا + **G** جزئيًا (Docker volumes / Render بلا وصول) + احتمال **E** غير محسوم طالما Docker متوقف.

---

## 2. إعداد اتصال Laravel الفعلي

| المفتاح | القيمة | الدليل |
|---|---|---|
| مصدر الإعداد | `backend/.env` (لا config cache) | `bootstrap/cache/config.php` = False |
| `APP_ENV` | `local` | `.env` |
| `DB_CONNECTION` | `pgsql` | `.env` |
| `DB_HOST` | `127.0.0.1` | `.env` |
| `DB_PORT` | `5432` | `.env` |
| `DB_DATABASE` | `wsa_enterprise` | `.env` |
| `DB_USERNAME` | `wsa` | `.env` |
| `DB_PASSWORD` | مضبوط (مخفي) | `.env` |
| اتصال Cap Store | الاتصال الافتراضي `pgsql` | `config/database.php` + diagnose |
| اتصال LocalIndex | `scientific_records` → SQLite افتراضيًا | diagnose: `driver=sqlite`, ملف `scientific_record_store.sqlite` |

Compose المحلي ما زال يعرّف خدمة `postgres` و`POSTGRES_DB=wsa_enterprise` (تصميم Docker) — **ليس** ما يتصل به `.env` الحالي بعد توجيه المضيف إلى `127.0.0.1`.

---

## 3. قواعد البيانات المحلية وحالة الاتصال

| الهدف | الحالة الدقيقة | الدليل |
|---|---|---|
| خادم PostgreSQL على `127.0.0.1:5432` | **متاح** (TCP/accepting؛ عمليات `postgres` تعمل) | `pg_isready` + قائمة العمليات |
| مصادقة Laravel كـ `wsa` | **فشلت** | `scientific:diagnose-persistence` → `authentication_or_authorization_failure` |
| وجود قاعدة `wsa_enterprise` | **غير محسوم بهذا المستخدم** (المصادقة تفشل قبل فحص الكتالوج) | مسبار PDO |
| وجود دور `wsa` | **مرجح موجود** (رسالة الفشل = password auth failed، وليس role does not exist) — يتعارض مع إفادة سابقة للمالك عبر SQL Shell؛ يحتاج تحقق إداري | مسبار PHP |
| مخطط Laravel / `cghia_*` على PG المحلي | **لم يُفحص** (لا اتصال ناجح) | — |
| LocalIndex SQLite | **حي وقابل للقراءة** · جداول علمية · ليس Laravel رئيسي | ملف ~1.35 GiB؛ فحوص سابقة `quick_check=ok` |
| Mission Artifacts SQLite | **حي** · مخزن أرشيف | ~6.6 MiB |
| Test durability SQLite | اختبار | صغير |
| `database/database.sqlite` | غائب | فحص مسار |

---

## 4. جدول حاويات Docker

| البند | النتيجة |
|---|---|
| `docker` CLI | مثبت (`DockerDesktop\...\docker.exe`) |
| Docker Engine / Desktop daemon | **غير متصل** (`dockerDesktopLinuxEngine` pipe مفقود) |
| `docker ps -a` / `volume ls` | **تعذّر** — لا حاويات ولا volumes قابلة للجرد الآن |
| تشغيل Desktop | **لم يُنفَّذ** (محظور إن كان متوقفًا) |

لا حاوية PostgreSQL حية داخل Docker في هذه الجلسة.

---

## 5. جدول Docker volumes

| البند | النتيجة |
|---|---|
| جرد volumes | **تعذّر** (daemon متوقف) |
| `postgres_data` من Compose | معرّف في `docker-compose.yml` كاسم منطقي فقط |
| الاسم الفعلي على القرص / المحتوى | **غير متحقق** |
| هل فُحص المحتوى؟ | **لا** |

أي volume قديم = مرشح تاريخي محتمل (**E**) إلى أن يُشغَّل Docker للقراءة فقط بموافقة لاحقة.

---

## 6. نتائج تحقيق GitHub

### الملفات الحالية (عبر الـ clone المحلي = محتوى المستودع)
- `docker-compose.yml`: PostgreSQL 16، DB/user `wsa_enterprise`/`wsa`.
- `.env.example`: `DB_CONNECTION=pgsql`, `DB_HOST=postgres`.
- migrations + سكربتات backup (`scripts/backup-production.sh` → `*.sql.gz` محلي/إنتاجي).
- CI يستخدم Postgres مؤقتًا للاختبارات؛ الافتراضي SQLite.

### تاريخ المستودع (رسائل/مسارات — بلا checkout)
- commits نشر Render: `a02cac6`, `8341583`, `c391a53` (PostgreSQL/SSL/migrations on boot).
- CI/عزل اختبارات Postgres: `f202531`, `656d5cd`, …

### ملفات قواعد بيانات في GitHub
- بحث code API عن `.sqlite` / `toc.dat` / `.sql`: **total_count=0** (ضمن أداة البحث المتاحة؛ لا يثبت غياب binaries غير المفهرسة بالكامل، لكن لا دليل إيجابي على رفع DB حية).

### أدلة الإنتاج
- توثيق `docs/stage-10-production.md`: Render متغيرات منها `DATABASE_URL`؛ نص صريح: *«This repository does not run a Render deploy»* من مرحلة التوثيق، مع وجود سكربتات `scripts/render-web-start.sh` وcommits إصلاح Render.

---

## 7. الخدمة الخارجية (Render)

| البند | الحالة |
|---|---|
| مؤشرات في الكود/التوثيق/التاريخ | **نعم** — هدف PostgreSQL على Render |
| اتصال فعلي بقاعدة Render في هذه المهمة | **لم يُنفَّذ / غير متحقق** |
| صلاحية لوحة Render | غير متاحة لهذه الجلسة |

لا ادّعاء بأن قاعدة الإنتاج الحية فُحصت.

---

## 8. ترتيب مرشحي «قاعدة Laravel الرئيسية»

| الترتيب | المرشح | قوة الدليل | حي الآن؟ |
|---|---|---|---|
| 1 | إعداد `.env` → `wsa_enterprise@127.0.0.1` | إعداد صريح | **لا** (مصادقة فاشلة) |
| 2 | Dump خارجي 2026-09-11 `wsa_enterprise_db` | مخطط Laravel كامل مثبت سابقًا | **لا** (أرشيف) |
| 3 | Volume Docker `postgres_data` (إن وُجد) | تصميم Compose فقط | **غير متحقق** |
| 4 | قاعدة Render عبر `DATABASE_URL` | تاريخ نشر موثّق | **غير متحقق** |
| — | LocalIndex / Mission SQLite | مخطط فرعي فقط | حية كمخازن فرعية |

---

## 9. التمييز: حي / نسخة / فرعي / اختبار

| الفئة | العناصر |
|---|---|
| Laravel رئيسية حية | **لا شيء مثبت الاتصال** |
| Capability Store حي | **لا** (يتطلب PG الافتراضي الفاشل) |
| Scientific LocalIndex حي | `scientific_record_store.sqlite` |
| Mission Artifacts حي | `archive.sqlite` |
| اختبار | `scientific_records_durability.sqlite`؛ CI Postgres مؤقت |
| نسخة احتياطية معروفة | Directory dump 2026-09-11 خارج المشروع |
| قديم/مجهول | Docker volumes (daemon متوقف) |

---

## 10. مقارنة بالنسخة 2026-09-11

| | Dump 2026-09-11 | البيئة المحلية الآن |
|---|---|---|
| محرك | PostgreSQL 18 dump | PG 17 يستمع محليًا؛ Laravel لا يدخل |
| اسم القاعدة | `wsa_enterprise_db` | إعداد: `wsa_enterprise` |
| دور | `wsa_enterprise_db_user` | إعداد: `wsa` |
| مخطط تطبيق كامل | نعم (~147 جدولًا) | غير مقروء عبر التطبيق |
| Cap Store / LocalIndex العلمي | غائبان من الـ dump | LocalIndex حي على SQLite؛ Cap Store غير متصل |

الـ dump هو **أفضل لقطة مثبتة تاريخيًا** لقاعدة Laravel الرئيسية؛ **ليس** قاعدة حية حالية.

---

## 11. ما ثبت / غير محسوم / تعذّر

**ثبت:** إعداد Laravel؛ فشل مصادقة `wsa`؛ LocalIndex+Mission حيّان؛ Docker daemon متوقف؛ GitHub بلا دليل على DB مرفقة؛ Render موثّق غير مفحوص؛ dump سبتمبر مرجع Laravel رئيسي.

**غير محسوم:** وجود/محتوى `wsa_enterprise` على القرص المحلي إداريًا؛ تطابق إفادة «لا دور wsa» مع خطأ المصادقة الحالي؛ محتوى volumes؛ حالة Render الحية.

**تعذّر:** فحص Docker؛ سرد قواعد/أدوار بدون اعتماد إداري؛ الاتصال بخدمة خارجية.

---

## 12. توصية واحدة للخطوة التالية

**بعد موافقة صريحة فقط:** تشغيل Docker Desktop ثم جرد قراءة فقط (`docker ps -a`, `docker volume ls/inspect`) للبحث عن volume مرتبط بـ Compose/`postgres` — **أو** جلسة `psql` إدارية محلية للتحقق من الأدوار والقواعد — دون استعادة ودون تغيير `.env` حتى يُحسم مصدر القاعدة الحية المطلوب ربطه.

**توقف.** لا إصلاح ولا استعادة في هذه المهمة.
