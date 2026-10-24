<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17280). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير: حاوية وVolume قاعدة Laravel الأصلية

**التحقق:** Docker Engine يعمل؛ **0 حاويات عاملة**؛ كل حاويات PostgreSQL متوقفة. لم يُشغَّل شيء ولم يُعدَّل أي ملف أو Volume.

---

## 1. ربط الحاويات ↔ الـ Volumes (مثبت بـ `docker inspect`)

| الحاوية | الصورة / الإصدار | Volume المرتبط | قاعدة (`POSTGRES_DB`) | مستخدم | منافذ مضيف | Compose |
|---|---|---|---|---|---|---|
| **`wsa-enterprise-postgres-1`** | `postgres:16-alpine` (16.14) | **`wsa-enterprise_postgres_data`** (~194 MB) | `wsa_enterprise` | `wsa` | **5432→5432** | `wsa-enterprise` / `postgres` |
| `wsa-enterprise-postgres-1-orphan` | `postgres:16-alpine` (16.14) | **نفس** `wsa-enterprise_postgres_data` | `wsa_enterprise` | `wsa` | لا ربط مضيف | بلا labels (يتيم) |
| `wsa-backup-verification-pg` | `postgres:17-alpine` (17.11) | Volume مجهول `13592f57…` (~77 MB) | `wsa_backup_test` | (افتراضي الصورة) | **55432→5432** | لا |
| `wsa-r7-store-pg-20261015` | `postgres:16-alpine` (16.14) | **`wsa-r7-store-pgdata-20261015`** (~130 MB) | `wsa_r7_store_test` | `wsa_r7` | لا | لا (`NetworkMode=none`) |

Volumes أخرى: `wsa-enterprise_backend_vendor` (PHP vendor)، `wsa-enterprise_redis_data` (Redis) — ليست قواعد Laravel.

---

## 2. أيها قاعدة Laravel الأصلية؟

### المرشّح الأقوى: `wsa-enterprise-postgres-1` + `wsa-enterprise_postgres_data`
**مستوى الثقة: عالٍ**

الأدلة:
1. مطابقة تامة لـ `docker-compose.yml`: خدمة `postgres`، صورة `postgres:16-alpine`، `POSTGRES_DB=wsa_enterprise`، `POSTGRES_USER=wsa`، volume اسمه `postgres_data` → Docker يسمّيه `wsa-enterprise_postgres_data`.
2. Labels Compose: مشروع `wsa-enterprise`، ملف `G:\WSA-Enterprise\docker-compose.yml`.
3. Volume أُنشئ **2026-08-09** مع label `com.docker.compose.volume=postgres_data` (من عمر المشروع المحلي).
4. آخر تشغيل ناجح مع المكدس: **2026-10-15** ≈ 11:32→18:43 UTC، مع `backend`/`redis` في نفس النافذة؛ إيقاف نظيف (`database system is shut down`).
5. اسم القاعدة والمستخدم يطابقان إعدادات Laravel المستهدفة (`wsa_enterprise` / `wsa`).

`wsa-enterprise-postgres-1-orphan` = جيل أقدم (أغسطس 2026) لنفس الـ Volume، وليس قاعدة منفصلة.

---

## 3. التمييز عن النسخ الأخرى

| النوع | الحاوية | الحكم |
|---|---|---|
| **قاعدة Laravel المحلية التاريخية** | `wsa-enterprise-postgres-1` | الأصل المرجّح (Compose + volume المشروع) |
| **تحقق من نسخة احتياطية** | `wsa-backup-verification-pg` | مؤقتة: `wsa_backup_test`، منفذ 55432، PG17، سجلات تشير لمحاولات تحقق بعد استعادة |
| **مخزن R7 / علمي تجريبي** | `wsa-r7-store-pg-20261015` | منفصل: `wsa_r7_store_test`، جداول `scientific_source_*`، شبكة `none` — ليس default Laravel |

---

## 4. ملاحظة تشغيلية مهمة

المنفذ **5432 على المضيف** مربوط تاريخيًا بحاوية Compose المتوقفة، بينما خدمة **PostgreSQL 17 على Windows** تعمل حاليًا وتستمع على 5432. اتصال Laravel الحالي إلى `127.0.0.1:5432` يصل إلى **تثبيت Windows**، لا إلى Volume Docker الأصلي المتوقف — وهذا يفسّر فشل مصادقة `wsa` دون نفي وجود بيانات المشروع داخل `wsa-enterprise_postgres_data`.

---

## 5. ما لم يُثبت بعد (يتطلب موافقة لاحقة)

- محتوى الجداول داخل الـ Volume (وجود `migrations` / `cghia_capability_cell_records`) — يحتاج تشغيل الحاوية أو فحصًا معتمدًا للقراءة فقط.
- تطابق كلمة مرور Compose مع `.env` الحالي.

**لم يُنفَّذ:** تشغيل حاويات، Compose up/down، migrations، restore، تعديل `.env`، أو أي كتابة.
