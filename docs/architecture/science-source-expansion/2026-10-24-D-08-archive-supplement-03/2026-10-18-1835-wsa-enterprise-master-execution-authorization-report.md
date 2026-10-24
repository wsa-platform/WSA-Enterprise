<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17104). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# WSA-Enterprise — تقرير هندسي شامل: تفعيل المصادر الخمسة

**الفرع:** `phase-18-m18-ai-marketing-communications` · **HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0` · **ADR:** §8.55  
**التقرير الكامل:** `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-FIVE-SOURCE-FULL-ACTIVATION-REPORT.ar.md`

## A. الملخص التنفيذي

نُفِّذ عمل هندسي فعلي (ليست خطة فقط): تمكين `pdo_pgsql`، إصلاح دلالات CapVer، أدوات LocalIndex، استئناف حصاد AGRIS، إسقاط Duke موسّع مع فصل FARMACY/FARMACY_NEW. **التفعيل الإنتاجي الكامل للمصادر الخمسة لم يُغلق** لأن مضيف Cap Store `postgres` غير قابل للوصول ولا يوجد PostgreSQL محلّي مصرّح (Docker ممنوع؛ تثبيت winget تطلّب موافقة ولم يُكتمل).

| المصدر | الحالة |
|---|---|
| AGRIS | `ACTIVE` · GEN=6 / **17223** نشط · **27/1244** مزوّد — ليس `CAPABILITY_PERSISTED` ولا `FULL_SCOPE_COMPLETE` ولا `PRODUCTION_READY` |
| Dr. Duke | `ACTIVE` · GEN=10 / **42376** مفهرس · أثر كامل **211961** على القرص — ليس `CAPABILITY_PERSISTED` ولا فهرسة كاملة للأثر 211961 |
| OE / BHL / GBIF | `ACTIVE` محفوظة · CapVer عقد مؤلَّف · تخزين القدرة **غير مُثبَّت** |

## B. PostgreSQL Capability Store

- **الجذر:** بعد تمكين السائق، الفشل = `could not translate host name "postgres"` (لا خدمة على 5432).
- **الدلالة:** `STORE_UNAVAILABLE` ⇒ `activation_pass=no` و`capability_persisted=false` (إصلاح كود + اختبارات).
- **التنفيذ:** `scientific:capver-accepted` → المقاعد الأربعة: `contract_verified=yes` / `store=STORE_UNAVAILABLE` / `activation_pass=no` · EXIT=1.
- **قراءة مستقلة / PERSISTED:** غير ممكنة دون مضيف PostgreSQL.

## C. Production LocalIndex

- الاتصال الفعلي: **sqlite** `scientific_records` (ليس إنتاج PostgreSQL).
- أدوات: `ensure-record-store` · `reconcile-record-store` · `--copy-to-pgsql` عند التوفر.
- جرد: AGRIS 17223 GEN=6 · Duke 42376 GEN=10 · OE 189 GEN=2 · **`PRODUCTION_READY` = لا**.

## D. AGRIS

- نطاق قانوني: ODS + CC-BY-4.0 لكل أثر · fail-closed.
- تقدم: **27/1244** · GEN=6 / 17223 · بحث `wheat` ناجح.
- هندسة: سقف CLI أعلى · إعادة محاولة 429 · مسح كامل لا يُسقط الفاشل نهائيًا عبر المؤشر.

## E. Dr. Duke

- مصالحة 16 جدولًا · FARMACY≠FARMACY_NEW (`merged=0`) · ETHNOBOT «ليس فعالية سريرية».
- `dr_duke_full_search.v1.jsonl` = 211961 · المفهرس المتماسك = 42376 من v3 · بحث `Mentha` ناجح.
- فهرسة 211961 دفعة واحدة تتطلب بث snapshot (التجزئة سحبت صفوفًا؛ أُعيدت الفهرسة المتماسكة).

## F. OE / BHL / GBIF

- CapVer: نفس حاصرة المتجر · `activation_pass=no`.
- OE: اختبارات مسار LocalIndex ناجحة.
- BHL: المفتاح لم يُطبع/يُؤرشف · تدوير غير مُنفَّذ.
- GBIF/BHL: فشلان توافقيان مسبقان حول توقع `licensing unresolved` مقابل أدلة معلنة.

## G–H. بنية مشتركة واختبارات

- CSQ محفوظ · طبقات التنزيل/التخزين المحلية مُعاد استخدامها · عزل فشل Cap Store عن ادّعاء النجاح.
- CapVer unit: EXIT=0 (4/38) · AGRIS/Duke حزمة: EXIT=0 (8/59) · CapVer الحي: EXIT=1 متوقع · diagnose: Cap Store غير متاح.

## I. المتبقي (أصغر إجراء خارجي)

1. مضيف PostgreSQL مصرّح → `diagnose-persistence` reachable=true  
2. ترحيل Cap Store + CapVer حتى PERSISTED + قراءة مستقلة  
3. `SCIENTIFIC_RECORD_STORE_DRIVER=pgsql` + ensure/copy  
4. مواصلة حصاد AGRIS تحت حدود المعدّل  
5. بث ingest لفهرسة Duke 211961

## J. سلامة Git

- لا commit / push / deploy / Docker / reset / clean.
- HEAD وWIP السابقان محفوظان.
- إيقاف عند ما قبل الـ commit بعد المراجعة.
