<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17598). Message timestamp as recorded in the conversation: Wednesday, Oct 21, 2026, 1:24 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17599; it is archived once. -->

---
# WSA-Enterprise — LocalIndex Test Isolation Audit
## الهدف
حلّل كيفية عزل اختبارات LocalIndex وScientific Record Store عن قاعدة SQLite التشغيلية في مشروع WSA-Enterprise.
المستودع الأصلي:
`G:\WSA-Enterprise`
الفرع:
`phase-18-m18-ai-marketing-communications`
المرجع البعيد المعروف:
`52e904ea7e037747bf9147ce95baef80965e195e`
هذه المهمة تشخيص وتخطيط فقط. لا تنفذ إصلاحات أو Commit أو Push.
## 1. حماية المستودع
ابدأ بقراءة حالة Git الحالية دون تغييرها.
لا تستخدم:
`git add`, `git commit`, `git push`, `git pull`, `git merge`, `git rebase`, `git cherry-pick`, `git reset`, `git clean`, أو `git stash`.
لا تعدّل أو تحذف أي ملف. لا تغيّر أي Worktree أو فرع. لا تنفذ Migration أو حصادًا أو أمر أرشفة.
لا تفتح قاعدة SQLite التشغيلية بغرض الاختبار أو التعديل.
## 2. تحديد الاختبارات المعنية
راجع، بحسب وجودها في النسخة الحالية:
- `LocalIndexSearchIntegrationTest`
- `LocalIndexLifecycleTest`
- `ScientificRecordsSqliteDurabilityTest`
- `IncrementalBulkIngestDoesNotWithdrawPeersTest`
- اختبارات LocalIndex وScientific Record Store المرتبطة.
- `phpunit.xml`
- إعدادات قاعدة البيانات في `backend/config/database.php`
- إعدادات Scientific Record Store.
- المصانع وتهيئة الاختبار، بما فيها `MissionArtifactArchiveFactory::forTesting()` عند صلتها بالموضوع.
لا تفترض أن هذه الأسماء أو الإعدادات لم تتغير؛ تحقق من المحتوى الفعلي.
## 3. تتبع مسارات الاتصال والكتابة
لكل اختبار ذي صلة، وثّق:
1. كيف تُهيأ قاعدة البيانات.
2. كيف يُحدد مسار SQLite.
3. هل يستخدم `:memory:` أم ملفًا دائمًا.
4. هل يتصل بالاتصال الافتراضي أم باتصال مسمى مثل `scientific_records`.
5. هل يستخدم الإنتاج الفعلي لـ LocalIndex أو Production Orchestrator.
6. هل ينشئ بيانات أو يحذفها أو يعدلها.
7. هل توجد إعدادات ثابتة أو متغيرات بيئة تتجاوز إعدادات الاختبار.
8. هل يمكن أن يتصل الاختبار بملف `storage/app/scientific_record_store.sqlite` التشغيلي.
9. هل توجد اتصالات متزامنة أو قفل SQLite محتمل.
10. هل تنظيف البيانات أو Rollback مضمون فعلًا.
لا تكتفِ بقراءة أسماء متغيرات البيئة؛ تتبع التنفيذ إلى نقطة إنشاء الاتصال واستخدامه.
## 4. تحليل المشكلة السابقة
التقرير السابق وثّق أن:
`LocalIndexSearchIntegrationTest::test_a_local_index_source_runs_through_the_production_orchestrator_without_http`
وصل إلى قاعدة التشغيل، وحاول حذف بيانات `dataset_example` من FTS، ثم فشل بسبب `database is locked`.
تعامل مع هذه الواقعة كدليل تاريخي يحتاج مطابقة مع الكود الحالي.
لا تعِد تشغيل الاختبار على قاعدة التشغيل.
حدد لماذا وصل إلى الاتصال التشغيلي، وأي تهيئة أو اعتماد أدى إلى ذلك، وكيف يمكن تشغيل الاختبار عبر Production Orchestrator مع قاعدة اختبار معزولة دون استبدال مسار الإنتاج بمنطق وهمي.
## 5. تصميم العزل المطلوب
قدّم تصميمًا محددًا يضمن:
- اتصال التطبيق الافتراضي للاختبارات يستخدم قاعدة مستقلة.
- اتصال `scientific_records` يستخدم قاعدة اختبار مؤقتة مستقلة.
- لا يمكن للاختبار حذف أو تعديل البيانات العلمية التشغيلية.
- ملفات SQLite المؤقتة وملفات WAL/SHM تبقى داخل مساحة الاختبار.
- الاختبار الذي يستخدم Production Orchestrator يحتفظ بسلوك الاستدعاء الإنتاجي، لكن يحقن أو يهيئ الاعتماد على مخزن اختبار مستقل.
- تنظيف الموارد لا يحذف أي ملف خارج مساحة الاختبار.
- فشل تهيئة العزل يؤدي إلى فشل مغلق (fail closed) قبل تنفيذ عمليات الكتابة.
إذا اقترحت متغيرات بيئة، أثبت من الكود أنها تُقرأ فعلًا. لا تفترض أن تعيينها وحده يحقق العزل.
## 6. خطة الاختبارات المقترحة
حدد أصغر مجموعة اختبارات ينبغي تشغيلها بعد تنفيذ العزل، ثم ترتيب تشغيل مجموعة LocalIndex الكاملة.
لكل اختبار، وضح:
- سبب تشغيله.
- قاعدة البيانات التي سيستخدمها.
- الملفات التي يُسمح له بالكتابة إليها.
- معيار النجاح.
- كيفية إثبات أن قاعدة التشغيل لم تتغير.
لا تشغّل أي اختبار في هذه الجولة.
لا تشغّل اختبارات PostgreSQL أو Migrations أو حصاد المصادر.
## 7. شروط التوقف
توقف عن أي إجراء قد يفتح اتصالًا تشغيليًا أو يكتب خارج مجلد الاختبار.
إذا لم يمكن إثبات مسارات الاتصال والكتابة من الكود، اذكر ما ينقص ولا تخمّن.
لا تعدّل ملفات الاختبار أو الإنتاج في هذه المهمة.
## 8. التقرير النهائي
أرسل تقريرًا عربيًا واحدًا يشمل:
1. الملفات والأصناف التي فُحصت.
2. مسار الاتصال الفعلي لكل اختبار.
3. السبب الجذري المثبت أو الفرضيات التي ما زالت غير مثبتة.
4. خطة عزل دقيقة بأسماء الملفات ونقاط الحقن المقترحة.
5. الاختبارات المطلوبة وترتيبها.
6. وسائل التحقق من عدم لمس قاعدة التشغيل.
7. المخاطر المتبقية.
8. قائمة التغييرات المقترحة، دون تنفيذها.
9. تأكيد عدم تشغيل اختبارات أو تعديل ملفات أو تغيير Git أو قواعد البيانات.
معيار النجاح هو تقديم خطة عزل قابلة للتنفيذ ومبنية على الكود الفعلي، لا مجرد تعيين متغيرات البيئة أو تعطيل الاختبار المتكامل.
