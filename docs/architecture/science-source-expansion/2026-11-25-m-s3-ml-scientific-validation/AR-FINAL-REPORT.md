# التقرير النهائي — M-S3-ML-SCIENTIFIC-VALIDATION

**التاريخ:** 2026-11-25 (تحديث استمرار)  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**Mission ID:** `M-S3-ML-SCIENTIFIC-VALIDATION`  
**Mission Name:** `GENERAL_SCIENTIFIC_ENGINE_ENGINEERING_AND_VALIDATION`

## A. الملخص التنفيذي

أُصلحت آليتان عامّتان في المحرك العلمي متعدد اللغات:

1. **RC-ML-ENTITY-01** (سابق): مطابقة الجنس اللاتيني المجرد عبر AR/EN/FR/TR.
2. **RC-ML-QTYPE-01** (هذه الجولة): منع فتحات الاستفهام السطحية (`ما هو` / `nedir` / كمية) من إسقاط `question_type` إلى `definition`/`quantity` بعد أن يُحلّ القصد العلمي (`scientific_intent_qualifier` / sense).

**النتيجة النهائية:** `PARTIALLY_COMPLETED` مع `WITH_LIMITATIONS`

لا `COMPLETE_VERIFIED` لأن Live Answer الشامل لكل المجالات ولكل المصادر المسجّلة غير مكتمل.

## B. التصنيفات

| البعد | القيمة |
|---|---|
| Baseline | `BASELINE_READY_WITH_LIMITATIONS` |
| Final Status | `PARTIALLY_COMPLETED` |
| Limitations | `WITH_LIMITATIONS` |
| CONTRACT_VERIFIED | نعم (Latin taxon + QUS intent parity) |
| LIVE_ANSWER_VERIFIED | نعم لمجال الملقحات × 4 لغات (من الجولة السابقة) |
| LIVE_ANSWER لكل المجالات/المصادر | لا |

## C. الأسباب الجذرية

| ID | المشكلة | السبب | المكوّن | الإصلاح | التحقق |
|---|---|---|---|---|---|
| RC-ML-ENTITY-01 | فشل entity_matched لـ FR/AR | استخراج ثنائيات فقط | RelevanceGate | latinTaxonCandidates + stop list | وحدة + حي ملقحات |
| RC-ML-QTYPE-01 | AR/TR ري → definition؛ سماد FR/TR غير متسق | أولوية فتحات التعريف/الكمية قبل القصد المحلول؛ معاجم ناقصة | QUS + EntityCatalog | `questionTypeFromResolvedIntent` + recommendation-over-quantity + معاجم AR/FR/TR | مصفوفة تخطيط 5 عائلات × 4 لغات + 37 اختباراً |

لا استثناءات باسم مصدر أو DOI أو سؤال واحد.

## D. التغييرات

### جولة سابقة (`e2bb272`)
- `ScientificEvidenceRelevanceGate` / `ScientificEvidenceDirectnessAssessor`
- `ScientificEvidenceRelevanceLatinTaxonContractTest`

### جولة الاستمرار
- `QueryUnderstandingService`: أسبقية القصد المحلول؛ تفضيل recommendation على quantity عند إشارات التوصية.
- `AgriculturalEntityCatalog`: توسيع معاجم requirements / nitrogen / fertilization / recommendation لـ AR/FR/TR.
- `MultilingualQuestionTypeIntentParityContractTest`: ري + ملوحة + سماد × 4 لغات + تعريف سلبي.

## E. نتائج اللغات (تخطيط QUS)

| عائلة علمية | EN | FR | AR | TR | توحيد النوع/الحس/الدليل |
|---|---|---|---|---|---|
| Irrigation requirement | requirements | requirements | requirements | requirements | **نعم** |
| Yield / deficit effect | causes | causes | causes | causes | **نعم** |
| Salinity physiology | causes | causes | causes | causes | **نعم** |
| Fertilizer recommendation | recommendation + plant_nutrition | نفس | نفس | نفس | **نعم** |
| Pollinator host | general | general | general | general | **نعم** |

## F. Live Answer / المصادر

| مستوى | الحالة |
|---|---|
| Pollinator × 4 لغات | LIVE_ANSWER_VERIFIED (سابق) |
| Irrigation/Yield/Fertilizer Live Answer | NOT_VERIFIED في هذه الجولة (إصلاح تخطيط فقط) |
| مصادر مسجّلة | 11 |
| مصادر Live Answer | osmia (عيّنة) — لا تعميم |

## G. الاختبارات

| الأمر | Passed | Assertions | Exit |
|---|---:|---:|---:|
| Regression filter (Latin+Directness+Osmia+RelevanceSense+QUS parity) | 37 | 125 | 0 |

UI: NOT APPLICABLE

## H. Git

يُحدَّث بعد الالتزام والدفع في نفس القسم عند الإصدار.

## I. القيود

1. Live Answer غير منفّذ لـ 7 مجالات × 4 لغات.
2. 10/11 مصدراً بلا تحقق حي في هذه المهمة.
3. مصفوفة أدلة عبر-لغوية كاملة (سؤال بلغة × دليل بلغة أخرى) جزئية.
4. معاجم التوصية/المغذيات قد تحتاج توسعة لاحقة دون إعادة فتحة التعريف.

---

## J. المهمة التالية — مقترح فقط

### A. Mission Name
`M-S3-LIVE-DOMAIN-MATRIX-AR-EN-FR-TR`

### B. Rationale
- **Gap:** إصلاح التخطيط متعدد اللغات مثبت؛ مسار الإجابة الحية غير مثبت لـ Water/Yield/Soil/Fertilizer/Disease.
- **Evidence:** مصفوفة QUS PASS؛ Live Answer فقط للملقحات.
- **Impact:** لا ضمان أن Stage-3→4→Composer يحترم أنواع الدليل الجديدة خارج biodiversity.

### C. Scope / Out of Scope
Scope: Live Answer لمصفوفة مجالات أساسية × 4 لغات + عينة مصادر متنوعة.  
Out: مصادر جديدة؛ إضعاف بوابات القياس.

### D. Current State
- Latin entity ML: `VERIFIED`
- QUS intent parity (ري/ملوحة/سماد): `VERIFIED`
- Domain live answers beyond pollinators: `NOT_VERIFIED`
- All-source live: `NOT_VERIFIED`

### E–L
عينة مصادر: LocalIndex (agris/oe/osmia) + scholarly REST.  
Acceptance: ≥3 مجالات × 4 لغات LIVE_ANSWER_VERIFIED دون انحدار QUS/Latin.  
Readiness: `READY`  
**Execution Authorization:** `NOT_AUTHORIZED — Awaiting user approval.`
