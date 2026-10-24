<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 16717). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# التدقيق التكاملي لطبقات Persistence واستراتيجية الاستفادة منها في تفعيل المصادر العلمية الـ110

**وضع التنفيذ:** فحص وتحليل فقط — بلا تعديل مستودع، بلا تفعيل، بلا commit/push/Docker.  
**HEAD المحلي وقت الفحص:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**فصل الحقائق عن الاستنتاجات:** `[V]` ثابت من الكود/الملفات الآن · `[P]` تقرير تاريخي · `[I]` استنتاج · `[U]` غير متحقَّق

---

## 1) الملخص التنفيذي

| الطبقة | الوظيفة | الحالة الفعلية الآن |
|---|---|---|
| **ScientificKnowledgePersistenceService** | حفظ معرفة بحثية مؤهلة في Library + تنزيل بايتات المصدر الأصلي (R3/R5) | **مربوطة تشغيليًا** عبر `AgriculturalResearchAgent`؛ ليست مسار استيعاب المصادر الـ110 |
| **Capability Store** | حقائق قدرات قابلة للدعم العلمي (CURRENT/HISTORICAL) | **منفَّذة ومختبرة** (`31a6ee3`)؛ **لا كاتب/قارئ إنتاجي**؛ CapVer **DEFERRED** |
| **IC Persistence** | لقطات تصنيف التكامل لكل `adr_id` | **منفَّذة ومختبرة**؛ DI مربوط؛ **لا مستهلك إنتاجي يكتب/يقرأ**؛ لا مخزون IC للمقاعد |
| **LocalIndex / ScientificRecordStore** | استيعاب bulk/OAI/static ثم بحث محلي | **كود + اختبارات**؛ مربوط في DI؛ **ليس مسار البحث الافتراضي** لمعظم المقاعد؛ فهرس فارغ → `UNAVAILABLE` |
| **Source Registry (Stage-3 catalog)** | تسجيل adapters قابلة للاختيار | **5 legacy + next-gate المفعَّلة**؛ حاليًا next-gate النشط المؤكد: **G5-04/gbif** فقط `[V/P]` |

**إعادة الاستخدام الأهم دون تكرار:** الإبقاء على فصل المسؤوليات (IC ≠ Cap ≠ Library ≠ LocalIndex)، وربطها لاحقًا ببوابات onboarding بدل دمجها في مخزن واحد.

**أكبر عوائق تفعيل الـ110:**  
(1) عضوية ≠ تفعيل — CURRENT قد يكون 110 بينما التشغيل الفعلي ≈ 5–6 مصادر.  
(2) CapVer ومخزون IC وbindings غير مكتملة.  
(3) معظم المقاعد بلا adapter بحث حي أو بلا ingest LocalIndex.  
(4) حفظ PDF في Library لا يثبت أهلية المصدر للبحث.

---

## 2) جدول مقارنة الطبقات

| الطبقة | المسؤولية | البيانات المحفوظة | واجهة الكتابة | واجهة القراءة | التخزين الفعلي | المستهلك التشغيلي | الاختبارات | الحالة | الاستفادة في التفعيل |
|---|---|---|---|---|---|---|---|---|---|
| ScientificKnowledgePersistence | ذاكرة مكتبة بعد تحقق الأدلة | `LibraryItem` + metadata + ملف أصلي اختياري | `persist()` | عبر نماذج Library / APIs عامة | DB + `Storage::disk('local')` مسار `research-sources/{org}/{slug}.{ext}` | `AgriculturalResearchAgent` | Phase8B2، PublicTenant، Stage10… | **نشطة للبحث المجاب عنه** | **لاحقة** بعد نجاح بحث+تحقق — ليست بوابة تفعيل مصدر |
| Capability Store | حقائق Cap للموضوع/البُعد | صفوف `cghia_capability_cell_records` | `persistCurrent` / `supersedeCurrent` | `findFact` / `findCurrentFact` / `loadCurrentForSubject` | PostgreSQL/SQLite جدول Cap | **لا أحد في `app/` خارج DI** `[V]` | `CapabilityStorePersistenceTest` | **عقد جاهز / تشغيل خامل** | شرط أهلية لاحق بعد CapVer+population |
| IC Persistence | تصنيف تكامل مقعد | `cghia_integration_classification_records` | `persist` / `supersede` / `invalidate` | `findCurrentByAdrId` وغيرها | جدول IC | **DI فقط — لا مستهلك** `[V/P]` | IC Persistence (+ Integrity) tests | **عقد جاهز / تشغيل خامل** | شرط D-10.8.1 قبل runtime binding |
| LocalIndex | بحث على سجلات محصودة | سجلات علمية + جيل فهرس | `LocalIndexIngestionService` | `LocalIndexScientificSourceAdapter::search` | DB store + فهرس ذاكرة/إعادة بناء | فقط إن سُجّل adapter في الكتالوج | LocalIndex unit + Pgsql | **جاهز تقنيًا؛ غير مأهول إنتاجيًا** | bulk/OAI/static بعد ingest مصرّح |
| Source Catalog | من يُسمح باستدعائه | تسجيلات `sourceKey`→adapter | تعريفات كتالوج + next-gate ACTIVE | `ScientificSourceCatalog` / Registry | كود/تكوين | Orchestrator / Selector | CatalogContract، NextGate tests | **جزئي** | نقطة دخول التفعيل التشغيلي |
| Durable Correlation (IU-07) | ربط B7 | `durable_correlation_records` | repository اختياري | — | جدول correlation | اختياري null في Coexistence | اختبارات correlation | **منفصل وخامل افتراضيًا** | ليس شرط تفعيل مصدر |

---

## 3) تتبع مسارات التنفيذ

### A) ScientificKnowledgePersistenceService `[V]`

**الكتابة**
1. `AgriculturalResearchAgent` → `knowledgePersistenceService->persist(...)` (مساران ~L176 و~L321).
2. بوابات R5: `evidenceSufficient` + حالة توليف ليست `insufficient_evidence|needs_clarification|no_search_results` + وجود claims/citations/validated evidence.
3. R1: رفض عند `TenantContext` عام لا يطابق `organizationId`.
4. إنشاء `LibraryItem` ثم `preserveOriginalSourceFile($item, $primarySource)`.

**`preserveOriginalSourceFile`**
- HTTP GET للـ URL (timeout 20s، حد 15MB، redirects≤3).
- بايتات كما هي → `Storage::disk('local')->put('research-sources/{orgId}/{slug}.{ext}')`.
- يضبط `file_disk='local'` و`file_path` و`metadata.original_source_file` (`transformed=false`, `translated=false`).
- فشل التنزيل: تسجيل info وتخطي — لا يفشل حفظ المعرفة.

**القراءة**
- عبر `LibraryItem.file_disk/file_path` وواجهات المكتبة؛ المسار ليس مفهرسًا كنص PDF كاملًا عبر LocalIndex؛ يُزامَن العنصر عبر `KnowledgeSemanticIndexSync` (تضمين معرفة المكتبة).

**إعادة الاستخدام لمسار الـ110**
- **نعم كطبقة لاحقة** بعد بحث ناجح ومعرفة مؤهلة.  
- **لا** كمسار استيعاب ODS/OAI/bulk للمقاعد. ذلك مسؤولية LocalIndex/Ingestion.  
- تعديلات لازمة فقط عند تفويض صريح لربط نتائج مصدر محكوم بـ Library (ليست مطلوبة لتفعيل البحث نفسه).

### B) Capability Store `[V]` + ADR §8.18.1–§8.18.2 `[P/V]`

- Commit: `31a6ee3984c800ff771508f8e496ea75e2cd0938`.
- CURRENT واحد لكل (subject, dimension, seat_override)؛ `supersedeCurrent` ذري؛ HISTORICAL للاحتفاظ.
- **CapVer:** لا صنف/خدمة تعيين VERIFIED في `backend/app`؛ المستودع يقبل VERIFIED من أي مستدعٍ — الإنفاذ مؤجّل `[V]`.
- **الإنتاج:** `CapabilityStoreRepository` مربوط في DI فقط؛ لا `persistCurrent`/`loadCurrentForSubject` من تطبيق الإنتاج `[V]`.
- `CapabilityRequirementEvaluator` يُستدعى من `Stage3CghiaCoexistenceBoundary` بسجلات **يمرّرها المستدعي**؛ الـ Orchestrator يستدعي `plan(...)` **بدون** سجلات → افتراضي `[]` → UNVERIFIED عند المسار المرتبط بـ ADR فقط `[V]`.
- سجلات Cap لـ 109/110: **لا دليل على صفوف مأهولة في الإنتاج** `[U]` لمحتوى DB؛ **صفر كتّاب إنتاج** `[V]`.

### C) IC Persistence `[V/P]`

**مرجع التقرير المطلوب:** ملف `Pasted markdown(20261009-145706).md` **غير موجود بهذا الاسم**. الأقرب المؤرشف:
- `…/2026-10-09-1734-wsa-enterprise-ic-persistence-forensic-status-audit-report-full.md` (تدقيق IC).
- ولتقريب زمني 14:57 يوم 9: تقرير Cap/IC في `2026-10-09-1528-…`.

**ما تحفظه:** قرارات تصنيف تكامل (status، modalities، nature، boundary، أدلة/ترخيص مراجع) — **ليست** نصوص الأوراق ولا ملفات PDF ولا حقائق Cap.

**الربط:** بمفتاح `adr_id` (+ اختياري `canonical_identity_id`).

**المستهلك:** DI فقط؛ لا استدعاء إنتاجي لـ `persist`/`findCurrentByAdrId` خارج الحزمة/الاختبارات `[V]`.

**109/110:** مخزون IC للمقاعد **غير مأهول**؛ حالات IC في مراجعة الأدلة كانت مقترحة `UNCLASSIFIED` ولم تُحفظ `[P]` (تقرير 14 أكتوبر). يحتاج تحقق جديد قبل الاعتماد كبوابة جاهزية حية.

### D) تقرير ~14 أكتوبر `[P]`

`Pasted markdown(20261014-140203).md` غير موجود بالاسم؛ الأقرب:  
`2026-10-14-1405-final-master-execution-order-report.md`  
الخلاصة آنذاك: Phase 1 **BLOCKED** (CapVer DEFERRED، إلخ)؛ IC review لـ 109؛ لا adapters/Registry/Cap facts؛ WIP غير مودع.  
**يحتاج إعادة تحقق:** منذ ذلك الحين وُجد next-gate (G5-04 ACTIVE)، LocalIndex، وCURRENT membership **110.v1** مقابل 109.v1 المجمد.

### E) R1–R8 `[V]`

الملف الفعلي: `docs/architecture/WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md` (يتضمن قسم R8).

| قرار | التطبيق التنفيذي |
|---|---|
| **R1** | مطبّق في `ScientificKnowledgePersistenceService` + TenantContext |
| **R3** | مطبّق في `preserveOriginalSourceFile` + اختبارات Phase8B2 (مسار ملف/تكرار) |
| **R5** | مطبّق (`evidenceSufficient` + حالات التوليف) |
| **R6** | محاور أدلة منفصلة في مسار البحث/التحقق (ليس في Cap/IC) |
| **R8** | قرار معماري؛ التنفيذ معلّق حسب الوثيقة |

**الحاكم لـ 109/110:** ADR-023 للعضوية/Cap/IC/التفعيل؛ R1–R5 تحكم مسار Library بعد الإجابة — لا تُلغي بوابات ADR-023.

### LocalIndex ونقطة البحث الحالية `[V]`

- DI: `ScientificRecordStore` → `DatabaseScientificRecordStore`؛ `LocalScientificIndex`.
- الكتالوج: 5 legacy + `NextGateEligibleSeatCatalog::activatedRegistrations()` (GBIF REST نشط).
- Orchestrator لا يمرّر حقائق Cap من المتجر.

---

## 4) مصفوفة تكامل المصادر (عضوية ≠ تشغيل)

### أعداد معتمدة `[V]`

| السجل | العدد |
|---|---|
| `ADR-023-109-MEMBERSHIP-REGISTER.v1.json` | **109** (`109.v1`) — تجميد D-09 |
| `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` | **110** (`110.v1`) — CURRENT |
| نشط في كتالوج البحث (تقريبًا) | **5 legacy** + **≥1** next-gate (`gbif`/G5-04) |

لا تُساوَى 110 مدخلًا بـ 110 مصدرًا يبحث فعليًا.

### فئات جاهزية (لا اختلاق لكل مقعد)

| فئة | Registry/Adapter | هوية | ترخيص | وصول | Cap Store | IC | حفظ/فهرسة | حاجز التفعيل |
|---|---|---|---|---|---|---|---|---|
| **Legacy Stage-3** (openalex, crossref, semantic_scholar, consensus, fao_stat) | مسجّل + adapter | EXTERNAL_ONLY بلا ADR binding افتراضيًا | حسب المزوّد | API حي | لا حقائق مخزّنة؛ تقييم اختياري فارغ | لا | نتائج → تحقق → Library عند الأهلية؛ لا LocalIndex | ليسوا من 109/110 seats |
| **G5-04 GBIF** | ACTIVE next-gate REST | وثائقي/عقد ACTIVE | ToU | API | غير مخزّن في Cap Store كصف CURRENT منتَج | غير محفوظ كـ IC ACTIVE مخزون | بحث حي؛ ليس مسار Library الافتراضي للمقعد | الأقرب للتشغيل المحكوم |
| **مقاعد bulk/static مرخّصة** (مثل G1-01/G1-17) | scaffold / غير ACTIVE | مرشّح | معلن | bulk/static | لا | لا | LocalIndex fixture فقط؛ production generation=0 | نشر مخزن+ingest+قرار تفعيل |
| **مقاعد API موثّقة غير مفعّلة** | غالبًا بلا adapter محكوم | مرشّح/ملتبس | UNKNOWN/معلن | موثّق جزئيًا | لا | لا | UNKNOWN | هوية+ترخيص+CapVer+adapter+IC |
| **ويب فقط / غير موثّق** | لا | ملتبس/غير مثبت | غالبًا UNRESOLVED | ضعيف | لا | لا | لا تنزيل آمن كمسار بحث | قرار مالك + وصول آلة |
| **Dual peers G6↔G1** | مؤجّل سياسة | نظير | — | — | — | — | — | تجنّب دليل مزدوج |

تفاصيل المقاعد الـ17 في مهمة 2026-10-17: انظر `2026-10-17-ACTIVATION-ELIGIBILITY.v1.json` — لا تعمَّم على الـ110 دون مراجعة IC لكل مقعد.

---

## 5) الفجوات والتعارضات

| النوع | المثال |
|---|---|
| **كود موجود ومختبر** | Cap Store، IC Persistence، Library R3/R5، LocalIndex fixtures، GBIF adapter |
| **موجود وغير مربوط تشغيليًا** | كتابة/قراءة Cap وIC؛ تحميل `loadCurrentForSubject` في مسار البحث؛ ADR bindings في Coexistence |
| **تصميم بلا تنفيذ كامل** | CapVer ownership؛ population لـ 109/110؛ R8 Multi-Answer |
| **دليل تاريخي يحتاج تحقق** | تقرير 14 أكتوبر (0 adapters) مقابل حالة 17 أكتوبر (GBIF + LocalIndex WIP)؛ محتوى جداول الإنتاج `[U]` |
| **قرار/تفويض مفتوح** | CapVer؛ ترتيب Cap population مقابل IC inventory؛ تفعيل مقاعد LocalIndex بلا فهرس؛ تعارض 109↔110 عضوية |

**تعارض يجب عدم «حسمه» هنا:** أي مسار يدمج IC أو Cap أو ملفات Library في مخزن واحد يخالف ADR-023 صراحة.

---

## 6) التوصية التنفيذية (أقل مجموعة أعمال — بلا تنفيذ الآن)

ترتيب يسمح به المعمارية (اقتراح بوابة لاحقًا، ليس تفويضًا):

1. **تثبيت مرجع العضوية الحاكم** (109.v1 vs 110.v1) بقرار مالك صريح قبل أي حملة تفعيل جماعية.  
2. **عدم بناء طبقة Persistence جديدة** — إعادة استخدام العقود الحالية.  
3. **قبل تفعيل مقعد محكوم:**  
   - هوية كافية / binding عند اللزوم؛  
   - IC ACTIVE موثّق (كتابة عبر IC repository عند التفويض)؛  
   - حقيقة Cap CURRENT عبر مسار CapVer-معتمد (اليوم غير موجود)؛  
   - adapter أو LocalIndex مأهول؛  
   - تسجيل في `ScientificSourceCatalog` / next-gate ACTIVE.  
4. **LocalIndex:** تفويض نشر ترحيل المخزن + ingest مقعدًا بمقعد؛ لا تفعيل بفهرس فارغ.  
5. **ScientificKnowledgePersistence:** الإبقاء كـ **مرحلة بعد الإجابة** (R3/R5)؛ ربط اختياري لاحق لنتائج المقاعد المحكومة دون جعله بوابة تفعيل.  
6. **ربط تشغيلي لـ Cap (عند التفويض):** قارئ يستدعي `loadCurrentForSubject` قبل/مع Coexistence بدل تمرير `[]` — دون جعل المستودع يعيّن VERIFIED.  
7. **لا تفعيل جماعي لـ 110** حتى تُغلق CapVer + سياسة population + مصفوفة أهلية لكل مقعد.

---

### متطلبات الملفات المنزّلة (خلاصة)

| سؤال | الجواب `[V]` |
|---|---|
| أين يُحفظ PDF/الملف؟ | `storage/app/research-sources/{organization_id}/{slug}.{ext}` عبر قرص Laravel `local` |
| التسجيل | أعمدة `LibraryItem.file_disk` / `file_path` + `metadata.original_source_file` |
| الربط بالمصدر | عبر مرجع الاستشهاد/URL في metadata البحث — **ليس** عبر `adr_id` Cap/IC |
| فهرسة الملف؟ | لا LocalIndex للبايتات؛ مزامنة دلالية لعنصر المكتبة |
| فشل/حظر التنزيل | تخطي صامت لحفظ الملف؛ المعرفة قد تُحفظ بدون ملف |
| التنزيل ≠ موثوقية | R5 يشترط `evidenceSufficient` قبل الحفظ المؤهل |

---

**الخلاصة النهائية:** الطبقات الأربع (ومجاوراتها) **موجودة ومتمايزة**؛ التشغيلي منها لمسار المستخدم اليوم هو **Library Persistence + adapters الـlegacy (+ GBIF)**. Cap وIC **جاهزان كعقد تخزين** لكن **خاملان تشغيليًا**. تفعيل الـ110 يتطلب توصيل هذه العقود بمسار الأهلية والبحث **حسب التفويض**، لا استبدالها ولا اعتبار حفظ ملف أو صف جدول دليلَ بحث أو جاهزية.
