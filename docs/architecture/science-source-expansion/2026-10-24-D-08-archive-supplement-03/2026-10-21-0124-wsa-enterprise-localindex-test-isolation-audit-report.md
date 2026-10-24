<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17610). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
الاختبار المتكامل يصل إلى SQLite التشغيلية لأن الحاوية تحل `LocalIndexSearchEngine` إلى `SqliteFtsScientificIndex`، بينما الاختبار يربط صنفًا آخر فقط. عزل المسار يجب أن يحدث قبل الإقلاع، مع فشل مغلق إذا بقي المسار التشغيلي.

## 1. ما فُحص
الحالة: الفرع `phase-18-m18-ai-marketing-communications`، و`HEAD` هو `f0ac89700b7a822bd83b2cd821a18e493089d786`، والتتبع `behind 5`. لم يُستخدم fetch.

فُحصت الاختبارات الأربعة تحت `backend/tests/Unit/Agriculture/Research/Search/LocalIndex/`، مع `phpunit.xml` و`tests/bootstrap.php` و`tests/TestCase.php` و`tests/Support/DatabaseSafety.php`. وعلى جانب الإنتاج: `config/database.php`، و`config/agricultural_intelligence.php`، و`AgriculturalIntelligenceServiceProvider`، و`ScientificSourceAdapterRegistry`، و`LocalIndexScientificSourceAdapter`، و`SqliteFtsScientificIndex`، و`LocalScientificIndex`، و`LocalIndexIngestionService`. `MissionArtifactArchiveFactory::forTesting()` لا يشارك هذا المسار؛ هو ينشئ `archive.sqlite` داخل المجلد الذي يُمرر له ولا يقرأ `scientific_records`.

## 2. مسار كل اختبار

| الاختبار | القاعدة | الكتابة |
|---|---|---|
| `LocalIndexLifecycleTest` | `InMemoryScientificRecordStore` و`LocalScientificIndex` يُنشآن في `setUp` مباشرة | ذاكرة فقط. الحصاد عبر `Http::fake`. لا اتصال `scientific_records` |
| `IncrementalBulkIngestDoesNotWithdrawPeersTest` | المخزن والفهرس في الذاكرة | ملفات JSONL تحت `storage/framework/testing/incremental_bulk/` ثم `unlink` للملفين. لا SQLite |
| `ScientificRecordsSqliteDurabilityTest` | بعد إقلاع التطبيق يستبدل `database.connections.scientific_records.database` بـ `storage/framework/testing/scientific_records_durability.sqlite` ثم `Artisan::call('migrate')` على الاتصال `scientific_records` | ينشئ الملف المؤقت ويكتب جداول الهجرة. `tearDown` يحذف الملف فقط، لا `-wal` ولا `-shm`، ولا يستدعي `DB::purge` |
| `LocalIndexSearchIntegrationTest` ما عدا اختبار المنسق | المخزن والفهرس في الذاكرة، والاختبار يمررهما إلى المحول يدويًا | لا SQLite |
| `test_a_local_index_source_runs_through_the_production_orchestrator_without_http` | الابتلاع يكتب في الذاكرة، ثم الاختبار يربط `LocalScientificIndex` و`ScientificRecordStore` في الحاوية. المنسق الإنتاجي يُنشأ يدويًا، لكن `ScientificSourceAdapterRegistry` يعمل `$container->make(LocalIndexScientificSourceAdapter::class)` | الكتابة الخطرة تحدث هنا، كما يلي |

## 3. السبب الجذري
منشئ `LocalIndexScientificSourceAdapter` يطلب `LocalIndexSearchEngine` لا `LocalScientificIndex`.

في الموفّر، مفتاح `search_backend` غير موجود في ملف الإعداد، فيصبح الافتراضي `auto`. ومع `driver=sqlite` يُحل `LocalIndexSearchEngine` إلى `SqliteFtsScientificIndex`. ربط `LocalScientificIndex` في الاختبار لا يغيّر هذا الربط.

`SqliteFtsScientificIndex::rebuild()` ينفّذ:

`scientific_source_fts WHERE source_key = ?` ثم `delete()`.

بعد الابتلاع تكون `generation` في المخزن الذاكري أكبر من صفر، بينما عدد وثائق FTS على الملف التشغيلي صفر. شرط البحث في المحول يستدعي `rebuild()`، فيصدر الحذف على الاتصال `scientific_records`.

مسار ذلك الاتصال يُقرأ فعلًا من `SCIENTIFIC_RECORD_STORE_DATABASE` في `config/database.php`، والافتراضي هو `storage_path('app/scientific_record_store.sqlite')`. `tests/bootstrap.php` يجبر قاعدة التطبيق على `:memory:` ولا يمس هذا المتغير ولا `SCIENTIFIC_RECORD_STORE_URL`. إذا وُجد URL، Laravel يفضله على مسار الملف.

`DatabaseSafety` يرفض فقط قاعدة التطبيق إذا كانت `wsa_enterprise`. لا يفحص مسار المتجر العلمي، لذلك الإقلاع ينجح ثم تبدأ الكتابة.

القفل `database is locked` يطابق فتح الملف التشغيلي من عملية أخرى. الإعداد لا يفرض WAL؛ `journal_mode` قيمته `null`. لم يُعاد تشغيل الاختبار، ولم تُفتح قاعدة التشغيل.

اختبار المتانة يعيد ضبط المسار بعد `parent::setUp()`. هذا يكفي فقط إذا لم يكن الاتصال قد أُنشئ أثناء الإقلاع. الربط كسول، لذا لا يُفتح أثناء الإقلاع اليوم. أي حل يعتمد على `config()` بعد الإقلاع يفشل إذا بقي الاتصال مخزنًا في `DatabaseManager`.

## 4. خطة العزل
1. في `tests/bootstrap.php`، قبل تحميل Laravel وبنفس أسلوب `DB_DATABASE`، أجبر:
   - `SCIENTIFIC_RECORD_STORE_DRIVER=sqlite`
   - `SCIENTIFIC_RECORD_STORE_URL` فارغًا
   - `SCIENTIFIC_RECORD_STORE_DATABASE` على ملف تحت `storage/framework/testing/` باسم فريد للعملية
   هذه المفاتيح الثلاثة يقرأها `config/database.php` عند تحميل الإعداد. مفتاح `search_backend` لا يُقرأ من البيئة؛ لا يُضاف متغير له ما لم يُضف أولًا إلى `agricultural_intelligence.php`. الاختيار الإنتاجي `auto` مع `sqlite` يبقى كما هو ويختار FTS.

2. في `DatabaseSafety`، أضف فحصًا يُستدعى من `TestCase::setUp` بعد الإقلاع ويقرأ المسار من `config` فقط، من دون فتح SQLite. يفشل الاختبار إذا كان المسار يساوي `storage/app/scientific_record_store.sqlite`، أو لم يكن تحت `storage/framework/testing`، أو كانت قاعدة التطبيق خارج `:memory:` وممنوعة. هذا هو الفشل المغلق قبل أي `migrate` أو `delete`.

3. في اختبار المنسق، أبقِ `MultiSourceScientificSearchOrchestrator` و`ScientificSourceAdapterRegistry(app(), $catalog)`. لا تستبدل المحول بكائن وهمي. أزل الاعتماد على `instance(LocalScientificIndex::class)` كوسيلة عزل. الابتلاع يجب أن يمر عبر `LocalIndexIngestionService` المحلول من الحاوية حتى يتشارك `ScientificRecordStore` و`LocalIndexSearchEngine` ملف الاختبار نفسه. قبل ذلك، نفّذ هجرة ملف المتجر على الاتصال المؤقت فقط، وبعد أي تغيير لاحق لـ `config` استدعِ `DB::purge('scientific_records')`.

4. في `ScientificRecordsSqliteDurabilityTest`، بعد استبدال الإعداد استدعِ `purge` قبل `migrate`. في `tearDown` احذف الملف و`-wal` و`-shm` فقط إذا كان المسار تحت `framework/testing`.

## 5. ترتيب التشغيل بعد تنفيذ العزل
لا يُشغَّل شيء في هذه الجولة.

1. `ScientificRecordsSqliteDurabilityTest` على ملف `framework/testing/scientific_records_durability.sqlite`. النجاح: الجدول موجود على هذا الملف وغائب عن اتصال `:memory:`، والجيل يُقرأ من نسخة مخزن ثانية.
2. `test_a_local_index_source_runs_through_the_production_orchestrator_without_http` فقط. الكتابة مسموحة على SQLite المؤقت للعملية وملفات WAL/SHM بجانبه. النجاح: المصدر `dataset_example` ناجح ومن دون HTTP.
3. بقية `LocalIndexLifecycleTest` و`IncrementalBulkIngestDoesNotWithdrawPeersTest` وباقي دوال اختبار التكامل. الأولى ذاكرة، والثانية ملفات JSONL تحت `framework/testing/incremental_bulk`.
4. لا تُشغَّل اختبارات `backend/tests/Pgsql/` ولا حصاد تشغيلي.

قبل المجموعة وبعدها، قارن حجم `storage/app/scientific_record_store.sqlite` ووقت تعديله عبر بيانات الملف فقط، من دون فتحه. واطبع مسار `config('database.connections.scientific_records.database')` وتأكد أنه تحت `framework/testing`.

## 6. التحقق والمخاطر
التحقق هو ثبات حجم الملف التشغيلي ووقت تعديله، مع فشل الاختبار إذا تطابق `realpath` مع ذلك الملف.

المخاطر المتبقية: كاش الإعداد إذا جُمّد المسار القديم؛ بقاء اتصال مفتوح إذا نُسي `purge`؛ ملف PHPUnit مشترك بين الاختبارات قد يُقفل داخل عملية الاختبار لا على قاعدة التشغيل؛ وهجرة اختبار المتانة تستخدم `--force`، لذا يجب أن يسبقها الفحص المغلق.

## 7. التغييرات المقترحة دون تنفيذ
- `backend/tests/bootstrap.php`
- `backend/tests/Support/DatabaseSafety.php`
- `backend/tests/TestCase.php` لاستدعاء الفحص
- `LocalIndexSearchIntegrationTest.php` حتى يبتلع عبر خدمة الحاوية
- `ScientificRecordsSqliteDurabilityTest.php` لإضافة `purge` وحذف WAL/SHM
- اختياريًا توثيق `search_backend` في `agricultural_intelligence.php` فقط إذا أُريد التحكم به من البيئة؛ العزل لا يعتمد على ذلك

## 8. ما لم يُفعل
لم يُشغَّل اختبار، ولم يُفتح ملف SQLite التشغيلي، ولم يُعدَّل أي ملف، ولم يُغيّر Git أو أي Worktree أو قاعدة بيانات.
