<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17819). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
المصادر الخمسة محددة بالأدلة، وحالتها في شجرة العمل معروفة بما يكفي لخطة إكمال. التفعيل الإنتاجي الكامل غير مغلق: تسجيل القدرات في PostgreSQL لم يُثبت بعد إنشاء الجدول، واسترجاع النتائج لم يُعد تشغيله في هذا الفحص، وجزء من كود التفعيل ما زال غير متتبع في Git.

## A. الحكم التنفيذي

هوية المصادر الخمسة مثبتة من ADR-023 §8.51 و§8.55 ومن تقرير `2026-10-18-FIVE-SOURCE-FULL-ACTIVATION-REPORT.ar.md`. هي مقاعد العضوية `G5-04` و`G1-02` و`G6-20` و`G1-01` و`G1-17`. هذه المجموعة مختلفة عن مسار Stage-3 القديم (`openalex`، `crossref`، `semantic_scholar`، `consensus`، `fao_stat`).

حالة كل مصدر معروفة على مستوى شجرة العمل الحالية. ما تبقى لإغلاق التفعيل معروف أيضًا. الفجوات التي تمنع اعتبار التفعيل مكتملًا هي إثبات استرجاع حي على الكود الحالي، وكتابة Cap Store للحالات الأربع المفوَّضة، وحصاد AGRIS غير المكتمل نسبتًا إلى الجسم الكامل، ونطاق Dr. Duke المحدود، ومفتاح BHL الذي لم يُفحص قيمته هنا.

## B. الحالة الأولية للمستودع

| البند | القيمة |
|---|---|
| المسار | `G:\WSA-Enterprise` |
| الفرع | `phase-18-m18-ai-marketing-communications` |
| HEAD و`origin` | `fb60b60746ed487e2bfa04e3d335827d91cda446` |
| عمليات Git غير المكتملة | لا يوجد merge أو rebase أو cherry-pick |
| الفهرس | فارغ |
| شجرة العمل | 145 سطرًا في `git status --porcelain`: 42 ملفًا متتبعًا معدّلًا و103 غير متتبعة |
| التطابق مع نقطة التوقف | يطابق آخر نقطة موثّقة. HEAD هو الالتزام المطلوب |

لقطة الحالة في بداية المحادثة تسجّل كتالوجات الإلحاق وأوامر الحصاد وCapVer كملفات غير متتبعة (`??`)، ومنها `NextGateEligibleSeatCatalog.php` و`CurrentMembershipNearReadySeatCatalog.php` و`AcceptedOwnerDecisionCatalog.php` و`CapVerSeatVerificationService.php` و`scientific:harvest-agris-ods` و`scientific:harvest-oai` و`scientific:ingest-bulk` و`scientific:capver-accepted`. اللقطة مقطوعة بعد `LocalIngestionReport.php`، لذلك حالة ملفات `Registry` و`Orchestrator` و`ScientificSourceProfileCatalog` والاختبارات بالنسبة إلى HEAD لم تظهر كلها في الجزء المرئي.

هذا الفحص قراءة فقط. لم يُعدَّل ملف، ولم يُنشأ commit أو push، ولم تُشغَّل اختبارات أو migrations أو استعلامات على مخزن SQLite التشغيلي.

## C. قائمة المصادر الخمسة

العدد المعتمد للمقاعد هو **110** في `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` بمراجعة `110.v1` (ADR-023 §8.45). التوزيع: G1=22، G2=26، G3=14، G4=7، G5=19، G6=22. المقعد الزائد عن السجل التاريخي ذي 109 مقعدًا هو `G2-26`، وهو خارج هذه الخمسة. المقاعد الخمسة كلها `CURRENT` داخل الـ110. وجود المقعد في السجل يعني عضوية، ولا يعني تكاملًا أو تفعيلًا.

| الاسم الرسمي | Source ID | المجموعة | إثبات الهوية | الحالة العامة في شجرة العمل | آخر تقدم مثبت | أهم عائق |
|---|---|---|---|---|---|---|
| GBIF | `gbif` / `G5-04` | G5، REST | §8.51.1 و§8.55.6 | مسجّل و`ACTIVE` في الكتالوج؛ بحث أنواع مباشر | عقد `cap-r9-20261016-G5-04-search`؛ ليس ضمن `capVerAuthorizedSeats()` | إثبات Cap Store واسترجاع حي على HEAD الحالي غير منجزين هنا |
| Organic Eprints | `organic_eprints` / `G1-02` | G1، OAI-PMH ثم LocalIndex | §8.53 وقرار `OD-G1-02-ONBOARDING-ACTIVE` | `ACTIVE` محكوم؛ حصاد ثم بحث محلي | §8.62: الجيل 2 في SQLite؛ العقد `cap-r11-20261018-G1-02-search` | `CAPABILITY_PERSISTED` غير مثبت بعد توفر الجدول |
| Biodiversity Heritage Library (BHL) | `bhl` / `G6-20` | G6، REST `PublicationSearch` | §8.53 وقرار `OD-G6-20-ONBOARDING-ACTIVE` | `ACTIVE` محكوم؛ بحث مباشر بمفتاح | العقد `cap-r11-20261018-G6-20-search`؛ المفتاح يُقرأ من الإعداد ولا يُطبع | إثبات المفتاح حيًا، ثم Cap Store |
| FAO AGRIS | `agris` / `G1-01` | G1، ODS / bulk ثم LocalIndex | §8.54 و`OD-G1-01-SCOPE-BOUNDED-SUBSET` | `ACTIVE` لنطاق ODS مرخّص، لا للجسم الكامل | §8.57: 97/1244 مزوّدًا؛ §8.62: الجيل 29 | الحصاد غير مكتمل، وCap Store غير مثبت؛ ملاحظات الكتالوج ما زالت عند GEN=4 / 1317 |
| Dr. Duke's Databases | `dr_duke` / `G1-17` | G1، static dataset ثم LocalIndex | §8.54 و`OD-G1-17-SCOPE-LIMITED-PROJECTION` | `ACTIVE` للإسقاط المحدود | §8.56: 211948 صفًا نشطًا؛ §8.62: الجيل 19 | Cap Store غير مثبت؛ أسقف الجداول باقية؛ القرين `G6-06` مؤجّل |

القرين `G6-06` (`dr_duke_g6`) معرّف `DEFERRED` وليس مصدرًا سادسًا. لا يوجد تكرار لمفتاح التشغيل بين الخمسة.

## D. مصفوفة دورة الحياة

القيم تخص شجرة العمل التي قُرئت. `NOT_VERIFIED` يعني أن هذا الفحص لم يُعد التشغيل، لا أن المرحلة فشلت.

| المرحلة | GBIF | Organic Eprints | BHL | AGRIS | Dr. Duke |
|---|---|---|---|---|---|
| Defined | PASS | PASS | PASS | PASS | PASS |
| Registered | PASS | PASS | PASS | PASS | PASS |
| Capability-Declared | PARTIAL | PARTIAL | PARTIAL | PARTIAL | PARTIAL |
| Adapter-Implemented | PASS | PASS | PASS | PASS | PASS |
| Query-Integrated | PARTIAL | PARTIAL | PARTIAL | PARTIAL | PARTIAL |
| Orchestrator-Integrated | PASS | PASS | PASS | PASS | PASS |
| Retrieval-Operational | NOT_VERIFIED | NOT_VERIFIED | NOT_VERIFIED | NOT_VERIFIED | NOT_VERIFIED |
| Normalized | PARTIAL | PARTIAL | PARTIAL | PARTIAL | PARTIAL |
| Provenance-Preserved | PARTIAL | PARTIAL | PARTIAL | PARTIAL | PARTIAL |
| Persisted/Harvested | NOT_APPLICABLE | PARTIAL | NOT_APPLICABLE | PARTIAL | PARTIAL |
| Enabled-by-Configuration | PASS | PASS | PARTIAL | PASS | PASS |
| Test-Verified | NOT_VERIFIED | NOT_VERIFIED | NOT_VERIFIED | NOT_VERIFIED | NOT_VERIFIED |

أدلة الخلايا المشتركة:

- **Defined:** السجل `110.v1`، المقاعد `G5-04` و`G1-02` و`G6-20` و`G1-01` و`G1-17`.
- **Registered:** `ScientificSourceCatalog::productionRegistrations()` يدمج تفعيلات `NextGateEligibleSeatCatalog` و`CurrentMembershipNearReadySeatCatalog`. اختبار العقد يتوقع المفاتيح العشرة بهذا الترتيب: الخمسة القديمة ثم `agris`، `dr_duke`، `gbif`، `organic_eprints`، `bhl`. شرط `isRuntimeActive()` هو `ACTIVE` مع عقد `ACTIVE` وبدون حواجز، وهو متحقق للخمسة في التعريفات المقروءة.
- **Capability-Declared:** العقود تعلن `search=VERIFIED` بهويات `cap-r9` و`cap-r11` و`cap-r12`. النجاح التشغيلي لـ CapVer يشترط `PERSISTED` أو `ALREADY_CURRENT`. آخر تشغيل مسجّل للمقاعد الأربعة كان `STORE_UNAVAILABLE` و`activation_pass=no` (§8.57). بعد إنشاء الجدول في §8.60 بقي تشغيل `scientific:capver-accepted` خطوة اختيارية غير مسجّلة كنتيجة. `G5-04` خارج `capVerAuthorizedSeats()`.
- **Adapter-Implemented:** GBIF وBHL عبر `RestJsonScientificSourceAdapter`. الثلاثة الأخرى عبر `LocalIndexScientificSourceAdapter` لأن عائلتها ليست `REST_API`. التنفيذ يتعامل مع الاستعلام الفارغ، ونفاد الميزانية، وفشل الشبكة، ومفتاح BHL الغائب، والفهرس المحلي غير المبني.
- **Query-Integrated:** عند وجود السؤال العلمي يبني `ScientificSearchQueryBuilder::buildVariantsFromCanonicalSpecification()` متغيرات من أدوار السؤال. المترجم `ScientificQueryCompiler` يضع الحزمة في `scientific_query_compilation` فقط. `DelegatesSearchRequestToSearch` يمرر نص المتغير إلى `search()` دون استبدالها بـ `sourceQuery`. لا محوّل يقرأ حقل الترجمة. FAOSTAT هو الاستثناء المنفصل، وليس أحد هذه الخمسة.
- **Orchestrator-Integrated:** `ScientificSourceSelector` يلحق دور `GOVERNED_EXPANSION` بعد المصادر الإحصائية والافتراضية عندما تكون مفعّلة ومسموحة القدرة. `ScientificSourceAdapterRegistry` يبني المحوّل من تسجيل الكتالوج ويمرر `familyProfile`.
- **Retrieval-Operational:** سجلات تاريخية فقط: GBIF HTTP 200، BHL `PublicationSearch` بحالة `ok`، Organic Eprints جيل 2 / 189، AGRIS بحث `wheat`، Dr. Duke بحث `Mentha`. لم يُعاد أي Probe في هذا الفحص.
- **Normalized / Provenance:** `ScientificSourceFamilyRecord` يبني `ScientificSearchResult` مع `sourceKey` والمعرف والرابط و`rawMetadata` و`ScientificSourceRecordProvenance`. `ScientificResultNormalizer` خاص بالمصادر الأربعة الأدبية القديمة ولا يمر عبره هؤلاء الخمسة. لم تُقرأ صفوف حية.
- **Persisted/Harvested:** §8.62 يثبت مخزن التشغيل SQLite `scientific_records`: الأجيال `organic_eprints=2` و`dr_duke=19` و`agris=29`، و300635 صفًا إجماليًا. GBIF وBHL مساران مباشران؛ §8.57 سجّل 0 صفًا محليًا لهما، وهذا متوافق مع عقدهما. AGRIS عند §8.57 كان 97 مزوّدًا من 1244، والجسم الجديد لم يُستوعب كله. ملاحظات الكتالوج ما زالت تصف AGRIS عند 1317 صفًا وDr. Duke عند 1500 صفًا، وهذا أقدم من §8.56 و§8.62.
- **Enabled-by-Configuration:** المفاتيح `GBIF_ENABLED` و`ORGANIC_EPRINTS_ENABLED` و`BHL_ENABLED` و`AGRIS_ENABLED` و`DR_DUKE_ENABLED` افتراضيها `true` في `agricultural_intelligence.php`. `.env.example` يوثّق الثلاثة الأولى ومفتاح `BHL_API_KEY`، ولا يذكر `AGRIS_ENABLED` ولا `DR_DUKE_ENABLED`. وجود المفتاح الفعلي لـ BHL لم يُفحص هنا؛ §8.57 سجّل طولًا 36 دون طباعة القيمة.
- **Test-Verified:** الاختبارات موجودة في الشجرة. لم تُشغَّل في هذا الفحص. نجاحات PHPUnit المذكورة في §8.51–§8.61 مرتبطة بتواريخ أقدم وHEAD `f2025315`، ولا تُنسب إلى `fb60b60`.

## E. التفاصيل الهندسية لكل مصدر

### 1. GBIF — `G5-04` / `gbif`

1. **الموجود:** مقعد `CURRENT`، تعريف `ACTIVE`، ملف REST إلى `https://api.gbif.org/v1/species/search` بمعامل `q` وحد `limit`، ترخيص شروط GBIF مع تراخيص مجموعات البيانات، تسجيل `GOVERNED_ONBOARDED`، ربط `adr023.canonical.seat.G5-04`، وتفعيل `agricultural_intelligence.gbif.enabled`.
2. **ما يعمل وفق سجل تنفيذ سابق:** probe تاريخي HTTP 200، واختبارات محوّل/منسّق على fixtures. لم يُعاد ذلك هنا.
3. **الناقص:** GBIF ليس ضمن المقاعد الأربعة التي يشغّلها `scientific:capver-accepted`. لا دليل في §8.60–§8.62 على إعادة قراءة حقيقة القدرة بعد إنشاء الجدول. الاسترجاع الحي على هذه الشجرة غير معاد.
4. **مسار الاستدعاء:** خطة السؤال → `ScientificSourceSelector` → مفتاح `gbif` → `ScientificSourceAdapterRegistry` → `RestJsonScientificSourceAdapter::searchRequest()` → GET على species search → `ScientificSourceFamilyRecord`.
5. **الملفات:** `NextGateEligibleSeatCatalog.php`، `ScientificSourceCatalog.php`، `ScientificSourceProfileCatalog.php`، `RestJsonScientificSourceAdapter.php`، `Stage3SourceKeyIdentityBridge.php`، `config/agricultural_intelligence.php`.
6. **الاختبارات:** `GbifOrchestratorEndToEndTest.php` و`ScientificSourceCatalogContractTest.php` موجودان. التنفيذ الحالي: `NOT_VERIFIED`.
7. **العوائق:** `OPERATIONAL_VERIFICATION_GAP` للاسترجاع الحي؛ `CAPABILITY_CONTRACT_GAP` لأن المقعد خارج أمر CapVer الافتراضي؛ `QUERY_INTEGRATION_GAP` لأن حزمة الترجمة لا تصبح طلب HTTP؛ `TEST_COVERAGE_GAP` حتى يُعاد تشغيل الاختبار على هذه الشجرة.
8. **الإكمال:** إبقاء التفعيل الحالي، وإعادة اختبار المنسّق على fixture، ثم probe حي بحد نتيجة واحدة، ثم قرار المالك إن كان يجب إدخال `G5-04` إلى CapVer. لا حصاد مطلوب لعقد البحث الحالي.

### 2. Organic Eprints — `G1-02` / `organic_eprints`

1. **الموجود:** مقعد `CURRENT`، قبول المالك `OD-G1-02-ONBOARDING-ACTIVE`، تفويض CapVer، ملف OAI-PMH `https://orgprints.org/cgi/oai2`، والتسجيل التشغيلي يستخدم LocalIndex لأن العائلة `OAI_PMH`.
2. **ما يعمل وفق السجل:** حصاد ثم بحث محلي، جيل 2، و189 صفًا في سجلات 2026-10-18. §8.62 يبقي الجيل عند 2.
3. **الناقص:** persist القدرة. الجسم الكامل للمجلة ليس ضمن نطاق التفعيل المسجّل. ملاحظات الوصول ما زالت تتحدث عن 189 صفًا كنطاق محدود.
4. **مسار الاستدعاء:** `scientific:harvest-oai` يكتب المخزن، ثم البحث يختار `organic_eprints`، ثم `LocalIndexScientificSourceAdapter` يبحث في FTS. إن كان الجيل 0 تُعاد `local_index_not_built`.
5. **الملفات:** `CurrentMembershipNearReadySeatCatalog.php`، `HarvestScientificOaiSourceCommand.php`، `LocalIndexScientificSourceAdapter.php`، `DatabaseScientificRecordStore.php`، `ScientificRecordCodec.php`.
6. **الاختبارات:** `OrganicEprintsOaiLocalIndexPathTest.php`. السجل التاريخي: 4 اختبارات ناجحة في §8.56. غير معاد هنا.
7. **العوائق:** `CAPABILITY_CONTRACT_GAP` حتى `PERSISTED|ALREADY_CURRENT`؛ `OPERATIONAL_VERIFICATION_GAP`؛ `HARVESTING_GAP` فقط إذا طُلب الجسم الكامل، وهو خارج قبول 2026-10-18.
8. **الإكمال:** تشغيل CapVer وقراءة مستقلة، ثم probe بحث على مخزن الاختبار أو قراءة فقط من الجيل الملتزم دون إعادة بناء FTS على الملف التشغيلي.

### 3. BHL — `G6-20` / `bhl`

1. **الموجود:** مقعد `CURRENT`، قبول `OD-G6-20-ONBOARDING-ACTIVE`، نقطة `https://www.biodiversitylibrary.org/api3`، العملية `PublicationSearch`، والمعامل `searchterm`، والمفتاح من `agricultural_intelligence.bhl.api_key` عبر query parameter `apikey`. الغياب يعيد `missing_api_key`.
2. **ما يعمل وفق السجل:** بحث حي سابق أعاد `Status=ok` مع PartID/TitleID، دون أرشفة المفتاح. §8.57: طول المفتاح 36، وصفر صفوف LocalIndex، وهذا متوقع لمسار API.
3. **الناقص:** تأكيد وجود المفتاح في البيئة الحالية دون طباعته، وpersist القدرة، وإعادة probe.
4. **مسار الاستدعاء:** المخطّط → `bhl` → `RestJsonScientificSourceAdapter` → GET مع `op=PublicationSearch`. حقول PartID/TitleID والروابط تدخل الـ provenance عبر `ScientificSourceFamilyRecord`.
5. **الملفات:** `CurrentMembershipNearReadySeatCatalog.php`، `RestJsonScientificSourceAdapter.php`، `config/agricultural_intelligence.php`.
6. **الاختبارات:** `BhlOrchestratorNearReadyPathTest.php` و`BhlApiEnvelopeAndCredentialTest.php`. غير معاد تشغيلهما.
7. **العوائق:** `CREDENTIAL_OR_ACCESS_GAP` حتى يُثبت وجود المفتاح؛ `CAPABILITY_CONTRACT_GAP`؛ `OPERATIONAL_VERIFICATION_GAP`؛ `QUERY_INTEGRATION_GAP` المشترك.
8. **الإكمال:** فحص وجود `BHL_API_KEY` دون طباعة، ثم CapVer، ثم probe منشور واحد. لا حصاد محلي مطلوب لعقد البحث الحالي.

### 4. FAO AGRIS — `G1-01` / `agris`

1. **الموجود:** مقعد `CURRENT`، قبول النطاق المحدود `OD-G1-01-SCOPE-BOUNDED-SUBSET` وقبول الإلحاق. الجذر `https://agris.fao.org/ods/AGRIS.ODS.xml`. الرخصة المطبّقة على الآثار المحصودة CC-BY-4.0 لكل أثر، مع إغلاق إن لم تُتحقق الرخصة. البحث عبر LocalIndex.
2. **ما يعمل وفق السجل:** حصاد قابل للاستئناف، وبحث `wheat` سابقًا. §8.57: 97 مزوّدًا من 1244، و87984 صفًا نشطًا قبل استيعاب الدفعة الأحدث. §8.62: الجيل 29، أي أن المخزن تحرك بعد ملاحظات الكتالوج المجمدة عند الجيل 4 و1317 صفًا.
3. **الناقص:** الجسم الكامل غير مطلوب لإغلاق القبول المسجّل، وما زال غير مكتمل. persist القدرة غير مثبت. ملاحظات الكتالوج أقدم من المخزن المسجّل. هذا الفحص لم يعد عدّ الصفوف.
4. **مسار الاستدعاء:** `scientific:harvest-agris-ods` ثم `scientific:ingest-bulk` إلى SQLite، ثم الاختيار → `LocalIndexScientificSourceAdapter`.
5. **الملفات:** `NextGateEligibleSeatCatalog.php`، `AgrisOdsBoundedHarvester.php`، `HarvestAgrisOdsBoundedCommand.php`، `IngestScientificBulkSourceCommand.php`، مخزن LocalIndex.
6. **الاختبارات:** `AgrisOdsBoundedHarvesterTest.php` و`AgrisOdsAndDrDukeProjectorTest.php` و`AgrisAndDrDukeGovernedActivationTest.php`. غير معاد تشغيلها.
7. **العوائق:** `HARVESTING_GAP` للجسم الكامل؛ `CAPABILITY_CONTRACT_GAP`؛ `OPERATIONAL_VERIFICATION_GAP`؛ انحراف توثيقي في `access_notes`.
8. **الإكمال:** CapVer أولًا. تحديث ملاحظات النطاق بعد عدّ قراءة فقط للجيل 29. مواصلة الحصاد حتى 1244 فقط بقرار نطاق جديد؛ القبول الحالي لا يشترطه.

### 5. Dr. Duke — `G1-17` / `dr_duke`

1. **الموجود:** مقعد `CURRENT`، قبول الإسقاط المحدود `OD-G1-17-SCOPE-LIMITED-PROJECTION`. المصدر ثابت من phytochem، والرخصة CC0. `FARMACY` و`FARMACY_NEW` هويتان منفصلتان. ETHNOBOT استخدام تقليدي وليس ادعاء فعالية. القرين `G6-06` مؤجّل.
2. **ما يعمل وفق السجل:** إسقاط وبحث `Mentha` سابقًا، ثم تحميل متزايد إلى 211948 صفًا نشطًا في §8.56. §8.62 يرفع الجيل إلى 19. أسقف ETHNOBOT/FARMACY/FARMACY_NEW عند 60000 في الأثر الكامل بقيت قيود نطاق.
3. **الناقص:** persist القدرة. ملاحظات الكتالوج ما زالت عند GEN=2 / 1500. القرين `G6-06` ليس جزءًا من التفعيل. هذا الفحص لم يعد فتح قاعدة التشغيل.
4. **مسار الاستدعاء:** مصالحة الجداول ثم الإسقاط ثم `scientific:ingest-bulk`، ثم LocalIndex عند البحث.
5. **الملفات:** `NextGateEligibleSeatCatalog.php`، `DrDukeCsvRelationshipProjector.php`، `DrDukeDatasetReconciler.php`، `ReconcileDrDukeDatasetCommand.php`، LocalIndex.
6. **الاختبارات:** `DrDukeDatasetReconcilerTest.php` و`AgrisAndDrDukeGovernedActivationTest.php`. غير معاد تشغيلها.
7. **العوائق:** `CAPABILITY_CONTRACT_GAP`؛ `PERSISTENCE_GAP` جزئي بسبب أسقف الجداول؛ `OPERATIONAL_VERIFICATION_GAP`؛ `IDENTITY_GAP` فقط إن طُلب تفعيل `G6-06`، وهو مؤجّل صراحة.
8. **الإكمال:** CapVer، ثم تثبيت أرقام الجيل 19 في الملاحظات بعد عدّ قراءة فقط، والإبقاء على `G6-06` خارج التشغيل.

## F. التبعيات المشتركة

هذه المكونات موجودة في شجرة العمل، والعائق المشترك هو الإثبات التشغيلي لا غيابها:

- **Registry:** `ScientificSourceCatalog` هو سجل التشغيل. سجل العضوية منفصل في JSON. الخمسة يدخلون من كتالوجات المقاعد لا من نسخ مكررة للمفاتيح.
- **Capability Contract:** الإعلان موجود في عقود الإلحاق. مخزن PostgreSQL `cghia_capability_cell_records` كان حاضرًا في §8.60 و§8.62. كتابة الحقائق الأربع بعد ذلك غير مسجّلة.
- **Query Builder / Compiler:** المتغيرات القانونية المشتركة تصل إلى كل مصدر غير FAOSTAT. الترجمة لكل مصدر بيانات وصفية فقط. هذا سلوك العقد الحالي (`D-04`: نص الاستعلام يصل إلى `search()` كما هو)، لا عطل بناء.
- **Orchestrator:** يختار دور `GOVERNED_EXPANSION` ويستدعي المحوّل ويعزل فشل مصدر واحد في نتيجة `FAILED`.
- **Normalizer القديم:** لا يخدم هذه الخمسة. التطبيع يتم في `ScientificSourceFamilyRecord`.
- **LocalIndex:** قرار §8.62 يثبت SQLite ملف `storage/app/scientific_record_store.sqlite` على الاتصال `scientific_records` كمخزن التشغيل. ترحيل PostgreSQL العلمي غير المتتبع يبقى غير مطبّق. لا يُعاد بناء FTS على الملف التشغيلي ضمن اختبارات الدليل؛ تشغيل سابق لمجلد LocalIndex حاول `DELETE` على FTS التشغيلي وفشل بـ `database is locked` دون تغيير الحجم (§8.62.6).

## G. خطة التنفيذ المرتبة

لا تُنفَّذ في هذه المهمة.

1. **حماية الشجرة وقراءة الأعداد.** المصادر: الخمسة. المكوّن: Git وSQLite للقراءة فقط. العمل: تثبيت porcelain وHEAD، ثم عدّ الأجيال والصفوف النشطة لكل مفتاح دون فتح المحوّل الذي يعيد بناء FTS. السبب: ملاحظات الكتالوج أقدم من §8.62، والملف ربما تغيّر بعدها. الاعتماد: لا شيء. القبول: أرقام مصدرها استعلام قراءة فقط، مع بقاء حجم الملف ووقت تعديله كما كانا إن لم يُفتح للكتابة. الاختبار: مقارنة العدّ مع `scientific_source_states`. المخاطر: أي مسار بحث تشغيلي قد يعيد بناء FTS. القرار: داخل العقود الحالية.
2. **CapVer للمقاعد الأربعة.** المصادر: `G1-02` و`G6-20` و`G1-01` و`G1-17`. المكوّن: `CapVerSeatVerificationService` و`scientific:capver-accepted`. العمل: التشغيل حتى `PERSISTED` أو `ALREADY_CURRENT`، ثم قراءة مستقلة من `cghia_capability_cell_records`. السبب: آخر سجل كان `STORE_UNAVAILABLE` قبل أن يصبح الجدول موجودًا. الاعتماد: الخطوة 1 لمعرفة البيئة، وPostgreSQL الأساسي الذي كان سليمًا في §8.62. القبول: `activation_pass=yes` لكل مقعد، ثم إعادة التشغيل تعيد `ALREADY_CURRENT`. الاختبار: `CapVerSeatVerificationServiceTest` ثم الأمر الحي. المخاطر: كتابة صفوف قدرة حقيقية. القرار: مفوَّض أصلًا؛ لا ترحيل علمي إلى PostgreSQL.
3. **إثبات المسار دون الشبكة.** المصادر: الخمسة. المكوّن: اختبارات الوحدة المذكورة في القسم E. العمل: تشغيلها على SQLite اختبار معزول، لا على الملف التشغيلي. القبول: نجاح المرشّحات المحددة. الاختبار: هو هذه المرشّحات. المخاطر: مجلد LocalIndex الكامل لمس الملف التشغيلي سابقًا، فيُستبعد. القرار: داخل العقود.
4. **إثبات BHL السري.** المصدر: `G6-20`. المكوّن: `BHL_API_KEY`. العمل: التحقق من أن القيمة غير فارغة دون طباعتها. القبول: الإعداد موجود، وprobe واحد يعيد `Status=ok`. الاختبار: `BhlApiEnvelopeAndCredentialTest` ثم probe حي. المخاطر: تسريب المفتاح في السجلات. القرار: المفتاح موجود كمتطلب وصول؛ التدوير قرار مالك منفصل.
5. **Probes حية ضيقة.** المصادر: GBIF وBHL للـ API، والثلاثة الأخرى لاستعلام LocalIndex واحد لكل منها. القبول: نتيجة `SUCCESS` أو `EMPTY` المفسّر، مع `sourceKey` ومعرف أصلي في الـ provenance. الاختبار: سجل الأمر نفسه. المخاطر: إعادة بناء FTS إذا اختلف الجيل. القرار: داخل العقود إن بقي الاستعلام على القراءة المتزامنة مع الجيل.
6. **تصحيح ملاحظات النطاق.** المصادر: AGRIS وDr. Duke وOrganic Eprints. المكوّن: `access_notes` في الكتالوج. العمل: استبدال أرقام GEN=4/1317 وGEN=2/1500 بأرقام الخطوة 1، مع الإبقاء على «الجسم الكامل غير مدعى». القبول: الملاحظة تطابق العدّ. الاختبار: اختبار الكتالوج الذي يقرأ حالة `ACTIVE`. المخاطر: كتابة رقم قديم. القرار: توثيق، لا تغيير نطاق.
7. **قرار الاستعلام المترجم.** المصادر: الخمسة. المكوّن: المترجم والمحوّلات. العمل مؤجّل: جعل `sourceQuery` هو نص البحث الفعلي يحتاج قرارًا لأن العقد الحالي يبقي نص المتغير كما يصل إلى `search()`. القبول إن قُبل القرار: اختبار يثبت أن قيد السؤال العلمي يصل إلى طلب GBIF/BHL أو إلى استعلام FTS. المخاطر: تغيير سلوك المصادر الخمسة القديمة إن عُمّم التغيير. القرار: **مطلوب من المالك قبل أي تعديل.**

أهلية الإعلان عن التفعيل، لكل مصدر على حدة: `Registered` و`Orchestrator-Integrated` و`Enabled-by-Configuration` موجودة في الشجرة؛ ويُغلق الإعلان عندما تصبح `Retrieval-Operational` و`Test-Verified` مثبتتين على هذه الشجرة، وتصبح `Capability-Declared` مثبتة بالتخزين للمقاعد الأربعة. GBIF يُعلن بحثًا تشغيليًا دون CapVer إضافي ما لم يوسّع المالك قائمة المقاعد. AGRIS وDr. Duke يُعلنان ضمن النطاق المقبول فقط، لا كجسم كامل.

## H. القرارات المطلوبة من المالك

1. إدخال `G5-04` إلى `capVerAuthorizedSeats()` أو إبقاؤه خارج الأمر. السجل الحالي يبقيه خارجًا (§8.58.5).
2. توسيع AGRIS من النطاق المرخّص المحصود إلى 1244 مزوّدًا. القبول المسجّل لا يطلب ذلك.
3. رفع أسقف جداول Dr. Duke أو تفعيل القرين `G6-06`. القبول المسجّل يبقي الإسقاط محدودًا والقرين مؤجّلًا.
4. استبدال نص البحث المشترك بـ `sourceQuery` الخاص بكل مصدر. العقد `D-04` لا يفرض ذلك.
5. تدوير `BHL_API_KEY` إن وُجد تعرض سابق خارج نطاق الفحص. §8.57 لم يجد تعرضًا داخل النطاق المفحوص.
6. تضمين ملفات التفعيل غير المتتبعة في commit. هذا قرار نشر، وليس شرطًا لفهم الشجرة المحلية. قرار Option A يبقى: لا ترحيل PostgreSQL علمي ولا نسخ صفوف علمية إليه.

## I. حدود التحقق

- لم تُشغَّل PHPUnit ولا أوامر Artisan ولا probe شبكة ولا SQL على المخزن التشغيلي.
- لم تُعاد قراءة `.env`، ولم تُطبع أسرار.
- أعداد §8.62 مؤرخة 2026-10-20. هذا الفحص لم يثبت أن ملف SQLite بقي عند الحجم `1354821632` والوقت `2026-10-19T17:14:04Z`.
- نجاحات الاختبار في §8.51–§8.61 ليست نجاحًا على `fb60b60`.
- لقطة Git الظاهرة مقطوعة، فمساواة ملفات `Registry` و`Orchestrator` والاختبارات مع HEAD غير محسومة من الجزء المرئي. كتالوجات المقاعد وأوامر الحصاد وCapVer ظاهرة كملفات غير متتبعة.
- تقارير 2026-10-18 التي تقول `PRODUCTION_READY=no` و`CAPABILITY_PERSISTED=no` ما زالت آخر حكم مسجّل لهذه الحقول. §8.62 أغلق محرك المخزن العلمي على SQLite ولم يغلق CapVer للمقاعد.

## J. بوابة الانتقال إلى التنفيذ

الفحص كافٍ للبدء بإكمال هذه الخمسة، بهذه الشروط قبل أي تعديل:

- الهدف هو `G5-04` و`G1-02` و`G6-20` و`G1-01` و`G1-17`، داخل عضوية `110.v1`.
- مخزن السجلات العلمية يبقى SQLite وفق §8.62.
- لا يُطبَّق `2026_10_15_220000_create_scientific_source_record_store_tables.php` على PostgreSQL.
- لا يُشغَّل مجلد اختبارات LocalIndex الكامل ضد الملف التشغيلي.
- أول تنفيذ مسموح به داخل العقود الحالية هو: عدّ قراءة فقط، ثم `scientific:capver-accepted` للمقاعد الأربعة، ثم اختبارات fixtures المعزولة، ثم probes ضيقة.
- لا يُعلن مصدر «مفعّلًا» بمعنى واحد. الإعلان يغلق فقط الأبعاد التي صار لها دليل تشغيل على هذه الشجرة: التسجيل موجود، والقدرة المخزّنة والاسترجاع الحي ما زالا مفتوحين.
