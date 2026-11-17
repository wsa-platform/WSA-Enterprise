# تدقيق هيمنة Semantic Scholar في البحث العلمي — Rev 10.27

التاريخ: 2026-11-17  
النطاق: تدقيق مستقل عن BY0، من دون تشغيل ingest أو FTS أو checkpoint ومن دون الكتابة إلى Scientific LocalIndex SQLite الإنتاجي.

## الخلاصة التنفيذية

- لم يثبت أن المنسق يستدعي Semantic Scholar وحده. الاختيار التشغيلي الحالي يضم FAOSTAT وOpenAlex وCrossref وSemantic Scholar وAGRIS وDr Duke وGBIF وOrganic Eprints وBHL.
- لم يثبت تعطيل المصادر الأخرى بالرايات. جميع المصادر المسجلة كانت مفعلة في إعداد التشغيل.
- بيانات الاعتماد ليست السبب العام: مفتاح Semantic Scholar الاختياري موجود، مفتاح OpenAlex الاختياري غير موجود، Crossref وGBIF لا يحتاجان مفتاحاً، ومفتاح BHL وزوج اعتماد FAOSTAT موجودان. مفتاح Consensus المطلوب غير موجود، لكنه مصدر اختياري لا يُختار تلقائياً.
- البحث الحي المعزول كان متعدد المصادر فعلاً، لكن المراجع النهائية بقيت من مزود واحد أو بلا مراجع. السبب الرئيسي هو عقد الدليل: `AnswerComposer` يقبل في المراجع الأساسية دليلاً DIRECT فقط، أو SUPPORTING مؤهلاً في وضع محدود، ولا يفرض تنوع المزودين.
- لم تُجرَ معالجة برمجية؛ فرض مرجع ثانٍ ضعيف فقط لتحقيق التنوع سيخالف بوابة الدليل المباشر.

## الحالة

### Outcome A — التشخيص والمعالجة

`PASS_DIAGNOSTIC_COMPLETE_NO_SAFE_CODE_REMEDIATION_REQUIRED`

اكتمل تتبع خط البحث وفحص السجل والعقود والإعدادات والتنفيذ الحي الآمن. لم يُثبت عيب Tier 1 يحتاج تعديلاً برمجياً. أضيفت وثائق التدقيق فقط.

### Outcome B — قبول تشغيلي متعدد المصادر

`FAIL_NOT_OPERATIONALLY_ACCEPTED`

شرط القبول هو مساهمة مزودين مستقلين على الأقل في المراجع النهائية. لم يتحقق هذا الشرط في أي من الاستعلامات التمثيلية الثلاثة، رغم نجاح أكثر من مزود في الاسترجاع.

## مسار التنفيذ الكامل

1. يبني `ScientificSearchQueryBuilder` متغيرات الاستعلام، ثم يطبق `ScientificSearchVariantBudget` الحدود.
2. يختار `ScientificSourceSelector` المصادر المفعلة والمصرح بها من `ScientificSourceCatalog`.
3. يحل `ScientificSourceAdapterRegistry` المحولات من السجل القانوني ويتحقق من مفتاح المصدر وإصدار العقد.
4. ينفذ `MultiSourceScientificSearchOrchestrator` المزودين المستقلين، يعزل الأعطال، ويطبق ميزانية زمنية مشتركة.
5. تطبع المحولات النتائج إلى `ScientificSearchResult` مع الهوية والمنشأ.
6. يزيل `ScientificResultDeduplicator` التكرار بحسب DOI أو URL أو هوية المزود مع الاحتفاظ بكل سجلات المنشأ.
7. يرتب `ScientificResultRanker` النتائج ويستبعد غير الملائم.
8. تتحقق `AgriculturalScientificValidationService` من الهوية والجودة وعلاقة الادعاء ومباشرة الدليل.
9. يبني `AnswerComposer` الادعاءات والمراجع من الأدلة القابلة للاستخدام فقط.
10. يعيد `AgriculturalResearchAgentController` و`AgriculturalResearchAgent` البحث والتحقق والتركيب و`user_presentation`.
11. تعرض الواجهة `scientificUserPresentation.ts` و`ScientificResearchResultsList.tsx` النتائج ذات الثقة `>= 0.50`، من دون منطق لفرض تنوع المزودين.

## نتائج Tier 1

- **المنسق يستدعي Semantic Scholar فقط:** مرفوض. أظهرت آثار الاختيار والتنفيذ استدعاء عدة مزودين.
- **الرايات تعطل المصادر الأخرى:** مرفوض. جميع التسجيلات التشغيلية مفعلة.
- **بيانات الاعتماد مفقودة أو غير صالحة:** مرفوض جزئياً. الغياب المؤثر يخص Consensus الاختياري فقط؛ OpenAlex يعمل من دون مفتاح، وCrossref وGBIF لا يحتاجان مفتاحاً.

لم تُطبع أو تُسجل أي قيمة سرية؛ فُحص الوجود فقط.

## الأسباب الجذرية

1. **بوابة المراجع مبنية على جودة الدليل لا على توازن المصدر.** لا يوجد شرط «مزودان على الأقل» في `AnswerComposer`.
2. **تفاوت ملاءمة البيانات الوصفية.** جلب Crossref عدداً كبيراً من السجلات، لكن كثيراً منها لا يقدم abstract مفيداً؛ Semantic Scholar وOpenAlex أكثر قدرة على تقديم نص يساعد بوابة المباشرة.
3. **GBIF وBHL ليسا بديلين مكافئين للمصادر الأكاديمية في كل سؤال.** GBIF بحث تصنيفي، ونتائج BHL التاريخية الواسعة لم تجتز الملاءمة في العينة.
4. **أفضلية اعتماد تشغيلية لـSemantic Scholar.** مفتاحه الاختياري موجود بينما مفتاح OpenAlex الاختياري غائب، لكن Semantic Scholar فشل في واحد من ثلاثة اختبارات حية، لذلك لا توجد هيمنة مطلقة.
5. **هوية المزود غير بارزة في الواجهة.** المنشأ محفوظ في الخلفية، لكن نموذج العرض يركز على العنوان والمجلة والمؤسسة والرابط، ما يجعل مساهمة المزودين أقل وضوحاً.
6. **الدمج يحتفظ بالمصدر الأول كمصدر أساسي.** ترتيب السجل يفضل OpenAlex ثم Crossref ثم Semantic Scholar؛ لذلك هذا السلوك لا يفسر هيمنة Semantic Scholar، مع بقاء `found_by_sources` والمنشأ محفوظين.

## مصفوفة المصادر

- **OpenAlex:** مسجل، عقد v2، مفعّل، المفتاح اختياري وغائب، مختار تلقائياً، نجح 3/3، ساهم في مرجع نهائي لاستعلام واحد.
- **Crossref:** مسجل، عقد v2، مفعّل، لا يحتاج اعتماداً، مختار تلقائياً، نجح 3/3، ساهم بكثافة في نتائج ما بعد الترتيب ولم يساهم في مرجع نهائي.
- **Semantic Scholar:** مسجل، عقد v2، مفعّل، المفتاح الاختياري موجود، مختار تلقائياً، نجح 2/3 وفشل 1/3، ساهم في مرجع نهائي لاستعلام واحد.
- **Consensus:** مسجل، عقد v2، مفعّل، مفتاحه المطلوب غائب، اختياري صريح فقط، لم يُشغل.
- **FAOSTAT:** مسجل، عقد v2، مفعّل، زوج الاعتماد موجود، مختار تلقائياً؛ لم يدخل العينة لأنها غير إحصائية ولأن الاختبار عزل مزودي REST المستهدفين.
- **AGRIS:** مسجل، عقد v2، مفعّل، LocalIndex، مختار تلقائياً؛ لم يُشغل حفاظاً على SQLite الإنتاجي.
- **Dr Duke:** مسجل، عقد v2، مفعّل، LocalIndex، مختار تلقائياً؛ لم يُشغل حفاظاً على SQLite الإنتاجي.
- **GBIF:** مسجل، عقد v2، مفعّل، لا يحتاج اعتماداً، مختار تلقائياً؛ أعاد EMPTY في 3/3 من الأسئلة الزراعية التمثيلية.
- **Organic Eprints:** مسجل، عقد v2، مفعّل، LocalIndex، مختار تلقائياً؛ لم يُشغل حفاظاً على SQLite الإنتاجي.
- **BHL:** مسجل، عقد v2، مفعّل، المفتاح المطلوب موجود، مختار تلقائياً، نجح 3/3 في الاسترجاع ولم تجتز نتائجه بوابة الترتيب النهائية.

## الاختبارات الحية الآمنة

المصادر المسموح بها فقط: `openalex`, `crossref`, `semantic_scholar`, `gbif`, `bhl`.

- `maize drought tolerance physiology`: نجح OpenAlex وCrossref وBHL، فشل Semantic Scholar، كان GBIF فارغاً؛ المرجع النهائي من OpenAlex فقط.
- `tomato salinity yield response`: نجح OpenAlex وCrossref وSemantic Scholar وBHL، كان GBIF فارغاً؛ المرجع النهائي من Semantic Scholar فقط.
- `wheat nitrogen fertilizer efficiency`: نجح OpenAlex وCrossref وSemantic Scholar وBHL، كان GBIF فارغاً؛ لا مراجع نهائية.

بدأ فحص قراءة فقط لحالة LocalIndex، لكنه توقف بسبب انشغال قاعدة SQLite بكاتب قائم. لم تُعد المحاولة ولم يُنفذ أي تغيير.

## التحقق

الأمر:

```text
docker compose exec -T backend php artisan test --no-ansi tests/Unit/Agriculture/Research/Search/Registry/ScientificSourceCatalogContractTest.php tests/Unit/Agriculture/Research/Search/ProviderConcurrencyOrchestratorTest.php tests/Unit/Agriculture/Research/Search/ScientificResultProvenanceTest.php tests/Feature/SemanticScholarProviderIntegrationTest.php
```

النتيجة: `58 passed`, `4 failed`, `501 assertions`.

نجحت اختبارات السجل والتزامن والمنشأ. الإخفاقات الأربعة في الاختبار الواسع `SemanticScholarProviderIntegrationTest` هي اختلافات عقود موجودة في شجرة العمل الحالية: توقعان قديمان لبناء الاستعلام، توقع مرجع SUPPORTING، وتوقع قديم بأن الاختيار يضم ثلاثة مزودين فقط.

## المعالجة والتوصيات

لم يتغير الكود. التغييرات المقترحة تحتاج موافقة عقد/منتج:

1. إضافة قياس واضح لمساهمة المراجع حسب `source_key` و`found_by_sources`.
2. إظهار هوية المزود في `user_presentation` وفي الواجهة.
3. تعريف كيفية احتساب السجل المدمج الذي اكتشفه أكثر من مزود في Outcome B.
4. إنشاء fixture قبول غير إنتاجي يوفر دليلاً DIRECT مؤهلاً من مزودين مستقلين.
5. عدم فرض مرجع ثانٍ إذا لم يجتز بوابة الدليل.

## الملفات

- `docs/architecture/science-source-expansion/2026-11-17-PHASE-1-SEMANTIC-SCHOLAR-SOURCE-AUDIT.v10.27.json`
- `docs/architecture/science-source-expansion/2026-11-17-PHASE-1-SEMANTIC-SCHOLAR-SOURCE-AUDIT.ar.md`
