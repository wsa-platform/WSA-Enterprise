<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17294). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير التحقق قبل الاستعادة — Docker PostgreSQL الأصلي

**الحالة:** تشخيص وخطة فقط. لم يُشغَّل أي حاوية، ولم يُوقَف PostgreSQL على Windows، ولم يُعدَّل `.env` أو أي ملف.

---

## المرحلة الأولى — إعادة التحقق (بدون تغيير)

### الهوية (مثبتة الآن)
| البند | القيمة |
|---|---|
| الحاوية | `wsa-enterprise-postgres-1` |
| الحالة | `exited` / Exit `0` |
| آخر إيقاف | `2026-10-15T18:43:20Z` |
| الصورة | `postgres:16-alpine` (16.14) |
| Compose | مشروع `wsa-enterprise`، خدمة `postgres`، ملف `G:\WSA-Enterprise\docker-compose.yml` |
| Volume | `wsa-enterprise_postgres_data` → `/var/lib/postgresql/data` |
| إنشاء الـ Volume | `2026-08-09` مع label `postgres_data` |
| `POSTGRES_DB` / `POSTGRES_USER` | `wsa_enterprise` / `wsa` |
| ربط المنفذ المخزَّن في الحاوية | `5432:5432` |
| مالك 5432 حاليًا | عملية `postgres` لخدمة **`postgresql-x64-17` (Running)** |

حاوية يتيمة أخرى `wsa-enterprise-postgres-1-orphan` تشير لنفس الـ Volume (جيل أقدم) — ليست قاعدة منفصلة.

### تبعيات Compose (من الملف)
خدمات تعتمد على `postgres` الصحي: `backend`, `queue`, `scheduler`, و`backend-test` (profile `test`).  
`nginx` يعتمد على `backend`/`frontend` فقط.  
`restart: unless-stopped` على `postgres` — تشغيلها عبر Compose قد يعيدها تلقائيًا لاحقًا؛ لذلك **يُفضَّل عدم `compose up` للتحقق**.

### السجلات — آخر دورة حياة
- عند الإقلاع 2026-10-15: استرداد تلقائي بعد إيقاف غير نظيف سابق، ثم **`database system is ready to accept connections`**.
- عند الإيقاف 18:43 UTC: **`fast shutdown`** ثم **`database system is shut down`** — إيقاف نظيف ناجح.
- هذا يدعم سلامة الملفات على مستوى التشغيل، **ولا يثبت بعد** وجود جداول التطبيق داخل الـ Volume.

---

## 1) أدلة سلامة الـ Volume (وما لم يُثبت)

| الدليل | المستوى |
|---|---|
| Volume موجود، مرتبط بمشروع Compose، حجم غير صفري (~194 MB سابقًا) | مثبت |
| آخر إيقاف نظيف Exit 0 | مثبت |
| استرداد WAL نجح عند آخر إقلاع قبل الإيقاف | مثبت |
| تطابق الاسم/المستخدم/الصورة مع `docker-compose.yml` | مثبت |
| محتوى الجداول (`migrations`, CapVer، بيانات Laravel) | **غير مثبت** — يتطلب تشغيل PostgreSQL 16 على هذا الـ Volume |

---

## 2) النسخة الاحتياطية (تحقق TOC دون استعادة)

المسار:  
`G:\New folder (3)\wsa-render-backup-extracted\2026-09-11T22_27Z\wsa_enterprise_db`

| البند | النتيجة |
|---|---|
| قابلة للقراءة | نعم (`pg_restore --list` نجح) |
| الصيغة | directory dump |
| التاريخ | 2026-09-11 22:27 |
| مصدر dump | PostgreSQL **18.4** (Render)، اسم القاعدة في الأرشيف: `wsa_enterprise_db` |
| `TABLE public migrations` | موجود في TOC |
| `cghia_capability_cell_records` | **غير موجود** في هذه النسخة |
| جداول Laravel أساسية (`users`, `jobs`, …) | موجودة في TOC |

**الاستخدام الآمن الآن:** مرجع مخطط/مقارنة فقط.  
**ليست:** إثباتًا لمحتوى `wsa-enterprise_postgres_data`، ولا بديلاً عن القاعدة الحية، ولا يُنفَّذ عليها `pg_restore` في هذه المرحلة.

ملاحظة: غياب `cghia_*` في نسخة سبتمبر متوقع إن أُضيف الجدول لاحقًا (هجرة أكتوبر) — وجوده أو غيابه داخل Docker Volume ما زال مجهولًا.

---

## المرحلة الثانية — خطة التحقق (توقف قبل التشغيل)

### القيود المحترمة
- لا `docker compose down` / لا `compose up` للمكدس كاملًا.
- لا حذف/إعادة تهيئة Volume.
- لا migrations / لا كتابة تطبيقية.
- لا لمس منفذ 5432 على Windows ولا إيقاف خدمته.
- **لا تشغيل الآن** — يلزم موافقتك الصريحة على الأمر أدناه.

### لماذا لا يكفي `docker start wsa-enterprise-postgres-1`؟
الحاوية مربوطة بـ **`5432:5432`**. المنفذ مشغول بـ PostgreSQL 17 على Windows → الإقلاع غالبًا يفشل على الربط، أو يتعارض إن تغيّرت الحالة. لا يُنصح بهذا المسار للتحقق.

### الأمر المقترح عند الموافقة (PostgreSQL 16 + نفس الـ Volume + منفذ بديل)

```text
docker run -d --name wsa-pg-volume-verify `
  --mount type=volume,source=wsa-enterprise_postgres_data,target=/var/lib/postgresql/data `
  -p 15432:5432 `
  postgres:16-alpine
```

| الجانب | التوضيح |
|---|---|
| التأثير | حاوية مؤقتة جديدة؛ **نفس** Volume التاريخي؛ استماع المضيف على **15432** فقط |
| لماذا بدون `POSTGRES_*` | المجلد فيه بيانات موجودة؛ الـ entrypoint يشغّل الخادم دون `initdb` جديد |
| ما يُتجنَّب | `compose up`، `compose down -v`، `-v` بمسار فارغ، أي `postgres` أحدث من 16 على هذا الـ data dir |
| الكتابة غير المقصودة | لا `migrate`؛ فحوص `SELECT`/`information_schema` فقط؛ إيقاف لاحق بـ `docker stop` ثم `docker rm` للحاوية المؤقتة **دون** `-v` |
| كلمة المرور | من داخل الحاوية: `psql -U wsa -d wsa_enterprise` عبر socket غالبًا بلا كلمة مرور؛ من المضيف عبر TCP قد تحتاج كلمة مرور Compose المخزّنة في تعريف الخدمة (لن تُطبع هنا) |

**بديل مرفوض في هذه الخطة:** تشغيل كل خدمات المشروع أو إعادة ربط 5432 قبل تحريره من Windows.

---

## 3) خطوات تشغيل PostgreSQL الأصلي وحده (بعد الموافقة)

1. التأكد أن 15432 حر.  
2. تنفيذ `docker run` أعلاه (أو أمر توافق عليه حرفيًا).  
3. انتظار جاهزية: `docker exec wsa-pg-volume-verify pg_isready -U wsa -d wsa_enterprise`.  
4. فحوص القراءة فقط (القسم 4).  
5. `docker stop wsa-pg-volume-verify` ثم `docker rm wsa-pg-volume-verify` (**بدون** حذف Volume).

---

## 4) فحص `migrations` و`cghia_capability_cell_records`

بعد الجاهزية فقط:

```sql
SELECT current_database(), current_user, version();

SELECT EXISTS (
  SELECT 1 FROM information_schema.tables
  WHERE table_schema='public' AND table_name='migrations'
) AS has_migrations;

SELECT EXISTS (
  SELECT 1 FROM information_schema.tables
  WHERE table_schema='public' AND table_name='cghia_capability_cell_records'
) AS has_cghia_capability_cell_records;
```

اختياري للقراءة فقط: عدد صفوف `migrations` (بدون محتوى تطبيقي حسّاس).  
لا `INSERT`/`UPDATE`/`DELETE`/`migrate`.

---

## 5) إعادة ربط Laravel بعد نجاح التحقق (خطة فقط)

| الخيار | الآلية | متى |
|---|---|---|
| **أ — مؤقت للتحقق** | `DB_HOST=127.0.0.1` و`DB_PORT=15432` أثناء عمل حاوية التحقق | فور نجاح الفحوص، للتجربة فقط |
| **ب — دائم مع الإبقاء على Windows PG** | الإبقاء على منفذ Docker بديل (مثل 15432) وتحديث `.env` وفقه؛ تشغيل حاوية `postgres` وحدها بهذا الربط | إذا بقي 5432 لـ PG17 |
| **ج — إعادة الوضع التاريخي لـ Compose** | تشغيل `wsa-enterprise-postgres-1` (أو الخدمة) على 5432 **بعد** تحرير المنفذ — يتطلب إيقاف/تغيير منفذ Windows PG | **يحتاج موافقة منفصلة صريحة** — ممنوع في المرحلة الحالية |

بعد الربط: اختبار PDO قراءة فقط، ثم CapVer/diagnose عند الموافقة — دون migrations تلقائية.

---

## الخلاصة والانتظار

- الأدلة تدعم أن **`wsa-enterprise_postgres_data`** هو مخزن Laravel المحلي التاريخي الأرجح، وآخر إيقاف كان نظيفًا.  
- النسخة الاحتياطية Render **قابلة للقراءة** للتحقق المقارن، وليست بديلاً عن فحص الـ Volume، ولا تحتوي `cghia_capability_cell_records`.  
- فحص المحتوى الحقيقي **يتطلب تشغيل PostgreSQL 16 على الـ Volume بمنفذ غير 5432**؛ الأمر المقترح أعلاه جاهز، **وموقوف بانتظار موافقتك الصريحة قبل أي تشغيل.**
