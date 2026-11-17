# سلامة استئناف BY0 — Revision 10.27

**التصنيف: `RESUME_SAFETY_PROVEN`**

تم التحقيق بالقراءة فقط، من دون تشغيل ingestion أو فتح SQLite أو تنفيذ PRAGMA/integrity check أو بدء أي كاتب.

## الخلاصة

- لا توجد عملية `scientific:ingest-agris-projected` نشطة؛ أظهر `docker top` عمليات `php-fpm` فقط في فحصين.
- يحتوي checkpoint على **123** حزمة مكتملة، وBY0 جزئية عند:
  - `skip_records = 5000`
  - `chunks = 5`
  - `generation = 1723`
  - `last_package = AGRIS.ODS.BY0`
  - `updated_at = 2026-11-17T15:07:43+00:00`
- معنى `skip=5000` الدقيق: أول 5000 سطر سجل في BY0 تم تثبيتها عبر خمس معاملات ناجحة، وليس مجرد معالجتها في الذاكرة.
- السبب: تحديث `partial.skip_records` وإخراج `chunk_progress` يحدثان في callback لا يُستدعى إلا بعد عودة `commit` بنجاح.
- تسلسل الأجيال متسق: MY0 انتهت عند 1718، ثم خمس دفعات BY0 رفعت الجيل إلى 1723. فشل HY000 وقع في محاولة الدفعة التالية.
- منع التكرار مثبت بواسطة القيد الفريد و`upsert` على `(source_key, original_identifier)`.
- منع فقد السجلات عند الاستئناف من 5000 مثبت منطقياً: checkpoint لا يتقدم قبل نجاح commit، والمعاملة الفاشلة لا تنشر جزءاً من الدفعة.

## ملاحظة سلامة

هذا إثبات لسلامة منطق الاستئناف وحدود المعاملة، وليس شهادة سلامة مادية لملف SQLite؛ لم يُنفذ أي فحص integrity التزاماً بتوجيه المالك. لذلك يبقى التنفيذ مشروطاً بمراجعة/تفويض المالك.

لوحظ ملف SQLite بحجم `7613120512` بايت. ظهر WAL صفري وSHM بحجم 32768 بايت في الرصد الأول ثم اختفيا في الرصد الثاني؛ هذه ملاحظة ملفات فقط وليست نتيجة integrity.

## الأمر المقترح للمراجعة فقط

لم يُنفذ الأمر التالي:

```sh
docker exec wsa-enterprise-backend-1 sh -lc 'cd /var/www/html && PHP_INI_SCAN_DIR="" php -d output_buffering=Off artisan scientific:ingest-agris-projected --manifest=/var/www/html/storage/app/scientific_datasets/agris/harvest_manifest.v10.19.local-only.json --checkpoint=/var/www/html/storage/app/scientific_datasets/agris/harvest_cache/agris_projected_ingest_checkpoint.v10.21.json --fts-checkpoint=/var/www/html/storage/app/scientific_datasets/agris/harvest_cache/agris_fts_checkpoint.v10.23.json --chunk-size=1000 --max-packages=1'
```

يستأنف الأمر BY0 فقط من checkpoint، ولا يفعّل البحث. يجب إعادة التحقق من غياب كاتب ingest مباشرة قبل أي تنفيذ مصرح به.
