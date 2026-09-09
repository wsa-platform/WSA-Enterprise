# ADR-001 — المعمارية الموحدة لمزودي ومهايئات الذكاء الزراعي
## Universal Agricultural Intelligence Provider & Adapter Architecture

**المشروع:** WSA-Enterprise  
**الحالة:** ACCEPTED — معتمد  
**نوع القرار:** قرار معماري ADR  
**النموذج:** Nygard Extended ADR  
**النطاق:** طبقة الذكاء الزراعي Agricultural Intelligence Layer  
**الأولوية:** عالية  
**الثقة المعمارية:** HIGH — عالية  
**حالة التنفيذ:** NOT YET IMPLEMENTED — لم يتم التنفيذ بعد  
**حالة التحقق:** NOT YET VALIDATED — لم يتم التحقق بعد

---

# 1. العنوان

## ADR-001: اعتماد المعمارية الموحدة لمزودي ومهايئات الذكاء الزراعي

يعتمد WSA-Enterprise معمارية موحدة قائمة على:

- Providers — مزودي الخدمات والمصادر
- Adapters — مهايئات التكامل
- Microservices — الخدمات المستقلة
- MCP Adapters — مهايئات MCP
- Scientific Providers — مزودي المصادر العلمية
- External Model Providers — مزودي النماذج الخارجية
- Execution Adapters — مهايئات المحركات التنفيذية

وذلك لدمج مصادر البيانات الزراعية، المصادر العلمية، خدمات الذكاء الاصطناعي، نماذج تشخيص الأمراض، MCP Servers، خدمات Python، واجهات APIs الخارجية، والمحركات الحسابية دون ربط WSA Core مباشرة بهذه المشاريع.

# 2. الحالة

**ACCEPTED — معتمد**

يمثل هذا القرار الأساس المعماري الرسمي لتكاملات الذكاء الزراعي الحالية والمستقبلية في WSA-Enterprise.

## دورة حياة القرار

```text
PROPOSED
    ↓
ACCEPTED
    ↓
IMPLEMENTED
    ↓
VALIDATED
```

الحالات المستقبلية المحتملة:

```text
SUPERSEDED
DEPRECATED
```

# 3. السياق

يهدف WSA-Enterprise إلى بناء منصة ذكاء زراعي شاملة قادرة على:

- الإجابة عن الأسئلة الزراعية.
- البحث في الويب.
- البحث في المصادر العلمية.
- استخدام البيانات الرسمية الزراعية.
- تحليل التربة.
- تحليل الظروف البيئية والمناخية.
- تحليل صور النباتات.
- تشخيص أمراض النباتات والمحاصيل.
- تقييم مخاطر الصقيع والحرارة.
- تقديم توصيات الري.
- حساب Growing Degree Days.
- تحليل الحقول.
- تشغيل النماذج الزراعية المتخصصة.
- استخدام MCP Servers.
- دمج عدة مصادر للوصول إلى إجابة أفضل.
- تقديم إجابات مدعومة بالأدلة والمصادر ومستوى الثقة.

تحتاج المنصة إلى التعامل مع عدد كبير من المشاريع والخدمات الخارجية، ومنها:

- FAO / FAOSTAT
- Open-Meteo
- Agriculture MCP Server
- AgriSignal-MCP
- Green-Sense
- FieldSense
- Plant Disease Detector
- leaf-diseases-detect
- FarmAdvisor AI
- FarmGuard AI
- Farm Disease Detection API
- Plant Disease Detection Web
- octoPus
- وغيرها مستقبلًا.

وتستخدم هذه المشاريع تقنيات مختلفة مثل REST API وMCP وPython وFlask وFastAPI وAI/ML Models وExternal APIs وExecutable Models وScientific Data Providers.

إدخال هذه المشاريع مباشرة داخل Laravel Core سيؤدي إلى زيادة الترابط وصعوبة الصيانة والتوسع والاختبار والاستبدال.

# 4. المشكلة

كيف يستطيع WSA-Enterprise دمج عدد كبير من مصادر البيانات الزراعية والنماذج والخدمات الخارجية دون ربط Laravel Core مباشرة بكل مشروع؟

ويجب أن تسمح المعمارية للنظام بأن:

- يختار المصدر المناسب للسؤال.
- يستخدم أكثر من مصدر عند الحاجة.
- يوحد النتائج المختلفة.
- يقارن النتائج المتعارضة.
- يقيّم مستوى الثقة.
- يقيّم قوة الأدلة العلمية.
- يعزل فشل أي Provider.
- يستبدل Provider بآخر.
- يضيف Providers جديدة بسهولة.
- يمنع تكرار الوظائف.
- يحافظ على Laravel Core نظيفًا.
- يدعم React وFlutter من خلال WSA فقط.

# 5. المتطلبات والقيود

يجب أن تحقق المعمارية ما يلي:

1. فصل WSA Core عن المشاريع الخارجية.
2. دعم REST APIs.
3. دعم MCP Servers.
4. دعم Python وFlask وFastAPI.
5. دعم AI/ML Models.
6. دعم المحركات الحسابية والتنفيذية.
7. دعم المصادر الزراعية الرسمية.
8. دعم المصادر العلمية.
9. دعم البحث العام في الويب.
10. توحيد نتائج المصادر المختلفة.
11. الاحتفاظ ببيانات الأدلة.
12. الاحتفاظ ببيانات الثقة.
13. دعم Consensus.
14. دعم اكتشاف التعارضات وحلها.
15. عزل فشل Provider.
16. حماية API Keys وSecrets.
17. دعم React وFlutter من خلال WSA.
18. منع منطق Crop-Specific غير الضروري.
19. منع Question-Specific Hacks.
20. احترام تراخيص المشاريع الخارجية.
21. السماح بإضافة أو إزالة Provider دون تغييرات جوهرية في WSA Core.

# 6. البدائل التي تمت دراستها

## البديل الأول — دمج الأكواد الخارجية مباشرة داخل Laravel

```text
Laravel
 ├── Python Code
 ├── AI Models
 ├── MCP
 ├── External APIs
 └── Executables
```

**القرار: مرفوض — REJECTED**

الأسباب: زيادة الترابط، صعوبة الصيانة والاختبار والتحديث، زيادة المخاطر الأمنية، إدخال تقنيات خارجية إلى Laravel Core، وصعوبة استبدال Providers.

## البديل الثاني — إنشاء Microservice زراعي ضخم

```text
Laravel
   ↓
Agricultural Mega-Service
   ├── Weather
   ├── Disease
   ├── Soil
   ├── AI
   ├── MCP
   └── Models
```

**القرار: مرفوض — REJECTED**

الأسباب: إنشاء Monolith جديد، زيادة نطاق الفشل، صعوبة التوسع والنشر، وربط جميع Providers ببعضها.

## البديل الثالث — ربط كل مشروع بشكل مستقل

```text
Laravel
 ├── Green-Sense
 ├── FieldSense
 ├── FarmGuard
 ├── AgriSignal
 ├── octoPus
 └── ...
```

**القرار: مرفوض — REJECTED**

الأسباب: تكرار الكود والبيانات، اختلاف صيغ النتائج، صعوبة إدارة التكاملات واختيار المصدر والتعامل مع الفشل.

## البديل الرابع — Provider / Adapter Architecture

```text
WSA Core
   ↓
Provider / Adapter Layer
   ↓
External Systems
```

**القرار: معتمد — ACCEPTED**

الأسباب: تقليل الترابط، سهولة استبدال Providers، المحافظة على نظافة Core، سهولة التوسع، عزل الأعطال، دعم تقنيات مختلفة، وسهولة الاختبار.

# 7. القرار

يعتمد WSA-Enterprise رسميًا:

## Universal Agricultural Intelligence Provider & Adapter Architecture

أي أن جميع الأنظمة الخارجية يجب أن تدخل إلى WSA من خلال Provider أو Adapter مناسب لطبيعتها.

ولا يجوز إدخال مشروع خارجي كامل داخل Laravel Core إلا إذا ثبت أن ذلك:

- ضروري.
- مبرر تقنيًا.
- آمن.
- متوافق مع الترخيص.
- لا يسبب Coupling غير ضروري.

# 8. البنية المعتمدة

```text
                         WSA-Enterprise
                                │
                                ▼
                  Universal Answer Orchestrator
                                │
             ┌──────────────────┼──────────────────┐
             │                  │                  │
             ▼                  ▼                  ▼
       Knowledge Layer    Agricultural Tools    AI Analysis
             │                  │                  │
             ▼                  ▼                  ▼
         Providers           Adapters          Providers
             │                  │                  │
             └──────────────────┼──────────────────┘
                                │
                                ▼
                       Normalization Layer
                                │
                                ▼
                    Evidence / Consensus Layer
                                │
                                ▼
                    Agricultural Reasoning
                                │
                                ▼
                       Answer Composer
                                │
                       ┌────────┴────────┐
                       ▼                 ▼
                     React             Flutter
```

# 9. أنواع التكامل المعتمدة

## 9.1 مزود مباشر — Native Provider

يستخدم للمصادر التي يمكن لـ WSA استدعاؤها مباشرة.

أمثلة: FAOSTAT وOpen-Meteo.

## 9.2 مزود علمي — Scientific Provider

للمصادر العلمية والبحثية والرسمية.

أمثلة: FAO وSemantic Scholar وOpenAlex وCrossref.

## 9.3 مهايئ MCP — MCP Adapter

لخوادم MCP.

```text
WSA
 ↓
MCP Adapter
 ↓
MCP Client
 ↓
External MCP Server
```

أمثلة: Agriculture MCP Server وAgriSignal-MCP.

## 9.4 مهايئ خدمة مستقلة — REST Microservice Adapter

للخدمات المستقلة المبنية باستخدام Python / Flask / FastAPI.

أمثلة: Green-Sense وFieldSense وPlant Disease Detector وleaf-diseases-detect.

## 9.5 مزود نموذج خارجي — External Model Provider

للنماذج الموجودة خارج WSA.

مثال: Hugging Face Disease Detection.

## 9.6 مهايئ محرك تنفيذي — Execution Adapter

للمحركات التي تحتاج إلى تشغيل Executable أو Computational Engine.

مثال: octoPus.

## 9.7 مزود دراسة واستخراج — Study / Extract Provider

للمشاريع التي تحتوي على مكونات مفيدة ولكن لا توجد حاجة لإدخال التطبيق كاملًا إلى WSA.

أمثلة: Plant Disease Detection Web وFarmGuard AI Components.

# 10. سجل Providers المركزي

يعتمد WSA على:

```text
AgriculturalProviderRegistry
```

ويحتوي على بيانات مثل:

```text
Provider
Type
Capabilities
Inputs
Outputs
Priority
Timeout
Health Status
Configuration
Version
License
Confidence Metadata
```

ويستخدم Answer Orchestrator هذا السجل لاختيار Provider المناسب.

# 11. معمارية ذكاء وتشخيص أمراض النباتات

يتم إنشاء واجهة موحدة:

```text
PlantDiseaseAnalysisProvider
```

وتحتها يمكن تشغيل:

```text
GreenSenseProvider
PlantDiseaseDetectorProvider
LeafDiseaseProvider
FarmAdvisorProvider
FarmGuardProvider
HuggingFaceDiseaseProvider
PlantDiseaseWebProvider
```

المسار:

```text
Plant Image
     ↓
Disease Orchestrator
     ↓
Provider Selection
     ↓
One or More Providers
     ↓
Predictions
     ↓
Normalization
     ↓
Consensus
     ↓
Confidence
     ↓
Scientific Evidence
     ↓
Final Result
```

ويجب عدم اعتبار Model Confidence دليلًا علميًا نهائيًا.

# 12. معمارية الذكاء البيئي

يعتمد WSA على واجهة موحدة:

```text
EnvironmentalProvider
```

ويمكن أن تستخدم Open-Meteo وAgriSignal-MCP وAgriculture MCP Server.

ويجب منع تكرار تنفيذ Weather/Environmental APIs بدون حاجة.

# 13. تكامل AgriSignal-MCP

يتم دمج AgriSignal-MCP باعتباره **MCP Provider واحدًا**، ولا يتم إنشاء Microservice مستقل لكل أداة.

الأدوات تشمل:

```text
get_irrigation_advice
get_frost_and_heat_risk
get_growing_degree_days
get_soil_profile
get_growing_conditions
get_dry_spell_status
```

```text
AgriSignalAdapter
       ↓
AgriSignal-MCP
       │
       ├── Irrigation Advice
       ├── Frost / Heat Risk
       ├── Growing Degree Days
       ├── Soil Profile
       ├── Growing Conditions
       └── Dry Spell Status
```

# 14. تكامل FAOSTAT

يتم اعتماد FAOSTAT كمصدر رسمي للبيانات الزراعية:

```text
FAOProvider
     ↓
FAOSTAT API
     ↓
Normalized Agricultural Data
     ↓
Evidence Layer
```

ولا يتم إنشاء Microservice وسيط دون سبب تقني واضح.

# 15. تكامل Open-Meteo

يتم اعتماد Open-Meteo كمصدر مركزي للبيانات الجوية والبيئية:

```text
WeatherProvider
      ↓
Open-Meteo
      ↓
Canonical Weather Data
```

وتستخدم بقية مكونات WSA WeatherProvider بدل إنشاء تطبيقات Weather مكررة.

# 16. تكامل FieldSense

يعمل FieldSense كخدمة Python مستقلة:

```text
FieldSenseProvider
       ↓ REST
FieldSense Service
       ↓
ML / Field Analysis
       ↓
Normalized Result
```

ولا يتم إدخال كود Python مباشرة داخل Laravel Core.

# 17. تكامل Green-Sense

يعمل Green-Sense كخدمة مستقلة:

```text
GreenSenseProvider
       ↓ REST
Green-Sense Service
       ↓
Image Analysis
       ↓
Normalized Disease Result
```

ولا يتم نسخ كود Green-Sense داخل Laravel Core إلا بقرار منفصل.

# 18. تكامل leaf-diseases-detect

يتم تشغيله كخدمة FastAPI مستقلة:

```text
LeafDiseaseProvider
       ↓ REST
FastAPI
       ↓
Disease Analysis
       ↓
Normalized Result
```

ويجب أن تبقى مفاتيح الخدمات الخارجية مثل API Keys داخل إعدادات الخدمة، وألا تصل إلى React أو Flutter.

# 19. تكامل Plant Disease Detector

يتم التعامل معه كـ Python/Flask Disease Provider:

```text
PlantDiseaseDetectorProvider
       ↓ REST
Python / Flask Service
       ↓
Prediction
       ↓
Normalized Result
```

# 20. تكامل FarmAdvisor AI

يتم التعامل معه كـ Disease Provider:

```text
FarmAdvisorProvider
       ↓
FarmAdvisor AI
       ↓
Disease Prediction
       ↓
WSA Normalization
```

# 21. تكامل FarmGuard AI

لا يتم إدخال FarmGuard AI كاملًا إلى WSA بشكل افتراضي.

يتم الاستفادة من المكونات المناسبة:

```text
Vision      → Disease Provider
Weather     → WSA WeatherProvider
Speech      → Frontend Capability
PDF         → WSA Reporting
```

والهدف هو منع تكرار الوظائف الموجودة أصلًا في WSA.

# 22. تكامل Hugging Face Disease Detection

يتم التعامل معه كـ External Model Provider:

```text
HuggingFaceDiseaseProvider
       ↓
Hugging Face
       ↓
Prediction
       ↓
Normalizer
```

ويجب تقييم التوفر، زمن الاستجابة، Rate Limits، تحميل النموذج، الاعتمادية، والخصوصية قبل جعله اعتمادًا إنتاجيًا أساسيًا.

# 23. تكامل octoPus

يتم التعامل مع octoPus كـ Computational Execution Engine.

```text
WSA
 ↓
OctoPusAdapter
 ↓
Validated JSON / CSV
 ↓
octoPus Executable
 ↓
Output
 ↓
Normalizer
 ↓
WSA
```

يجب أن يتضمن التنفيذ Input Validation وOutput Validation وProcess Isolation وTimeout وError Handling وLogging وResource Limits.

ويجب مراجعة ترخيص المشروع قبل الاستخدام التجاري أو إعادة التوزيع أو تعديل الكود.

# 24. تكامل Agriculture MCP Server

يتم دمجه عبر MCP Adapter:

```text
AgricultureMcpAdapter
       ↓
MCP Client
       ↓
Agriculture MCP Server
       ↓
Agricultural Tools / Data
       ↓
WSA
```

ويتم استخدامه فقط عندما تكون قدراته مناسبة للسؤال، مع منع تكرار البيانات الموجودة بالفعل في Providers أخرى عندما تكون هناك إمكانية لذلك.

# 25. تكامل Plant Disease Detection Web

لا يتم إدخال التطبيق كاملًا إلى WSA.

```text
Study
 ↓
Model / Classifier / API Analysis
 ↓
Identify Useful Components
 ↓
Extract or Wrap Useful Capability
 ↓
Provider
```

وأي إعادة استخدام مباشر للكود يحتاج إلى مراجعة تقنية وأمنية وترخيصية واختبارات توافق ووظائف.

# 26. اختيار Provider

لا يجب تشغيل جميع Providers لكل سؤال.

```text
User Question
      ↓
Question Understanding
      ↓
Capability Resolution
      ↓
Provider Selection
      ↓
Provider Execution
```

ويتم الاختيار وفقًا لـ Capability وRelevance وSource Quality وAvailability وConfidence وFreshness وLatency وCost عند وجود تكلفة وطبيعة السؤال.

# 27. توحيد النتائج — Normalization

بسبب اختلاف صيغ النتائج، تمر جميع النتائج عبر:

```text
Provider Result
      ↓
Normalizer
      ↓
Canonical Agricultural Result
```

ويمكن أن تحتوي النتيجة الموحدة على:

```text
subject
crop
disease
disease_type
severity
confidence
symptoms
causes
recommendations
measurements
units
source
timestamp
evidence
limitations
```

# 28. دمج الأدلة — Evidence Fusion

بعد الحصول على النتائج:

```text
Results
 ↓
Deduplication
 ↓
Conflict Detection
 ↓
Ranking
 ↓
Evidence Fusion
 ↓
Scientific Validation
 ↓
Answer Eligibility
```

نتيجة Provider واحدة لا تعتبر تلقائيًا حقيقة علمية.

# 29. نموذج الثقة

يجب فصل ثلاثة أنواع من الثقة:

```text
Architectural Confidence
        ≠
Provider / Model Confidence
        ≠
Scientific Evidence Confidence
```

- **الثقة المعمارية:** تقيس ثقتنا في القرار المعماري.
- **ثقة Provider / Model:** الثقة التي يقدمها النموذج أو الخدمة أو التي يتم استنتاجها من أدائها.
- **ثقة الأدلة العلمية:** قوة الأدلة العلمية التي تدعم النتيجة.

لا يجوز استخدام أحد هذه الأنواع بدل الآخر.

# 30. الثقة المعمارية لهذا القرار

**HIGH — عالية**

السبب: المعمارية لا تعتمد على Provider واحد، وتسمح باستبدال Providers، وتعزل الخدمات الخارجية، وتقلل الترابط، وتسمح باستخدام عدة مصادر، وتدعم Failure Isolation، وتسمح بإضافة Providers جديدة.

# 31. عزل فشل Providers

فشل Provider واحد لا يجب أن يؤدي تلقائيًا إلى فشل النظام بالكامل.

```text
Provider A
   ↓
FAIL
   ↓
Isolation
   ↓
Provider B
   ↓
Provider C
   ↓
Continue Answer Pipeline
```

# 32. الأمن

كل Provider وAdapter يجب أن يراعي:

- Authentication.
- Authorization.
- Input Validation.
- Output Validation.
- Timeout.
- Rate Limiting.
- Logging.
- Error Handling.
- Secret Management.
- Process Isolation.
- Resource Limits.

ولا يجوز تخزين Secrets في React أو Flutter أو Git أو Public Configuration.

# 33. عقود التكامل — Integration Contracts

كل Provider يجب أن يمتلك Integration Contract واضحًا يحدد:

```text
Input
Output
Errors
Timeout
Authentication
Health Check
Version
Capabilities
Confidence
Evidence
Limitations
```

الفرق:

**ADR يجيب عن:** لماذا اخترنا هذه المعمارية؟

**Integration Contract يجيب عن:** كيف يتعامل WSA تقنيًا مع هذا Provider؟

# 34. سجل القرارات المعمارية المركزي

يجب تخزين القرارات تحت:

```text
/docs/adr/
```

ويعتبر هذا المسار مصدر الحقيقة الرسمي للقرارات المعمارية.

# 35. معيار ADR الموحد

يجب أن يحتوي كل ADR جديد على:

```text
Title
Status
Date
Context
Problem
Requirements / Constraints
Alternatives Considered
Decision
Scope / Boundaries
Consequences
Risks
Confidence
Validation / Acceptance
Related ADRs
```

# 36. سجل القرارات — Decision Log

يجب أن يحتوي:

```text
/docs/adr/README.md
```

على فهرس مركزي للقرارات.

ويجب عدم إنشاء ADR منفصل لكل وظيفة صغيرة بدون سبب معماري.

# 37. دورة حياة القرار

```text
PROPOSED
    ↓
ACCEPTED
    ↓
IMPLEMENTED
    ↓
VALIDATED
```

وفي حالة الاستبدال: `SUPERSEDED`، وفي حالة الإيقاف: `DEPRECATED`.

# 38. العواقب الإيجابية

- قابلية التوسع.
- قابلية الاستبدال.
- عزل الأعطال.
- تعدد المصادر.
- Consensus.
- Evidence.
- سهولة الصيانة.
- قابلية التطوير المستقبلي لدعم Satellite وIoT وDrones وGIS وSensors وRemote Sensing ونماذج ومصادر جديدة.

# 39. العواقب السلبية

تضيف المعمارية بعض التعقيد:

- زيادة عدد Adapters.
- زيادة عدد الخدمات.
- الحاجة إلى Monitoring.
- الحاجة إلى Normalization.
- إدارة نسخ APIs.
- إدارة خدمات Python.
- إدارة MCP Servers.
- زيادة Integration Tests.
- إدارة External Dependencies.

يتم قبول هذه التكاليف مقابل قابلية التوسع والاستقرار وقابلية الصيانة.

# 40. المخاطر وطرق المعالجة

| الخطر | المعالجة |
|---|---|
| توقف Provider | Failure Isolation |
| تغير API | Adapter + Versioning |
| نتيجة خاطئة من AI | Evidence + Validation |
| اختلاف صيغ النتائج | Normalization |
| تعارض النتائج | Conflict Resolution |
| تسريب API Key | Secret Management |
| بطء الخدمة | Timeout |
| إساءة الاستخدام | Rate Limiting |
| مشاكل الترخيص | License Review |
| تكرار البيانات | Provider Registry |
| تكرار Weather APIs | Central Weather Provider |
| تكرار Disease Engines | Disease Provider Interface |
| توقف مشروع خارجي | Health Monitoring + Replaceability |

# 41. معايير القبول — Acceptance Criteria

لا يعتبر هذا القرار منفذًا بصورة صحيحة إلا بعد تحقق:

- [ ] جميع التكاملات تستخدم Provider/Adapter Boundaries.
- [ ] لا يوجد كود خارجي غير ضروري داخل Laravel Core.
- [ ] وجود Provider Registry.
- [ ] وجود Integration Contracts.
- [ ] وجود Normalization Layer.
- [ ] وجود Evidence Fusion.
- [ ] وجود Conflict Resolution.
- [ ] وجود Provider Failure Isolation.
- [ ] وجود Health Checks عند الحاجة.
- [ ] وجود Timeouts.
- [ ] وجود Security Controls.
- [ ] توحيد Disease Providers.
- [ ] توحيد Weather Provider.
- [ ] توحيد MCP Integrations.
- [ ] React لا يتصل مباشرة بالخدمات الخارجية.
- [ ] Flutter لا يتصل مباشرة بالخدمات الخارجية.
- [ ] عدم وجود Question-Specific Hacks.
- [ ] عدم وجود Crop-Specific Hacks غير مبررة.
- [ ] مراجعة تراخيص المشاريع الخارجية.
- [ ] وجود اختبارات للـ Providers/Adapters.
- [ ] وجود وثائق ADR.
- [ ] إمكانية إضافة Provider جديد دون تعديل جوهري في WSA Core.

# 42. القرارات المرتبطة

```text
ADR-001
Provider / Adapter Architecture
        │
        ├── ADR-002 Universal Answer Orchestrator
        ├── ADR-003 Scientific / Web Evidence Fusion
        ├── ADR-004 Plant Disease Provider Architecture
        ├── ADR-005 MCP Integration Architecture
        └── ADR-006 External Microservice Architecture
```

# 43. القرار المعماري النهائي

يعتمد WSA-Enterprise رسميًا:

## Universal Agricultural Intelligence Provider & Adapter Architecture

والقاعدة الأساسية:

> الأنظمة الخارجية هي Providers أو Adapters أو Services أو Engines، وليست جزءًا من WSA Core.

والقاعدة الثانية:

> WSA هو العقل المركزي المسؤول عن اختيار المصادر، تنسيقها، تشغيلها، توحيد نتائجها، مقارنة الأدلة، اكتشاف التعارضات، تقييم الثقة، التحقق العلمي، وصياغة الإجابة النهائية.

والقاعدة الثالثة:

> لا يتم إدخال مشروع خارجي كامل إلى WSA Core إلا إذا كان مبررًا تقنيًا وضروريًا وآمنًا ومتوافقًا مع الترخيص.

والقاعدة الرابعة:

> كل قرار معماري جديد يجب توثيقه وفق Extended Nygard ADR داخل `/docs/adr/`، مع تسجيل السياق والبدائل والعواقب والمخاطر والثقة ومعايير التحقق.

# 44. الحالة النهائية

**الحالة:** ACCEPTED — معتمد  
**الثقة المعمارية:** HIGH — عالية  
**حالة التنفيذ:** NOT YET IMPLEMENTED — لم يتم التنفيذ بعد  
**حالة التحقق:** NOT YET VALIDATED — لم يتم التحقق بعد

هذا القرار هو المرجع المعماري الرسمي لتكاملات طبقة الذكاء الزراعي في WSA-Enterprise.
