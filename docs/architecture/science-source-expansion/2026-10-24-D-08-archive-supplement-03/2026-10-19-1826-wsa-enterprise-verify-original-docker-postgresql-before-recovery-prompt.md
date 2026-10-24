<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17287). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 6:26 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17288; it is archived once. -->

---
# WSA-Enterprise — Verify Original Docker PostgreSQL Before Recovery
## الهدف
التحقق من سلامة قاعدة Laravel التاريخية داخل `wsa-enterprise_postgres_data` وتحديد طريقة آمنة لإعادة اتصال Laravel بها.
## الحقائق المثبتة من الفحص السابق
- الحاوية الأصلية المرجّحة: `wsa-enterprise-postgres-1`.
- الصورة: `postgres:16-alpine`.
- قاعدة البيانات: `wsa_enterprise`.
- المستخدم: `wsa`.
- Volume: `wsa-enterprise_postgres_data`.
- الحاوية متوقفة حاليًا.
- خدمة PostgreSQL 17 على Windows تشغل المنفذ `5432`.
- Laravel يتصل حاليًا بـ `127.0.0.1:5432`، ولذلك يصل إلى PostgreSQL على Windows بدل حاوية المشروع المتوقفة.
- محتويات قاعدة البيانات التاريخية لم تُفحص بعد.
## المرحلة الأولى — لا تغييرات
1. تحقق مجددًا من هوية الحاوية وVolume وملف Compose.
2. افحص إعدادات Compose كاملة لتحديد الخدمات التابعة لـ `postgres` ومتطلبات التشغيل، دون طباعة الأسرار.
3. افحص سجلات PostgreSQL السابقة المتاحة للعثور على آخر تشغيل نظيف وأي أخطاء.
4. تحقق من وجود نسخة احتياطية قابلة للقراءة قبل أي إجراء قد يتطلب تشغيل القاعدة، إن أمكن دون تعديلها.
5. لا تشغّل الحاوية بعد. لا تغيّر المنفذ 5432، ولا توقف خدمة Windows.
## المرحلة الثانية — خطة التحقق
قدّم خطة دقيقة لفحص القاعدة الأصلية باستخدام PostgreSQL 16، بحيث:
- لا يتم حذف أو تهيئة Volume.
- لا تُستخدم `docker compose down` أو أي أمر قد يعيد إنشاء الخدمات.
- لا يتم تشغيل جميع خدمات المشروع.
- لا تُنفذ migrations أو عمليات كتابة.
- يتم تحديد طريقة الوصول إلى PostgreSQL دون تعارض مع المنفذ 5432 المستخدم حاليًا على Windows.
إذا كان تشغيل الحاوية ضروريًا، فتوقف قبل تشغيلها وقدّم الأمر المقترح، وتأثيره، وكيفية تجنب أي كتابة غير مقصودة، وانتظر موافقتي الصريحة.
## التقرير المطلوب
أصدر تقريرًا عربيًا واحدًا يوضح:
1. ما الأدلة المتاحة على سلامة Volume.
2. هل توجد نسخة احتياطية يمكن استخدامها للتحقق دون استعادة.
3. ما الخطوات اللازمة لتشغيل PostgreSQL الأصلي وحده عند الموافقة.
4. كيف سيتم فحص وجود `migrations` و`cghia_capability_cell_records`.
5. كيف سيُعاد ربط Laravel بالقاعدة الأصلية بعد التحقق.
لا تنفذ أي إصلاح أو تشغيل أو تعديل قبل الموافقة.
