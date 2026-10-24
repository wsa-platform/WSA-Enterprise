<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17206). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير استعادة PostgreSQL / CapVer (v4.4)

**الحالة الإجمالية:** طبقة DNS أُصلحت؛ المسار متوقف عند مصادقة الدور `wsa`. CapVer الحي والقراءة المستقلة **لم يُثبتا**.  
**HEAD:** `f2025315…` · **الفرع:** `phase-18-m18-ai-marketing-communications` · **ADR:** §8.58  
**Commit/Push:** لم يُنفَّذا.

---

## ما فُحص / نُفّذ / نجح

| المرحلة | النتيجة |
|---|---|
| Gate A — Git/WIP/PHP | `VERIFIED` — HEAD ثابت؛ ~150 WIP؛ PHP 8.4.26؛ `pdo_pgsql` |
| جاهزية الخادم | `VERIFIED` — `127.0.0.1:5432` accepting؛ اسم `postgres` بلا DNS |
| تشخيص قبل الإصلاح | `VERIFIED` — `dns_name_resolution_failure` |
| إصلاح منفَّذ | **`DB_HOST`: `postgres` → `127.0.0.1`** (تغيير واحد فقط) |
| تشخيص بعد الإصلاح | `VERIFIED` — DNS/TCP ناجحان؛ `authentication_or_authorization_failure` |
| مسبار PDO كـ `wsa` | `VERIFIED` فشل مصادقة — الدور موجود؛ كلمة مرور `.env` غير متطابقة |
| وحدات CapVer (mock) | `VERIFIED` — 4/38 |
| وجود قاعدة `wsa_enterprise` | `NOT_VERIFIED` (محجوب بالمصادقة) |
| اتصال Laravel / migration / CapVer حي | `BLOCKED` / `PENDING` / `NOT_VERIFIED` |
| GBIF في CapVer الافتراضي | غير مشمول (`G1-02,G6-20,G1-01,G1-17` فقط) |

LocalIndex SQLite ما زال reachable — **لا يُحتسب** إثبات Cap Store.

---

## البوابات

| بوابة | الحالة |
|---|---|
| A | `VERIFIED` |
| B | جزئياً ثم **`BLOCKED`** عند المصادقة |
| C–E | متوقفة على B |
| F | مكتملة توثيقاً (§8.58 + هذا التقرير) |

**أول إيقاف كامل:**  
`BLOCKED — awaiting approval to rotate wsa password`  
(أو تحديث `DB_PASSWORD` ليطابق الدور دون طباعة السر — دون تدوير من الوكيل لأن الدور موجود والمصادقة فشلت، وفق §6.6.3)

لا تتوفر كلمة مرور إدارية لـ `postgres` في بيئة التنفيذ (`PGPASSWORD` فارغ؛ لا `pgpass`).

---

## الملفات التي لمسها هذا التنفيذ

- `backend/.env` (gitignored): `DB_HOST` فقط  
- `backend/e2e-tmp/_v44_pg_probe.php`: مسبار  
- `ADR-023` §8.58  
- `…/2026-10-19-POSTGRES-CAPVER-RECOVERY-V44-REPORT.ar.md`  
- بقية WIP: لم تُحذف

---

## ما لم يُنفَّذ (صواباً)

`migrate` · `capver-accepted` حي · قراءة مستقلة · تدوير كلمة المرور · إنشاء قاعدة · Docker · Commit/Push

---

## الخطوة التالية (تتطلب موافقتك — غير منفَّذة)

1. تدوير `\password wsa` تفاعلياً ثم مزامنة `DB_PASSWORD`، **أو** تصحيح `DB_PASSWORD` إن كانت كلمة المرور الصحيحة معروفة.  
2. `scientific:diagnose-persistence` حتى `reachable=true`.  
3. migration المحددة لـ Cap Store فقط.  
4. `scientific:capver-accepted` ثم قراءة بـ `adr_id` / `capability_record_id`.  

**توقف قبل Commit وقبل Push.**
