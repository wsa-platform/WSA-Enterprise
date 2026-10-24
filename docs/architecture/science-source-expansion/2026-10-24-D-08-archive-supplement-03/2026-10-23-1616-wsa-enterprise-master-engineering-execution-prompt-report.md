<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 18173). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## A. الملخص التنفيذي

الحالة النهائية: `NEEDS_OWNER_DECISION`.

اكتملت مراجعة خط الأساس، وسلامة الملفات المحمية، وسلسلة ADR، وعزل الاختبارات، وتشغيل مجموعة CapVer المعزولة. لم يُنفَّذ staging ولا commit ولا push، لأن القرارات الأربعة ما زالت بلا اعتماد، ولأن تنفيذ `CapVerSeatVerificationService` يحمّل كتالوجات غير متعقبة لم تُراجع سطرًا بسطر.

التغيير الهندسي الوحيد في هذه الجولة: تصحيح نص المساعدة في `CapVerAcceptedSeatCommand` حتى يصف المقعد الموضعي والدفعة المصرح بها، بدل حصر النص في `G1-02` و`G6-20`. الملف بقي غير متعقب.

نقطة التوقف: بوابة الـ staging. الفهرس فارغ وHEAD لم يتغير.

## B. خط الأساس والأدلة

| البند | النتيجة |
|---|---|
| المستودع | `G:/WSA-Enterprise` |
| الفرع | `phase-18-m18-ai-marketing-communications` |
| HEAD | `fb60b60746ed487e2bfa04e3d335827d91cda446` |
| upstream | `origin/phase-18-m18-ai-marketing-communications` |
| porcelain | 168 سطرًا: 51 معدّلًا، 117 غير متعقب، 0 محذوف، 0 في الفهرس |
| بعد الاختبارات | الفرع وHEAD والفهرس كما هم |

`git diff --numstat` الحالي لـ ADR-023 هو `231 0`. العناوين المضافة هي §§8.66–8.71 فقط. هذه الأقسام غير موجودة في HEAD.

بصمة شريحة §§8.60 حتى ما قبل §8.71، بفك UTF-8 ثم إعادة ترميز المقطع نفسه: `aeac035e95e242ddd149f05c6b48186d1c1f7e02ec6b43e53945a1fb09adaa54`. طول المقطع 35108 محرفًا. البصمة تطابق المرجع المسجل. بصمة المقطع من §8.60 حتى نهاية الملف، بما فيه §8.71: `4d2432ca1aee85d32e55d4d9361a07e23fe4027d8f6b178380c777cc1628e708`.

`DB_CONNECTION=pgsql` في `backend/.env`. لا يوجد `CAPABILITY_STORE_CONNECTION` ولا `CAPVER_DATABASE`، فيبقى افتراض `capability_store.php` هو `capver` ومسار `storage/app/capver.sqlite`. فرق `database.php` لا يمس مفتاح الاتصال الافتراضي. `bootstrap.php` يفرض للاختبارات `CAPABILITY_STORE_CONNECTION=sqlite` و`CAPVER_DATABASE=:memory:` وملفًا علميًا تحت `storage/framework/testing`. تعليق الملف نفسه ينص على أن PHPUnit 10+ يتجاهل `force` في `phpunit.xml`.

محاولة حفظ porcelain في مجلد مؤقت خارج المستودع فشلت بسبب اسم ترميز غير مدعوم في PowerShell. لم يُكتب ملف دليل داخل المستودع. الأرقام أعلاه من خرج Git في هذه الجولة.

## C. قرارات المالك

| القرار | الحالة | التوصية | سبب بقائه مفتوحًا |
|---|---|---|---|
| 1. الـ hunks المختلطة | `UNRESOLVED` | نشر كتلتَي `database.php` و`bootstrap.php` كاملتين، لأن كل سطر فيهما راجع ومطلوب لعزل الاختبار. استبعاد كتل المرض وFacebook في `.env.example` لأنها hunks منفصلة. جملة CapVer داخل كتلة التعليقات العلمية لا تنفصل دون تحرير التصحيح. | هذا البرومبت يوصي ولا يعتمد. نشر `scientific_records` يوسّع الوصف المعماري لاتصال المخزن العلمي. |
| 2. نطاق الاعتماد | `UNRESOLVED` | أصغر إغلاق تشغيلي، بعد مراجعة سطرية لـ `build()` في الكتالوجين. لا تُdropped الاعتمادات المطلوبة، ولا تُنشر محولات البحث المعدّلة لمجرد الجوار. | `get()` يستدعي `all()` ثم `build()` ويبني كل المقاعد. Onboarding وRegistry وFamily وContract وLocalIndex غير متعقبة بالكامل. |
| 3. ADR §§8.66–8.71 | `UNRESOLVED` | نشرها معًا كإلحاق واحد إذا اعتُمد النص. السلسلة متسقة: تفويض، ثم حجب، ثم استعادة أربعة صفوف، ثم إنشاء G5-04 كصف جديد. | لا يوجد اعتماد صريح في التعليمات الحالية. |
| 4. تنظيف التجاهل | `UNRESOLVED` | تأجيله. عدم إدخال `recovery/` ولا قواعد التشغيل في أي قائمة نشر. | القاعدة الناقصة لا تُصلح في هذه الجولة لأن تعديل `.gitignore` غير معتمد. |

## D. التغيير الهندسي والاعتماد

`resolveSeat()` يسأل `CurrentMembershipNearReadySeatCatalog::get()` ثم `NextGateEligibleSeatCatalog::get()`. كلاهما يبني الكتالوج كاملًا. كتالوج العضوية الحالية يعرّف `G1-02` و`G6-20`. G5-04 يُستكمل من كتالوج البوابة التالية، وهذا البناء ينشئ ملفات العائلات والاتصال. `AcceptedOwnerDecisionCatalog::capVerAuthorizedSeats()` يعيد `G1-02`، `G6-20`، `G1-01`، `G1-17`، `G5-04`.

الجرد الفعلي:

| المسار | متعقب في HEAD | غير متعقب |
|---|---|---|
| `Onboarding/` | 0 | 19 |
| `Search/Registry/` | 0 | 16 |
| `Search/Family/` | 0 | 16 |
| `Search/Contract/` | 0 | 12 |
| `Search/LocalIndex/` | 0 | 19 |
| `Path/` | 12 | 0 |
| `ScientificSourceAdapterV2Interface.php` | لا | نعم |
| `ScientificSourceAdapterInterface.php` | نعم، ومعدّل محليًا | — |

`ScientificSourceCatalog` يستورد الكتالوجين وواجهات البحث الأربع المعدّلة محليًا. اختبار المقاعد يستدعي `ScientificSourceCatalog::forgetDefault()`، فنشر الاختبار يسحب هذا الصف حتى لو كانت الخدمة لا تناديه مباشرة.

نموذج `CghiaCapabilityCell` والمستودع و`capability_store.php` وحارس الـ migration كافية لتعريف الاتصال، وغير كافية لتشغيل المقعد.

نص الأمر بعد التصحيح: الوسيط الموضعي يشغّل مقعدًا واحدًا، وغيابه يشغّل الدفعة المصرح بها. السلوك التنفيذي لم يتغير. `php -l` خرج 0.

حدود هذه الجولة: لم تُراجع ملفات الكتالوج والـ Registry والـ Family سطرًا بسطر، لذلك لا تدخل manifest النشر كـ `INCLUDE`.

## E. قائمة الملفات

| المسار | الحالة | القرار | السبب |
|---|---|---|---|
| `CghiaCapabilityCell.php`، المستودع، migration CapVer، `capability_store.php` | معدّل أو غير متعقب | `BLOCKED` | راجع، ومطلوب، ولا يُنشر قبل القرار 2 |
| `CapVerSeatVerificationService.php`، `CapVerSeatOutcome.php`، الأمر، المستورد، النسخ، أمر الاستيراد | غير متعقب | `BLOCKED` | الأمر راجع ونُقح نصه. الخدمة تسحب كتالوجات غير مراجعة |
| `database.php`، `bootstrap.php`، `DatabaseSafety.php`، `phpunit.xml` | معدّل | `BLOCKED` | الـ hunks راجعة. نشرها قرار 1 |
| `.env.example` | معدّل | `BLOCKED` | كتلة المصادر مختلطة. كتل المرض وFacebook تُستبعد |
| اختبارات CapVer الثلاثة وتركيب `tests/Fixtures/capver-historical-recovery.json` | غير متعقب | `BLOCKED` | الاختبارات نجحت. التركيب نسخة اختبار لا أثر التشغيل |
| `storage/framework/recovery/` | غير متعقب وغير متجاهل | `EXCLUDE` | أثر تشغيلي. `git ls-files` لا يتتبعه |
| ملفات sqlite الأربعة | غير متعقبة ومتجاهلة | `EXCLUDE` | بيانات محمية |
| Stage3، كتالوج الملفات، المزود، `agricultural_intelligence.php`، بحث وعرض واختبارات الميزات، ADR-021، `.gitignore` الجذر | معدّل | `EXCLUDE` | خارج إغلاق CapVer أو مكرر `/backups/` |
| `e2e-tmp/`، `tools/`، `composer.lock`، أرشيف التوسع | غير متعقب | `EXCLUDE` | غير مراجع للنشر |
| ADR-023 وتقارير 23 أكتوبر | معدّل أو غير متعقب | `BLOCKED` | القرار 3. التقارير وثائق جلسة، وليست دليلًا بديلًا عن القراءة الحالية لقاعدة التشغيل |
| `TestCase.php` | بلا فرق | `EXCLUDE` | يستدعي الحارس القائم. لا يحتاج تعديلًا |

لم تُراجع كل الملفات المرشحة بحثًا أمنيًا سطرًا بسطر خارج مسار CapVer والإعدادات التي فُتحت. في ما فُتح: لا قيم أسرار، والمفاتيح في `.env.example` و`agricultural_intelligence.php` استدعاءات `env()` أو تعليقات فارغة. هذا ليس تدقيقًا أمنيًا كاملًا للشجرة.

## F. الاختبارات

العزل قبل التشغيل: bootstrap يفرض `:memory:` لـ CapVer وملفًا باسم العملية تحت `storage/framework/testing`. `DatabaseSafety` يرفض مسار `capver.sqlite` التشغيلي ومسار المخزن العلمي. `deleteOwned` في اختبار G5-04 لا يحذف مسارًا خارج `storage/framework/testing`. الاختبارات لا تستدعي الاستيراد التاريخي على ملف التشغيل.

الأمر:

`G:\tools\php-8.4.26-nts\php.exe vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --do-not-cache-result` على الملفات الثلاثة.

النتيجة: `OK (10 tests, 127 assertions)`، الزمن `00:19.135`، الذاكرة 48 MB، `TEST_EXIT=0`. التوزيع: 3 لاختبار G5-04، و5 لاختبار الخدمة، و2 لمتجر CapVer المعزول.

لم تُشغَّل مجموعة PHPUnit الكاملة ولا اختبارات `LocalIndex`. لم يُعاد تشغيل `scientific:capver-accepted`.

## G. سلامة قواعد البيانات

لم تُفتح ملفات SQLite، ولم يُكتب إليها، ولم يُستخدم Docker أو Artisan على اتصال تشغيلي. الاختبارات كتبت فقط تحت مجلد testing المتجاهل.

| الملف | SHA-256 قبل الاختبارات وبعدها | الحجم | وقت الكتابة |
|---|---|---|---|
| المخزن العلمي | `8827901A30DC8F93888F82E10B096190DB440766733D9ABEEE11211` مطابق للمرجع | `1393659904` | `2026-10-22T09:53:58.9832998Z` |
| `capver.sqlite` | `66CA7C1497BA84E3FF9ECCBF01D6A5E2BC193571469EEAED1A5D10B19C00B875` ثابت | `32768` | `2026-10-23T12:20:07.1352738Z` |
| النسختان الاحتياطيتان | `5B8E3ABC1670C0D642308FF83A25300C2B0FAAE8345014FE738BF5D01C926D81` لكلتيهما | `32768` | وقتان مختلفان |

البصمتان ثابتتان قبل الاختبارات وبعدها. محتوى الصفوف داخل `capver.sqlite`: `NOT VERIFIED` في هذه الجولة. تطابق بصمة ملفي النسخ مع بعضهما لا يثبِت عدد الصفوف. نسخة SQLite الاحتياطية لا تساوي بالضرورة بايتات الملف الحي، لذلك لا تُقارَن بصمة النسخة ببصمة الملف الحي السابقة `412dee78…`.

هويات السجلات الخمسة: موجودة كنصوص في `seatSpec()` وفي تأكيدات الاختبار المعزول. لم تُقرأ من قاعدة التشغيل.

لم يُنفَّذ اتصال GBIF حي.

## H. حالة Git

شجرة العمل ما زالت تحمل العمل المحلي السابق، مع تعديل نص الأمر غير المتعقب. الفهرس فارغ. لا staged diff. لا commit ولا push.

لم يُستخدم `git add` ولا `reset` ولا `clean` ولا `stash` ولا تبديل فرع ولا دمج ولا إعادة كتابة تاريخ.

`capver.sqlite` و`.backup` و`pre-g504` والمخزن العلمي متجاهلة وغير متعقبة. `backend/storage/framework/recovery/capver-historical-recovery.json` غير متجاهل وغير متعقب. تجاهل الجذر لـ `/backups/` مكرر في النسختين المحليتين. `backend/storage/framework/.gitignore` لا يغطي مجلد recovery.

## I. المخاطر

- نشر الخدمة دون كتالوجات `build()` ينتج التزامًا لا يحمّل G5-04.
- نشر `ScientificSourceCatalog` يسحب واجهات بحث معدّلة محليًا لم تُراجع في هذه الجولة.
- `recovery/` يمكن أن يدخل أي `git add` غير انتقائي.
- نص §8.71 يصف قراءة PDO تشغيلية من جلسة سابقة. هذه الجولة لم تعد تلك القراءة.
- اختبار الخدمة بالمستودع الوهمي لا يثبت ملف `capver.sqlite`. اختبار العزل يثبت السلوك على ملف testing فقط.

## J. الإجراء التالي

اعتماد القرارات الأربعة في رد واحد، ثم مراجعة سطرية لإغلاق `build()` قبل أي staging. أصغر خطوة آمنة بعد الاعتماد: قرار 2 بخيار الإغلاق الأدنى، مع استبعاد `recovery/` وقواعد التشغيل وكتل المرض وFacebook، ثم إعادة بوابة الـ staging على المسارات المعتمدة فقط.

إلى أن يصل هذا الاعتماد، لا يُنفَّذ `git add` ولا commit ولا push.
