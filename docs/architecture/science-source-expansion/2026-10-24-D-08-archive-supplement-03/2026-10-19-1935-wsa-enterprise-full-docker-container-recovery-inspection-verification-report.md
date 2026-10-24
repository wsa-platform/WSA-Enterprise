<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17320). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير استعادة وفحص حاويات Docker — WSA-Enterprise

**النطاق المنفَّذ:** جرد كامل → تحقق PostgreSQL → تشغيل Redis → تشغيل Backend → تصنيف الباقي دون تشغيل تلقائي → تحقق شبكة واتصالات → جرد نهائي.  
**لم يُنفَّذ:** recreate، compose up/down، migrations، restore، تعديل `.env`، تشغيل اليتيمة/R7/backup، تشغيل nginx/frontend/queue/scheduler.

---

## Phase A — التصنيف

| الفئة | الحاويات |
|---|---|
| 1. تشغيل أساسي | `postgres-1`، `redis-1`، `backend-1` |
| 2. واجهة/ويب | `nginx-1` (8079)، `frontend-1` |
| 3. Queue/Scheduler | `queue-1`، `scheduler-1` |
| 4. تخزين علمي إضافي | `wsa-r7-store-pg-20261015` |
| 5. اختبار/تحقق مؤقت | `wsa-backup-verification-pg` |
| 6. يتيمة | `wsa-enterprise-postgres-1-orphan` (نفس `postgres_data`) |

**قبل الاستعادة:** 10 حاويات، عاملة = 1 (`postgres` فقط).

---

## Phase B — PostgreSQL (بدون إعادة تشغيل)

| فحص | نتيجة فعلية |
|---|---|
| Running | نعم |
| Volume | `wsa-enterprise_postgres_data` |
| منافذ | `0.0.0.0:5432` / `[::]:5432` |
| `pg_isready` | accepting connections |
| اتصال `wsa`@`wsa_enterprise` | OK — PG **16.14** |
| `migrations` | موجود، **55** صفًا |
| `cghia_capability_cell_records` | **غير موجود** |
| أخطاء منذ إقلاع 19 أكتوبر | لا شيء حرج |
| Windows `postgresql-x64-17` | **Stopped** |

**البوابة: PASS**

---

## Phase C — Redis

| قبل | بعد |
|---|---|
| `redis:7-alpine`، Volume `wsa-enterprise_redis_data`، شبكة `wsa-enterprise_wsa`، منفذ 6379 حر | `docker start` ناجح |
| | Running + **healthy** |
| | `redis-cli ping` → **PONG** |
| | تحميل RDB: 24 مفتاحًا، بدون FLUSH |

**البوابة: PASS**

---

## Phase D — Backend

| البند | نتيجة |
|---|---|
| إعدادات | `DB_HOST=postgres`، `REDIS_HOST=redis`، شبكة Compose، mounts مطابقة |
| `docker start` | ناجح |
| Docker Health | **healthy** (`php artisan about \| grep Application`) |
| DNS | `postgres`→172.18.0.2، `redis`→172.18.0.3 |
| `artisan about` | Laravel 12.69.2، Database=pgsql، Cache/Queue/Session=redis |
| اتصال DB من التطبيق | **DB_OK=wsa_enterprise\|wsa** |
| اتصال Redis من التطبيق | **REDIS_OK=true** |
| سجلات أخطاء حديثة | لا Fatal/SQLSTATE/`cghia` في نافذة الإقلاع |
| ملاحظة | `db:show` عرض الاتصال ثم خرج exit=1 بسبب امتداد PHP `intl` (تنسيق أرقام) — **ليس فشل اتصال** |

**البوابة: PASS (Running + healthy + DB/Redis OK)**  
غياب `cghia_capability_cell_records` لم يُظهر فشل إقلاع في هذه الفحوص.

---

## Phase E — ما تُرك متوقفًا عمدًا

| حاوية | القرار | السبب |
|---|---|---|
| nginx / frontend | متوقفة | ليسا ضمن جلسة 15 أكتوبر الأساسية؛ يحتاجان موافقة لاحقة لمسار الويب على 8079 |
| queue / scheduler | متوقفة | آخر تشغيل سبتمبر Exit 137؛ غير لازمتين للمسار الأساسي الآن |
| R7 store PG | متوقفة | قاعدة اختبار منفصلة (`NetworkMode=none`) |
| backup-verification-pg | متوقفة | تحقق نسخ مؤقت، منفذ 55432 |
| postgres orphan | متوقفة | **نفس Volume البيانات** — تشغيلها ممنوع |

---

## Phase F — الشبكة والاتصال

| فحص | نتيجة |
|---|---|
| شبكة `wsa-enterprise_wsa` | الأعضاء العاملون: postgres `.2`، redis `.3`، backend `.4` |
| Backend→Postgres بالاسم الداخلي | ناجح |
| Backend→Redis بالاسم الداخلي | ناجح |
| منافذ المضيف | 5432 و6379 مستخدَمان عبر Docker؛ 8079 حر (nginx متوقف) |
| تعارض Windows PG | لا — الخدمة 17 متوقفة |

---

## Phase G — الجدول النهائي

| Container | Previous | Final | Health | Ports | Data Volume | Verification |
|---|---|---|---|---|---|---|
| `wsa-enterprise-postgres-1` | Running | **Running** | healthy | 5432:5432 | `wsa-enterprise_postgres_data` | استُعيدت سابقًا؛ فحوص RO ناجحة |
| `wsa-enterprise-redis-1` | Exited(0) | **Running** | healthy | 6379:6379 | `wsa-enterprise_redis_data` | استُعيدت؛ PONG |
| `wsa-enterprise-backend-1` | Exited(0) | **Running** | healthy | 9000/tcp (داخلي) | vendor vol + bind code | استُعيدت؛ DB+Redis OK |
| `wsa-enterprise-nginx-1` | Exited(0) | Exited | — | 8079 | — | متوقفة عمدًا |
| `wsa-enterprise-frontend-1` | Exited(0) | Exited | — | — | — | متوقفة عمدًا |
| `wsa-enterprise-queue-1` | Exited(137) | Exited | — | — | vendor | متوقفة عمدًا |
| `wsa-enterprise-scheduler-1` | Exited(137) | Exited | — | — | vendor | متوقفة عمدًا |
| `wsa-r7-store-pg-20261015` | Exited(255) | Exited | — | — | `wsa-r7-store-pgdata-…` | اختبار؛ لا تشغيل |
| `wsa-backup-verification-pg` | Exited(255) | Exited | — | 55432 | anonymous vol | مؤقت؛ لا تشغيل |
| `wsa-enterprise-postgres-1-orphan` | Exited(0) | Exited | — | — | **نفس postgres_data** | يتيمة؛ لا تشغيل |

### الملخص العددي
| المقياس | العدد |
|---|---|
| إجمالي الحاويات | **10** |
| عاملة | **3** |
| متوقفة عمدًا | **7** |
| فشل تشغيل في هذه الجلسة | **0** |

### الخدمات الأساسية
PostgreSQL + Redis + Backend: **Running و healthy**، واتصالات التطبيق إلى DB/Redis **نجحت**.

### قابلية الاستخدام
**تشغيل جزئي مطابق لحالة 15 أكتوبر الأساسية** (postgres + redis + backend).  
الوصول عبر متصفح عبر nginx:8079 **غير متاح** حتى تشغيل nginx/frontend بموافقة لاحقة.  
واجهة PHP-FPM داخل الشبكة تعمل؛ لا يوجد نشر HTTP للمضيف من backend مباشرة في التكوين الحالي.

### اختلافات عن التاريخ / فجوات معروفة
1. `cghia_capability_cell_records` ما زال غائبًا (لا migrations في هذه المهمة).  
2. nginx/frontend/queue/scheduler لم تُستعد (قرار متعمد).  
3. Windows PG17 ما زال Stopped وStartType=Automatic.  
4. تحذير غير حرج: امتداد `intl` ناقص يؤثر على بعض أوامر العرض فقط.

---

## الخطوة التالية المقترحة (تتطلب موافقة)
- تشغيل `nginx` (+ `frontend` إن لزم) لمسار الويب على **8079**، أو  
- قرار منفصل بشأن جدول CapVer المفقود (migrations — موافقة صريحة)، أو  
- تشغيل queue/scheduler إن احتجتَ مهامًا خلفية.
