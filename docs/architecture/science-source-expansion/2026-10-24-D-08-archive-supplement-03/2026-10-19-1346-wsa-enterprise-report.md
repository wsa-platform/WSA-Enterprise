<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17187). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير قراءة فقط — إعدادات PostgreSQL المطلوبة لـ CapVer

**النطاق:** فحص داخل `G:\WSA-Enterprise` دون تعديل إعدادات، دون إنشاء قواعد بيانات، دون migrations، دون اختبارات كتابة.  
**تأكيد البيئة من المالك (خارج المستودع):** PostgreSQL 17.11 على Windows، `psql` ناجح، المنفذ 5432، قواعد موجودة: `postgres` / `template0` / `template1` فقط.

---

## 1. حالة Git — `VERIFIED`

| البند | القيمة |
|---|---|
| الفرع | `phase-18-m18-ai-marketing-communications` |
| HEAD | `f2025315d8990f52f3c658c968a3ec8252ac18b0` |
| آخر commit | `f202531 test(ic): verify PostgreSQL persistence isolation in CI` |
| WIP | ~150 سطراً (لم يُمس) |
| Commit / Push | لم يُنفَّذ |

---

## 2. إعدادات الاتصال (بدون أسرار) — `VERIFIED`

### المصدر الرسمي للقالب
`backend/.env.example`:

| المفتاح | القيمة في المثال |
|---|---|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `postgres` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `wsa_enterprise` |
| `DB_USERNAME` | `wsa` |
| `DB_PASSWORD` | موجود في المثال (قيمة قالب — لا تُعاد هنا) |

تعليق صريح في `.env.example` (حوالي السطور 130–140): Cap Store يستخدم الاتصال الافتراضي `pgsql`؛ LocalIndex منفصل عبر `SCIENTIFIC_RECORD_STORE_*` ويمكن أن يبقى SQLite.

### ما هو محمّل فعلياً في `backend/.env` (مفاتيح غير سرية فقط)

| المفتاح | القيمة الحالية |
|---|---|
| `APP_ENV` | `local` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | **`postgres`** |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `wsa_enterprise` |
| `DB_USERNAME` | `wsa` |
| `DB_PASSWORD` | مضبوط (`SET=yes`) — القيمة لم تُطبع |
| `SCIENTIFIC_RECORD_STORE_*` | غير ظاهرة كمفاتيح مفعّلة في الفحص → LocalIndex يبقى على الافتراضي |

---

## 3. إعداد Laravel ومصدر الاتصال — `VERIFIED`

الملف: `backend/config/database.php`

- **الاتصال الافتراضي:** `env('DB_CONNECTION')`؛ لـ `production` يُفضَّل `pgsql`.
- **اتصال Cap Store:** `pgsql` → `host`/`port`/`database`/`username` من `DB_*`؛ الافتراضي البرمجي للمضيف إن غاب env هو `127.0.0.1` (لكن `.env` الحالي يفرض `postgres`).
- **`search_path`:** `public`.
- **LocalIndex:** اتصال منفصل `scientific_records`؛ الافتراضي `driver=sqlite` وملف `storage/app/scientific_record_store.sqlite`. التعليق في الكود يوضح أن عزل LocalIndex وُجد أصلاً لأن مضيف Compose `postgres` قد لا يُحل على Windows.
- **اختبارات PostgreSQL المعزولة:** اتصال `pgsql_testing` + `DB_TEST_*` (قاعدة اسمها غالباً `*_test`) — مسار منفصل عن CapVer التشغيلي؛ لم يُشغَّل في هذا الفحص.

الربط البرمجي لـ Cap Store: `EloquentCapabilityStoreRepository` عبر `DB::transaction` على الاتصال الافتراضي؛ الجدول `cghia_capability_cell_records` (نموذج `CghiaCapabilityCell`).

---

## 4. أوامر CapVer ومسار التحقق — `VERIFIED` (تعريف) / `NOT_VERIFIED` (تشغيل كتابة)

أوامر متاحة (من `php artisan list`):

| الأمر | الدور |
|---|---|
| `scientific:diagnose-persistence` | تشخيص Cap Store + LocalIndex بلا أسرار؛ يفحص جدول `cghia_capability_cell_records` |
| `scientific:capver-accepted` | تنفيذ CapVer للمقاعد المصرّح بها؛ `--write-report=` اختياري؛ وسيط `adr_id` اختياري |
| `scientific:ensure-record-store` | مخطط LocalIndex على اتصاله المخصص — **ليس** إثبات Cap Store |
| `scientific:reconcile-record-store` | جرد/نسخ اختياري sqlite→pgsql لـ LocalIndex |

**مسار الكتابة الفعلية + القراءة المستقلة (عقد المشروع):**

1. اتصال `pgsql` reachable + جدول Cap Store موجود.
2. `scientific:capver-accepted` يستدعي `persistCurrent` / أو `ALREADY_CURRENT` عبر `findCurrentFact`.
3. النجاح التشغيلي يتطلب `capability_store_status` ∈ {`PERSISTED`,`ALREADY_CURRENT`} و`activation_pass` وفق العقد — **وليس** `STORE_UNAVAILABLE`.
4. قراءة مستقلة = اتصال جديد/عملية لاحقة ترى الصف في `cghia_capability_cell_records` (لم يُنفَّذ هنا).

**متطلبات سابقة قبل أي تشغيل كتابة (لم تُنفَّذ):**

- مضيف قابل للحل من PHP (اليوم `DB_HOST=postgres` ≠ localhost الذي يعمل في `psql`).
- قاعدة `wsa_enterprise` ودور/مستخدم متوافق مع `DB_USERNAME` (اليوم عندك فقط قواعد النظام).
- ترحيل إنشاء `cghia_capability_cell_records` على تلك القاعدة.
- تفويض المالك لـ CapVer للمقاعد (كان مسجلاً سابقاً في الكتالوج؛ لم يُعاد التحقق التشغيلي في هذا الفحص).

---

## 5. اعتماد اسم Docker `postgres` والتوثيق لـ Windows — `VERIFIED` / `PARTIAL`

| الدليل | المحتوى |
|---|---|
| `backend/.env` و`.env.example` | `DB_HOST=postgres` |
| `docker-compose.yml` | خدمة باسم `postgres`، صورة `postgres:16-alpine`، `POSTGRES_DB=wsa_enterprise`، `POSTGRES_USER=wsa`، منفذ `5432:5432` |
| `config/database.php` | يذكر صراحة مشكلة hostname Compose `postgres` على المضيف المحلي |

**تعليمات موثقة لتشغيل PostgreSQL محلياً على Windows (بدون Docker):** لم يُعثر على دليل إعداد Windows خطوة-بخطوة في README/docs بهذا المعنى. التقارير المهمة تسجّل العائق وتقترح «مضيفاً مصرّحاً» أو `127.0.0.1` ضمن تعليقات `.env.example` لـ LocalIndex — **ليست** دليل تثبيت Windows كاملاً → `PARTIAL` / غالباً `NOT_VERIFIED` كدليل تشغيل رسمي.

ملاحظة توافق: Compose يفترض **PostgreSQL 16**؛ بيئتك **17.11**. التوافق مع Laravel/`pdo_pgsql` غالباً مقبول، لكن لم يُختبر اتصال التطبيق بعد.

---

## 6. ما يتوقعه المشروع من قواعد البيانات / المخطط — `VERIFIED` (تعريف فقط)

| الكيان | المتوقع في المشروع | حالتك الحالية (من إفادة المالك) |
|---|---|---|
| قاعدة التطبيق / Cap Store | اسم `wsa_enterprise` | **غير موجودة** (فقط `postgres` + templates) |
| مستخدم التطبيق (قالب) | `wsa` | غير مؤكد في هذا الفحص؛ `psql` نجح كـ `postgres` |
| Schema | `public` (search_path) | افتراضي PostgreSQL |
| جدول Cap Store | `cghia_capability_cell_records` عبر migration `2026_10_09_140000_create_cghia_capability_cell_records_table.php` | غير مُنشأ على قاعدة التطبيق (القاعدة نفسها غير موجودة) |
| جداول CGHIA أخرى (IC/identity…) | migrations تحت `backend/database/migrations` | مطلوبة لتطبيق كامل؛ CapVer يركّز على جدول الخلايا أعلاه |
| LocalIndex إنتاجي (اختياري منفصل) | مثال في `.env.example`: `wsa_scientific_records` عبر `SCIENTIFIC_RECORD_STORE_*` | غير مطلوب لإثبات Cap Store؛ الافتراضي الحالي SQLite |

**لم يُنشأ ولم يُحذف ولم يُرحَّل شيء في هذه الجولة.**

---

## مؤكد مقابل غير متحقق

| البند | الحالة |
|---|---|
| Git / HEAD / WIP | `VERIFIED` |
| `.env` يشير إلى `pgsql` + مضيف `postgres` + DB `wsa_enterprise` + user `wsa` | `VERIFIED` |
| خدمة PostgreSQL المحلية على 5432 تعمل لـ `psql` | `VERIFIED` (إفادة مالك) |
| حل اسم المضيف `postgres` من PHP/Laravel | سابقاً فشل؛ **لم يُعاد فحصه الآن** بعد تثبيت PG → يُفترض ما زال غير محلول ما دام `DB_HOST=postgres` |
| وجود قاعدة `wsa_enterprise` ودور `wsa` | `VERIFIED` غياب القاعدة حسب المالك؛ دور `wsa` `NOT_VERIFIED` |
| اتصال التطبيق + migrations + CapVer كتابة/قراءة مستقلة | `NOT_VERIFIED` (محظور في هذا الطلب) |
| دليل Windows رسمي بدون Docker | `NOT_VERIFIED` / `PARTIAL` |
| جاهزية إنتاجية CapVer | **لا** — SQLite LocalIndex لا يُحتسب |

---

## الفجوة الحرجة (ملخص)

PostgreSQL المحلي جاهز على `127.0.0.1:5432` كمستخدم نظام `postgres` وقاعدة `postgres`، بينما التطبيق مضبوط على مضيف Docker **`postgres`** وقاعدة **`wsa_enterprise`** ومستخدم **`wsa`**. بدون محاذاة المضيف + إنشاء القاعدة/الدور + ترحيل جدول Cap Store، سيبقى CapVer على `STORE_UNAVAILABLE` أو فشل اتصال.

---

## الخطوة التالية المقترحة (دون تنفيذ)

بعد موافقة صريحة منك كسلسلة منفصلة:

1. **قرار إعداد:** توجيه `DB_HOST` إلى `127.0.0.1` (أو `localhost`) — وليس الإبقاء على اسم Compose `postgres` — مع الإبقاء على المنفذ `5432`.
2. **تجهيز قاعدة التطبيق:** إنشاء قاعدة `wsa_enterprise` ودور/صلاحيات متوافقة مع `DB_USERNAME` الحالي (أو تعديل بيانات الاعتماد لتتوافق مع دور موجود، بموافقتك).
3. **تحقق بلا كتابة أولاً:** `php artisan scientific:diagnose-persistence` حتى يظهر Cap Store `reachable` ثم وجود الجدول (قد يظهر `schema_migration_failure` قبل الترحيل).
4. **ثم فقط:** تشغيل migrations اللازمة لجدول Cap Store على تلك القاعدة، ثم `scientific:capver-accepted`، ثم قراءة مستقلة من `cghia_capability_cell_records` (عملية/اتصال منفصل) لتثبيت `PERSISTED`/`ALREADY_CURRENT`.

لا يُنصح باستخدام قاعدة النظام `postgres` كـ `DB_DATABASE` للتطبيق، ولا باعتبار نجاح `psql` وحده إثباتاً لـ CapVer.
