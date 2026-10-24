<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17295). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 6:33 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17296; it is archived once. -->

---
# WSA-Enterprise — Restore the October 15, 2026 Operating State
## الهدف
استعادة الوضع التشغيلي التاريخي لمشروع WSA-Enterprise كما كان في 2026-10-15 قبل إيقاف Docker، باستخدام الحاويات والإعدادات والبيانات الموجودة، دون استبدال قاعدة البيانات الأصلية أو فقد بياناتها.
المشروع: `G:\WSA-Enterprise`
## الأدلة المعتمدة
- الحاوية الأصلية: `wsa-enterprise-postgres-1`
- الصورة: `postgres:16-alpine` (16.14)
- قاعدة البيانات: `wsa_enterprise`
- المستخدم: `wsa`
- Volume الأصلي: `wsa-enterprise_postgres_data`
- آخر إيقاف مسجل: `2026-10-15T18:43:20Z`
- خدمة PostgreSQL 17 على Windows تشغل المنفذ 5432 حاليًا.
- الحاوية القديمة `wsa-enterprise-postgres-1-orphan` تشير إلى الـ Volume نفسه.
- خدمات Compose المعتمدة تشمل `postgres` و`backend` و`queue` و`scheduler`، مع خدمات أخرى حسب الملف الحالي.
- لم يتم حتى الآن التحقق من محتوى قاعدة البيانات الفعلي بعد الإيقاف.
## المرحلة 1 — إعادة بناء الحالة التاريخية
1. افحص `docker-compose.yml` الحالي وسجل Git وسجلات Docker المتاحة.
2. حدّد الإعدادات الفعلية في 15 أكتوبر: الصور، الخدمات، المنافذ، الشبكات، الـ Volumes، وسياسة restart.
3. حدّد أي اختلافات بين التكوين التاريخي والتكوين الحالي.
4. افحص سجلات الحاويات المتوقفة، بما فيها رسائل الإقلاع والإيقاف.
5. تحقّق من أن الـ Volume الأصلي غير مستخدم حاليًا بواسطة أي عملية أو حاوية أخرى.
6. لا تستنتج أن كل البيانات سليمة لمجرد أن الإيقاف كان نظيفًا.
## المرحلة 2 — خطة الاستعادة
قدّم خطة مرتبة لاستعادة الخدمات الأصلية باستخدام الـ Volume الحالي، دون إنشاء قاعدة بديلة أو تنفيذ restore.
يجب أن توضح الخطة:
- هل يمكن إعادة تشغيل الحاوية الأصلية دون إعادة إنشائها؟
- هل يتطلب ذلك تحرير المنفذ 5432؟
- ما تأثير إيقاف خدمة PostgreSQL 17 على Windows؟
- هل يمكن استخدام منفذ بديل مؤقتًا دون تغيير بيانات المشروع؟
- ما ترتيب تشغيل الخدمات والتحقق منها؟
- كيف نتجنب تشغيل الحاويتين اللتين تستخدمان الـ Volume نفسه معًا؟
## المرحلة 3 — شروط التوقف
لا تنفذ أي تغيير الآن. لا تشغّل الحاويات ولا توقف الخدمات ولا تعدّل أي ملف.
لا تستخدم `docker compose down` أو `docker compose up` أو `docker run` أو `docker start` في هذه المرحلة.
لا تنفذ migrations أو restore أو عمليات كتابة على قاعدة البيانات.
لا تغيّر `.env` أو كلمات المرور أو إعدادات المصادقة.
لا تحذف الحاوية اليتيمة أو أي Volume.
لا تنفذ Commit أو Push.
## التقرير المطلوب
أصدر تقريرًا عربيًا واحدًا يتضمن:
1. الحالة التاريخية المثبتة بتاريخ 15 أكتوبر.
2. الاختلافات عن الحالة الحالية.
3. خطة استعادة آمنة خطوة بخطوة.
4. المخاطر والتبعيات.
5. الأوامر الدقيقة المقترحة، مع شرح أثر كل أمر.
6. تحديد الإجراءات التي تتطلب موافقة صريحة، خصوصًا إيقاف PostgreSQL على Windows أو تشغيل حاويات المشروع.
توقف بعد تقديم التقرير وانتظر موافقة صاحب المشروع قبل التنفيذ.
