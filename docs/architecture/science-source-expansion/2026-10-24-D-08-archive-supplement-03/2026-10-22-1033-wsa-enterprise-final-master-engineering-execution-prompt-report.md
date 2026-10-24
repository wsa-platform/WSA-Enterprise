<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17971). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
التقرير محفوظ في `docs/2026-10-22-FIVE-SOURCE-SQLITE-CAPVER-AGRIS-PRECOMMIT-REPORT.ar.md`. لم يُنفَّذ ترحيل ولا التزام ولا دفع. إصرار CapVer على SQLite غير متحقق.

# تقرير الإغلاق قبل الالتزام — المصادر الخمسة، CapVer على SQLite، وتوسيع AGRIS

**التاريخ:** 2026-10-22  
**المستودع:** `G:\WSA-Enterprise`  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**HEAD:** `fb60b60746ed487e2bfa04e3d335827d91cda446`  
**الحالة الإجمالية:** العمل المستقل نُفِّذ. إصرار CapVer على SQLite لم يُنجز. هذا التقرير لا يُغلق التحقق من القدرة.

التقرير السابق `docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md` لم يُعدَّل. صفوف PostgreSQL المذكورة فيه دليل تاريخي فقط.

## 1. الملخص التنفيذي

- أُضيف تفويض CapVer للمقعد `G5-04` فقط في الكود وفي ADR-023 §8.66، مع الإبقاء على نص §8.58.5. `CONFIRMED`
- لم يُنفَّذ `scientific:capver-accepted`، ولم تُفتح PostgreSQL، ولم يُنشأ جدول CapVer أو ترحيل. المستودع الحالي `EloquentCapabilityStoreRepository` على الاتصال الافتراضي، ولا يوجد ملف SQLite يحتوي `cghia_capability_cell_records`. `BLOCKED`
- دفعة AGRIS واحدة: 50 مزوّداً على الكاش القائم، ثم إدخال 46 ملف JSONL مرخّص `CC-BY-4.0` بأسلوب `incremental`. النشط أصبح **96612** بعد **87984**. الجيل **76**. `CONFIRMED`
- لم تُرفع سقوف Dr. Duke، ولم يُفعَّل `G6-06`، ولم يُستدعَ بحث LocalIndex على الملف التشغيلي. `CONFIRMED`
- PHPUnit على PHP المضيف: **47 اختباراً، 373 تأكيداً، خروج 0**. أعلى طبقة للبحث هي Tier 3. Tier 1 لم يُشغَّل. `CONFIRMED`

## 2. خط الأساس

| البند | القيمة | الحكم |
|---|---|---|
| الفرع | `phase-18-m18-ai-marketing-communications` | `CONFIRMED` |
| HEAD | `fb60b60746ed487e2bfa04e3d335827d91cda446` | `CONFIRMED` |
| upstream عند البداية | `0 0` | `CONFIRMED` |
| `git status --short` | 148 عند البداية | `CONFIRMED` |
| `git diff --cached` | فارغ | `CONFIRMED` |

الملف العلمي قبل الكتابة: الطول `1354821632`، SHA-256 `3F0114A665682000637C318E07649F62B1A3B4482635A25A19726317EDD19E8C`. بعد الإدخال: الطول `1393659904`، `LastWriteTimeUtc` `2026-10-22T09:53:58Z`، SHA-256 `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`. لا `-wal` ولا `-shm`. تغيّر الهاش بسبب الإدخال المتزايد.

ملفات WIP غير المتتبَّعة التي عُدّلت من الداخل دون استبدالها: `AcceptedOwnerDecisionCatalog.php`، `CapVerSeatVerificationService.php`، `CapVerSeatVerificationServiceTest.php`، `NextGateEligibleSeatCatalog.php`.

## 3. المصادر الخمسة

| المصدر | المفتاح | المقعد | ما تأكد |
|---|---|---|---|
| GBIF | `gbif` | `G5-04` | العقد `cap-r9-20261016-G5-04-search` قائم. التفويض في الكود و§8.66. الإصرار لم يُنفَّذ |
| Organic Eprints | `organic_eprints` | `G1-02` | الجيل بقي `2` |
| BHL | `bhl` | `G6-20` | اختبارات `Http::fake`. المفتاح لم يُطبع. الاسترجاع الحي لم يُعد |
| AGRIS | `agris` | `G1-01` | القسم 5 |
| Dr. Duke | `dr_duke` | `G1-17` | الجيل بقي `19`. `G6-06` خارج التفويض |

مجسات HTTP الحية في التقرير السابق لم تُكرَّر: `SUPPORTED` لذلك التقرير، و`NOT VERIFIED` كإعادة قياس هنا.

## 4. CapVer وSQLite

`BLOCKED`

- PHP 8.4.26 يحمّل `pdo_sqlite`. `CONFIRMED`
- النموذج `CghiaCapabilityCell` بلا اتصال خاص، فالجدول `cghia_capability_cell_records` يتبع الاتصال الافتراضي. §8.58.5 و§8.60 يسجّلان PostgreSQL. `CONFIRMED`
- `scientific_records_108.sqlite` فيه جداول `scientific_source_*` فقط. `CONFIRMED`
- تاريخ مخطط الملف العلمي، حسب §8.62.4، هو ترحيل المخزن العلمي فقط. `SUPPORTED`
- لم يُنشأ جدول ولا ترحيل. لم يُشغَّل أمر CapVer. إدخال AGRIS أظهر الاتصال المفتوح `scientific_records` وحده.
- القراءة الراجعة والتكرار على SQLite: `NOT VERIFIED`. اختبار `ALREADY_CURRENT` يحاكي المخزن فقط.

الإجراء التالي: استخدام جدول SQLite قائم يقرأه المستودع الحالي دون إنشاء جدول، ثم تشغيل الأمر على PHP المضيف وقراءة الصف. حتى ذلك الحين القدرة `BLOCKED`.

## 5. AGRIS

**التصنيف:** `PARTIALLY EXPANDED — ENGINEERING WORK REMAINS`

| القياس | القيمة | الحكم |
|---|---:|---|
| عناوين `dcat:downloadURL` | 1244 | `CONFIRMED` |
| SHA-256 للجذر | `58f8532ef23cfe2d40666c03a1e8352b702fccb7ad6171483ce0fa3e3fbc7c95` | `CONFIRMED` |
| مكتمل في الحاجز قبل / بعد | 97 ثم **143** (المؤشر 147) | `CONFIRMED` |
| غير مكتمل | **1101 / 1244 = 11.50% مكتمل** | `CONFIRMED` |
| نشط قبل | 87984 عند الجيل 29 | `CONFIRMED` لذلك الهاش السابق |
| نشط بعد | **96612**، مسحوب 3، جيل **76** | `CONFIRMED` على نسخة معزولة حُذفت بعد العد |
| الدلتا | **+8628 = 9.81%** من مقام 87984 سجلاً | `CONFIRMED` |

الدفعة الوحيدة: attempted 50، downloaded 47، projected 46، skipped_unverified_license 1، failed 3، records 8628. الرخص المسقَطة `CC-BY-4.0` × 46. الاستبعاد: `AGRIS.ODS.FR0.zip` بلا رخصة. فشل المهلة: `DJ0.zip` و`SE0.xml` و`XS0.xml`. خروج الدفعة 0. نسبة الفشل 6%. لم تتكرر دفعتان فاشلتان، ولم يُطلق شرط الإيقاف. الدفعات 2–30 لم تُشغَّل لأن سقف الإسقاط 200 سجلاً مع تنزيل XML كبير، ولأن إدخال هذه الدفعة استغرق نحو 112 دقيقة.

الإدخال: 46 ملفاً `completed`، inserted 8628، updated 0، unchanged 0، deleted 0، withdrawn 0. إعادة `AGRIS.ODS.CU3.jsonl`: inserted 0، unchanged 163، الجيل 76. لا تكرار منطقي. الالتزام ينشئ جيلاً حتى لو لم يتغير سجل. `CONFIRMED`

جسم manifest v5 (588683 سجلاً مسقَطاً تاريخياً) لم يُدخَل. أجيال `dr_duke` و`organic_eprints` بقيتا 19 و2.

## 6. Dr. Duke

الجيل 19 لم يتغير. السقوف لم تُرفع. `G6-06` ليس في `capVerAuthorizedSeats()`، والاختبار الذي يثبت ذلك نجح. `CONFIRMED` لبقاء المقعد خارج التفويض. إعادة عدّ البادئات لم تُجرَ في هذه الجلسة: `NOT VERIFIED`.

## 7. البحث العلمي

فصل D-04 قائم ولم يُغيَّر: `ScientificSearchQueryBuilder` صاحب نص HTTP، و`ScientificQueryCompiler` يضيف `scientific_query_compilation` فقط. `CONFIRMED` أن الكود لم يُمس في هذا الفصل.

اختبارات المنسّق Tier 3. بحث FTS على الملف التشغيلي بعد الإدخال لم يُشغَّل، لأن `search()` قد يعيد بناء الفهرس عند اختلاف الجيل.

## 8. الاختبارات

`G:\tools\php-8.4.26-nts\php.exe` وPHPUnit 11.5.56 من `backend`، بالملفات الأحد عشر المذكورة في التقرير على القرص.

`OK (47 tests, 373 assertions)`، 00:51.622، 74 MB، **خروج 0**. `CONFIRMED`

| المصدر | الطبقة |
|---|---|
| GBIF، BHL | Tier 3 عبر `Http::fake` |
| Organic Eprints، AGRIS، Dr. Duke | Tier 3 على مخزن ذاكرة |
| AGRIS الإدخال | كتابة حقيقية على المخزن التشغيلي، وليست طلب منسّق |
| Tier 1 | `NOT VERIFIED` |

حزمة `tests/Pgsql` لم تُشغَّل. اختبار العزل استخدم `storage\framework\testing\scientific_records_21616.sqlite`.

## 9. الأرشيف

لم يُهاجَر الأرشيف ولم يُحذف. التقرير السابق بقي لنطاقه، بما فيه دليل PostgreSQL الذي لا يُعاد استخدامه كدليل SQLite. أُعيد استخدام عدد 87984 وهاش الملف السابق كخط أساس فقط، ثم أُعيد العدّ بعد الكتابة.

## 10. ADR-023

أُضيفت **§8.66** في نهاية الملف. 8.63–8.65 كانت مستخدمة. النص التاريخي لـ §8.58.5 لم يُعدَّل. الاستبدال محدود باستبعاد `G5-04` من `capVerAuthorizedSeats()` لهذا المقعد فقط. دليل الاكتمال (كتابة SQLite ثم قراءة راجعة ثم `ALREADY_CURRENT`) مسجَّل وغير متحقّق.

## 11. مصفوفة القبول

| البُعد | GBIF | Organic Eprints | BHL | AGRIS | Dr. Duke |
|---|---|---|---|---|---|
| الهوية | `gbif` / `G5-04` | `organic_eprints` / `G1-02` | `bhl` / `G6-20` | `agris` / `G1-01` | `dr_duke` / `G1-17` |
| CapVer SQLite | `BLOCKED` | `BLOCKED` | `BLOCKED` | `BLOCKED` | `BLOCKED` |
| قراءة راجعة | `NOT VERIFIED` | `NOT VERIFIED` | `NOT VERIFIED` | `NOT VERIFIED` | `NOT VERIFIED` |
| التماثل | `NOT VERIFIED` | `NOT VERIFIED` | `NOT VERIFIED` | CU3: inserted 0 / unchanged 163 | `NOT VERIFIED` |
| LocalIndex | مسار REST | جيل 2 ثابت | بلا فهرس لهذا المقعد | جيل 76، نشط 96612، مسحوب 3 | جيل 19 ثابت |
| E2E | Tier 3 | Tier 3 | Tier 3 | Tier 3 للبحث؛ إدخال تشغيلي للدفعة | Tier 3 |
| التفعيل | عقد ACTIVE؛ الإصرار غير مغلق | العقد ACTIVE؛ الإصرار غير مغلق | العقد ACTIVE؛ الحي غير معاد | العقد ACTIVE؛ التغطية جزئية | العقد ACTIVE؛ `G6-06` مؤجّل |

لا مصدر وصل إلى `CAPABILITY_PERSISTED` أو `PRODUCTION_READY`.

## 12. الفجوات المتبقية

1. CapVer على SQLite: `BLOCKED` حتى يتاح الجدول القائم للمستودع الحالي دون إنشاء جدول، ثم تُنفَّذ القراءة الراجعة للمقاعد الخمسة.
2. AGRIS: 1101 مزوّداً خارج الحاجز، وثلاثة انتهت مهلة تنزيلهم، و`FR0` بلا رخصة، وسقف 200 سجلاً، وجسم v5 غير مُدخَل.
3. Tier 1 وTier 2 للبحث غير متحقّقين.
4. استرجاع BHL الحي غير معاد، ومن دون طباعة المفتاح.
5. ترحيل الأرشيف بالجملة مؤجَّل وليس عائقاً لإغلاق ما اكتمل هنا.

## 13. مراجعة Git

- `git diff --check` على ADR-023: **خروج 0**.
- `git diff --cached`: فارغ.
- التقرير السابق ما زال `??` ولم يُمس. هذا التقرير `??`.
- نسخة العدّ `scientific_records_audit_20261022b.sqlite` حُذفت. `Test-Path` أعاد `False`.
- لا أسرار في التقرير.

## 14. تقسيم الالتزامات المقترح

لم يُنشأ التزام. لم يُنفَّذ `git add` ولا `commit` ولا `push`.

1. `docs(architecture): record scoped GBIF CapVer authorization` — §8.66 فقط.
2. `docs(research): record sqlite capver blocker and agris batch evidence` — هذا التقرير فقط.
3. تفويض `G5-04` وملاحظة AGRIS داخل ملفات غير متتبَّعة بالكامل. التزام لها يسحب WIP السابق مع سطور هذه المهمة. لا يُقترح فصلها قبل مراجعة المالك للملف كاملاً.
4. لا التزام للكاش ولا لملف SQLite العلمي.

## 15. نقطة التوقف

الشجرة عند `fb60b60746ed487e2bfa04e3d335827d91cda446` مع تعديلات غير مُرحَّلة. CapVer غير مُصرَّ على SQLite. AGRIS النشط **96612** عند الجيل **76**، بزيادة **8628** عن **87984**، والحاجز **143 من 1244**.

الخطوة التالية الآمنة هي مراجعة المالك لهذا التقرير و§8.66، دون التزام أو دفع. توسيع AGRIS التالي، إن طُلب، يبقى دفعة لا تتجاوز 50 مزوّداً وعلى الكاش نفسه، مع استبعاد ما لا تُثبت رخصته.
