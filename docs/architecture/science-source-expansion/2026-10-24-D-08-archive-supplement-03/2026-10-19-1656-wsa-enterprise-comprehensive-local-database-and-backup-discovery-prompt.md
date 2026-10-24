<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17234). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 4:56 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17235; it is archived once. -->

---
# WSA-Enterprise — Comprehensive Local Database and Backup Discovery
افحص المشروع الموجود على:
`G:\WSA-Enterprise`
## الهدف
العثور على أي نسخة إضافية أو قاعدة بيانات محلية أو نسخة احتياطية قد تحتوي على قاعدة Laravel الرئيسية الأصلية، وليس فقط مخازن البحث العلمي.
لدينا أيضًا نسخة PostgreSQL مستقلة في:
`G:\New folder (3)\wsa-render-backup-extracted\2026-09-11T22_27Z\wsa_enterprise_db`
لكن المطلوب الآن هو البحث داخل مجلد المشروع نفسه عن أي نسخة أخرى قد تكون أحدث أو تحتوي على بيانات مختلفة.
## قواعد إلزامية
- فحص للقراءة فقط.
- لا تعديل أو حذف أو نقل أو إعادة تسمية لأي ملف.
- لا استعادة أو استيراد لقواعد البيانات.
- لا تشغيل migrations أو أوامر تغيّر البيانات.
- لا تشغيل Docker أو تعديل إعداداته.
- لا تعديل `.env`.
- لا Commit ولا Push.
- لا تعرض كلمات المرور أو الأسرار أو محتوى بيانات المستخدمين.
- لا تعتبر ملف SQLite مخزنًا رئيسيًا قبل التحقق من محتواه ومخططه.
## نطاق البحث
افحص جميع المجلدات الفرعية داخل `G:\WSA-Enterprise`، بما فيها المجلدات المخفية والمجلدات التي قد تحتوي على بيانات تشغيلية أو ملفات قديمة، مع تجنب المسارات الدائرية والملفات التي لا يمكن الوصول إليها.
ابحث عن:
1. قواعد بيانات SQLite: `.sqlite`, `.sqlite3`, `.db`.
2. نسخ SQL: `.sql`, `.sql.gz`.
3. نسخ PostgreSQL: `.dump`, `.backup`, ملفات Directory Format التي تحتوي على `toc.dat` بتوقيع `PGDMP`.
4. نسخ MySQL/MariaDB وSQL Server.
5. أرشيفات النسخ الاحتياطية: `.zip`, `.7z`, `.tar`, `.gz`, `.bak`.
6. أي مجلدات أو ملفات تحمل أسماء مثل `backup`, `backups`, `database`, `db`, `dump`, `snapshot`, `restore`, `wsa_enterprise`, أو أسماء مشابهة.
7. ملفات إعدادات أو سجلات تشير إلى مسارات قواعد بيانات قديمة أو نسخ احتياطية.
8. ملفات بيانات غير مألوفة الامتداد، إذا كان حجمها أو توقيعها يشير إلى أنها قاعدة بيانات.
## لكل ملف مرشح
سجّل:
- المسار الكامل.
- الحجم.
- تاريخ آخر تعديل.
- نوع الملف الفعلي.
- محرك قاعدة البيانات المحتمل.
- هل يحتوي على مخطط Laravel الرئيسي أم مخزن فرعي؟
- هل يمكن قراءة بنيته دون تعديل؟
- درجة الثقة في تصنيفه.
افحص قواعد SQLite بوضع القراءة فقط، واستخدم فهرسة الأرشيف دون استعادة ملفات PostgreSQL.
قارن مخططات الجداول بأسماء migrations الحالية، مع التمييز بين:
- قاعدة Laravel الرئيسية.
- Capability Store / CapVer.
- Scientific LocalIndex.
- Mission Artifacts Archive.
- قواعد الاختبارات.
## التقرير النهائي
أرسل تقريرًا عربيًا واحدًا يتضمن:
1. نطاق البحث الفعلي والمجلدات التي تعذر فحصها.
2. جميع قواعد البيانات والنسخ الاحتياطية المرشحة.
3. ترتيب المرشحين حسب احتمال احتوائهم على قاعدة Laravel الرئيسية.
4. مقارنة التواريخ والأحجام والمخططات.
5. تحديد ما إذا عُثر على نسخة محتملة أحدث من نسخة 11 سبتمبر 2026.
6. ما الذي ثبت وما الذي لم يثبت.
7. الخطوة التالية الآمنة الوحيدة المقترحة.
لا تستعد أي نسخة ولا تغيّر أي شيء. توقف بعد التقرير.
