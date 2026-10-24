<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17252). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 5:29 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17253; it is archived once. -->

---
نفّذ الآن اختبار اتصال PostgreSQL المحلي لمشروع WSA-Enterprise بعد تصحيح اسم المتغير إلى `DB_PASSWORD` في `backend/.env`.
**المطلوب:**
1. تحقّق من أن Laravel يقرأ إعدادات الاتصال الصحيحة من `backend/.env`.
2. افحص الاتصال باستخدام الإعدادات الحالية: `pgsql`، والمضيف `127.0.0.1`، والمنفذ `5432`، وقاعدة البيانات `wsa_enterprise`، والمستخدم `wsa`.
3. إذا كان Laravel يستخدم إعدادات مخزنة مؤقتًا، افحص ذلك أولًا، ولا تمسح ذاكرة الإعدادات أو تغيّرها دون توضيح الحاجة.
4. نفّذ اختبار اتصال للقراءة فقط، دون تشغيل migrations أو إنشاء قاعدة بيانات أو مستخدم أو تعديل أي بيانات.
5. لا تطبع كلمة المرور أو أي أسرار في التقرير أو سجل الأوامر.
6. أبلغني بالنتيجة الفعلية، ونص الخطأ إن فشل الاتصال بعد حجب أي بيانات حساسة.
**قيود إلزامية:** لا تعدّل ملفات المشروع، ولا تنفّذ Commit أو Push، ولا تستخدم Docker، ولا تغيّر إعدادات قاعدة البيانات أثناء هذا الاختبار.
