<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 18121). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## الحالة النهائية

`RESOLVED — G5-04 CREATED AND VERIFIED`

أُنشئ سجل GBIF واحد في `backend/storage/app/capver.sqlite`. السجلات الأربعة التاريخية بقيت كما كانت. تكرار الأمر لم يضف صفًا ولم يغيّر الحقول.

## ما فُحص وما نُفّذ

فُحص الفرع وHEAD والفهرس، ونص ADR-023 §8.66 كاملًا، و`seatSpec()`، وتوقيع الأمر، وفهارس SQLite، وصفوف `capver.sqlite` بقراءة فقط. ثم كُتب الصف عبر الأمر، وقُرئ بـ PDO مستقل، وأُعيد الأمر، وشُغّلت اختبارات معزولة.

الفرع `phase-18-m18-ai-marketing-communications`، وHEAD قبل التنفيذ وبعده `fb60b60746ed487e2bfa04e3d335827d91cda446`. فهرس Git كان فارغًا وبقي فارغًا. لم يُنفَّذ staging ولا commit ولا push.

## البوابات

| البوابة | النتيجة |
|---|---|
| Authorization | PASS. §8.66 يخص `G5-04` / `gbif`، ويسمي `ACCESS_METHOD` / `api` والمعرّف `cap-rec-r9-20261016-G5-04-api`. §8.66.5 يطلب الكتابة والقراءة المستقلة والتكرار. الأقسام §§8.67–8.70 لا تلغي ذلك |
| Identity | PASS. المواصفة تطابق §8.66.4. الخلية seat-scoped، فـ `canonical_identity_id` فارغ و`seat_override_applied=false` |
| Conflict | PASS، الحالة A. قبل الكتابة لا مفتاح طبيعي لـ G5-04 ولا المعرّف نفسه |
| Isolation | PASS. الخيار `--seat` غير موجود. المحدد الفعلي وسيط موضعي، وغيابه يشغّل الدفعة كلها. استُخدم `scientific:capver-accepted G5-04` فقط |
| Persistence | PASS. `capability_store.connection=capver` والملف `capver.sqlite`. الافتراضي بقي `pgsql` |
| Evidence | PASS. المراجع الخمسة من المواصفة المعتمدة. هذه الجلسة لم تستدعِ واجهة GBIF ولم تخترع دليلًا |

## السجل

عدد صفوف المفتاح الطبيعي: **0** قبل التنفيذ، **1** بعده، **1** بعد التكرار.

المعرّف النهائي: `cap-rec-r9-20261016-G5-04-api`. المعرّف التخزيني التلقائي `id=6`. الحالة `VERIFIED` / `CURRENT`، والحداثة `CURRENT`، واللقطة `gbif.rest_api.species_search.v1`، و`observed_at` = `2026-10-22T06:33:36+00:00`.

`verified_at` = `2026-10-23T12:20:05+00:00`، و`created_at` / `updated_at` = `2026-10-23 12:20:06`. هذا وقت التنفيذ الجديد، وليس طابعًا مستعادًا من PostgreSQL.

الأمر الأول خرج 0 وطبع `store=PERSISTED`. التكرار خرج 0 وطبع `store=ALREADY_CURRENT`. قراءة PDO بوضع القراءة فقط بعد التكرار طابقت الصفوف بعد الإنشاء الأول. `PRAGMA integrity_check` كان `ok`. المجموع 5 صفوف، ولا معرّف جديد غير هذا.

الأعمدة الأربعة للمعرفات التاريخية طابقت تصدير ما قبل الكتابة حقلًا بحقل.

## المخزن العلمي والاتصال

`scientific_record_store.sqlite` بقي طوله `1393659904`، وLastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`، وSHA-256 `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`. لم يُفتح للكتابة.

`capver.sqlite` قبل الإدراج: `412dee7813730c6de5d4511c2e90f089ef83d21bce5f5d2d998d961efba36124`. بعده: `66ca7c1497ba84e3ff9eccbf01d6a5e2bc193571469eeaed1a5d10b19c00b875`. نُسخت القاعدة قبل الكتابة إلى `capver.sqlite.pre-g504.backup` دون استبدال `capver.sqlite.backup`.

## الاختبارات والملفات

- `CapVerG504IsolationTest`: `OK (3 tests, 47 assertions)`، خروج 0، `00:17.101`، على ملف تحت `storage/framework/testing`.
- `CapVerSeatVerificationServiceTest`: `OK (5 tests, 48 assertions)`، خروج 0، `00:01.966`، بمستودع وهمي.

تغير: ADR-023 بإلحاق §8.71 فقط، واختبار `CapVerG504IsolationTest.php`، والتقرير `docs/2026-10-23-G5-04-CAPVER-COMPLETION-REPORT.ar.md`، وصف القاعدة المتجاهَل من Git ونسخته الاحتياطية الجديدة. بصمة §§8.60–8.70 قبل الإلحاق وبعده: `aeac035e95e242ddd149f05c6b48186d1c1f7e02ec6b43e53945a1fb09adaa54`.

لم تُشغَّل مجموعة الاختبارات كاملة، ولم يُنفَّذ Docker، ولم يُعاد فتح PostgreSQL. الدليل الحي في الصف نص محفوظ في المواصفة، وليس طلب شبكة جديدًا من هذه الجلسة. الدفعة الكاملة دون وسيط المقعد ما زالت خارج هذا التنفيذ.
