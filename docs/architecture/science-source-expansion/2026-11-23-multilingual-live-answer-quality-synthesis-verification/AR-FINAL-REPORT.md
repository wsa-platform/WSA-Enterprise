# التقرير النهائي — التحقق من جودة الإجابة العلمية متعددة اللغات والتركيب

**المهمة:** Multilingual Live Answer Quality & Synthesis Verification  
**التاريخ:** 2026-11-24  
**الفرع:** `phase-18-m18-ai-marketing-communications`

---

## 1. الملخص التنفيذي

تم التحقق من مسار الإجابة العلمية (فهم السؤال → استرجاع → أدلة → ادعاءات → إسناد → سياق/تعارض → تركيب → إجابة) عبر اختبارات عقود/تجهيزات ومسبار حي للوكيل، مع إصلاح جذري في `AnswerComposer` لرفض شظايا الميتا، ومطابقة فعل الري الناقص، ومنع الإجابة الواثقة عن نطاق غير أرضي (مثل التربة المريخية). تحسّنت جودة EN للري الناقص؛ بقيت AR/TR/FR جزئية، وواجهة React لم تُكتمل للعرض الحي، وFlutter لم يُشغَّل.

## 2. التصنيف النهائي

**`MULTILINGUAL_LIVE_ANSWER_QUALITY_SYNTHESIS_WITH_LIMITATIONS`**

لم يُمنح `…_VERIFIED` بسبب قيود Live UI، وLocalIndex غير PRODUCTION_READY، وضعف اكتمال إجابات TR/FR، وبقايا شظايا في AR.

## 3. نقطة البداية ونقطة النهاية في Git

| | SHA |
|---|---|
| البداية (HEAD) | `9e733514b1650e3019bde120d6ded515353b4d5d` |
| نهاية الالتزام | `4469868265ca4a555555f29ccd0b2435103c54ff` |

المزامنة عند البداية: Local = Remote. WIP غير مرتبط محمي ومستثنى من الالتزام.

## 4. حالة Pre-Flight

**`BASELINE_READY_WITH_LIMITATIONS`**

- Git متسخ لكن محمي.
- LocalIndex قابل للوصول ≠ PRODUCTION_READY.
- AnswerComposer / Ledger / Evidence / اختبارات الأساس جاهزة للمسارات المستقلة.

## 5. المكونات التي فُحصت أو عُدّلت

| مكوّن | إجراء |
|---|---|
| `AnswerComposer` | تعديل (بوابات جودة حية) |
| `AnswerComposerPhrases` | تعديل طفيف |
| `ScientificSynthesisIntegrator` | فحص سلوك (بدون إعادة تصميم) |
| `EvidenceConflictContextAssessor` / `Detector` | فحص عبر عقود سابقة + مسبار |
| `ClaimAttributionLedger` | اختبار وحدات + صفوف حية |
| API / React shell | فحص جزئي |
| Flutter | لم يُشغَّل حياً |

## 6. الأسباب الجذرية

1. **AQ-01:** اعتماد الجملة الأولى ينتج شظايا ميتا/«However»/أهداف الدراسة.
2. **AQ-02:** أدلة ري بمياه الصرف تُقبل لأسئلة الري الناقص.
3. **AQ-03:** أسئلة نطاق غير أرضي تُجاب بأدلة أرضية بثقة مضللة.
4. **AQ-04:** عناوين «متطلبات المياه» تتسرب لأسئلة التأثير السببي.
5. **LIM-UI:** أتمتة النقر في المتصفح مرفوضة → لا إثبات عرض إجابة في React.
6. **LIM-ML:** فصل لغة المصدر/الإجابة يُبقي مقتطفات EN تحت أغلفة TR/FR دون تركيب كمي كافٍ لـ CWR.

## 7. التغييرات الهندسية المنفذة

- بوابات: `isLowQualityFindingFragment`, `preferredFindingSentence`, `findingAddressesAskedAct`, `questionRequiresUnavailableDomainEvidence`.
- استرداد جملة تجريبية من `evidenceText` عند فراغ النتائج المفيدة.
- تجاوز عنوان سببي/تأثيرات.
- اختبارات: `AnswerComposerLiveQualityGateTest` (+ عقد الشظايا التوضيحية).

## 8. جودة الإجابة قبل/بعد

| حالة | قبل (ملخص) | بعد (مسبار v2) |
|---|---|---|
| EN deficit | مقتطفات ضعيفة / احتمال wastewater | نتيجتان تجريبيتان عن عجز المياه؛ packet=true |
| Martian | خطر إجابة واثقة | `insufficient_evidence` |
| AR deficit | شظايا ميتا | تحسن جزئي؛ جملة methods قد تبقى |
| TR/FR CWR | — | إجابة مولَّدة؛ اكتمال ضعيف |

## 9. Claim Attribution وEvidence Integration

- Ledger: اختبارات PASS؛ صفوف حية متطابقة مع عدد الادعاءات في EN deficit.
- العلاقات: supported / conflicting / insufficient حسب الحالة.
- لا يُستخدم عدد الاستشهادات كدليل استقلال تلقائي.

## 10. Context and Conflict

- عقود التركيب والتعبير عن التعارض: PASS.
- EN deficit: اختلافات سياقية دون genuine_conflict=0 في المسبار.
- AR/TR/FR: تظهر ملاحظات سياقية وتعارض حقيقي عند التصنيف.
- عدم تحويل كل اختلاف إلى تعارض؛ عدم إخفاء التعارض المصنَّف.

## 11. جدول تحقق EN / AR / TR / FR

| المستوى | EN | AR | TR | FR |
|---|---|---|---|---|
| CONTRACT | نعم | نعم | نعم | نعم |
| FIXTURE | نعم | نعم | نعم | نعم |
| LIVE_RETRIEVAL | نعم* | نعم* | نعم* | نعم* |
| LIVE_ANSWER | جزئي→قوي لـ deficit | جزئي | جزئي | جزئي |
| LIVE_UI | لم يكتمل | صدفة إدخال فقط | لا | لا |

\*استرجاع عبر مسار الوكيل؛ ليس PRODUCTION_READY للمؤشر المحلي.

## 12. نتائج Live End-to-End

المسار الحي عبر الوكيل موثّق في `live_answer_quality_probe.json` و`06-LIVE-END-TO-END-EVIDENCE.md`.  
لم يُثبت مسار UI الكامل.

## 13. React وFlutter

- React: الصفحة وصندوق البحث العلمي وقيمة السؤال العربي — PASS جزئي؛ إرسال الإجابة — BLOCKED.
- Flutter: NOT_RUN.

## 14. Regression وPerformance

- 55 اختباراً مرتبطاً نجحت (250 تأكيداً).
- زمن الحالات الحية ~6–26 ثانية؛ بدون تحسين يضعف الأدلة.

## 15. جدول Acceptance Gates

| # | البوابة | النتيجة | الدليل |
|---|---|---|---|
| 1 | Git Baseline | PASS | `00-MISSION-BASELINE.md` |
| 2 | Component Readiness | PARTIAL | جاهز مع قيود LocalIndex/UI |
| 3 | Baseline Quality | PASS | 48→55 اختبارات |
| 4 | Claim Decomposition | PARTIAL | ادعاءات حية موجودة؛ تفكيك سببي/تفسيري محدود في الواجهة |
| 5 | Claim Attribution | PASS | Ledger + صفوف حية |
| 6 | Citation Integrity | PASS | عقود + استشهادات حية دون اختلاق مثبت |
| 7 | Context Preservation | PARTIAL | جيد في EN؛ أضعف في مقتطفات متعددة اللغات |
| 8 | Conflict Assessment | PASS | عقود + ملاحظات حية |
| 9 | Scientific Synthesis | PASS | packet في الحالات المدعومة |
| 10 | Causal Reasoning | PASS | لا سببية مريخية مضللة بعد الإصلاح |
| 11 | Uncertainty Calibration | PASS | نصوص عدم يقين متناسبة |
| 12 | English Live | PARTIAL→قوي لـ deficit | مسبار |
| 13 | Arabic Live | PARTIAL | مسبار |
| 14 | Turkish Live | PARTIAL | مسبار؛ ليس LIVE_ANSWER كامل |
| 15 | French Live | PARTIAL | كذلك |
| 16 | Cross-Lingual Identity | PASS | sense/intent/crop محفوظة |
| 17 | React Live UI | BLOCKED | نقر مرفوض |
| 18 | Flutter Live UI | NOT_RUN | لا بيئة |
| 19 | Regression | PASS | 55 passed |
| 20 | Performance | PARTIAL | latencies فقط |
| 21 | Documentation | PASS | 00–12 + هذا التقرير |
| 22 | Commit Verification | PASS | `4469868265ca4a555555f29ccd0b2435103c54ff` |
| 23 | Remote Push Verification | يُحدَّث بعد الدفع | §18 |

## 16. أوامر الاختبارات والنتائج

```text
docker compose exec -T backend php artisan test --filter="AnswerComposerLiveQualityGateTest"
→ 3 passed (8 assertions)

docker compose exec -T backend php artisan test --filter="ScientificSynthesisEvidenceIntegrationContractTest|ComposerConflictExpressionContractTest|IrrigationGeneralitySemanticMatrixTest|ClaimAttributionLedgerTest|AnswerComposerLiveQualityGateTest|AnswerComposerExplanatoryFragmentContractTest"
→ 55 passed (250 assertions), ~52.5s
```

مسبار حي: مخرجات JSON في مجلد المهمة.

## 17. التغييرات التي تم Commit لها

انظر `11-GIT-AND-RELEASE-RECORD.md` — فقط ملفات التركيب/الجودة ووثائق المهمة.

## 18. إثبات Push وLocal/Remote SHA

- Commit المحلي: `4469868265ca4a555555f29ccd0b2435103c54ff`
- بعد Push/Fetch يجب تطابق `origin/phase-18-m18-ai-marketing-communications` مع Local HEAD (يُثبت في تحديث وثائقي لاحق إن لزم).

## 19. القيود والمناطق غير المتحقق منها

انظر `12-LIMITATIONS-AND-UNVERIFIED-AREAS.md`. أبرزها: React UI، Flutter، PRODUCTION_READY للمؤشر، اكتمال TR/FR، استقلال الأدلة مقابل عدد الاستشهادات.

## 20. القرار النهائي بشأن اكتمال المهمة

المهمة **مكتملة ضمن نطاقها** بالتصنيف:

**`MULTILINGUAL_LIVE_ANSWER_QUALITY_SYNTHESIS_WITH_LIMITATIONS`**

مع توثيق القيود وإصلاحات جذرية مثبتة واختبارات انحدار ناجحة وCommit/Push للتغييرات المقبولة.  
**لا** يُعلن تحقق Live UI كامل ولا تكافؤ جودة الإجابة الحية لكل اللغات الأربع.

---

## اقتراح الخطوة التالية (خطة هندسية — للتنفيذ لاحقاً فقط)

### A. Mission Name

**Multilingual Answer Substance Parity & Live UI Closure**

### B. Rationale

- الفجوة: إجابات TR/FR (وبدرجة أقل AR) تحافظ على الهوية العلمية لكن تضعف في اكتمال المادة التجريبية المفيدة؛ React LIVE_UI غير مثبت.
- الدليل: مسبار v2 (مقتطفات علاج الري بدل CWR كمّي؛ نقر المتصفح مرفوض).
- الأثر: المستخدم متعدد اللغات لا يحصل على فائدة عملية متكافئة رغم نجاح العقود.
- لماذا التالية: تغلق قيود التصنيف الحالي دون إعادة مهمة التركيب السابقة.

### C. Scope / Out of Scope

**داخل النطاق:** تحسين اختيار/تركيب الجمل التجريبية متعددة اللغات؛ إغلاق مسار React Live UI؛ معايير اكتمال CWR؛ اختبارات تكافؤ مادة الإجابة EN↔AR↔TR↔FR.  
**خارج النطاق:** إعادة ADR-022/023؛ توسيع مصادر مدفوعة؛ إعادة مهمة الري العمومي أو التركيب السياقي دون دليل انحدار جديد؛ تنظيف WIP غير المرتبط.

### D. Current State

- **Verified:** بوابات جودة EN، تركيب/تعارض عقود، مصفوفة الري، Ledger، هوية لغوية أساسية.
- **Not Verified:** React/Flutter عرض الإجابة الحي؛ تكافؤ مادة TR/FR.
- **Blocked:** أتمتة نقر المتصفح (يحتاج مساراً بديلاً أو موافقة).
- **Known Limitations:** LocalIndex ≠ PRODUCTION_READY.

### E. Proposed Architecture

- الإبقاء على AnswerComposer Hybrid وScientificSynthesisIntegrator وبوابات الأدلة.
- إضافة طبقة اختيار «جملة تجريبية مفضّلة» واعية باللغة دون ترجمة آلية مدفوعة.
- ضمان تمرير `scientific_synthesis` وclaims إلى مكوّن React الحالي دون نقل مسؤوليات Membership/Eligibility.
- قرار معماري مطلوب فقط إذا لزم توطين تجريبي (ترجمة) بدل عرض مقتطف المصدر — يُعرض قبل التنفيذ.

### F. Workstreams

| Workstream | Objective | Expected Files/Components | Tests | Dependencies |
|---|---|---|---|---|
| A | معيار اكتمال المادة متعدد اللغات | Evaluation harness / benchmark cases | Unit + fixture pairs | لا |
| B | تحسين اختيار النتائج لـ CWR/TR/FR | AnswerComposer (+Phrases) | LiveQuality + paired | A |
| C | React Live UI closure | frontend home scientific search | Playwright/manual + contract | Backend API up |
| D | Flutter smoke إن توفرت بيئة | admin-mobile | smoke | جهاز/محاكي |
| E | Docs + commit/push | mission folder | regression suite | B/C |

### G. Verification Strategy

Unit + Integration + Live Retrieval/API + E2E UI + Regression؛ لا LLM judge مدفوع.

### H. Acceptance Gates

1. زوج EN/AR/TR/FR لنفس المعنى يحقق PASS على Relevance+Usefulness دون FAIL على Evidence Support.  
2. React يعرض الإجابة والمراجع/عدم اليقين حياً.  
3. لا انحدار في مصفوفة الري وعقود التركيب.  
4. وثائق + Commit/Push مثبتا SHA.

### I. Risks and Mitigations

- خطر تخفيف بوابات الأدلة لرفع النسب → ممنوع صراحة؛ قيس بالـ FAIL على الأبعاد الحرجة.  
- خطر ترجمة مضللة → تفضيل مقتطف مدعوم مع غلاف لغوي أو قرار ADR.  
- WIP مختلط → عزل الالتزام كما في هذه المهمة.

### J. Definition of Done

1. معيار مادة الإجابة موثّق ومقاس.  
2. تحسين مثبت على TR/FR أو قيد معماري موثّق.  
3. React LIVE_UI_VERIFIED لحالة واحدة على الأقل EN وAR.  
4. Flutter: LIVE أو NOT_RUN بسبب موثّق.  
5. انحدار 55+ أو ما يعادلها أخضر.  
6. لا أسرار في Commit.  
7. Push عادي وLocal=Remote.  
8. تقرير عربي نهائي.  
9. لا بدء مهمة لاحقة تلقائياً.

### K. Dependencies and Readiness

**`READY_WITH_DEPENDENCIES`** — يعتمد على توفر واجهة React قابلة للأتمتة أو تحقق يدوي موثّق، واستقرار مصادر الاسترجاع المحلية.

### L. Execution Authorization

هذه خطة وتوصية فقط. **لا تُنفَّذ المهمة التالية إلا بتوجيه مستقل صريح.**
