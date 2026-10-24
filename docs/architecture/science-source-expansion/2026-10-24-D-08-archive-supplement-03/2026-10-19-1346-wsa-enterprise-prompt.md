<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17179). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 1:46 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17180; it is archived once. -->

---
نواصل مشروع WSA-Enterprise من نقطة التوقف الحالية.

تأكيد البيئة:
- تم تثبيت PostgreSQL 17.11 على Windows.
- نجح الاتصال عبر SQL Shell (psql).
- current_database() = postgres
- current_user = postgres
- inet_server_port() = 5432
- قواعد البيانات الموجودة حاليًا هي: postgres وtemplate0 وtemplate1.
- لم يُثبت بعد اتصال التطبيق بقاعدة البيانات، ولم يُنفذ اختبار CapVer بكتابة فعلية وقراءة مستقلة.

المطلوب الآن: فحص قراءة فقط داخل المستودع G:\WSA-Enterprise لتحديد إعدادات PostgreSQL المطلوبة فعليًا قبل أي تعديل.

افحص:
1. حالة Git الحالية والفرع وHEAD دون تغيير أي شيء.
2. إعدادات DB_CONNECTION وDB_HOST وDB_PORT وDB_DATABASE في ملفات الإعدادات ذات الصلة، مع عدم طباعة أي أسرار أو كلمات مرور.
3. إعدادات Laravel الفعلية ومصدر إعداد الاتصال، دون تشغيل migrations.
4. أوامر CapVer المتاحة، ومسار اختبار الكتابة الفعلية والقراءة المستقلة، وأي متطلبات سابقة للتشغيل.
5. هل يعتمد الإعداد الحالي على اسم مضيف Docker مثل postgres، وهل توجد تعليمات موثقة لتشغيل PostgreSQL محليًا على Windows.
6. أي قواعد بيانات أو schemas أو migrations يتوقعها المشروع، دون إنشاء أو حذف أو تعديل أي منها.

القيود:
- لا تعدّل أي ملف.
- لا تنشئ قاعدة بيانات ولا مستخدمًا ولا schema.
- لا تشغّل migrations أو seeders أو اختبارات تكتب في قاعدة البيانات.
- لا تستخدم SQLite لإثبات جاهزية CapVer الإنتاجية.
- لا تستخدم Docker.
- لا تنفذ commit أو push.
- لا تعرض أسرار .env أو بيانات اعتماد.
- لا تنفذ أي عملية تدميرية.

قدّم تقريرًا عربيًا واحدًا يذكر المسارات والأدلة الفعلية، وما هو مؤكد وما يزال غير متحقق، ثم اقترح الخطوة التالية دون تنفيذها.
