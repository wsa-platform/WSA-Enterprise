<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17300). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير استعادة حالة التشغيل التاريخية — 2026-10-15

**الوضع:** تشخيص وخطة فقط. لم يُنفَّذ تشغيل أو إيقاف أو تعديل ملفات أو Volumes.

---

## 1) الحالة التاريخية المثبتة بتاريخ 15 أكتوبر 2026

### ما كان يعمل فعليًا ذلك اليوم
من `StartedAt` / `FinishedAt` وسجلات الإيقاف المتزامنة:

| الخدمة | الحاوية | نافذة 15 أكتوبر (UTC) | النتيجة |
|---|---|---|---|
| Redis | `wsa-enterprise-redis-1` | 11:32:00 → 18:43:20 | Exit 0، إيقاف نظيف + حفظ RDB |
| PostgreSQL 16 | `wsa-enterprise-postgres-1` | 11:32:01 → 18:43:20 | Exit 0، `database system is shut down` |
| Backend | `wsa-enterprise-backend-1` | 11:32:02 → 18:43:20 | Exit 0، PHP-FPM أُغلق بشكل طبيعي |

الإيقاف حوالي **18:43 UTC** يبدو إيقافًا منسّقًا للمكدس الأساسي (ليس crash عشوائيًا لـ postgres وحده).

### ما لم يكن جزءًا من جلسة 15 أكتوبر
| الخدمة | آخر تشغيل معروف | ملاحظة |
|---|---|---|
| `nginx` / `frontend` | 2026-10-01 | متوقفان قبل أسبوعين من 15 أكتوبر |
| `queue` / `scheduler` | 2026-09-18 | Exit 137؛ لم يعملا في 15 أكتوبر |

**الاستنتاج التشغيلي:** حالة «15 أكتوبر» الموثّقة = **`postgres` + `redis` + `backend`** على شبكة `wsa-enterprise_wsa`، وليس بالضرورة المكدس الكامل مع nginx/frontend/queue/scheduler.

### الإعدادات التقنية لذلك اليوم
| البند | القيمة المثبتة |
|---|---|
| صورة Postgres | `postgres:16-alpine` (16.14) |
| قاعدة / مستخدم | `wsa_enterprise` / `wsa` |
| Volume البيانات | `wsa-enterprise_postgres_data` |
| منفذ Postgres للمضيف | **5432→5432** |
| Redis للمضيف | 6379→6379 |
| شبكة Compose | `wsa-enterprise_wsa` |
| سياسة restart | `unless-stopped` (للخدمات الأساسية) |
| اتصال Backend داخل Docker | `DB_HOST=postgres` (تجاوز Compose لـ `.env`) |
| ملف Compose | مطابق لما هو اليوم: **لم يتغير منذ 2026-09-18** (`COMPOSE_UNCHANGED_SINCE_OCT15=True`) |

### Volume
- مرتبط بحاويتين **متوقفتين فقط**: `wsa-enterprise-postgres-1` و`…-orphan`.
- **لا حاوية عاملة** تستخدمه الآن (`RUNNING_ANY` فارغ).
- سلامة المحتوى الداخلي للجداول **لم تُثبت بعد** (الإيقاف النظيف ≠ إثبات مخطط/بيانات التطبيق).

---

## 2) الاختلافات عن الحالة الحالية

| المحور | 15 أكتوبر (التشغيل) | الآن |
|---|---|---|
| حاويات المكدس الأساسي | عاملة | كلها `exited` |
| من يستمع على 5432 | حاوية Docker Postgres 16 | **PostgreSQL 17 على Windows** (`postgresql-x64-17`) |
| Laravel على المضيف (`.env`) | غير ذي صلة داخل الشبكة؛ الـ backend داخل Docker يستخدم `postgres` | `DB_HOST=127.0.0.1` → يصل لـ Windows PG ويفشل بمصادقة `wsa` |
| nginx/frontend | غير عاملين أصلًا في 15 أكتوبر | ما زالا متوقفين (آخر نشاط 1 أكتوبر) |
| queue/scheduler | غير عاملين في 15 أكتوبر | متوقفان منذ سبتمبر |
| `docker-compose.yml` | نفس المحتوى الحالي | لا فرق تكويني في الملف |
| حاوية يتيمة | موجودة ومتوقفة | موجودة ومتوقفة — خطر إن شُغّلت مع الأصلية |

---

## 3) خطة استعادة آمنة (خطوة بخطوة — للتنفيذ لاحقًا فقط)

### إجابات مباشرة لأسئلة الخطة
| سؤال | الجواب |
|---|---|
| هل يمكن `docker start` للحاوية الأصلية دون إعادة إنشائها؟ | **نعم تقنيًا** لـ `wsa-enterprise-postgres-1` / `redis` / `backend` كما هي. |
| هل يلزم تحرير 5432؟ | **نعم** إذا أُريدت نفس خريطة المنافذ التاريخية (5432→5432). وإلا سيفشل ربط المنفذ. |
| أثر إيقاف PostgreSQL 17 على Windows؟ | يحرّر 5432؛ أي أدوات/جلسات محلية على تلك الخدمة تتوقف. **لا يمس** Volume Docker. يتطلب موافقة صريحة. |
| منفذ بديل مؤقت دون تغيير بيانات المشروع؟ | **نعم للبيانات** (نفس Volume)، لكن يتطلّب **إعادة إنشاء** تعريف المنافذ (الحاوية الحالية مثبتة على 5432) أو حاوية تحقق مؤقتة — وهذا مسار تحقق/انحراف عن «نفس حالة 15 أكتوبر» الحرفية. |
| ترتيب التشغيل المقترح | 1) تحرير 5432 عند اختيار المسار التاريخي → 2) postgres فقط → تحقق قراءة → 3) redis → 4) backend → 5) اختياريًا باقي الخدمات |
| تجنّب تشغيل حاويتي نفس الـ Volume معًا | **لا تشغّل أبدًا** `wsa-enterprise-postgres-1-orphan` أثناء عمل الأصلية. اترك اليتيمة `exited`. |

### المسار أ — أقرب لاستعادة حالة 15 أكتوبر (موصى به بعد الموافقة)
1. موافقة على إيقاف/تعطيل مؤقت لـ `postgresql-x64-17` لتحرير 5432.  
2. تشغيل `wsa-enterprise-postgres-1` فقط والتحقق قراءةً من الجداول.  
3. تشغيل `wsa-enterprise-redis-1`.  
4. تشغيل `wsa-enterprise-backend-1` (يتوقع `DB_HOST=postgres` داخل الشبكة).  
5. عدم تشغيل اليتيمة.  
6. nginx/frontend/queue/scheduler: **اختياري لاحقًا**؛ لم تكن جزءًا من جلسة 15 أكتوبر الموثّقة.

### المسار ب — تحقق Volume بمنفذ بديل دون إيقاف Windows PG
مناسب كبوابة تحقق فقط، وليس مطابقة حرفية لحالة 15 أكتوبر (انظر تقرير التحقق السابق: منفذ مثل 15432). بعد التحقق يمكن الانتقال للمسار أ.

### ما لا يُستخدم في الاستعادة
- لا `compose down` / لا `-v`.  
- لا `restore` من نسخة Render.  
- لا migrations.  
- لا حذف اليتيمة أو الـ Volume.  
- لا تشغيل المكدس كاملًا قبل نجاح فحص القاعدة.

---

## 4) المخاطر والتبعيات

| المخاطر | التفاصيل |
|---|---|
| تعارض المنفذ 5432 | تشغيل الحاوية الأصلية دون تحرير 5432 → فشل الإقلاع أو تعارض |
| فساد محتمل غير مكتشف | لم يُفحص بعد وجود `migrations` / CapVer داخل الـ Volume |
| تشغيل اليتيمة + الأصلية | خطر فساد بيانات إن عملتا على نفس البيانات معًا |
| `unless-stopped` | بعد `start` ناجح، قد تُعاد الحاويات تلقائيًا بعد إعادة تشغيل Docker Desktop |
| كلمة مرور / مصادقة | كلمة مرور Compose للحاوية قد تختلف عن `DB_PASSWORD` في `.env` على المضيف؛ داخل Docker يعتمد backend على بيئة الحاوية |
| إيقاف Windows PG | يقطع أي اعتماد محلي على تلك الخدمة (مثل جلسة `psql` الحالية على 5432) |
| `compose up` العريض | قد يحاول إقلاع خدمات غير مرغوبة أو إعادة إنشاء غير مقصودة — يُفضَّل `docker start` الانتقائي للحاويات الموجودة |

---

## 5) الأوامر المقترحة (للموافقة فقط — لا تُنفَّذ الآن)

### أ) تحرير 5432 (موافقة صريحة مطلوبة)
```powershell
# أثر: إيقاف خدمة PostgreSQL 17 على Windows وتحرير المنفذ 5432.
# لا يمس Docker volumes. يقطع الاتصالات المحلية لهذه الخدمة فقط.
Stop-Service postgresql-x64-17
# تحقق:
Get-NetTCPConnection -State Listen -LocalPort 5432 -ErrorAction SilentlyContinue
```

### ب) تشغيل Postgres الأصلي فقط
```powershell
# أثر: يعيد تشغيل الحاوية الموجودة؛ يفتح 5432→5432؛ يستخدم wsa-enterprise_postgres_data.
# لا ينشئ Volume جديدًا ولا ينفّذ restore.
docker start wsa-enterprise-postgres-1
docker exec wsa-enterprise-postgres-1 pg_isready -U wsa -d wsa_enterprise
```

### ج) فحوص قراءة فقط داخل الحاوية
```powershell
docker exec wsa-enterprise-postgres-1 psql -U wsa -d wsa_enterprise -c "SELECT current_database(), current_user;"
docker exec wsa-enterprise-postgres-1 psql -U wsa -d wsa_enterprise -c "SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='migrations') AS has_migrations;"
docker exec wsa-enterprise-postgres-1 psql -U wsa -d wsa_enterprise -c "SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='cghia_capability_cell_records') AS has_cghia;"
```

### د) إعادة Redis ثم Backend (بعد نجاح ج)
```powershell
# أثر: يعيد حالة 15 أكتوبر للمكدس الأساسي على شبكة Compose.
docker start wsa-enterprise-redis-1
docker start wsa-enterprise-backend-1
# تحقق صحة تقريبي:
docker ps --filter name=wsa-enterprise-postgres-1 --filter name=wsa-enterprise-redis-1 --filter name=wsa-enterprise-backend-1
```

### هـ) ممنوع في هذه الاستعادة
```text
docker start wsa-enterprise-postgres-1-orphan   # خطر مشاركة Volume
docker compose down / down -v                  # خطر إزالة/إعادة إنشاء
docker compose up                              # نطاق أوسع من حالة 15 أكتوبر
أي migrate / pg_restore / تعديل .env           # خارج نطاق الاستعادة الآمنة الأولى
```

### و) مسار بديل للتحقق فقط (إن رُفض إيقاف Windows PG)
استخدام `docker run` بمنفذ مضيف بديل على **نفس** الـ Volume — سبق اقتراحه؛ ينحرف عن المنافذ التاريخية ويحتاج موافقة منفصلة، ثم لاحقًا المسار أ للمطابقة.

---

## 6) ما يتطلب موافقة صريحة قبل التنفيذ

1. **إيقاف خدمة `postgresql-x64-17` على Windows** (أو أي تغيير لمنفذها).  
2. **`docker start` لأي حاوية مشروع** (`postgres` / `redis` / `backend` أو غيرها).  
3. أي **`docker run`** لحاوية تحقق مؤقتة.  
4. تشغيل `nginx` / `frontend` / `queue` / `scheduler` (خارج نطاق جلسة 15 أكتوبر الموثّقة).  
5. أي تعديل `.env` لربط المضيف بالقاعدة بعد الاستعادة.  
6. أي migrations أو restore أو حذف يتيم/Volume.

---

## خلاصة

حالة **15 أكتوبر** المثبتة هي مكدس **`postgres` + `redis` + `backend`** على Volume الأصلي، بإيقاف نظيف متزامن ~18:43 UTC. التكوين في `docker-compose.yml` لم يتغير منذ ذلك الحين. العائق الحالي الرئيسي لإعادة نفس الحالة هو **احتلال Windows PostgreSQL 17 للمنفذ 5432**، مع بقاء `.env` على المضيف موجّهًا لهذا الخادم وليس لحاوية المشروع.

**توقفت هنا بانتظار موافقتك الصريحة** قبل أي إيقاف لخدمة Windows أو أي `docker start` / تشغيل.
