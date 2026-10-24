<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17355). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير فحص جنائي — غياب `cghia_capability_cell_records`

**نطاق:** تحليل فقط. لم تُنفَّذ migrations، ولم يُغيَّر مخطط/بيانات، ولم تُوقَف أو تُعاد تشغيل أي حاوية.

---

## 1) الخلاصة المدعومة بالأدلة

**السيناريو الأقرب والأقوى دعمًا:** ملف الـ migration موجود في المستودع منذ 2026-10-09، لكن **لم يُطبَّق على قاعدة Docker الحالية** (`wsa_enterprise` على Volume `wsa-enterprise_postgres_data`).

| احتمال | الدعم |
|---|---|
| **Migration لم تُنفَّذ** | **مدعوم بقوة:** الملف موجود؛ غير مسجّل في `migrations`؛ الجدول غير موجود؛ آخر migration مطبّقة `2026_09_21_…`؛ كل ملفات أكتوبر 2026 غير مسجّلة |
| الجدول أُنشئ ثم أُزيل | **غير مدعوم:** لا صف `cghia`/`drop` في `migrations`؛ لا جداول `cghia_*` متبقية |
| الجدول غير مطلوب معماريًا أصلًا | **مرفوض للكود/ADR الحالي:** النموذج والمستودع وCapVer يتوقعون الجدول؛ ADR يذكره صراحة |
| يُنشأ بطريقة أخرى غير migration | **غير مدعوم:** المصدر الوحيد في المشروع هو `Schema::create` في migration أكتوبر |

لا يمكن من الأدلة الحالية إثبات *لماذا* لم تُشغَّل migrations أكتوبر على هذا الـ Volume (قرار تشغيلي، بيئة منفصلة، نسيان، إلخ) — فقط أن **التطبيق لم يحدث**.

---

## 2) ما يثبته كود المشروع

| الدليل | النتيجة |
|---|---|
| Migration | `backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php` — `Schema::create('cghia_capability_cell_records', …)` + فهرسان جزئيان فريدان لـ CURRENT |
| Git | متتبَّع؛ commit `31a6ee3` بتاريخ **2026-10-09** — `feat(research): persist capability store facts` |
| النموذج | `App\Models\CghiaCapabilityCell` → `protected $table = 'cghia_capability_cell_records'` |
| المستودع | `EloquentCapabilityStoreRepository` يقرأ/يكتب عبر هذا النموذج |
| CapVer | `CapVerSeatVerificationService`: فشل المتجر → `STORE_UNAVAILABLE`؛ **لا يُعدّ نجاح تفعيل** بدون `PERSISTED`/`ALREADY_CURRENT` |
| أوامر | `scientific:capver-accepted`، `scientific:diagnose-persistence` (يفحص وجود الجدول) |

Migrations أكتوبر على القرص (غير مطبّقة في DB أيضًا كحزمة):

- `2026_10_01_…durable_correlation…`
- `2026_10_02_…cghia_identity_binding…`
- `2026_10_02_…cghia_integration_classification…`
- `2026_10_09_140000_…cghia_capability_cell_records…` ← الجدول موضع الفحص
- `2026_10_09_190000_…integration_classification index…`
- `2026_10_15_…scientific_source_record_store…` (**غير متتبَّع** في git: `??`)

ملفات migration على القرص: **63**؛ صفوف `migrations` في DB: **55**.

---

## 3) ما تثبته قاعدة البيانات (قراءة فقط)

| فحص | نتيجة |
|---|---|
| `current_database` / `current_user` / إصدار | `wsa_enterprise` / `wsa` / **16.14** |
| عدد صفوف `migrations` | **55** |
| أول→آخر migration مسجّلة | `0001_01_01_000000_create_users_table` → **`2026_09_21_140000_create_research_feedback_dataset_table`** |
| تسجيل `2026_10_09_140000_create_cghia_capability_cell_records_table` | **0** |
| أي migration فيها `cghia` / `capabilit` / `capver` | **لا شيء** |
| وجود الجدول | **`f`** |
| جداول `public` تطابق `%cghia%` أو `%capabilit%` | **لا شيء** |
| جداول scientific / durable_correlation / identity_binding من أكتوبر | **لا شيء** |
| Migrations `2026_10_%` المسجّلة | **لا شيء** |

الاستعلامات المستخدمة: `SELECT` على `migrations` و`information_schema.tables` و`current_database`/`current_user`/`server_version` فقط — داخل `wsa-enterprise-postgres-1`.

---

## 4) ما تنص عليه ADR / العقود

- **§8.16 (CPD-A–D):** تصميم Cap Store؛ في حينه لم يكن يصرّح بالتنفيذ وحده.
- لاحقًا وُجد التنفيذ والـ migration في المستودع (commit 9 أكتوبر).
- **§8.58 (2026-10-19):** صرّح صراحة أن خطوة الـ migration للجدول كانت **`PENDING` / لم تُنفَّذ** (بسبب فشل الاتصال آنذاك)، والخطوة التالية المقترحة كانت diagnose → migration موجّه → CapVer.
- نقطة الاستعادة التشغيلية (§8.59): أعادت البيئة؛ **لم تنفّذ migrations**؛ سجّلت غياب الجدول كفجوة معروفة.

---

## 5) أثر الغياب على التطبيق (وفق الأدلة فقط)

| المسار | الأثر المثبت |
|---|---|
| موقع HTTP العام (`/`، `/api/v1/health`، `/up`) | يعمل دون اعتماد ظاهر على هذا الجدول في فحوص الاستعادة |
| مسار CapVer / Capability Store الحي | لا يمكن إكمال **activation-pass**؛ المتوقع `STORE_UNAVAILABLE` عند فشل الكتابة/القراءة |
| fail-closed في التعايش | الكود مصمَّم لعدم اعتبار غياب المتجر نجاح تفعيل |

**هل الجدول مطلوب الآن؟**  
- **لتفعيل CapVer وكتابة/قراءة Cap Store:** نعم وفق العقد والكود.  
- **لتشغيل الواجهة الأساسية الحالية على 8079:** غير مثبت كمتطلب حرج للتشغيل العام.  
الوضع: مطلوب لمسار CapVer؛ غير محسوم كـ «عطل تشغيل عام» لأن التطبيق يعمل fail-closed على هذا المسار.

---

## 6) خيارات مستقبلية (بدون تنفيذ)

1. **موافقة منفصلة** على تشغيل migration موجّه للملف `2026_10_09_140000_…` فقط (بعد نسخة احتياطية معتمدة)، ثم تحقق قراءة للجدول.  
2. مراجعة حزمة migrations أكتوبر غير المطبّقة كاملةً (correlation / identity / IC / Cap Store / scientific store) وخطة ترتيب آمنة — بعضها قد يعتمد على بعض.  
3. الإبقاء على الوضع الحالي إن كان القرار تأجيل CapVer الحي، مع الإبقاء على `STORE_UNAVAILABLE` كسلوك متوقع.  
4. **لا يُنصح** بإنشاء الجدول يدويًا خارج Laravel migrations، ولا بتشغيل الحاوية اليتيمة، ولا بـ restore فوق الـ Volume الحي دون خطة معزولة.

---

## 7) الملفات المفحوصة

- `backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php` (+ قائمة `*cghia*` و`2026_10_*`)
- `backend/app/Models/CghiaCapabilityCell.php`
- `backend/app/Services/Agriculture/Research/Capability/Persistence/EloquentCapabilityStoreRepository.php`
- `backend/app/Services/Agriculture/Research/Capability/CapVer/CapVerSeatVerificationService.php`
- `backend/app/Console/Commands/DiagnoseScientificPersistenceCommand.php` / `CapVerAcceptedSeatCommand.php`
- ADR-023 (§8.16، §8.58، إشارات الجدول)
- Git: `git log` / `git ls-files` على migration Cap Store

---

## 8) تأكيد السلامة

الخدمات السبع ما زالت **Up (healthy)** وقت الفحص. لم يُنفَّذ `migrate`/`migrate:status`، ولا DDL/DML، ولا تعديل ملفات، ولا مهمة النسخ الاحتياطية.

**توقف هنا — أي إنشاء للجدول أو migrate يحتاج موافقة منفصلة صريحة.**
