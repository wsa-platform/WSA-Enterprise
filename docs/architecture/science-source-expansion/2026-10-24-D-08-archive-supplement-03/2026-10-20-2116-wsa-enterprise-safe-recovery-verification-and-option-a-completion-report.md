<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17458). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
الخيار A موثّق بعد تحقق تشغيلي. المخزن العلمي الحالي هو ملف SQLite، وقاعدة PostgreSQL لم تُمس بترحيل جديد.

## 1. حالة Docker — `VERIFIED`

المحرك عاد: Docker 29.6.2، عشر حاويات، سبع تعمل وثلاث متوقفة. لم تُنشأ أو تُحذف أي حاوية أو Volume.

عند الإقلاع كان PostgreSQL وNginx في حالة `unhealthy` لأن PostgreSQL كان ينفّذ استعادة تلقائية بعد إيقاف غير نظيف، ثم سجّل `database system is ready to accept connections`. بعد ذلك أصبحت الحاويات السبع `healthy`. الحاويات المتوقفة بقيت كما هي: `wsa-r7-store-pg-20261015` و`wsa-backup-verification-pg` و`wsa-enterprise-postgres-1-orphan`. الحجم `wsa-enterprise_postgres_data` ما زال مركّبًا على `wsa-enterprise-postgres-1`.

## 2. Health — `VERIFIED`

أثناء إقلاع PostgreSQL أعاد `http://127.0.0.1:8079/api/v1/health` الخطأ HTTP 500. بعد اكتمال الاستعادة أعاد HTTP 200 والجسم `{"status":"ok"}`. أعيد الفحص في نهاية العمل وكانت النتيجة نفسها.

## 3. اتصال PostgreSQL — `VERIFIED`

Laravel يتصل بـ `wsa_enterprise` عبر `pgsql` والمضيف `postgres` والمستخدم `wsa`. جلسة PostgreSQL نفسها: القاعدة `wsa_enterprise`، المستخدم `wsa`، الإصدار 16.14. ذاكرة التكوين المؤقتة غير موجودة في الحاوية ولا في `backend/bootstrap/cache/config.php`. لم يُنفَّذ `migrate` ولا `rollback`.

## 4. CapVer وسجل migrations — `CONFIRMED`

جدول `cghia_capability_cell_records` موجود. الفهارس الحاضرة بالاسم:

- `cghia_capability_cell_records_pkey`
- `cghia_capability_cell_records_capability_record_id_unique`
- `cghia_cap_cells_subject_lifecycle_idx`
- `cghia_cap_cells_current_canonical_uq`
- `cghia_cap_cells_current_seat_scoped_uq`

عدد صفوف `migrations` هو 62. لا توجد جداول `scientific_source_*` في PostgreSQL. نص شروط الفهارس الجزئية لم يُعاد قراءته في هذه الجلسة.

## 5. النسخة الاحتياطية وSQLite وFTS — `VERIFIED`

المسار الفعلي من الكود والحاوية هو `storage/app/scientific_record_store.sqlite`. لا توجد متغيرات `SCIENTIFIC_RECORD_STORE_*` في `.env`، لذلك الاتصال `scientific_records` يبقى SQLite.

النسخة المتسقة أُخذت بـ `SQLite3::backup()` من فتح للقراءة فقط:

- المسار: `backups/scientific_record_store_pre_optionA_20261020_213410.sqlite`
- الحجم: 1 354 821 632 بايت
- SHA-256: `A7FEB1170BA77497BF18173441E0212F6DF0A7397856C7740BC9F94756EEBBC9`

`PRAGMA integrity_check` أعاد `ok` على قاعدة التشغيل وعلى النسخة. SQLite 3.46.1، و`journal_mode=delete`.

الفهرس جدول FTS5 افتراضي بترميز `unicode61`. لا توجد triggers. المزامنة تتم من التطبيق عبر `SqliteFtsScientificIndex::rebuild` و`synchronize` و`apply`. أمر `integrity-check` الكتابي نُفّذ على النسخة فقط وأعاد صفر صفوف، أي لا أخطاء. SHA-256 بعد هذا الأمر لم يتغير.

الأعداد على قاعدة التشغيل: 300 635 سجلًا، منها 300 121 نشطًا و514 غير نشطة. صفوف FTS تساوي السجلات النشطة: 300 121. الأجيال متطابقة بين الحالة والفهرس: `organic_eprints=2` و`dr_duke=19` و`agris=29`. وقت تعديل ملف التشغيل بقي 2026-10-19 20:14:04.

## 6. الاختبارات — `TESTED` مع استثناء واحد

الاختبارات التي لا تكتب في ملف التشغيل نجحت: `LocalIndexLifecycleTest` و`ScientificRecordsSqliteDurabilityTest` و`IncrementalBulkIngestDoesNotWithdrawPeersTest`، والنتيجة 16 اختبارًا و115 تأكيدًا وخروج 0.

تشغيل المجلد كاملًا أعطى 21 اختبارًا وخطأ واحدًا وخروج 2. الاختبار `LocalIndexSearchIntegrationTest::test_a_local_index_source_runs_through_the_production_orchestrator_without_http` حاول حذف صفوف `dataset_example` من FTS التشغيلي لأن محرك البحث في الحاوية مربوط مسبقًا بملف التشغيل. SQLite رفض العملية بـ `database is locked` بسبب فحص السلامة. الحذف لم يُلتزم، وحجم الملف ووقت تعديله لم يتغيرا، وعدد صفوف `dataset_example` هو صفر. لم أعد تشغيل هذا الاختبار.

مجموعة `tests/Pgsql` بقيت `NOT TESTED` حتى لا تمس `wsa_enterprise`.

## 7. توثيق الخيار A — `IMPLEMENTED`

أُلحق القسم `8.62` في `docs/architecture/ADR-023-scientific-source-expansion.md` دون تعديل الأقسام السابقة. القرار التشغيلي لهذه المرحلة هو SQLite. القسم `8.41` يبقى سجل التصميم السابق لـ PostgreSQL ولم يُعد كتابته. migration العلمية لم تُطبَّق على PostgreSQL ولم تُنقل أي بيانات إليه، والملف ما زال `??`.

## 8. الملفات التي تغيرت

في هذه الجلسة تغيّر `docs/architecture/ADR-023-scientific-source-expansion.md` فقط بإلحاق القسم 8.62. لم يُعدَّل كود PHP. ملف `.gitignore` كان معدّلًا قبل هذه الجلسة ولم ألمسه.

## 9. حالة WIP

قبل العمل وبعده: 151 مسارًا في `git status --short`. الملف العلمي غير المتتبع ما زال:

`?? backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php`

لا commit ولا push.

## 10. ما لم يُختبر أو بقي مفتوحًا

- مجموعة اختبارات PostgreSQL العلمية: `NOT TESTED`.
- إعادة قراءة نصوص شروط فهارس CapVer الجزئية: لم تُنفَّذ في هذه الجلسة.
- الاختبار الذي يمر عبر المنسق الإنتاجي يحل محرك FTS التشغيلي. فشله هنا كان بسبب القفل، وليس دليلًا على أن إعادة تشغيله آمنة.
- تصميم PostgreSQL في القسم 8.41 لم يُلغَ تاريخيًا؛ قرار هذه المرحلة فقط يبقي SQLite مخزن التشغيل ولا يفعّل migration الموازية.
