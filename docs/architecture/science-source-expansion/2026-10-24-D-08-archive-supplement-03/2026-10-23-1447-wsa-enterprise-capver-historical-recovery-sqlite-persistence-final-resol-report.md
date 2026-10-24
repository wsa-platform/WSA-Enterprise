<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 18090). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## الحالة النهائية

`RESOLVED — HISTORICAL DATA RECOVERED AND PERSISTENCE VERIFIED`

استُعيدت الصفوف الأربعة من PostgreSQL 16.14 واستُوردت إلى `backend/storage/app/capver.sqlite` مع الإبقاء على المعرّفات والأدلة والطوابع الزمنية. القراءة المستقلة بعد أمر القبول طابقت تجزئات الأثر.

## الفرع وشجرة العمل

الفرع `phase-18-m18-ai-marketing-communications`، وHEAD هو `fb60b60746ed487e2bfa04e3d335827d91cda446`. الفهرس كان فارغًا وبقي فارغًا. التعديلات السابقة في شجرة العمل بقيت. لم يحدث `git add` ولا commit ولا push ولا تبديل فرع.

ملف المتجر العلمي بقي طوله `1393659904` وLastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`.

## Docker والمصدر

محرك Docker 29.6.2، وأوامر الفحص خرجت 0. الحاوية `wsa-enterprise-postgres-1` كانت تعمل أصلًا منذ `2026-10-23T11:42:54Z`، الصورة `postgres:16-alpine`، وإصدار الخادم 16.14. حجم البيانات الوحيد هو `wsa-enterprise_postgres_data` على `/var/lib/postgresql/data`. القاعدة `wsa_enterprise` والمستخدم `wsa`.

`wsa-enterprise-backend-1` و`wsa-enterprise-redis-1` كانا يعملان أيضًا، لذلك لم تُوقف PostgreSQL بعد القراءة.

القراءة كانت `BEGIN READ ONLY` مع `transaction_read_only=on`. لم تُنفَّذ كتابة SQL ولا DDL ولا ترحيل على هذه القاعدة. الجدول `public.cghia_capability_cell_records` احتوى أربعة صفوف.

## السجلات الأربعة

وُجدت الصفوف الأربعة ونُقلت كاملة. المعرّف التخزيني `id=1` غير موجود؛ الصفوف المستعادة أرقامها 2 و3 و4 و5. `G5-04` غير موجود في الجدول التاريخي.

| المقعد | المعرّف | id | البعد | verified_at | الحالة |
|---|---|---|---|---|---|
| `G1-02` | `cap-rec-r11-20261018-G1-02-oai_pmh` | 2 | `ACCESS_METHOD` / `oai_pmh` | `2026-10-22T06:48:42+00:00` | `VERIFIED` / `CURRENT` |
| `G6-20` | `cap-rec-r11-20261018-G6-20-api` | 3 | `ACCESS_METHOD` / `api` | `2026-10-22T06:48:44+00:00` | `VERIFIED` / `CURRENT` |
| `G1-01` | `cap-rec-r12-20261018-G1-01-bulk` | 4 | `ACCESS_METHOD` / `bulk_download` | `2026-10-22T06:48:44+00:00` | `VERIFIED` / `CURRENT` |
| `G1-17` | `cap-rec-r12-20261018-G1-17-static` | 5 | `ACCESS_METHOD` / `static_download` | `2026-10-22T06:48:44+00:00` | `VERIFIED` / `CURRENT` |

المشترك: `canonical_identity_id` و`limitation_text` فارغان، و`seat_override_applied=false`، و`evidence_freshness_class=CURRENT`، و`update_method=capver_authorized_evidence_review`، و`created_at`/`updated_at` = `2026-10-22T06:48:44`. قوائم الأدلة و`snapshot_version` و`observed_at` محفوظة في الأثر وفي الصف المستورد.

حكم إعادة التوليد: `REGENERATION POSSIBLE BUT NOT EQUIVALENT`. المعرّف نص ثابت داخل `seatSpec()`، و`verified_at` ساعة التنفيذ، والأمر الكامل يضيف `G5-04`. الاستيراد استخدم الصفوف المقروءة، لا تنفيذًا جديدًا.

## أثر الاستعادة والمتجر

الأثر: `backend/storage/framework/recovery/capver-historical-recovery.json`، وفيه 4 سجلات. SHA-256 للملف: `e69ea920ced083f72aa1741aa78e162c0f0590dc1760553d65b98e765c3b78c8`. هذه التجزئة تثبت سلامة الملف. التحقق من المصدر هو جلسة القراءة فقط. تجزئة كل صف موجودة داخل الأثر.

المتجر: `backend/storage/app/capver.sqlite` عبر الاتصال `capver`. النموذج `CghiaCapabilityCell` ومعاملات المستودع يستخدمان `capability_store.connection`، وقيمته التنفيذية `capver`. الاتصال الافتراضي للتطبيق بقي `pgsql`. PHPUnit يجبر اختباراته على `sqlite` و`:memory:` حتى لا تُفتح القاعدة التشغيلية. الهجرة ترفض مسار المتجر العلمي وقاعدة PostgreSQL `wsa_enterprise`. العقد نفسه لم يتغير.

## الاختبارات والتحقق

- PHPUnit لملف الاختبار المعزول وثلاثة اختبارات مخطط: `OK (5 tests, 67 assertions)`، خروج 0، الزمن `00:09.201`. قاعدة تحت `storage/framework/testing`، لا الملف التشغيلي.
- `CapVerSeatVerificationServiceTest`: `OK (5 tests, 48 assertions)`، خروج 0، الزمن `00:04.258`.
- الترحيل التشغيلي خروج 0. الاستيراد `inserted=4 already_present=0`، ثم الإعادة `inserted=0 already_present=4`.
- `scientific:capver-accepted` لكل مقعد من الأربعة على حدة: `ALREADY_CURRENT` وخروج 0. الدفعة الكاملة لم تُشغَّل، فلم يُدرج `G5-04`.
- قراءة PDO بعد ذلك: `COUNT=4` و`MATCH=YES` و`PRAGMA integrity_check=ok`.
- النسخ `backend/storage/app/capver.sqlite.backup` سليم، واستعادته إلى ملف جديد تحت `storage/framework/testing` طابقت التجزئات الأربع. الملف التشغيلي لم يُستبدل.

أُلحق ADR-023 §8.70. الأقسام §§8.60–8.69 بقيت. التقرير محفوظ في `docs/2026-10-23-CAPVER-HISTORICAL-RECOVERY-REPORT.ar.md`.

حاوية PostgreSQL تُركت تعمل لأن الواجهة وRedis كانا يعملان معها. تشغيل الدفعة الكاملة لأمر القبول سيضيف `G5-04` كصف جديد خارج السجلات التاريخية.
