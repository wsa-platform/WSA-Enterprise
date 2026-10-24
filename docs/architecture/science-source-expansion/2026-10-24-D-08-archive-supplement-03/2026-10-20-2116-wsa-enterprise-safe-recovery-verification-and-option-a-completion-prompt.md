<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17401). Message timestamp as recorded in the conversation: Tuesday, Oct 20, 2026, 9:16 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17402, 17406, 17407; it is archived once. -->

---
# WSA-Enterprise — Safe Recovery Verification and Option A Completion
تابع مشروع `wsa-platform/WSA-Enterprise` من الحالة المسجلة في التقرير السابق.
## الحالة المعتمدة
- PostgreSQL: `wsa-enterprise-postgres-1`
- Database: `wsa_enterprise`
- Backend: `wsa-enterprise-backend-1`
- Application health URL: `http://127.0.0.1:8079/api/v1/health`
- سبع migrations طُبّقت سابقًا؛ migration history بلغ 62.
- نسخة ما قبل الترحيل موجودة في:
  `backups/wsa_pre_migrate_20261019_215350.dump`
- SQLite هو الخيار الذي اعتمده المالك للمخزن العلمي التشغيلي في هذه المرحلة، مع وجوب التحقق من الدور الفعلي في الكود.
- migration العلمية `2026_10_15_220000_create_scientific_source_record_store_tables.php` يجب أن تبقى غير مطبقة وغير متتبعة.
- لا Commit ولا Push.
## المرحلة الأولى — تحقق آمن من عودة Docker
1. افحص `docker info` و`docker ps -a`.
2. إذا كان Docker Engine غير متاح، توقف واطلب من المالك إعادة تشغيل Docker Desktop؛ لا تحاول إصلاح تثبيته أو حذف ملفاته تلقائيًا.
3. عند عودة المحرك، افحص أسماء الحاويات والـ Volumes الحالية قبل أي إجراء.
4. لا تنشئ حاويات بديلة ولا تحذف أو تعِد إنشاء أي Volume.
5. افحص حالة PostgreSQL وBackend وبقية الخدمات المطلوبة، مع حفظ المخرجات والأخطاء.
## المرحلة الثانية — تحقق من قاعدة البيانات دون تغييرها
1. تحقق من Health URL الحالي.
2. تحقق من اتصال Laravel بقاعدة `wsa_enterprise`.
3. تحقق من وجود جدول `cghia_capability_cell_records` والفهارس الأساسية.
4. اقرأ سجل migrations وتأكد من العدد الفعلي، المتوقع تاريخيًا 62.
5. لا تعِد تنفيذ migrations السبعة ولا تشغّل أوامر migrate أو rollback.
6. إذا ظهرت حالة مختلفة عن التقرير السابق، افحص السبب أولًا ولا تصلحها تلقائيًا.
## المرحلة الثالثة — استكمال الخيار A
1. افحص المسار الفعلي لقاعدة SQLite من الكود والإعدادات.
2. أنشئ نسخة احتياطية متسقة قبل أي فحص يمكنه الكتابة، مع التحقق من حجمها وSHA-256 وإمكانية فتحها.
3. افحص config cache قراءة فقط، دون حذفه أو تغيير الإعدادات.
4. تحقق من `PRAGMA integrity_check`.
5. حدّد نوع FTS وآلية مزامنته. لا تنفذ أي فحص FTS كتابي على قاعدة التشغيل؛ استخدم نسخة اختبارية إذا كان الفحص يتطلب كتابة.
6. تتبّع مسارات القراءة والكتابة الفعلية لتحديد دور SQLite.
7. شغّل الاختبارات العلمية المستهدفة الموجودة فعلًا فقط، مع التأكد من عدم اتصال اختبارات الكتابة بقاعدة التشغيل.
8. راجع ADR-023 وتحقق من عدم وجود تعارض مع قرار سابق.
9. وثّق الخيار A في القسم التالي الصحيح من ADR-023، دون تكرار الأقسام السابقة ودون ادعاءات غير مثبتة.
10. لا تطبق migration العلمية، ولا تنقل البيانات إلى PostgreSQL.
## حماية إلزامية
- لا `git reset`, `git clean`, `git restore`, أو `git stash`.
- لا حذف أو تعديل لملفات WIP دون ضرورة مثبتة.
- لا حذف للحاويات أو Volumes.
- لا تعديل لكود PHP إلا إذا ثبت عيب فعلي يستلزم إصلاحًا محدودًا.
- لا Commit ولا Push.
- افحص Git status قبل العمل وبعده.
## التقرير النهائي
قدّم تقريرًا عربيًا واحدًا يميز بين:
- حالة Docker الحالية.
- Health الحالي.
- سلامة اتصال PostgreSQL.
- سلامة CapVer وسجل migrations.
- النسخة الاحتياطية ونتائج فحص SQLite وFTS.
- الاختبارات المنفذة فعلًا.
- حالة توثيق الخيار A.
- الملفات التي تغيرت.
- حالة WIP.
- أي نقاط لم تُختبر أو ما زالت محجوبة.
لا تعلن نجاح أي فحص لم يُنفذ. إذا بقي Docker غير متاح، توقف عند بوابة الاستعادة ولا تحاول تنفيذ بقية الخطوات اعتمادًا على نتائج تاريخية.
