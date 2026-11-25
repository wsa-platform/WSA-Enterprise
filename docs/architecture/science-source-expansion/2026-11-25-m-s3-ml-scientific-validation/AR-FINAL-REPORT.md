# التقرير النهائي — M-S3-ML-SCIENTIFIC-VALIDATION

**التاريخ:** 2026-11-25  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**Mission ID:** `M-S3-ML-SCIENTIFIC-VALIDATION`  
**Mission Name:** `GENERAL_SCIENTIFIC_ENGINE_ENGINEERING_AND_VALIDATION`

## A. الملخص التنفيذي

أُصلح سبب جذري عام في مطابقة الكيانات اللاتينية عبر اللغات الأربع، كان يمنع التحقق العلمي (Stage-4) والإسناد عندما يظهر اسم الجنس اللاتيني مجرداً في أسئلة غير إنجليزية/تركية.

**النتيجة النهائية:** `PARTIALLY_COMPLETED` مع `WITH_LIMITATIONS`

- Biodiversity/Pollinators: AR/EN/FR/TR → `LIVE_ANSWER_VERIFIED` على أدلة LocalIndex حقيقية.
- بقية المجالات الأساسية: تغطية تخطيط السؤال (`PLAN_OK`) دون Live Answer شامل.
- سجل المصادر (11): موثّق؛ الحي العميق لعينة LocalIndex/scholarly فقط.

## B. تصنيف المهمة

| البعد | القيمة |
|---|---|
| Baseline | `BASELINE_READY_WITH_LIMITATIONS` |
| Final Status | `PARTIALLY_COMPLETED` |
| Limitations | `WITH_LIMITATIONS` |
| CONTRACT_VERIFIED | نعم (عقد Latin taxon متعدد اللغات) |
| LIVE_ANSWER_VERIFIED | نعم لمجال الملقحات × 4 لغات |
| LIVE_ANSWER لجميع المجالات/المصادر | لا |

## C. تحليل الأسباب الجذرية

| Root Cause ID | المشكلة | السبب | المكون | الأعراض | الإصلاح | التحقق |
|---|---|---|---|---|---|---|
| RC-ML-ENTITY-01 | فشل `entity_matched` لـ FR/AR رغم وجود نفس الجنس اللاتيني | استخراج الثنائيات فقط؛ EN/TR شكّلا ثنائيات زائفة | `ScientificEvidenceRelevanceGate` | validated_count=0، supporting فقط، no_validated_evidence | مرشحو جنس لاتيني + قائمة إيقاف غير تصنيفية + إشارات faunistic متعددة اللغات | وحدة + مسبار حي 4 لغات |

## D. التغييرات الهندسية

1. **`ScientificEvidenceRelevanceGate`**: `latinTaxonCandidatesInText()` يستخرج الجنس اللاتيني المجرد والثنائيات؛ يستبعد كلمات الخطاب المرسملة غير التصنيفية.
2. **`ScientificEvidenceDirectnessAssessor`**: توسيع قصد faunistic/pollinator لإشارات EN/FR/AR/TR دون ربط بمصدر أو نوع محدد.
3. **اختبار عقد جديد**: `ScientificEvidenceRelevanceLatinTaxonContractTest` (Mentha/Andrena عبر 4 لغات + رفض discourse openers).

لا استثناءات لـ Nomada/OSMIA/FR كحالات خاصة.

## E. الجودة العلمية

- لم تُضعَف بوابات المحاصيل/الري/القياسات الكمية.
- اختبار DirectnessAssessor السابق (4) بقي ناجحاً.
- مطابقة الكيان تتطلب ظهور المرشح اللاتيني في نص الدليل أيضاً (لا اختراع كيان).

## F. نتائج اللغات

| لغة | Biodiversity Live Answer | validated | composer |
|---|---|---:|---|
| EN | PASS | 2 | synthesis_completed_partial |
| FR | PASS | 2 | synthesis_completed_partial |
| AR | PASS | 2 | synthesis_completed_partial |
| TR | PASS | 2 | synthesis_completed_partial |

## G. تغطية المجالات العلمية

| مجال | خطة 4 لغات | Live Answer |
|---|---|---|
| Crops and Yield | PLAN_OK | NOT_RUN |
| Water and Irrigation | PLAN_OK | NOT_RUN |
| Soil | PLAN_OK | NOT_RUN |
| Fertilizers and Plant Nutrition | PLAN_OK | NOT_RUN |
| Diseases and Pests | PLAN_OK | NOT_RUN |
| Biodiversity and Pollinators | PLAN_OK | **PASS ×4** |
| Climate and Environment | PLAN_OK | NOT_RUN |
| Plant Physiology and Genetics | PLAN_OK | NOT_RUN |

ملاحظة: QUS ما زال يصنّف بعض أسئلة الري AR/TR كـ `definition` بدل `requirements` (قيد متبقٍ، ليس محور هذا الإصلاح).

## H. مصفوفة اللغة × المجال (أساسية)

كل خلية أساسية: `PARTIAL` (فهم/تخطيط) ما عدا Biodiversity = `PASS` (Live Answer).  
الخلايا غير الحية: السبب = لم يُنفَّذ مسار إجابة حية كامل في هذه المهمة؛ الأولوية كانت للإصلاح المشترك للكيانات متعددة اللغات.

## I. مصفوفة تغطية المصادر

**Total Registered Sources:** 11

| Source | Adapter family | Knowledge type | Contract | Live Retrieval | Live Answer |
|---|---|---|---|---|---|
| openalex | REST scholarly | scholarly_evidence | Inspected | NOT_LIVE_VERIFIED (هذه المهمة) | NOT_LIVE_VERIFIED |
| crossref | REST scholarly | scholarly_evidence | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| semantic_scholar | REST scholarly | scholarly_evidence | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| consensus | REST review | review_evidence | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| fao_stat | Stats portal | statistical_observation | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| agris | LocalIndex | agricultural_practice | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| dr_duke | LocalIndex | chemical_association | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| gbif | REST JSON | taxonomic_identity | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| organic_eprints | LocalIndex | agricultural_practice | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| bhl | REST JSON | bibliographic_heritage | Inspected | NOT_LIVE_VERIFIED | NOT_LIVE_VERIFIED |
| osmia | LocalIndex | scholarly_evidence | Verified | LIVE_RETRIEVAL_VERIFIED | LIVE_ANSWER_VERIFIED |

عينة حية واحدة لا تُعمَّم على جميع المصادر.

## J. أدلة الاختبارات

| الأمر | Passed | Failed | Exit |
|---|---:|---:|---:|
| Baseline Directness+Osmia+RelevanceSense | 21 | 0 | 0 |
| LatinTaxon + Directness + OsmiaG305 | 12 | 0 | 0 |
| `_ms3_ml_validation_diag.php` (4 لغات ملقحات) | 4/4 validated≥1 | 0 | 0 |

UI: NOT APPLICABLE

## K. Git و Push

يُستكمل بعد الالتزام (انظر نهاية التنفيذ).

## L. القيود والمخاطر المتبقية

1. Live Answer غير منفّذ لـ 7 مجالات أساسية × 4 لغات.
2. عدم التحقق الحي لـ 10/11 مصدراً في هذه المهمة.
3. عدم اتساق `question_type` للري في AR/TR (`definition` vs `requirements`).
4. قائمة إيقاف الكلمات المرسملة قد تحتاج توسيعاً تدريجياً دون إعادة ثغرة الجنس اللاتيني.

لا تمنع اعتماد إصلاح RC-ML-ENTITY-01؛ تمنع `COMPLETE_VERIFIED`.

---

## M. المهمة الهندسية التالية — مقترح فقط

### A. Mission Name
`M-S3-QUS-MULTILINGUAL-QUESTION-TYPE-ALIGNMENT`

### B. Rationale
- **Gap:** أسئلة الري/الاحتياج في AR/TR تُصنَّف أحياناً `definition` بدل `requirements` رغم تطابق المعنى العلمي مع EN/FR.
- **Evidence:** مصفوفة التخطيط في هذه المهمة (`irrigation_ar`/`irrigation_tr` → definitional_statement).
- **Impact:** يوجّه التحقق ونوع الدليل المطلوب مساراً غير مناسب للمجال الكمي.

### C. Scope / Out of Scope
Scope: محاذاة question_type / required_evidence_type عبر AR/EN/FR/TR للمجالات الكمية.  
Out of scope: مصادر جديدة؛ إضعاف بوابات القياس.

### D. Current State
- Entity Latin matching: `VERIFIED`
- Pollinator live answer 4-lang: `VERIFIED`
- Irrigation QUS parity: `NOT_VERIFIED`
- All-domain live answers: `NOT_VERIFIED`

### E–L
معمارية: توسيع عقود QUS/CSQ للقيود الكمية متعددة اللغات.  
اختبارات: مصفوفة لغة×مجال لـ Water/Yield/Fertilizer.  
Acceptance: AR/TR requirements questions تحافظ على sense=crop_water_requirement وrequired_evidence_type المناسب دون كسر EN/FR.  
Readiness: `READY_WITH_DEPENDENCIES` (يعتمد على ثبات Relevance الحالي).  
**Execution Authorization:** `NOT_AUTHORIZED — Awaiting user approval.`
