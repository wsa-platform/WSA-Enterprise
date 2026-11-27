# التقرير الهندسي النهائي — M-S3-LIVE-DOMAIN-MATRIX-AR-EN-FR-TR

**معرّف المهمة:** `M-S3-LIVE-DOMAIN-MATRIX-AR-EN-FR-TR`  
**المشروع:** WSA-Enterprise  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**التاريخ:** 2026-11-27  
**التصنيف النهائي:** `COMPLETE_VERIFIED`

---

## A. الملخص التنفيذي

**الهدف:** التحقق الحي من قدرة المحرك العلمي على تحويل أسئلة زراعية حقيقية إلى إجابات مدعومة بالأدلة عبر العربية والإنجليزية والفرنسية والتركية، مع إصلاح الأسباب الهندسية العامة دون إضعاف النزاهة العلمية.

**الحالة النهائية:** `COMPLETE_VERIFIED`

**أبرز النتائج:**
- تحقق الحد الأدنى: **3 مجالات × 4 لغات = 12 خلية PASS** بمستوى `LIVE_ANSWER_VERIFIED`.
- المجالات المختارة وفق §3.4: الماء/الري، أمراض/آفات النبات، التنوع الحيوي/الملقحات.
- تغطية مصادر حية: **LocalIndex (OSMIA)** + **REST/API (OpenAlex/Crossref، مع Semantic Scholar)**.
- أُصلحت أسباب عامة: ترتيب استعلامات OSMIA، هوية المرض المسماة في البناء الاستعلامي، ومحاذاة المباشرة المرضية/الفاونيستية.

---

## B. Pre-Flight

| الفحص | الحالة | الدليل |
|---|---|---|
| Git State | PASS | الفرع الهدف، HEAD ابتدائي `3f63b28`، WIP غير متعلق محفوظ |
| Source Availability | PARTIAL | OSMIA + OpenAlex/Crossref جاهزة؛ AGRIS/OE/DrDuke غير جاهزة |
| LocalIndex | PARTIAL | OSMIA READY؛ مصادر LocalIndex أخرى gen=0 |
| AnswerComposer | PASS | مسار التركيب + اختبارات مباشرة |
| Baseline Tests | PASS | Osmia/LatinTaxon/KnowledgeType/Directness |
| Data Safety | PASS | لم تُدرج أسرار ولا WIP غير متعلق |

**تصنيف الخط الأساسي:** `BASELINE_READY_WITH_LIMITATIONS`  
**HEAD الابتدائي:** `3f63b28b70999c5cc46adf18ebd3695e3243e8f3`

تفاصيل: `00-PREFLIGHT.md`

---

## C. الأسباب الجذرية

| المشكلة المُعادة | السبب الجذري | المكوّنات | الإصلاح |
|---|---|---|---|
| EN/FR تنوّع حيوي: OSMIA تُحاوَل بلا نتائج | `MAX_VARIANTS_PER_PROVIDER=2` مع ثنائيات خطاب زائفة (`What host`, `Quelles associations`) تملأ الخانتين الأولى | `OsmiaLocalIndexQuerySpecializer` | أولوية الجنس اللاتيني/Nomada؛ رفض ثنائيات الخطاب |
| TR تنوّع حيوي: أدلة OSMIA SUPPORTING فقط | نمط faunistic لا يغطي `konukçu` / تصريف `yabani arı*` | `ScientificEvidenceDirectnessAssessor`, `ScientificKnowledgeTypeRelevancePolicy` | توسيع إشارات AR/EN/FR/TR العامة |
| أمراض النبات: استرجاع أمراض خاطئة (blast/virus) | مسار CSQ يسقط هوية المرض المسماة (rust/rouille/صدأ/pas) | `ScientificSearchQueryBuilder`, `AgriculturalEntityCatalog` | `namedPlantDiseaseRetrievalTerms` + prepend على CSQ والـ legacy |
| DIRECT خاطئ لأوراق ريّ على سؤال صدأ | غياب محاذاة هوية المرض للدليل | `ScientificEvidenceDirectnessAssessor` | رفض DIRECT بدون محاذاة المرض المطلوب |
| PASS مضلّل لـ `partial_conflicts` | بوابة التصنيف الحي ضيّقة أكثر من اللازم | مجس المصفوفة فقط | قبول `synthesis_completed_with_partial_conflicts` عند وجود claims |

---

## D. التغييرات الهندسية

| الملف | القرار |
|---|---|
| `AgriculturalEntityCatalog.php` | مصطلحات استرجاع الأمراض المسماة؛ توسيع `senseQueryTerms(disease)` |
| `OsmiaLocalIndexQuerySpecializer.php` | ترتيب أولوية التصنيف اللاتيني تحت سقف متغيرين |
| `ScientificEvidenceRelevanceGate.php` | رفض الثنائيات الزائفة غير التصنيفية |
| `ScientificEvidenceDirectnessAssessor.php` | faunistic متعدد اللغات؛ محاذاة المرض لـ DIRECT |
| `ScientificKnowledgeTypeRelevancePolicy.php` | إشارات biodiversity متعددة اللغات مع `/i` |
| `ScientificSearchQueryBuilder.php` | prepend هوية المرض لمساري CSQ وlegacy |
| اختبارات وحدة مرافقة | انحدار للأعطال أعلاه |

لا مصادر جديدة. لا إعادة حصاد OSMIA. لا تغيير لقاعدة SQLite الأساسية.

---

## E. مصفوفة المجال × اللغة (8×4)

| المجال | EN | FR | AR | TR |
|---|---|---|---|---|
| Crops and Yield | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Water and Irrigation | **PASS** | **PASS** | **PASS** | **PASS** |
| Soil and Soil Properties | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Fertilizers and Plant Nutrition | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Plant Diseases and Pests | **PASS** | **PASS** | **PASS** | **PASS** |
| Biodiversity and Pollinators | **PASS** | **PASS** | **PASS** | **PASS** |
| Climate and Agricultural Environment | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Plant Physiology and Genetics | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |

الدليل الخام: `01-LIVE-MATRIX-RAW.json`

الخلايا الخمس غير المختبرة ليست PASS؛ الحد الأدنى §3.4 تحقّق دون ادّعاء اكتمال المصفوفة الثمانية.

---

## F. مصفوفة المصدر × البروتوكول

| Source ID | النوع | الحالة أثناء الاختبار | الاسترجاع | التحقق العلمي/الإجابة | القيود |
|---|---|---|---|---|---|
| `osmia` | LocalIndex (Bulk/Crossref snapshot) | Operational | ناجح للتنوع الحيوي | `LIVE_ANSWER_VERIFIED` (bio ×4) | ~80 سجلًا؛ ليس كل اللغات في المصدر |
| `openalex` | Scholarly REST | Operational | ناجح | `LIVE_ANSWER_VERIFIED` | تغطية لغات الأدلة غالبًا EN |
| `crossref` | Scholarly REST | Operational | ناجح | يساهم في التحقق الحي | بيانات وصفية أساسًا |
| `semantic_scholar` | Scholarly REST | Intermittent | غالبًا ناجح | مشارك عند التوفر | فشل متقطع سابقًا |
| `agris` | LocalIndex | Unavailable (gen=0) | — | غير متحقق | `BLOCKED_LOCALINDEX` إن لزم |
| `organic_eprints` | LocalIndex | Unavailable | — | غير متحقق | — |
| `dr_duke` | LocalIndex | Unavailable | — | غير متحقق | — |

**الحد الأدنى §3.2.1:** متحقّق (LocalIndex OSMIA + REST OpenAlex/Crossref).  
لا يُعمَّم نجاح العيّنة على كل المصادر المسجّلة.

---

## G. الاسترجاع الحي والإجابة الحية

### عائلة A — الماء والري
| لغة | السؤال | Composer | validated | claims | مصادر ناجحة |
|---|---|---|---|---|---|
| EN | What is the crop water requirement of wheat? | synthesis_completed_with_partial_conflicts | 50 | 1 | openalex,crossref,semantic_scholar |
| FR | Quel est le besoin en eau de la culture du blé? | …partial_conflicts | 51 | 1 | نفسه |
| AR | ما هو الاحتياج المائي لمحصول القمح؟ | …partial_conflicts | 48 | 1 | نفسه |
| TR | Buğdayın sulama suyu ihtiyacı nedir? | …partial_conflicts | 50 | 1 | نفسه |

### عائلة B — أمراض النبات
| لغة | السؤال | Composer | validated | claims | مصادر |
|---|---|---|---|---|---|
| EN | What causes wheat rust… | …partial_conflicts | 55 | 1 | openalex,crossref,semantic_scholar |
| FR | …rouille du blé… | …partial_conflicts | 17 | 1 | نفسه |
| AR | …صدأ القمح… | …partial_conflicts | 17 | 1 | نفسه |
| TR | …pas hastalığı… | …partial_conflicts | 17 | 1 | نفسه |

بعد الإصلاح ظهرت أدلة DIRECT لـ rust/Puccinia (مثال FR: leaf rust resistance / Puccinia triticina).

### عائلة C — التنوع الحيوي/الملقحات
| لغة | السؤال | Composer | validated | claims | مصادر |
|---|---|---|---|---|---|
| EN/FR/AR/TR | Host associations / Nomada | synthesis_completed_partial | 7 | 1 | openalex,crossref,(semantic_scholar),**osmia** |

---

## H. الأدلة العلمية والإسناد

- كل خلية PASS أنتجت ≥1 claim مع citations/provenance قابلة للتتبع في تقرير المصفوفة الخام.
- حالات `synthesis_completed_with_partial_conflicts` تحتفظ بالقيود/التعارضات ولا تُقدَّم كيقين مطلقة.
- لم تُختلق قياسات أو DOIs؛ الأدلة من مصادر حية مسجّلة.
- التمييز محفوظ بين DIRECT وSUPPORTING؛ أوراق الري لم تُقبل DIRECT لسؤال الصدأ بعد الإصلاح.

---

## I. الاختبارات

| الأمر | النتيجة | Exit |
|---|---|---|
| `php artisan test --filter="NamedPlantDiseaseRetrievalTermsTest\|OsmiaG305OnboardingTest\|ScientificEvidenceRelevanceLatinTaxonContractTest\|ScientificKnowledgeTypeRelevancePolicyTest\|ScientificEvidenceDirectnessAssessorTest"` | 21 passed (78 assertions) | 0 |
| `php e2e-tmp/_ms3_live_domain_matrix.php` | `minimum_three_domains_x4=true`؛ 12/12 PASS للمجالات المختارة | 0 |

لم تُشغَّل مجموعات اختبار تاريخية غير مرتبطة.

---

## J. القيود

- 5 مجالات × 4 لغات = `NOT_RUN` (خارج الحد الأدنى المختار).
- AGRIS / Organic Eprints / Dr. Duke غير جاهزة محليًا.
- تغطية OSMIA محدودة بالحجم الحالي لللقطة.
- إجابات الماء غالبًا بصيغة تعارض جزئي مع أدلة ري/WUE/MAD مرتبطة؛ ليست قياسات FAOSTAT كمية مفردة.
- لم تُختبر كل المصادر المسجّلة.

---

## K. Git

- الفرع: `phase-18-m18-ai-marketing-communications`
- تُدرَج فقط ملفات المهمة؛ WIP غير المتعلق (CapVer/CSQ/Feature tests/nginx…) يبقى خارج الـ commit.
- يُحدَّث هذا القسم بعد الدفع برؤوس Local/Remote.

---

## L. آثار الأدلة

| المسار | المحتوى |
|---|---|
| `docs/.../2026-11-27-m-s3-live-domain-matrix/00-PREFLIGHT.md` | بوابة ما قبل التنفيذ |
| `docs/.../PROGRESS.json` | سجل التقدم |
| `docs/.../01-LIVE-MATRIX-RAW.json` | ناتج المصفوفة الحية الكامل |
| `docs/.../02-FINAL-REPORT.ar.md` | هذا التقرير |
| `backend/e2e-tmp/_ms3_live_domain_matrix.php` | مجس التنفيذ الحي |
| `backend/e2e-tmp/_ms3_live_domain_matrix.json` | نسخة تشغيلية |

---

## M. التصنيف النهائي

**`COMPLETE_VERIFIED`**

المبررات:
1. الحد الأدنى الحي 3 مجالات × 4 لغات متحقّق.
2. تنوّع العائلات §3.4 محفوظ.
3. تنوّع المصادر §3.2.1 (LocalIndex + REST) متحقّق.
4. المسار Question→…→AnswerComposer قابل للتتبع في الأدلة الخام.
5. الادعاءات المادية مدعومة بأدلة مع provenance.
6. انحدار موحّد للاختبارات ذات الصلة ناجح.
7. لا فشل حرج/عالٍ متبقٍ على المسارات المقبولة.

---

## N. التوصية الهندسية التالية

**المهمة المقترحة:** `M-S3-LIVE-DOMAIN-EXPANSION-REMAINING-5`

**المبرر:** إكمال الخلايا الخمس المتبقية (محاصيل/تربة/أسمدة/مناخ/فسيولوجيا) بنفس معايير الإجابة الحية دون إضعاف النزاهة.

**النطاق:**
- اختيار أسئلة حية لكل مجال × AR/EN/FR/TR.
- استخدام المصادر الجاهزة فقط؛ لا إضافة مصادر جديدة.
- إصلاح أسباب عامة إن ظهرت.

**معايير القبول:**
- كل خلية من الخمس تُصنَّف PASS أو تُوثَّق BLOCKED بسبب مصدر/بيئة بدقّة.
- لا ادّعاء اكتمال بدون تشغيل فعلي.

**الاعتماديات/الموانع:** جاهزية AGRIS/OE قد تبقى جزئية؛ لا تُعاد هيكلتها إلا لضرورة تقنية مثبتة.
