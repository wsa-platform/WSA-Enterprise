# تقرير التحقيق الجنائي: `all_results_rejected_by_relevance`

**المهمة:** Forensic root-cause + verified remediation  
**التاريخ:** 2026-11-21  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**التصنيف النهائي:** `RELEVANCE_ROOT_CAUSE_IDENTIFIED_AND_FIXED_VERIFIED`

---

## A. Executive Summary

الحالة `all_results_rejected_by_relevance` كانت عرضاً صحيحاً لفراغ الناجين بعد بوابة الصلة (Stage-3)، وليست خطأ تسمية في الـ API. السبب الجذري المثبت لاستعلام Mentha (GS-06) مزدوج:

1. **فساد هوية السؤال في Query Understanding:** سؤال التصنيف التصنيفي للثنائية العلمية رُoute كتصنيف أراضي/أصناف، مع كيان `taxonomic mentha piperita` و`scientific_name=null`.
2. **مسار DirectnessAssessor أعاد تقييم الصلة بدون `source_key`:** حتى بعد إصلاح الهوية، نُسبت مقالات OpenAlex ذات الصلة التصنيفية إلى `IRRELEVANT` فمُسحت الناجون في `filterRelevant`.

بعد التصحيح: Mentha يسترجع ~70 مرشحاً ويُبقي ~69 ناجياً في Stage-3؛ الحالة لم تعد `all_results_rejected_by_relevance` بل `no_validated_evidence` (فشل Stage-4/كفاية أدلة — لم يُضعَف عمداً). Wheat الإنجليزي ما زال `scientific_generated`.

---

## B. Git and Runtime Baseline

| بند | قيمة |
|-----|------|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Starting HEAD | `3f16a6c12795d2934a42f5a5d21f22f59c325a54` |
| Runtime | Docker Compose محلي: nginx `:8079`, backend healthy, bind `./backend:/var/www/html` |
| DB النشط للتطبيق الحي | Postgres في الـ stack المحلي (`wsa-enterprise-postgres-1`)؛ SQLite يبقى قاعدة المشروع الأصلية ولم تُهاجر |
| WIP | محمي — لم يُمس CapVer / Docker build / ملفات غير ذات صلة |

أدلة: `E-R01`…`E-R13` في هذا المجلد.

---

## C. Original Incident Evidence and Reproduction Status

- **استعلام تاريخي/تمثيلي موثّق:** `What is the taxonomic classification of Mentha piperita?`
- **قبل الإصلاح (E-R01):** `status=all_results_rejected_by_relevance`, `raw=60`, `dedup=0`, `scientific_name=null`, كيان `taxonomic mentha piperita`.
- **إعادة الإنتاج:** عبر `POST http://127.0.0.1:8079/api/v1/public/research-agent/query` بجسم `{ "query": "..." }` (وليس `question`).
- ملاحظة: لا يُدّعى أن هذا هو أثر الإنتاج التاريخي الوحيد؛ هو إعادة إنتاج محلية موثّقة لنفس العَرَض.

---

## D. Exact Origin and Meaning of `all_results_rejected_by_relevance`

يُنشأ عندما تكون نتائج الاسترجاع الخام غير فارغة بينما `deduplicatedResults` (الناجون بعد الترتيب/بوابة الصلة) فارغة. يُصدره `AgriculturalScientificValidationService` ويُمرَّر عبر `AgriculturalResearchAgent` / `AnswerComposer` إلى الواجهة.

المعنى: **كل المرشحين رُفضوا أو أُسقطوا في مسار الصلة/الترتيب قبل التحقق العلمي Stage-4** — وليس دليلاً على أن خوارزمية العتبة الرقمية وحدها معطوبة.

---

## E. Status Provenance Map

```
Retrieval raw results (n>0)
  → ScientificResultRanker.rank (+ RelevanceGate + DirectnessAssessor)
  → filterRelevant (rejected_by_relevance_gate || directness=IRRELEVANT)
  → MultiSourceScientificSearchOrchestrator.deduplicatedResults (post-filter survivors)
  → AgriculturalScientificValidationService: survivors empty + raw nonempty
       → status "all_results_rejected_by_relevance"
  → AgriculturalResearchAgent / AnswerComposer failure_code
  → API JSON → Frontend presentation
```

---

## F. Actual Execution Path and Candidate Counts

| المرحلة | قبل (Mentha E-R01) | بعد (Mentha E-R12) |
|---------|-------------------|---------------------|
| QU scientific_name | null | Mentha piperita |
| QU question_type | classification (مسار أراضي) | species |
| required_evidence | classification_or_types_inventory | species_list_or_taxonomy |
| raw | 60 | 70 |
| survivors (dedup field) | 0 | 69 |
| successful_sources | openalex, crossref | openalex, crossref, gbif |
| status | all_results_rejected_by_relevance | no_validated_evidence |

Wheat (E-R13): `scientific_generated`, raw=60, dedup=22 — انحدار سليم.

---

## G. Candidate-Level Rejection Ledger

عيّنة تشخيصية (ليس اختراعاً لأرقام عتبة غير موجودة):

| مرشح | المصدر | التصرف قبل | التصرف بعد إصلاح الهوية+source_key |
|------|--------|------------|-------------------------------------|
| عنوان تصنيفي Mentha piperita (اختبار وحدة) | openalex | REJECTED_BY_RELEVANCE (`missing_topic_or_factor`) ثم عبر Directness بدون source_key → IRRELEVANT | ACCEPTED_BY_RELEVANCE / directness ≠ IRRELEVANT |
| مجموعة حية ~60–70 | openalex/crossref/(gbif) | NOT_EVALUATED بشكل صحيح بسبب فساد الهوية → رفض جماعي Stage-3 | RETRIEVED + مرّت Stage-3؛ Stage-4: لا أدلة مُتحقق منها كافية → INSUFFICIENT_EVIDENCE |

لم تُخترع درجات رقمية حيث لا يحسبها التنفيذ.

---

## H. Relevance Evaluator Implementation and Findings

- المقيّم: `ScientificEvidenceRelevanceGate` + سياسة `ScientificKnowledgeTypeRelevancePolicy`.
- آلية قرار هجينة قواعدية (كيان/موضوع/sense/معرفة-نوع) وليست نموذجاً مدفوع الأجر.
- خلل مثبت: أسئلة `TAXONOMIC_IDENTITY` التي تسترجع `scholarly_evidence` كانت تفشل `identity_oriented` لأن المسار كان مقصوراً على أنواع سجلات غير علمية؛ أُضيف قبول scholarly عند تطلب الهوية التصنيفية وتطابق الكيان.
- خلل ثانٍ مثبت: `ScientificEvidenceDirectnessAssessor::assess` استدعى البوابة **بدون** `source_key`، فألغى المسار identity-oriented وأسقط الناجين في الـ Ranker.

---

## I. Scientific Question Identity / CSQ Findings

- ADR-022: الهوية العلمية للثنائية أُفسدت بضمّ كلمة `taxonomic` إلى الكيان وإسقاط `scientificName`.
- إصلاحات: توجيه `asksTaxonomicSpeciesClassification` → `species` / `species_list_or_taxonomy`؛ استعادة binomial؛ إزالة stop-tokens التصنيف من الكيان؛ intent ليس `varieties` للتصنيف التصنيفي.
- تصنيف الأراضي (Morocco soil/land) لم يُحوَّل إلى species (اختبار وحدة).

---

## J. Retrieval / Index / Adapter Findings

- الاسترجاع الخارجي (OpenAlex/Crossref/GBIF) نجح (`raw>0`)؛ العطل لم يكن «صفر نتائج».
- بعد الإصلاح ظهرت GBIF ضمن المصادر الناجحة.
- لم يُثبت عطل FTS/LocalIndex كسبب لهذا العَرَض.

---

## K. Verified Root Cause

**RC1 (أساسي):** سوء توجيه فهم الاستعلام لسؤال تصنيف تصنيفي لثنائية علمية → متطلبات أدلة أراضي/أصناف + كيان فاسد → بوابة الصلة ترفض كل شيء.  
**RC2 (مكمّل بعد RC1):** DirectnessAssessor/VerificationLayer بدون `source_key` → إعادة تقييم تُسقط scholarly تصنيفي كـ IRRELEVANT رغم قبول البوابة المصدرية في الـ Ranker.

---

## L. Implemented Correction or Reason for No Correction

1. `QueryUnderstandingService` + `AgriculturalEntityCatalog` — توجيه species، binomial، stop-tokens، intent taxonomy.
2. `ScientificKnowledgeTypeRelevancePolicy` — identity-oriented لـ scholarly عند TAXONOMIC_IDENTITY.
3. `ScientificEvidenceDirectnessAssessor` + `ScientificResultRanker` + `EvidenceVerificationLayer` — تمرير `source_key`/metadata.
4. اختبارات: `TaxonomicClassificationRelevanceRoutingTest` (4 اختبارات).

**لم يُنفَّذ:** خفض عتبات، تعطيل بوابة الصلة، إضعاف Stage-4، إجبار AnswerComposer على إجابة غير مدعومة.

---

## M. Before/After Behavior

| | قبل | بعد |
|--|-----|-----|
| Mentha status | all_results_rejected_by_relevance | no_validated_evidence |
| Mentha survivors | 0 | 69 |
| Mentha scientific_name | null | Mentha piperita |
| Wheat EN | scientific_generated | scientific_generated (انحدار OK) |

`no_validated_evidence` حالة صحيحة منفصلة (فئة 10 في مصفوفة التمييز) — لم تُعالَج ضمن هذه المهمة لأنها ليست فساد بوابة الصلة.

---

## N. Unit and Integration Test Results

```
docker compose exec -T backend php artisan test --filter=TaxonomicClassificationRelevanceRoutingTest
→ PASS 4 tests (16 assertions)
```

أدلة: مخرجات الطرفية محفوظة ضمن جلسة التنفيذ؛ الاختبار يغطي التوجيه + بوابة Stage-3 + Directness مع source_key + عدم تحويل land classification.

---

## O. Real Application/API Verification

- `POST /api/v1/public/research-agent/query` على `http://127.0.0.1:8079`
- E-R12 Mentha: PASS لمسار الصلة (dedup=69، ليس all_results_rejected…)
- E-R13 Wheat: PASS `scientific_generated`

---

## P. Real Source Retrieval and Model Inference Status

- **Real Source Retrieval:** VERIFIED (OpenAlex/Crossref/GBIF في الاستجابة الحية).
- **Real Model Inference:** NOT_RUN / غير مطلوب لإثبات جذر الصلة (لا استدلال نموذج مدفوع في مسار الإصلاح).

---

## Q. Scientific Evidence and Answer Validity

Mentha بعد الإصلاح: لا تُنتج إجابة علمية مدعومة (`no_validated_evidence`) — السلوك يحترم كفاية الأدلة ولا يلفّق إجابة. Wheat ينتج إجابة مدعومة كما سبق.

---

## R. Browser E2E Status

**NOT_RUN** لهذه المهمة (التحقق عبر API الحي كافٍ لعَرَض الحالة؛ لا يُدعى E2E متصفح).

---

## S. Changed Files, Commits, Push, and Final HEAD

يُحدَّث بعد الـ commit/push في `E-R14-git-delivery.txt`.

ملفات النطاق فقط:
- `AgriculturalEntityCatalog.php`
- `QueryUnderstandingService.php`
- `ScientificKnowledgeTypeRelevancePolicy.php`
- `ScientificEvidenceDirectnessAssessor.php`
- `ScientificResultRanker.php`
- `EvidenceVerificationLayer.php`
- `TaxonomicClassificationRelevanceRoutingTest.php`
- `docs/.../relevance-rejection-investigation/**`

---

## T. Remaining Risks, Limitations, and NOT_RUN Items

- Stage-4 لأسئلة التصنيف التصنيفي قد يبقى `no_validated_evidence` رغم نجاة Stage-3 — يحتاج مهمة منفصلة إن رُغب بتحسين قبول أدلة GBIF/taxonomy دون إضعاف العقود.
- Browser E2E: NOT_RUN.
- Arabic wheat / مسارات أخرى: PARTIAL (Wheat EN فقط كانحدار هذه الجولة).
- Postgres محلي ≠ استبدال SQLite كقاعدة مشروع أصلية.

---

## U. Final Classification

**`RELEVANCE_ROOT_CAUSE_IDENTIFIED_AND_FIXED_VERIFIED`**

دليل: قبل/بعد API + اختبارات وحدة + مسار provenance مثبت؛ لم تُضعف معايير الأدلة العلمية.
