<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17219). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 4:25 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17220; it is archived once. -->

---
افحص النسخة الاحتياطية القديمة لقاعدة بيانات مشروع WSA-Enterprise، المعروفة باسم `wsa_enterprise_db`.
المطلوب فحص واستكشاف فقط، دون تعديل أي شيء:
1. ابحث عن ملف أو مجلد باسم `wsa_enterprise_db` داخل مجلد المشروع والمسارات المرتبطة به في إعداداته ووثائقه.
2. إذا لم تجده داخل المشروع، اطلب مني تحديد مكانه بدل إجراء بحث واسع غير ضروري على الجهاز.
3. حدد الاسم الكامل والامتداد الفعلي والحجم وتاريخ آخر تعديل ونوع الملف.
4. ميّز هل هو:
   - PostgreSQL custom dump (`.backup` أو `.dump`)
   - SQL text dump (`.sql`)
   - TAR archive (`.tar`)
   - ZIP أو 7z
   - مجلد بيانات PostgreSQL
   - أو نوع آخر غير معروف بعد.
5. إذا كان ملفًا، افحص ترويسة الملف أو بياناته الوصفية بطريقة قراءة فقط لتحديد نوعه، دون طباعة محتويات حساسة أو استخراج البيانات.
6. إذا كان نسخة PostgreSQL، حدد إصدار المصدر إن أمكن إثباته.
7. لا تنفذ `pg_restore` أو `psql -f` أو أي استعادة.
8. لا تنشئ قواعد بيانات أو مستخدمين، ولا تشغل migrations، ولا تعدّل الملفات أو الإعدادات، ولا تستخدم Docker.
9. لا تعرض كلمات مرور أو أسرارًا.
أعد تقريرًا عربيًا مختصرًا يتضمن المسار الكامل، الامتداد، الحجم، النوع المثبت، وما إذا كانت النسخة قابلة للفحص بأمان. توقف بعد التقرير.
