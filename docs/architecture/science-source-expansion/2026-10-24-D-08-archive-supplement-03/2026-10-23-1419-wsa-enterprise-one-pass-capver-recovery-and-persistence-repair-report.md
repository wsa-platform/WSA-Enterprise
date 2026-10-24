<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 18045). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## الحالة النهائية

`BLOCKED — HISTORICAL DATA SOURCE UNAVAILABLE`

تقرير 2026-10-22 ملخص فقط. إعادة التوليد ممكنة وغير مكافئة للصفوف التاريخية. لم يُعثر على تلك الصفوف، ولم يُنشأ `backend/storage/app/capver.sqlite`، ولم يُشغَّل `scientific:capver-accepted`.

## الفرع وشجرة العمل

الفرع `phase-18-m18-ai-marketing-communications`، وHEAD هو `fb60b60746ed487e2bfa04e3d335827d91cda446`. الفهرس فارغ. كانت هناك 45 مسارًا متتبعًا معدّلًا قبل هذه الجولة. ما أُضيف هنا هو ADR-023 §8.69 والتقرير `docs/2026-10-23-CAPVER-ONE-PASS-RECOVERY-REPORT.ar.md`. `git diff --check` على ADR-023 خرج 0. الأقسام §8.60 حتى §8.68 بقيت كما هي.

## تصنيف التقرير

التصنيف **C — ملخص فقط**.

جدول `cghia_capability_cell_records` في الهجرة `2026_10_09_140000_create_cghia_capability_cell_records_table.php` لا يحتوي أعمدة `seat_id` أو `decision` أو `persisted_at` أو `provenance`، ولا مفاتيح أجنبية. الهوية هي `capability_record_id` الفريد. المفتاح الطبيعي لسجل `CURRENT` هو `adr_id` مع `canonical_identity_id` و`dimension_family` و`dimension_code` و`seat_override_applied`.

التقرير يثبت للمقاعد الأربعة المعرّف، والعائلة `ACCESS_METHOD`، ورمز البعد، وحالتي الأمر `PERSISTED` ثم `ALREADY_CURRENT`، وقراءة `VERIFIED` / `CURRENT`. لا يثبت `canonical_identity_id` ولا `seat_override_applied` ولا `evidence_freshness_class` ولا `snapshot_version` ولا `update_method` ولا `limitation_text` ولا `verified_at` ولا `observed_at` ولا `capability_evidence_refs` ولا `created_at` ولا `updated_at`.

ملفات JSON المحفوظة، ومنها `e2e-tmp/capver_v22.json`، تحمل قوائم أدلة مع `capability_persisted: false` و`STORE_UNAVAILABLE`. لا ملف في المستودع يحتوي `"capability_store_status": "PERSISTED"`.

## إعادة التوليد

الحكم: **`REGENERATION POSSIBLE BUT NOT EQUIVALENT`**.

`capability_record_id` نص ثابت داخل `CapVerSeatVerificationService::seatSpec()`، لا تجزئة ولا UUID. `verified_at` هو ساعة التنفيذ، و`created_at` و`updated_at` يولّدهما التخزين عند الإدراج. الدفعة الحالية تشمل `G5-04`، بينما تقرير 2026-10-22 استبعده. ملف الخدمة غير متتبع، لذلك لا يثبت سجل Git أن مواصفات المقاعد الأربعة لم تتغير منذ الكتابة الناجحة. لا مفتاح أجنبي يشير إلى المعرّفات القديمة. لم يُشغَّل أمر القبول.

## PostgreSQL والنسخ

لا خدمة Windows باسم PostgreSQL، ولا مستمع على المنفذ 5432، ولا عملية `postgres`. `pg_ctl` غير موجود على `PATH`، و`C:\Program Files\PostgreSQL\17\bin\postgres.exe` غير موجود. دليل البيانات هناك إصداره 17، بلا `postmaster.pid`، وسجله الوحيد بتاريخ 2026-10-19 يقول إن الدور `wsa` غير موجود. لم تُشغَّل هذه النسخة: الثنائيات غائبة، والدور غير موجود، والكاتب المذكور في التقرير جلسة PostgreSQL 16. لم تُغيَّر ملفات المصادقة ولا الجدار الناري.

الإعداد غير السري: `DB_CONNECTION=pgsql`، المضيف `127.0.0.1`، المنفذ `5432`، القاعدة `wsa_enterprise`، المستخدم `wsa`. كلمة المرور لم تُطبع. المنفذ لا يستمع، فلم تُفتح جلسة.

النسخة الوحيدة المطابقة هي `G:\WSA-Enterprise\backups\wsa_pre_migrate_20261019_215350.dump` بحجم 4 600 454 بايت ووقت `2026-10-19T18:54:04Z`. قائمة TOC بلا `cghia_capability_cell_records`. `backend\storage\app` فيه ملف المتجر العلمي فقط. مسارات `WSA-Enterprise-archive-reconcile\backups` و`WSA-Enterprise-push-isolated\backups` وسطح المكتب غير موجودة. `New folder (3)` والتنزيلات والمستندات بلا تفريغ CapVer.

## أثر الاستعادة

لم يُكتب ملف استعادة JSON، لأن الحقول الناقصة لا تُخترع. لا توجد تجزئة SHA-256 لأثر لم يُنشأ.

## المصفوفة

| المقعد | المثبت في التقرير | غير القابل للتحقق من صف مخزّن |
|---|---|---|
| `G1-02` | `cap-rec-r11-20261018-G1-02-oai_pmh`، `oai_pmh`، `PERSISTED` ثم `ALREADY_CURRENT`، `VERIFIED` `CURRENT` | باقي أعمدة العقد، ومنها الأدلة والطوابع الزمنية |
| `G6-20` | `cap-rec-r11-20261018-G6-20-api`، `api`، نفس نمطي الحالة | نفس الفجوة |
| `G1-01` | `cap-rec-r12-20261018-G1-01-bulk`، `bulk_download`، نفس نمطي الحالة | نفس الفجوة |
| `G1-17` | `cap-rec-r12-20261018-G1-17-static`، `static_download`، نفس نمطي الحالة | نفس الفجوة |

## الاختبار

على `backend/storage/framework/testing/migration_test.sqlite` فقط، عبر PHP `G:\tools\php-8.4.26-nts\php.exe`:

- رفع الهجرة: خروج 0، الاتصال `migration_test`، SQLite `3.53.4`
- القراءة المستقلة لتركيب اصطناعي: `CURRENT`، والمراجع `evidence://synthetic/migration`
- سجل `CURRENT` ثانٍ: `CapabilityInvariantViolation`
- تكرار `capability_record_id` وإدراج بلا الحقول المطلوبة: `QueryException`
- التراجع: خروج 0، والجدول بعده غائب
- `PRAGMA integrity_check`: `ok`

هذا دليل قاعدة اختبار معزولة. الهجرة بلا قيود `CHECK` وبلا مفاتيح أجنبية، والفهارس الفريدة الجزئية ظهرت كما هي مكتوبة. الملف يغطيه `backend/storage/framework/testing/.gitignore`. لا يحتوي خلايا تاريخية. لم يُنشأ متجر تشغيلي، لذلك لا توجد نتيجة نسخ أو استعادة تشغيلية.

المتجر العلمي لم يُفتح. طوله بقي `1393659904` وLastWriteTimeUtc بقي `2026-10-22T09:53:58.9832998Z`. لم يحدث commit ولا push ولا تبديل فرع.

الإجراء التالي: إما تصدير الصفوف الأربعة من دليل بيانات PostgreSQL الذي نفذ الكتابة في 2026-10-22، وإما تفويض صريح بإنشاء `capver.sqlite` فارغ ثم كتابة جديدة غير مكافئة زمنيًا تشمل `G5-04`.
