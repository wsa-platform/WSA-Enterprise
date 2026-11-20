# تقرير إصلاح المسار العلمي — WSA-Enterprise
**التاريخ:** 2026-11-21  
**التصنيف:** `SCIENTIFIC_PIPELINE_REPAIR_PARTIALLY_COMPLETED`

## A. Executive Summary
أُصلحت فجوة `source_key` في Stage-4/Composer، وأُضيف حارس الإجابة التصنيفية، ومواضيع taxonomy، وإثراء ملخص GBIF التصنيفي.  
Mentha: من `no_validated_evidence` → `synthesis_completed_partial` مع استشهادات GBIF (لا Rhizobacteria).  
Wheat: يبقى `scientific_generated`.  
إجابة Mentha ما زالت جزئية (ليست هرمية كاملة غنية) — لذلك التصنيف PARTIAL وليس COMPLETE.

## B. Git Baseline / Final
يُحدَّث في `E-GIT-delivery.txt` بعد الدفع.

## C. Previous Mentha Fixes
QU binomial + Stage-3 Directness source_key: **ما زالا صالحين** (E-P3 sci=Mentha piperita, survivors>0).

## D. P0 Source Identity
انظر `01-source-identity-matrix.md`. اختبارات: `Stage4SourceKeyPropagationTest` PASS.

## E–H. Sources / GBIF / Taxonomy
- OpenAlex/Crossref/GBIF نفّذت على Mentha.
- Scholarly بلا إشارات تصنيفية يُرفض كدليل تصنيف.
- GBIF: `composeGbifTaxonomicAbstract` يبني نصاً من الحقول المُرجَعة فقط.
- جرد كامل لكل مصادر السجل: **PARTIAL** (تنفيذ حي على المصادر المستخدمة؛ لا live لكل المقاعد).

## I–J. Identity
سؤال أصلي محفوظ؛ CSQ/topics taxonomy مملوءة؛ إجابة Mentha الجزئية تشير إلى Taxonomic classification + GBIF — تحسين مطلوب لاستخراج الهرم كاملاً.

## K–N. Stages
Stage-3: يصفّي الزيوت/المعالجة. Stage-4: validation_completed مع أدلة. Composer: partial synthesis.

## O. Observability
تحسين محدود عبر اختبارات/أدلة مهمة؛ توسيع API observability: **PARTIAL / مؤجل**.

## P. Mentha
قبل: no_validated_evidence. بعد: synthesis_completed_partial, cites≈10 GBIF, answer mentions Taxonomic classification.  
ليس نجاح إجابة علمية كاملة غنية.

## Q. Wheat
scientific_generated, surv=22, cites=5 — لا تراجع.

## R. Other categories
NOT_RUN موسّع (حيوان/تربة…) — خارج الحد الأدنى المطلوب مع Mentha/Wheat.

## S. Tests
`TaxonomicClassificationRelevanceRoutingTest` + `Stage4SourceKeyPropagationTest`: **8 PASS / 28 assertions**.

## T. Verification levels
- Unit: PASS  
- Real API: PASS (Mentha/Wheat)  
- Real retrieval: PASS (OpenAlex/Crossref/GBIF)  
- Model inference: NOT_RUN  
- Browser E2E: NOT_RUN هذه المهمة

## U. Changed files
- ClaimEvidenceMatcher.php  
- ScientificEvidenceRelevanceGate.php  
- AnswerComposer.php  
- ScientificEvidenceDirectnessAssessor.php  
- ScientificQuestionSemantics.php  
- RestJsonScientificSourceAdapter.php  
- TaxonomicClassificationRelevanceRoutingTest.php  
- Stage4SourceKeyPropagationTest.php (new)  
- docs/.../2026-11-21-scientific-pipeline-repair/**

## V–Y. Gates
| Gate | Status |
|------|--------|
| 0 Baseline | PASS |
| P0 Source identity | PASS |
| P1 Taxonomy/GBIF/topics | PASS (with remaining answer-richness gap) |
| P2 Observability/tests | PARTIAL |
| P3 E2E | PARTIAL (Mentha partial answer; Wheat full) |
| GIT | بعد الدفع |

## W. Risks
استخراج حقائق Composer لأسئلة taxonomy ما زال ضعيفاً؛ جرد كل المصادر غير مكتمل live؛ Bare topic phrases تحتاج مراقبة مستمرة.
