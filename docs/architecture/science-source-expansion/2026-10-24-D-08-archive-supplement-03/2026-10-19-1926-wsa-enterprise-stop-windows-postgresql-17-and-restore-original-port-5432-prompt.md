<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17301). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 7:26 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17302; it is archived once. -->

---
# WSA-Enterprise — Stop Windows PostgreSQL 17 and Restore Original Port 5432
أوافق صراحة على إيقاف خدمة PostgreSQL 17 في Windows، المسماة `postgresql-x64-17`، لتحرير المنفذ TCP 5432 وإعادته إلى الاستخدام الأصلي لمشروع WSA-Enterprise.
نفّذ المهمة بالترتيب التالي:
1. تحقق من حالة خدمة Windows `postgresql-x64-17` ومن العملية المالكة للمنفذ TCP 5432.
2. أوقف خدمة `postgresql-x64-17` فقط. لا تحذف الخدمة ولا تغيّر نوع بدء تشغيلها.
3. تحقق من توقف الخدمة وتحرير المنفذ 5432.
4. شغّل حاوية PostgreSQL الأصلية الموجودة `wsa-enterprise-postgres-1` باستخدام `docker start` فقط.
5. تحقق من أن الحاوية تعمل، وأنها تستخدم Volume الأصلي `wsa-enterprise_postgres_data`، وأن المنفذ `5432:5432` مرتبط بها.
6. تحقق من جاهزية PostgreSQL ومن الاتصال بقاعدة `wsa_enterprise` باستخدام المستخدم `wsa`.
7. نفّذ فحوص قراءة فقط للتأكد من وجود جدول `migrations`، واعرض أي أخطاء فعلية في سجلات PostgreSQL.
8. عند النجاح، أكد أن خدمة Windows PostgreSQL 17 متوقفة وأن PostgreSQL الخاصة بالمشروع تعمل على المنفذ 5432.
**قيود إلزامية:**
- لا توقف أي خدمة Windows أخرى.
- لا تشغّل الحاوية اليتيمة `wsa-enterprise-postgres-1-orphan`.
- لا تستخدم `docker compose up/down` أو `docker run` أو أي أمر يعيد إنشاء الحاويات.
- لا تحذف أو تعدّل أي Volume أو قاعدة بيانات.
- لا تنفّذ migrations أو restore.
- لا تعدّل `.env` أو ملفات المشروع.
- لا تشغّل Redis أو Backend أو بقية الخدمات في هذه المهمة.
- لا تنفّذ Git commit أو push.
- إذا فشلت أي خطوة، توقف واعرض الخطأ الفعلي دون إجراء إصلاحات تلقائية.
نفّذ المهمة فعليًا، ولا تكتفِ بعرض خطة أو أوامر مقترحة. أبلغني بالنتيجة الفعلية لكل خطوة، ولا تدّعِ النجاح قبل التحقق منه.
