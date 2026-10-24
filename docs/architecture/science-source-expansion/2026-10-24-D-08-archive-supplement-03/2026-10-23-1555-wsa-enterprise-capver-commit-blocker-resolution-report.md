<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 18151). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## A. الحالة النهائية

`NEEDS_OWNER_DECISION`

المراجعة التشخيصية اكتملت بما يكفي لتحديد سبب التوقف وطريقة الفصل. النشر ما زال متوقفًا لأن الملفات المشتركة وسلسلة ADR وشجرة الاعتماد غير المتعقبة تحتاج اختيارًا صريحًا. هذه الجولة لا تمنح `READY_FOR_COMMIT`.

## B. حالة Git

| البند | القيمة |
|---|---|
| المستودع | `G:/WSA-Enterprise` |
| الفرع | `phase-18-m18-ai-marketing-communications` |
| HEAD | `fb60b60746ed487e2bfa04e3d335827d91cda446` |
| upstream | `origin/phase-18-m18-ai-marketing-communications` |
| آخر الالتزامات | `fb60b60`، `573c3b4`، `527ebe8`، `168edcc`، `dffeed5` |
| متعقب معدّل | 51 |
| غير متعقب في `porcelain` | 117 إدخالًا |
| ملفات غير متعقبة بعد فك المجلدات | 1444 |
| محذوف | 0 |
| الفهرس | فارغ (`git diff --cached` بلا مخرجات) |
| الاختلاف عن خط الأساس | لا يوجد. الفرع وHEAD مطابقان. |

## C. الأسباب الفعلية للتوقف

### 1. ملفات مختلطة في hunk واحد

- الدليل: `backend/config/database.php` إدخال واحد `+44/-0` يضم اتصال `scientific_records` ثم اتصال `capver` بلا سطر قديم بينهما. `backend/tests/bootstrap.php` hunk واحد يضم إنشاء ملف الاختبار العلمي ومتغيري `CAPABILITY_STORE_CONNECTION` و`CAPVER_DATABASE`. `backend/.env.example` يضم جملة CapVer داخل كتلة تعليقات المخزن العلمي نفسها (`+22`)، ثم كتل `DISEASE_*` و`FACEBOOK_*` في hunks لاحقة.
- الأثر: لا يمكن إبقاء نصف الملف خارج الالتزام دون تعديل شجرة العمل أو تحرير التصحيح. `git add -p` مع أمر التقسيم لا يجد سياقًا غير متغير بين النصفين.
- الإجراء: اختيار المالك أدناه. لا يُنفَّذ الفصل في هذه الجولة.
- قرار مالك: نعم.

### 2. ADR-023 حالة A

- الدليل: `git diff --numstat` للملف `231 0`. عناوين HEAD تتوقف عند §§8.63–8.65 ثم §§8.60–8.62. النسخة المحلية تضيف §§8.66–8.71 بعد §8.62. لا أسطر محذوفة.
- الأثر: نشر الملف ينشر سلسلة التفويض والحجب والاستعادة مع سجل G5-04. نشر §8.71 وحده يتطلب قص الملف.
- الإجراء: اعتماد نشر §§8.66–8.71 معًا، أو إبقاء الملف كله خارج الالتزام.
- قرار مالك: نعم.

### 3. سلوك CapVer غير موجود في HEAD

- الدليل: `CapVerSeatVerificationService` و`CapVerAcceptedSeatCommand` و`capability_store.php` والمستورد وأمر الاستيراد غير متعقبة. الخدمة تستورد `AcceptedOwnerDecisionCatalog` و`CurrentMembershipNearReadySeatCatalog` و`NextGateEligibleSeat` و`NextGateEligibleSeatCatalog` و`SeatActivationState`. اختبار المقاعد يستورد أيضًا `ScientificSourceCatalog`.
- الأثر: الالتزام بالنموذج واتصال `capver` وحدهما لا يشحن تنفيذ G5-04، ويفشل التحميل عند تشغيل الاختبارات.
- الإجراء: توسيع النطاق إلى مجموعة الاعتماد بعد مراجعة سطرية لتلك الشجرة، أو إبقاء CapVer خارج الالتزام.
- قرار مالك: نعم. مراجعة شجرة Onboarding/Registry لم تكتمل سطرًا بسطر في هذه الجولة.

### 4. فجوة تجاهل ملف الاستعادة

- الدليل: `git check-ignore` تجاهل `capver.sqlite` بالسطر 3، و`.backup` بالسطر 6، و`pre-g504` والمخزن العلمي بالقاعدة `*` في `backend/storage/app/.gitignore`. `backend/storage/framework/recovery/capver-historical-recovery.json` (7327 بايت) وملف `.sha256` غير متجاهَلين وغير متعقبين. `backend/storage/framework/.gitignore` يتجاهل ملفات الكاش فقط.
- الأثر: `git add -A` لاحقًا قد يدخل أثر الاستعادة. الالتزام الانتقائي بالأسماء يتجنبه.
- الإجراء: في مهمة لاحقة مصرح لها بتعديل `.gitignore`، تُضاف قاعدة لـ `backend/storage/framework/recovery/`. لا تُضاف القاعدة الآن.
- قرار مالك: نعم، لتعديل التجاهل لاحقًا. ليس مانعًا للالتزام الانتقائي إذا بقي المسار خارج القائمة.

## D. جدول مراجعة الملفات

روجعت فروقها كاملة أو أُضيفاتها المميزة:

| الملف | التصنيف | النتيجة |
|---|---|---|
| `CghiaCapabilityCell.php` | CapVer | `getConnectionName()` فقط. مطلوب ضمن المجموعة، وغير كافٍ وحده. |
| `EloquentCapabilityStoreRepository.php` | CapVer | `transaction()` على اتصال النموذج. مطلوب مع النموذج. |
| migration `2026_10_09_140000_...` | CapVer | حارس يرفض `scientific_records` وملف المخزن العلمي وقاعدة `wsa_enterprise`. |
| `capability_store.php` | CapVer، غير متعقب | المفتاح `connection` افتراضه `capver`. |
| `database.php` | مختلط | الاتصالان في hunk واحد. الافتراضي لم يُمس. |
| `phpunit.xml` | عزل اختبارات | سطران فقط. PHPUnit 10+ يتجاهل `force`؛ الفرض الفعلي في `bootstrap.php`. |
| `bootstrap.php` | مختلط | إنشاء ملف علمي تحت `storage/framework/testing`، ثم `:memory:` لـ CapVer. |
| `DatabaseSafety.php` | عزل اختبارات | `assertCapVerStoreIsIsolated()` يُستدعى من بوابة كل `TestCase`. يرفض الملف التشغيلي ويسمح بـ `:memory:` أو مسار تحت `storage/framework/testing`. |
| `TestCase.php` | متعقب بلا فرق | يستدعي بوابة العزل قبل `parent::setUp()`. لم يتغير. |
| `storage/app/.gitignore` | حماية | الأسطر الجديدة توثيق؛ القاعدة `*` تغطي الملفات أصلًا. |
| `.gitignore` الجذر | خارج النطاق | يضيف `/backups/` مرتين. HEAD لا يحتوي القاعدة. |
| `.env.example` | مختلط | تعليقات فارغة. لا قيم مفاتيح. |
| `agricultural_intelligence.php` | نطاق أوسع | مفاتيح تفعيل المصادر الخمسة و`local_index.connection`. `BHL_API_KEY` عبر `env()` بلا قيمة ملتزمة. |
| `AgriculturalIntelligenceServiceProvider.php` | نطاق أوسع | ربط LocalIndex وOAI وBulk، لا مسار كتابة CapVer. |
| `Stage3CghiaCoexistenceBoundary.php` و`Stage3SourceKeyIdentityBridge.php` | نطاق أوسع | قراءة متجر القدرات وربط المقاعد الخمسة. يعتمدان على `ScientificSourceCatalog` غير المتعقب. |
| `ScientificSourceProfileCatalog.php` | نطاق أوسع | تفعيل ملفات مصادر، منها G5-04 كبحث لا كسجل CapVer. |
| `ScientificRecordStoreIsolationGuardTest.php` | عزل علمي | سطر `assertFileExists` واحد. |
| `WsaEnterpriseStage9SecurityTest.php` | خارج CapVer |stubs لـ Semantic Scholar. |
| `docs/adr/README.md` | وثائق | روابط سجلات غير متعقبة. |
| ADR-023 | ADR | حالة A، تفصيلها في القسم E. |
| `CapVerG504IsolationTest.php` | اختبار، غير متعقب | ملف تحت `storage/framework/testing`، و`deleteOwned` يرفض أي مسار لا يحتوي هذا المقطع. |
| `CapVerSeatVerificationServiceTest.php` | اختبار، غير متعقب | Mockery. لا يفتح ملف CapVer. يعتمد على كتالوجات غير متعقبة. |
| `CapVerDedicatedStoreTest.php` | اختبار، غير متعقب | ملف معزول، وفيه اختبار اسم الملف العلمي تحت مجلد testing. |
| `CapabilityStorePersistenceTest.php` | متعقب بلا فرق | `RefreshDatabase` على الاتصال الافتراضي للاختبار. |
| `ScientificRecordsSqliteDurabilityTest.php` | متعقب بلا فرق | موجود. فرق شجرة العمل لا يمسه. |
| أوامر وخدمات CapVer غير المتعقبة | CapVer | الأمر الموضعي `scientific:capver-accepted {adr_id?}`. غياب الوسيط يشغّل كل المقاعد المصرح بها. نص المساعدة ما زال يذكر `G1-02` و`G6-20` فقط. |
| تقارير 22 و23 أكتوبر | وثائق | قُرئت عناوينها وادعاءاتها. انظر القسم 7 أدناه. |
| `backend/storage/framework/recovery/` | تشغيل، غير متجاهل | أثر استعادة. لا تطابق لكلمات سر في الملف. |
| `composer.json` و`package.json` | بلا فرق | متعقبان ولم يتغيرا. |
| `composer.lock` | غير متعقب، غير محسوم | لم يُراجع. يبقى خارج أي مجموعة CapVer. |

لم تُراجع سطرًا بسطر، وتُستبعد من أي مجموعة حالية:

- بقية الفروقات المتعقبة في البحث والعرض واختبارات الميزات وADR-021 و`QueryUnderstandingService` و`ScientificUserPresentation`. مسح `-G` لم يجد فيها `capver` أو `G5-04` أو `scientific_records`. أحجامها كبيرة، منها `ScientificEvidenceAnswerPipelineContractTest.php` بنحو `+628`.
- 650 ملفًا تحت `docs/architecture/science-source-expansion/`.
- `backend/e2e-tmp/` و`admin-mobile/e2e-tmp/` و`tools/`: مخرجات تحقيق ونسخ احتياطية نصية. لم يُفتح محتواها بحثًا عن أسرار ملفًا ملفًا، لذلك تبقى كلها خارج النشر.
- أوامر الحصاد والإدخال والمخزن العلمي وmigration `2026_10_15_220000_...`.

الفحص الأمني على الفروق التي قُرئت: التطابقات هي تعليقات مفاتيح فارغة، و`env()` لكلمة مرور المخزن العلمي بلا قيمة ظاهرة، وأسماء رموز هوية. لا `.env` في قائمة الحالة. لا `dd(` ولا `var_dump` ولا `print_r` في خدمات CapVer. ملفات SQLite والنسخ الاحتياطية غير متعقبة.

## E. ADR-023

الحالة المعمارية: **A**. §§8.66–8.70 موجودة محليًا وغير موجودة في HEAD. §8.71 جديد معها.

في HEAD: §§8.63 و8.64 و8.65 ثم §§8.60 و8.61 و8.62. هذا الترتيب تاريخي داخل الملف.

محليًا بعد §8.62: §8.66 تفويض G5-04، §8.67 حجب PostgreSQL، §8.68 بوابة البيانات التاريخية، §8.69 توقف الاستعادة، §8.70 استعادة أربعة صفوف، §8.71 إنشاء G5-04.

المقارنة: `231` سطرًا مضافًا و`0` محذوفًا. العناوين المضافة هي هذه الستة فقط. النص الذي كان في HEAD لم يُحذف ولم يُستبدل. لا توجد حالة `ADR_HISTORICAL_SECTIONS_UNRESOLVED`.

البصمة `aeac035e…` سُجلت سابقًا على شريحة شجرة العمل من §8.60 حتى نهاية §8.70، وهي تشمل §§8.66–8.70 غير الموجودة في HEAD. لم تُعد حسابها في هذه الجولة حتى لا تُنشأ طريقة استخراج مختلفة. الدليل المستخدم هنا هو فرق Git نفسه: صفر حذف مقابل HEAD.

§8.71 يميز `observed_at` في 2026-10-22 عن `verified_at` و`created_at` في 2026-10-23، وينص على أن سطر GBIF الحي نص محفوظ لا طلب شبكة جديد. هذا متسق مع الخدمة: لا عميل HTTP داخل `execute()`.

الخيار المطلوب: اعتماد نشر §§8.66–8.71 كسلسلة إلحاق واحدة، لأنها سلسلة التفويض التي يستند إليها §8.71. إبقاء الملف خارج الالتزام يترك الكود بلا قرار منشور.

## F. حماية البيانات

فُحصت البيانات الوصفية بالقراءة فقط. لم تُفتح قواعد التشغيل.

| الملف | الحجم | LastWriteTimeUtc | التتبع |
|---|---|---|---|
| `capver.sqlite` | 32768 | `2026-10-23T12:20:07.1352738Z` | متجاهل، غير متعقب |
| `capver.sqlite.backup` | 32768 | `2026-10-23T12:02:43.2931886Z` | متجاهل صراحة، غير متعقب |
| `capver.sqlite.pre-g504.backup` | 32768 | `2026-10-23T12:19:49.7203659Z` | متجاهل بالقاعدة `*`، غير متعقب |
| `scientific_record_store.sqlite` | 1393659904 | `2026-10-22T09:53:58.9832998Z` | متجاهل بالقاعدة `*`، غير متعقب |

حجم المخزن العلمي ووقت كتابته مطابقان لخط الأساس المسجل. SHA-256 لم يُعد حسابه في هذه الجولة، لذلك لا يُعاد إعلان البصمة كنتيجة لهذا الفحص. عدد صفوف `capver.sqlite` لم يُقرأ هنا لأن فتح SQLite لم يُثبت أنه بلا أثر جانبي.

`DB_CONNECTION=pgsql` في `backend/.env`. لا يوجد `CAPABILITY_STORE_CONNECTION` ولا `CAPVER_DATABASE` هناك، فيبقى افتراض الملف `capver` ومسار `storage/app/capver.sqlite`. فرق `database.php` لا يغيّر مفتاح الاتصال الافتراضي.

`backend/backups/` غير موجود. `/backups/` في الجذر صار متجاهلًا بالنسخة المحلية المكررة. أثر الاستعادة تحت `storage/framework/recovery/` غير متجاهل.

## G. الاختبارات

لم تُشغَّل اختبارات في هذه الجولة.

المراجعة الساكنة:

- `CapVerG504IsolationTest` يرحّل اتصال `capver` إلى ملف تحت `storage/framework/testing` بعد إقلاع `TestCase`، ويستورد التركيبات الأربع، ثم يستدعي `execute('G5-04')` لا الدفعة الكاملة. `tearDown` يقارن حجم المخزن العلمي ووقت تعديله ويحذف الملف المملوك فقط.
- `CapVerSeatVerificationServiceTest` مستودع وهمي. يثبت سلوك الخدمة عند توفر المستودع، ولا يثبت ملف التشغيل.
- `CapVerDedicatedStoreTest` ملف اختبار منفصل، ويرفض اسم ملف المخزن العلمي عندما يكون تحت مجلد testing.
- `CapabilityStorePersistenceTest` يستخدم `RefreshDatabase` على الاتصال الافتراضي. مع فرض `CAPABILITY_STORE_CONNECTION=sqlite` يبقى على قاعدة الاختبار `:memory:` لا على `capver.sqlite`.
- خطر الإقلاع: `DatabaseSafety` يرفض التشغيل إذا كان اتصال `capver` غير معرّف أو ليس sqlite أو يشير إلى الملف التشغيلي. لذلك لا يُنشر الحارس دون كتلة `capver` في `database.php` وفرض bootstrap.

في الجولة التالية، وبعد بقاء الفرض على `:memory:` أو مسار testing، يمكن تشغيل الملفات الثلاثة المعزولة واختبار المثابرة المتعقب. تُستبعد اختبارات `LocalIndex` التي كتبت سابقًا إلى FTS التشغيلي إلى أن يُعاد إثبات مسارها. لا يُعاد تشغيل `scientific:capver-accepted`.

نتيجة سابقة من مهمة مصرح بها في 2026-10-23، ولم تُعد هنا: الملفات `CapVerG504IsolationTest` و`CapVerSeatVerificationServiceTest` معًا أعطت `OK (8 tests, 95 assertions)` وخروج 0. هذا ليس تحققًا لهذه الجولة.

## H. تصميم مجموعة التغييرات

لا توجد قائمة `git add` في هذه الجولة.

| المسار | الفئة | النشر المستقل | مختلط | الفصل المقترح | المخاطرة | الموافقة |
|---|---|---|---|---|---|---|
| النموذج، المستودع، حارس migration، `capability_store.php` | CapVer | لا | لا | تُنشر مع الخدمة والاتصال | منخفضة داخل المجموعة | مراجعة المجموعة |
| `database.php` | مختلط | لا | نعم | الإبقاء على الكتلتين معًا، أو تحرير تصحيح لاحقًا دون مس شجرة العمل | نشر `scientific_records` يوثّق خيار A ولا يكتب الملف | قرار 1 |
| `bootstrap.php` و`DatabaseSafety.php` و`phpunit.xml` | عزل | لا | bootstrap نعم | نشر hunk الـ bootstrap كاملًا مع الحارس | يغيّر إقلاع كل الاختبارات | قرار 1 |
| خدمة CapVer، النتيجة، الأمر، المستورد، النسخ، أمر الاستيراد، اختبارات CapVer الثلاثة، تركيب `tests/Fixtures/capver-historical-recovery.json` | CapVer | لا | لا | مع كتالوجات Onboarding و`ScientificSourceCatalog` بعد مراجعتها | الشجرة أوسع من G5-04 | قرار 2 |
| ADR-023 | ADR | لا | السلسلة كلها إضافة | نشر §§8.66–8.71 معًا أو استبعاد الملف | قرار معماري | قرار 3 |
| `.env.example` كتلة المصادر وCapVer | مختلط | لا | نعم | نشر الكتلة كلها كتعليقات، واستبعاد hunks المرض وFacebook | لا أسرار؛ يوثّق متغيرات غير مستخدمة في الكود المختار | قرار 1 |
| `.env.example` المرض وFacebook، `.gitignore` الجذر، مزود LocalIndex، تعايش Stage3، كتالوج الملفات، بحث وعرض واختبارات الميزات، ADR-021، `docs/adr/README.md` | خارج النطاق | نعم لبعضها | بعضها | تبقى في الشجرة وتخرج من مجموعة CapVer | نشرها يسحب عملًا غير مراجع | لا تُدرج الآن |
| `storage/framework/recovery/`، ملفات sqlite، `e2e-tmp/`، `tools/`، `composer.lock` | تشغيل أو غير محسوم | لا | — | تبقى غير مضافة | أثر تشغيلي أو محتوى غير مراجع | ممنوعة |

اعتماد الحد الأدنى إذا اختار المالك المجموعة الضيقة: النموذج يقرأ `capability_store.php`، والمستودع يكتب عبر هذا الاتصال، والاتصال يُعرَّف في `database.php`، والاختبارات ترفض الملف التشغيلي فقط إذا وُجدت الكتلة والحارس وفرض bootstrap معًا، والخدمة لا تُحمَّل بلا كتالوجات Onboarding.

## I. القرارات المطلوبة من المالك

**القرار 1 — الملفات المختلطة.**  
الخيار أ: في مهمة لاحقة، تُنشر كتل `database.php` و`bootstrap.php` وكتلة تعليقات `.env.example` كاملة، لأن الفصل يمس الملف أو التصحيح، والنصف العلمي إضافة مستقلة الاسم ولا يكتب المخزن. الخيار ب: تأذن مهمة لاحقة بتحرير تصحيح الفهرس فقط (`git apply --cached` من تصحيح مراجع) مع إبقاء شجرة العمل كاملة. مخاطر ب: خطأ في القص يصعّب المراجعة. مخاطر أ: الالتزام يوثّق اتصال المخزن العلمي مع CapVer.

**القرار 2 — عرض الاعتماد.**  
الخيار أ: مجموعة CapVer الضيقة بعد مراجعة سطرية لكتالوجات Onboarding و`ScientificSourceCatalog` والاختبارات الثلاثة والمستورد. خيار ب: توسيع الالتزام إلى تفعيل المصادر الخمسة وLocalIndex والمزود. مخاطر ب: مئات الملفات غير المراجعة تدخل النشر. مخاطر أ: بحث G5-04 وتفعيل الواجهات يبقى محليًا غير منشور، وهذا مقصود إذا كان النطاق هو مثابرة CapVer فقط.

**القرار 3 — ADR.**  
الخيار أ: نشر §§8.66–8.71 معًا كإلحاق واحد. الخيار ب: استبعاد ADR-023 من الالتزام إلى أن يُقبل هذا النطاق. قص §8.71 وحده يرفض لأنه يعدّل ملف القرار.

**القرار 4 — التجاهل.**  
مهمة لاحقة مصرح لها بتعديل `.gitignore` تضيف تجاهل `backend/storage/framework/recovery/` وتزيل تكرار `/backups/`. لا يُنفَّذ ذلك داخل مجموعة CapVer الحالية.

نص مساعدة الأمر الذي ما زال يذكر مقعدين فقط يمكن تصحيحه في المهمة التي يُصرَّح لها بتعديل الملفات. غيابه لا يغيّر سلوك `executeAcceptedSeats()`.

## J. تأكيدات التنفيذ

- لم يُنفَّذ `git add` ولا أي شكل من أشكاله.
- لم يُنفَّذ commit ولا push.
- لم يُعدَّل أي ملف، بما فيه ADR و`.gitignore`.
- لم يُحذف عمل محلي ولم يُستبدل.
- لم يُستخدم `reset` ولا `clean` ولا `stash` ولا تبديل فرع ولا دمج ولا إعادة قاعدة.
- لم تُشغَّل الاختبارات ولا Artisan ولا Tinker ولا Docker.
- لم تُكتب بيانات إلى قواعد التشغيل، ولم تُفتح ملفات SQLite.
- المخزن العلمي لم يُفتح للكتابة. فُحص حجمه ووقت كتابته فقط، ولم تُحسب بصمته في هذه الجولة.
