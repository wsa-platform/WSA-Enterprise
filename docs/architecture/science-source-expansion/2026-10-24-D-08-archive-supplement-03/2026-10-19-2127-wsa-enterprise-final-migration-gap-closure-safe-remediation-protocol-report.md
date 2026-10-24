<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17378). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير إغلاق فجوة Migrations — توقف عند بوابة الموافقة

**التفويض المستخدم:** تشخيص وقراءة فقط.  
**لم يُنفَّذ:** نسخة احتياطية جديدة، ولا `migrate`، ولا تعديل `.env`/حاويات، ولا تحديث ADR بعد إصلاح (لا إصلاح بعد).

---

## A. ملخص الحالة

| البند | الدليل الحالي |
|---|---|
| التطبيق | `http://127.0.0.1:8079/api/v1/health` → **200** `{"status":"ok"}` |
| حاويات أساسية | 7 × Up (healthy): postgres/redis/backend/frontend/nginx/queue/scheduler |
| PostgreSQL | `wsa-enterprise-postgres-1` + Volume `wsa-enterprise_postgres_data` |
| الهوية | DB=`wsa_enterprise`، user=`wsa`، ver=**16.14**، Backend HOST=`postgres`، APP_ENV=`local` |
| Cap Store على PG | **غير موجود** — لم يُصلَح |
| فجوة العدد | القرص **63** / DB **55** → فرق **8** (CONFIRMED) |

---

## B. سلامة البيئة والاتصال

| فحص | نتيجة | الثقة |
|---|---|---|
| dotenv | لا orphan بلا `=`؛ لا تكرار DB_* | CONFIRMED |
| تكرار | `FAOSTAT_ENABLED` سطران 73–74 | CONFIRMED |
| config cache | غير موجود | CONFIRMED |
| Backend↔PG | `wsa_enterprise` / `postgres` / `wsa` / `pgsql` | CONFIRMED |
| `DB_PASSWORD` vs `POSTGRES_PASSWORD` (حاويات) | **MATCH** | CONFIRMED |
| أدوات النسخ داخل الحاوية | `pg_dump`/`pg_restore` **16.14** متوفران | CONFIRMED |
| نسخة حديثة معتمدة من Volume الحي | **غير موجودة / لم تُنشأ** (بلا موافقة) | CONFIRMED |

---

## C. جرد Migrations — الثمانية كاملة

`artisan migrate:status` أظهر **8 Pending** بالضبط:

### الملفان اللذان لم يُسمَّيا في التقرير السابق

| # | Migration | Git | في DB | يخلق/يغيّر | التفسير |
|---|---|---|---|---|---|
| 1 | `2026_09_23_180000_add_platform_administrator_identity_to_users` | tracked (`75614b4`) | Pending | `users.is_platform_administrator` | من موجة platform admin؛ **لم تُطبَّق** على Volume |
| 2 | `2026_09_23_180100_create_platform_rbac_tables` | tracked (`75614b4`) | Pending | `platform_permissions/roles/.../settings` + FK إلى `users` | كذلك؛ يعتمد على وجود `users` (موجود) |

### الستة المعروفة سابقًا

| Migration | Git | DB | Creates/Changes | Dependencies | Approval Status | Risk | Recommended Action |
|---|---|---|---|---|---|---|---|
| `…durable_correlation…` | tracked | Pending | `durable_correlation_records` | مستقلة | **BLOCKED** (نسخة+موافقة تنفيذ) | منخفض (CREATE) | بعد البوابة: `--pretend` ثم migrate ملف واحد |
| `…identity_binding…` | tracked | Pending | `cghia_identity_binding_records` | مستقلة | BLOCKED | منخفض | كذلك |
| `…integration_classification…` | tracked | Pending | `cghia_integration_classification_records` | مستقلة | BLOCKED | منخفض | كذلك |
| `…cghia_capability_cell…` | tracked | Pending | Cap Store + partial UQs | مستقلة عن جداول cghia | BLOCKED | منخفض هيكليًا؛ حرج لـ CapVer | بعد IC اختياريًا؛ مستقل فعليًا |
| `…single_active_index…` | tracked | Pending | فهرس جزئي على IC | **يتطلب جدول IC** | BLOCKED_BY_DEPENDENCY حتى ينجح IC | فشل إن ACTIVE مكررة | بعد IC فقط |
| `…scientific_source_record_store…` | **`??` untracked** | Pending على PG | `scientific_source_*` | مستقلة هيكليًا | **REQUIRES_OWNER_DECISION** | ازدواج مع SQLite الحي | **لا تُطبَّق على PG** قبل قرار SoT |

**لا سجلات في DB بلا ملف على القرص** (0 orphan records).

---

## D. الجداول اليتيمة

فحص `information_schema` للجداول المتوقعة من الملفات الثمانية: **لا صفوف** → لا كائنات مخطط يتيمة متعارضة على PG.  
`users.is_platform_administrator` = **f** (العمود غير موجود).

تصنيف: لا `ORPHAN_SCHEMA_OBJECT` للجداول المستهدفة — CONFIRMED.

---

## E. الاعتماديات (ترتيب منطقي عند الموافقة لاحقًا)

```text
[users موجودة]
    → 09-23 platform admin column
    → 09-23 platform RBAC (FK → users)
[مستقلة]
    → 10-01 durable_correlation
    → 10-02 identity_binding
    → 10-02 integration_classification
         → 10-09 IC single-active index
    → 10-09 capability_cell (مستقل عن الثلاثة أعلاه)
[قرار مالك]
    → 10-15 scientific_* على PG؟ (SoT الحالي = SQLite LocalIndex)
```

لا يُنفَّذ شيء من هذا الترتيب الآن.

---

## F. CapVer

| البند | الحالة |
|---|---|
| سبب الغياب | Migration Pending وغير منفَّذة — CONFIRMED |
| هل أُصلح؟ | **لا** |
| اختبار وحدة Mock | سابقًا OK 4/38 — لم يُعد في هذه الجولة كشرط إغلاق |
| مسار حي persist | **NOT TESTED** / محظور بلا موافقة |

---

## G. Scientific Persistence

| مخزن | SoT الحالي |
|---|---|
| SQLite `scientific_record_store.sqlite` | جداول `scientific_source_*` + FTS؛ migration مسجّلة داخل SQLite؛ ~300k سجل |
| PG `wsa_enterprise` | لا جداول scientific_source_* |
| Mission archive SQLite | منفصل |

تطبيق ملف أكتوبر 15 على PG = مخزن موازٍ محتمل → **قرار مالك**.

---

## H. النسخة الاحتياطية

| البند | نتيجة |
|---|---|
| أدوات داخل postgres container | متاحة (16.14) |
| نسخة حديثة من القاعدة الحية | **لم تُنشأ** — تتطلب موافقة صريحة (§8.2) |
| Render 11 سبتمبر | مرجع تاريخي فقط؛ **ليس** بديلًا لبوابة ما قبل migrate |
| SHA / استعادة معزولة | **NOT TESTED** هذه الجولة |

**بوابة النسخة: غير مستوفاة → أي migrate على الحي ممنوع.**

---

## I. سجل التنفيذ

| Migration | الحالة |
|---|---|
| الثمانية كلها | **NOT RUN** |
| أي منها `APPROVED_FOR_EXECUTION`؟ | **لا** — تنقص النسخة + موافقة التنفيذ (+ قرار للملفين الإداريين/العلمي) |
| Platform admin ×2 | **REQUIRES_OWNER_DECISION** (نطاق admin منفصل عن CapVer) |
| CGHIA/CapVer ×5 (بدون scientific) | **BLOCKED** ببوابة النسخة/الموافقة |
| Scientific `??` | **REQUIRES_OWNER_DECISION** |
| `--pretend` | **NOT RUN** (يُحجز لما بعد الموافقة على كل ملف) |

---

## J. التحقق بعد التنفيذ

لا ينطبق — لم يُنفَّذ شيء. Health الحالي **200** قبل أي تغيير.

---

## K. تحديث ADR

آخر قسم: **§8.59**. التالي سيكون **§8.60** بعد نجاح إصلاح موثّق فقط.  
**لم يُحدَّث ADR** — لا نجاح إصلاح يستحق الإلحاق.

---

## L. تأكيد السلامة

| العنصر | دون تغيير؟ |
|---|---|
| مخطط/بيانات PG | نعم (قراءة + `migrate:status` فقط) |
| الحاويات | نعم (لا start/stop) |
| `.env` | نعم |
| WIP / `??` scientific | نعم |
| Git commit/push/clean | لم يحدث |
| backups/ جديدة | لم تُنشأ |

---

## بوابة الموافقة — ما يلزم قبل أي إغلاق فعلي

1. **موافقة صريحة على `pg_dump` من الحاوية** إلى مسار مضيف معروف + تسجيل SHA-256 و`--list`.  
2. **موافقة صريحة على نطاق التنفيذ**، مقترَحًا منفصلًا:  
   - (أ) platform admin Sept 23 فقط، أو  
   - (ب) سلسلة CGHIA/CapVer أكتوبر فقط، أو  
   - (ج) كلاهما بالترتيب أعلاه.  
3. **قرار مالك** بشأن scientific migration على PG (الافتراضي الموصى به: **لا**).  
4. بعد كل ذلك فقط: `--pretend` لملف واحد → migrate → تحقق → التالي.  
5. ثم إلحاق ADR §8.60 بالنتائج الفعلية فقط.

**توقفت هنا.** الفجوة العددية مفسَّرة بالكامل (8 ملفات = 2 منصة إدارية + 5 بحث/CapVer متتبّعة + 1 علمي غير متتبّع). لم يُغلَق شيء على القاعدة الحية لأن بوابات الأمان والموافقة غير مكتملة.
