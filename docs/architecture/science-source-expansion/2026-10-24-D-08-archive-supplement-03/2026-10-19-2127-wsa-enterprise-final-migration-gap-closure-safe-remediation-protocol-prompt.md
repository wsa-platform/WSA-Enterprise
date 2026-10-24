<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17371). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 9:27 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17372; it is archived once. -->

---
# WSA-Enterprise — Final Migration Gap Closure & Safe Remediation Protocol
## Comprehensive Forensic Audit, CapVer Recovery, Database Integrity, Scientific Persistence, Backup Verification, and ADR Closure
**Repository:** `wsa-platform/WSA-Enterprise`  
**Project path:** `G:\WSA-Enterprise`  
**Application URL:** `http://127.0.0.1:8079/`  
**Expected primary database:** `wsa_enterprise`  
**Expected PostgreSQL container:** `wsa-enterprise-postgres-1`  
**Expected PostgreSQL volume:** `wsa-enterprise_postgres_data`  
**Architectural authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`
---
# 0. المهمة والتفويض
تصرف كمهندس برمجيات أول ومهندس معماري وخبير Laravel وPostgreSQL وSQLite، مسؤول عن سلامة البيانات وصحة التغييرات المعمارية.
المطلوب هو اكتشاف جميع الفجوات ذات الصلة، وتحديد أسبابها المثبتة، وإعداد إصلاح آمن، ثم تنفيذ ما هو معتمد ومصرح به فقط.
يجب أن تشمل المهمة:
1. تحديد جميع ملفات Migration على القرص ومقارنتها بسجلات قاعدة البيانات.
2. تحديد الملفات الثمانية التي فُسّر بها سابقًا الفرق بين 63 ملفًا و55 سجلًا، والتحقق من العدد الحالي.
3. تحديد الملفين اللذين لم يحددهما التقرير السابق، إن بقي فرق عددي، وتفسير سبب عدم تسجيل كل ملف.
4. مراجعة جميع Migrations أكتوبر واعتمادياتها ومخاطرها.
5. مراجعة CapVer وCapability Store ومعالجة الفجوات المثبتة.
6. فحص سلامة `.env` ومصادر اتصال Laravel الفعلية.
7. إثبات الحاوية والـ Volume وقاعدة PostgreSQL المستخدمة.
8. مراجعة Scientific LocalIndex وScientific Source Record Store، دون افتراض أنهما مخزن واحد.
9. مراجعة النسخ الاحتياطية وصلاحيتها قبل أي تغيير دائم.
10. تنفيذ Migrations المعتمدة بالطريقة الصحيحة فقط بعد استيفاء بوابات الأمان والموافقة.
11. التحقق بعد كل خطوة وتوثيق النتائج.
12. تحديث ADR-023 بطريقة Append-only بعد نجاح الإصلاح والتحقق منه، وفق صلاحية التوثيق المعتمدة.
**لا تعتبر وجود ملف Migration أو غياب سجل له تفويضًا كافيًا لتنفيذه. ولا تعتبر تسجيل الاسم يدويًا في جدول `migrations` إصلاحًا.**
## 0.1 حدود التفويض
مسموح دون موافقة إضافية:
- فحص ملفات المشروع وGit والقرارات المعمارية.
- قراءة `.env` دون عرض الأسرار.
- فحص Docker metadata والـ Volumes دون تغييرها.
- استعلامات SQL للقراءة فقط.
- فحص ملفات SQLite بوضع القراءة فقط.
- مراجعة Migrations والاختبارات والسجلات.
- إعداد جداول المقارنة وخطة الإصلاح.
يتطلب موافقة صريحة منفصلة:
- إنشاء نسخة احتياطية جديدة أو ملفات جديدة.
- تطبيق Migration على قاعدة التشغيل.
- أي تغيير دائم في البيانات أو المخطط.
- تشغيل اختبارات كتابة على قاعدة التشغيل.
- تعديل `.env` أو كلمات المرور أو إعدادات الخدمات.
- أي إجراء قد يغير حالة الحاويات أو الـ Volumes.
- إضافة Migration غير متتبعة إلى Git.
- تنفيذ تراجع قد يؤدي إلى فقد بيانات.
إذا كانت هناك موافقة سابقة صريحة ومحددة لأحد هذه الإجراءات، تحقق من نطاقها قبل استخدامها؛ لا توسعها إلى إجراءات أخرى.
لا تنفّذ إجراءً دائمًا يتطلب موافقة لم تُمنح. أكمل التشخيص الآمن، ثم توقف عند بوابة الموافقة.
---
# 1. حماية المشروع والتشغيل الحالي
التقارير السابقة تفيد بأن التطبيق يعمل على:
`http://127.0.0.1:8079/`
والحاويات الأساسية المتوقعة هي:
- `wsa-enterprise-postgres-1`
- `wsa-enterprise-redis-1`
- `wsa-enterprise-backend-1`
- `wsa-enterprise-frontend-1`
- `wsa-enterprise-nginx-1`
- `wsa-enterprise-queue-1`
- `wsa-enterprise-scheduler-1`
كما ورد سابقًا وجود حاويات متوقفة:
- `wsa-enterprise-postgres-1-orphan`
- `wsa-backup-verification-pg`
- `wsa-r7-store-pg-20261015`
هذه معلومات سابقة يجب إعادة التحقق منها.
## 1.1 حماية Git والملفات المحلية
ابدأ بفحص:
- `git status --short`
- `git diff --stat`
- الملفات المعدّلة وغير المتتبعة.
- Git history للملفات ذات الصلة.
لا تستخدم:
- `git reset`
- `git clean`
- `git restore`
- `git checkout --`
- `git stash`
- Commit أو Push أو Merge أو Rebase.
لا تحذف أو تنقل أو تعدّل أو تعِد تسمية أي ملف غير متتبع أو WIP.
لا تستبدل التعديلات المحلية ولا تعِد تنسيق الملفات غير المرتبطة بالمهمة.
لا تعدّل ADR-023 خلال مرحلة التشخيص.
---
# 2. سلامة `.env` ومصادر اتصال Laravel
قبل تشغيل أي أمر Artisan يعتمد على Bootstrap الخاص بـ Laravel، افحص سلامة `.env` قراءةً فقط.
## 2.1 فحص `.env`
تحقق من:
- أخطاء التنسيق المحتملة و`dotenv parse error`.
- مفاتيح بلا `=`.
- تكرار مفاتيح الاتصال أو كلمات المرور.
- وجود `DATABASE_URL` أو `DB_URL`.
- وجود متغيرات قديمة مثل `RENDER_*` و`RAILWAY_*` و`HEROKU_*`.
- وجود Laravel cached configuration.
- مصدر الإعداد الفعلي داخل Backend.
لا تطبع قيم الأسرار أو Connection Strings.
إذا كان `.env` تالفًا:
1. لا تشغّل أوامر Artisan المعتمدة على تحميله حتى تقيّم المشكلة.
2. سجّل موضع الخطأ دون نسخ السطر السري.
3. لا تصلح الملف أو تحذف أي سطر.
4. أوقف الفحوص التي تعتمد على البيئة المتأثرة فقط.
5. واصل الفحوص المستقلة الآمنة.
لا تستنتج أن `DATABASE_URL` مستخدم لمجرد وجوده؛ افحص `config/database.php` وآلية تحميل البيئة وإعدادات الحاوية.
## 2.2 إثبات هوية Backend والاتصال الفعلي
لا تعتمد على `.env` الموجود على مضيف Windows وحده.
بعد التأكد من سلامة Bootstrap، وإذا كان `tinker` متاحًا وآمنًا، استخدم فحصًا لا يطبع الأسرار:
```powershell
docker exec wsa-enterprise-backend-1 php artisan tinker --execute='$c=DB::connection(); $c->getPdo(); echo "DB=".$c->getDatabaseName().PHP_EOL; echo "HOST=".$c->getConfig("host").PHP_EOL; echo "USER=".$c->getConfig("username").PHP_EOL; echo "DRIVER=".$c->getDriverName().PHP_EOL;'
```
إذا تعذر ذلك، استخدم وسيلة قراءة فقط بديلة وسجّل سبب التعذر.
قارن النتيجة مع جلسة PostgreSQL:
```powershell
docker exec wsa-enterprise-postgres-1 psql -U wsa -d wsa_enterprise -c "SELECT current_database(), current_user, version();"
```
المتوقع وفق التقارير السابقة:
- Database: `wsa_enterprise`
- User: `wsa`
- Backend host: `postgres`
- PostgreSQL: `16.14`
لا تعتبر القيم المتوقعة حقائق حتى يثبتها الفحص الحالي.
## 2.3 مقارنة كلمات المرور
تحقق من وجود `DB_PASSWORD` و`POSTGRES_PASSWORD` ومصدرهما، دون طباعة القيم أو أطوالها أو Hash لها.
لا تقارن القيم إلا إذا كانت المقارنة ذات معنى في سياق التشغيل الحالي. سجّل فقط:
- `MATCH`
- `MISMATCH`
- `NOT_COMPARABLE`
تغيير `POSTGRES_PASSWORD` في البيئة لا يثبت تغيير كلمة مرور المستخدم الموجودة داخل قاعدة البيانات.
لا تغيّر أي إعداد اتصال أو كلمة مرور.
---
# 3. تحديد حاوية PostgreSQL والـ Volume الفعليين
استخدم عمليات قراءة فقط لفحص الحاويات:
```powershell
docker ps --format "{{.Names}}|{{.Ports}}|{{.Image}}"
```
افحص metadata لكل حاوية PostgreSQL ذات صلة، بما فيها الحاويات المتوقفة.
تحقق من:
- حالة التشغيل.
- الصورة والإصدار.
- المنافذ.
- الشبكات.
- الـ Volumes المركبة.
- هوية قاعدة البيانات عند إمكان إثباتها.
- علاقة الحاوية بمسار التطبيق.
افحص الـ Volume:
```powershell
docker volume inspect wsa-enterprise_postgres_data
```
لا تطبع بيئة الحاوية كاملة، ولا تعرض الأسرار.
لا تشغّل أو توقف أو تعِد تشغيل أي حاوية. لا تغيّر أي Volume.
إذا كانت حاوية orphan مرتبطة بالـ Volume نفسه، فلا تشغّلها.
---
# 4. اكتشاف Migrations ومقارنة القرص بقاعدة البيانات
## 4.1 استخدام الأداة الرسمية
بعد التحقق من سلامة `.env` واتصال Backend، نفّذ فحص الحالة:
```powershell
docker exec wsa-enterprise-backend-1 php artisan migrate:status
```
هذا الأمر للفحص فقط، وليس إذنًا لتطبيق Migrations.
إذا تعذر تشغيله بأمان، استخدم مقارنة يدوية للقراءة فقط وسجّل سبب التعذر.
## 4.2 المقارنة الكاملة
قارن:
1. جميع ملفات `backend/database/migrations`.
2. جميع سجلات جدول `migrations` في قاعدة البيانات الصحيحة.
3. الملفات غير المتتبعة والمعدّلة.
4. أي سجلات لا يقابلها ملف على القرص.
5. أي ملفات خارج النمط المتوقع أو في مجلدات فرعية.
لا تفترض أن الفرق ما زال ثمانية.
أعد الحساب من الأدلة الحالية.
## 4.3 ملفات أكتوبر المعروفة
افحص الملفات الفعلية ذات الأسماء القريبة من:
- `2026_10_01_150000_*durable_correlation*`
- `2026_10_02_160000_*identity_binding*`
- `2026_10_02_230000_*integration_classification*`
- `2026_10_09_140000_create_cghia_capability_cell_records_table.php`
- `2026_10_09_190000_*single_active_index*`
- `2026_10_15_220000_create_scientific_source_record_store_tables.php`
استخرج المسارات والأسماء الدقيقة من المستودع.
## 4.4 تحديد الملفين المتبقيين
إذا بقي فرق بين عدد الملفات وسجلات قاعدة البيانات:
- حدّد كل ملف غير مسجل بالاسم والمسار.
- اشرح سبب عدم تسجيله وفق الأدلة.
- حدّد هل هو Migration معتمدة أو WIP أو تجربة أو ملف غير محسوم.
- افحص الاعتماديات والمخاطر.
- لا تطبّقه لمجرد سد الفرق العددي.
إذا لم يبق فرق قدره ثمانية، فاشرح سبب تغير العدد بدل محاولة فرض النتيجة السابقة.
أنشئ جدولًا:
`Migration | Exact Path | Git Status | DB Status | Creates/Changes | Dependencies | Approval Status | Risk | Recommended Action`
---
# 5. فحص الجداول اليتيمة والسجلات السابقة
قبل تطبيق أي Migration لاحقًا، افحص وجود الجداول المتوقعة دون سجل Migration مقابل.
مثال أولي:
```sql
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name IN (
    'durable_correlation_records',
    'cghia_identity_binding_records',
    'cghia_integration_classification_records',
    'cghia_capability_cell_records'
  )
ORDER BY table_name;
```
هذه قائمة أولية فقط؛ استخرج جميع أسماء الجداول من ملفات Migration الفعلية.
إذا وُجد جدول أو فهرس متوقع دون سجل Migration مقابل:
- توقف عن تطبيق Migration التي تنشئه.
- افحص المخطط والقيود والبيانات بقراءة فقط.
- افحص السجلات السابقة والأخطاء.
- صنّف الحالة `ORPHAN_SCHEMA_OBJECT_REQUIRES_INVESTIGATION`.
- لا تحذف الكائن ولا تعِد إنشاءه ولا تعدّل جدول `migrations` يدويًا.
افحص سجل Laravel قبل التنفيذ إذا كان متاحًا:
```powershell
docker exec wsa-enterprise-backend-1 sh -lc "tail -n 100 storage/logs/laravel.log"
```
لا تعرض أسرارًا أو بيانات حساسة. اربط الأخطاء السابقة بالـ Migration المناسبة قبل تقرير إمكانية إعادة المحاولة.
---
# 6. مراجعة كل Migration غير مسجلة
لكل ملف:
1. اقرأ `up()` و`down()` كاملتين.
2. حدّد الجداول والأنواع والفهارس والقيود.
3. استخرج الاعتماديات السابقة واللاحقة.
4. افحص Foreign Keys والفهارس الجزئية والقيود الفريدة.
5. افحص أي Backfill أو عمليات بيانات أو آثار جانبية في PHP.
6. تحقق من احتمال تعارض المخطط أو البيانات.
7. راجع Git history وADR-023.
8. حدّد التوافق مع PostgreSQL الفعلي.
9. قيّم إمكانية التراجع ومخاطر فقد البيانات.
أنشئ مخطط اعتماديات حقيقيًا، لا تعتمد على التاريخ وحده.
## 6.1 معايير `APPROVED_FOR_EXECUTION`
لا تُصنّف Migration بهذه الحالة إلا بعد إثبات:
- أنها معتمدة معماريًا أو يوجد قرار مالك صريح يجيزها.
- محتواها مفهوم ومراجع.
- الاعتماديات السابقة مستوفاة.
- لا توجد جداول أو فهارس يتيمة متعارضة.
- مخاطر البيانات معروفة ومقبولة.
- معاينة `--pretend` ناجحة ومتوقعة.
- الاختبارات المطلوبة اجتازت شروط العزل.
- النسخة الاحتياطية المعتمدة جاهزة.
- الموافقة المطلوبة على التنفيذ موجودة.
لا تشترط أن تكون كل Migration من نوع `CREATE` فقط. قد تكون `ALTER` أو Backfill مشروعة، لكنها تحتاج تحليل مخاطر وموافقة مناسبة.
إذا غاب شرط مطلوب، استخدم:
- `REQUIRES_OWNER_DECISION`
- `BLOCKED_BY_DEPENDENCY`
- `BLOCKED_BY_DATA_RISK`
- `UNRESOLVED`
لا تمنح ملفًا غير متتبع حالة الاعتماد تلقائيًا.
---
# 7. CapVer وCapability Store
افحص:
- `backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php`
- `backend/app/Models/CghiaCapabilityCell.php`
- `backend/app/Services/Agriculture/Research/Capability/Persistence/EloquentCapabilityStoreRepository.php`
- `backend/app/Services/Agriculture/Research/Capability/CapVer/CapVerSeatVerificationService.php`
- `backend/app/Console/Commands/DiagnoseScientificPersistenceCommand.php`
- `backend/app/Console/Commands/CapVerAcceptedSeatCommand.php`
وابحث عن جميع المراجع الأخرى ذات الصلة.
تحقق من:
- تطابق الأعمدة بين Migration والنموذج والمستودع.
- الفهارس الجزئية الفريدة وشروط CURRENT.
- التزامن والمعاملات والتكرار.
- شروط نجاح التفعيل.
- الحالات `PERSISTED` و`ALREADY_CURRENT` و`STORE_UNAVAILABLE`.
- سلوك الفشل عند غياب الجدول.
- التوافق مع ADR-023.
لا تعتبر الجدول مكتملًا بمجرد إنشائه.
معيار إغلاق CapVer:
1. المخطط الصحيح موجود.
2. Migration مسجلة كنتيجة لتنفيذ Laravel الناجح.
3. القراءة والكتابة تعملان وفق العقد.
4. اختبارات الفشل المغلق ناجحة.
5. اختبار مسار تجريبي مصرح به يثبت الحفظ الدائم دون تغيير بيانات إنتاج فعلية.
6. النتائج موثقة بدليل قابل للتحقق.
لا تنفذ اختبار كتابة على قاعدة التطبيق قبل استيفاء بوابة الموافقة المناسبة.
---
# 8. بوابة النسخة الاحتياطية
لا تطبق أي Migration على قاعدة التشغيل قبل استيفاء هذه البوابة.
التقرير السابق لم يثبت وجود نسخة حديثة من قاعدة البيانات الحالية قابلة للاستعادة المعزولة.
لا تستخدم نسخة Render التاريخية من 11 سبتمبر بديلًا عن نسخة حديثة من قاعدة Docker الحالية.
## 8.1 فحص الأدوات
لا تفترض وجود `pg_dump` أو `pg_restore` على مضيف Windows. افحص توفر الأدوات داخل الحاوية:
```powershell
docker exec wsa-enterprise-postgres-1 which pg_dump
docker exec wsa-enterprise-postgres-1 pg_dump --version
docker exec wsa-enterprise-postgres-1 pg_restore --version
```
إذا لم تتوفر أداة، توقف وسجّل ذلك؛ لا تثبّت أدوات ولا تغيّر الحاويات.
## 8.2 إنشاء نسخة جديدة — بعد موافقة صريحة
إنشاء النسخة عملية كتابة وإنشاء ملف جديد. لا تنفذها إلا بعد الموافقة المطلوبة.
بعد التأكد من الهدف ومساحة التخزين:
```powershell
$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$containerFile = "/tmp/wsa_pre_migrate_$stamp.dump"
$backupDir = ".\backups"
$hostFile = Join-Path $backupDir "wsa_pre_migrate_$stamp.dump"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
```
تحقق من مساحة التخزين ومن أن المسار لا يستبدل ملفًا موجودًا.
بعد الموافقة:
```powershell
docker exec wsa-enterprise-postgres-1 pg_dump -U wsa -d wsa_enterprise -F c -f $containerFile
```
تحقق من رمز الخروج ووجود الملف وحجمه. ثم انسخه إلى المضيف:
```powershell
docker cp "wsa-enterprise-postgres-1:$containerFile" $hostFile
```
تحقق من الملف:
```powershell
Get-Item $hostFile | Select-Object FullName, Length, LastWriteTime
```
افحص فهرس النسخة:
```powershell
docker exec wsa-enterprise-postgres-1 pg_restore --list $containerFile
```
احسب SHA-256:
```powershell
Get-FileHash $hostFile -Algorithm SHA256
```
سجّل المسار والتاريخ والحجم والبصمة ونتيجة الفحص.
لا تعتبر نجاح `pg_restore --list` وحده دليلًا على إمكانية الاستعادة الكاملة.
## 8.3 معيار قبول النسخة
يلزم:
- قاعدة بيانات مصدر صحيحة ومثبتة.
- نسخة حديثة ومناسبة للتغيير.
- مسار معروف.
- حجم وتاريخ وبصمة تحقق مسجلة.
- نجاح الفحص المناسب لصيغة النسخة.
- خطة استعادة معزولة واختبار قابلية الاستعادة عند الإمكان.
- توثيق النتيجة.
لا تعتمد على حجم ثابت مثل 100 MB معيارًا للقبول.
إذا لم تُستوفَ هذه البوابة، فلا تطبق أي Migration على قاعدة التشغيل.
لا توقف PostgreSQL ولا تنسخ ملفات Volume الخام أثناء تشغيله باعتبار ذلك نسخة متسقة مضمونة.
---
# 9. المعاينة والتنفيذ الصحيح
## 9.1 `--pretend` إلزامي
قبل كل Migration مصرح بها، راجع الملف كاملًا ثم نفّذ معاينة لملف واحد محدد:
```powershell
docker exec wsa-enterprise-backend-1 php artisan migrate --pretend --path=database/migrations/EXACT_MIGRATION_FILENAME.php
```
استبدل الاسم بالاسم الدقيق، ولا تستخدم Wildcards.
تحقق من:
- SQL المتوقع.
- أي `DROP` أو `ALTER` غير متوقع.
- إنشاء الفهارس والقيود.
- الاعتماديات.
- أي آثار جانبية في PHP لا تكشفها معاينة SQL.
- توافق المخرجات مع الملف الذي تمت مراجعته.
`--pretend` ليس ضمانًا مطلقًا للسلامة. إذا فشلت المعاينة أو ظهر شيء غير متوقع، لا تنفذ Migration.
## 9.2 التنفيذ المتسلسل وBatch
الافتراضي هو Migration واحدة في كل مرة، ثم التحقق من نتيجتها قبل الانتقال إلى التالية.
لا تستخدم Wildcards. لا تستخدم `migrate:fresh` أو أي خيار يتجاوز تعارضًا.
بعد تنفيذ Migration مصرح بها بنجاح، افحص سجلات Batch بقراءة فقط:
```sql
SELECT migration, batch
FROM migrations
ORDER BY id DESC
LIMIT 10;
```
لا تُدخل سجلات يدويًا إلى جدول `migrations`.
التنفيذ المنفصل يسهل تحديد موضع الفشل، لكنه لا يجعل rollback آمنًا تلقائيًا. راجع `down()` ومخاطر البيانات قبل أي تراجع.
لا تنفذ rollback تلقائيًا.
## 9.3 `--isolated` و`--force`
تحقق من إصدار Laravel الحالي ودعم الخيارات.
استخدم `--isolated` فقط إذا كانت آلية القفل المشتركة مهيأة وصالحة. لا تفترض أن Queue أو Scheduler ينفذان Migrations تلقائيًا.
افحص `APP_ENV` الفعلي قبل التنفيذ. لا تستخدم `--force` تلقائيًا لمجرد تجاوز تأكيد Laravel. إذا كانت البيئة Production، راجع الموافقة والمخاطر، ولا تستخدم `--force` إلا ضمن تفويض صريح.
---
# 10. التحقق بعد كل Migration
بعد كل Migration مصرح بها ونُفذت بنجاح:
1. تحقق من وجود الجدول أو الفهرس المتوقع.
2. تحقق من الأعمدة والأنواع والقيود.
3. تحقق من تسجيل Migration الناتج عن التنفيذ.
4. افحص أي تعارض أو فقد بيانات غير متوقع.
5. راجع سجل Laravel والأخطاء ذات الصلة.
6. نفّذ Health check.
قائمة الجداول:
```sql
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
ORDER BY table_name;
```
وجود Cap Store:
```sql
SELECT EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = 'public'
      AND table_name = 'cghia_capability_cell_records'
) AS cap_store_exists;
```
Health check من PowerShell:
```powershell
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8079/api/v1/health" -UseBasicParsing -TimeoutSec 15
    $response.StatusCode
} catch {
    $_.Exception.Message
}
```
إذا كانت النتيجة غير 200 أو تعذر الوصول، توقف عن تنفيذ الخطوات التالية وافحص السجلات. لا تنفذ إصلاحًا تلقائيًا.
نجاح Health لا يثبت نجاح CapVer أو Scientific Persistence؛ يجب التحقق من كل عقد على حدة.
---
# 11. Scientific Persistence والملف غير المتتبع
افحص:
- `scientific_record_store.sqlite`
- `mission_artifact_archive/archive.sqlite`
- قواعد بيانات الاختبار.
- Migration الخاصة بـ `scientific_source_record_store`.
- الخدمات التي تستخدم هذه المخازن.
اكتشف أسماء الجداول الفعلية من المخطط والكود. لا تفترض أن `scientific_records` و`scientific_source_records` اسمان للجدول نفسه.
بالنسبة إلى الملف:
`backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php`
تحقق من حالة Git والمحتوى والاعتماديات وتاريخ Git إن كان موجودًا.
لا تحذفه أو تعدله أو تنقله أو تضفه إلى Git أو تطبقه على PostgreSQL تلقائيًا.
حدّد مصدر الحقيقة لكل مخزن، وسياسة المزامنة إن وجدت، ومخاطر إنشاء مخزن موازٍ.
يحتاج تطبيق Migration العلمية على PostgreSQL إلى قرار معماري موثق وموافقة منفصلة.
---
# 12. خطة التراجع
لكل Migration، وثّق:
- التغيير المتوقع.
- البيانات التي قد تتأثر.
- سلوك `down()`.
- مخاطر فقد البيانات بعد التنفيذ.
- إمكانية التراجع الآمن.
- ما إذا كانت الاستعادة من نسخة معتمدة أكثر أمانًا.
لا تعتبر وجود `down()` دليلًا على أن التراجع آمن.
لا تنفذ rollback أو Restore تلقائيًا.
---
# 13. تحديث ADR-023 بعد النجاح فقط
بعد نجاح الإصلاح والتحقق من نتائجه، افحص آخر قسم في:
`docs/architecture/ADR-023-scientific-source-expansion.md`
أضف قسمًا جديدًا Append-only باستخدام الرقم التالي الصحيح؛ لا تفترض أن `§8.60` ما زال الرقم التالي دون فحص.
وثّق:
- التاريخ.
- أسماء Migrations المنفذة فعلًا.
- SHA-256 للنسخة الاحتياطية المعتمدة.
- نتائج التحقق بعد كل Migration.
- نتائج CapVer التي ثبتت فعليًا.
- حالة Scientific Source Store والقرار المعماري.
- نتائج Health checks.
- أي فجوات متبقية وقرارات المالك المطلوبة.
لا تعدّل الأقسام التاريخية.
لا تسجل نتائج لم تُختبر.
لا تنفذ Commit أو Push.
إذا تطلب تحديث ADR تفويضًا منفصلًا غير متاح، توقف عند بوابة التوثيق واطلب الموافقة.
---
# 14. قائمة الممنوعات
- لا `migrate:fresh` أو `migrate:refresh` أو `migrate:reset`.
- لا DDL أو DML يدوي لتجاوز Migration.
- لا إدراج يدوي في جدول `migrations`.
- لا Restore على قاعدة التشغيل.
- لا تشغيل أو إيقاف أو إعادة تشغيل الحاويات.
- لا تعديل `.env` أو كلمات المرور.
- لا حذف أو تعديل WIP أو ملفات غير متتبعة.
- لا Commit أو Push أو Reset أو Clean أو Stash.
- لا Migration غير معتمدة.
- لا اختبارات كتابة حية دون تصريح.
- لا تطبيق Migration العلمية على PostgreSQL قبل القرار المعماري.
- لا كشف الأسرار.
- لا الادعاء بنجاح إجراء لم ينفذ أو لم يُتحقق منه.
---
# 15. التقرير النهائي
قدّم تقريرًا واحدًا بالعربية يتضمن:
## A. ملخص الحالة
الحالة الفعلية للتطبيق والقاعدة والخدمات.
## B. سلامة البيئة والاتصال
نتائج `.env`، مصدر اتصال Laravel، الحاوية والـ Volume والقاعدة الفعلية.
## C. جرد Migrations
القائمة الكاملة للملفات والسجلات والفروق، مع تفسير الملفين المتبقيين إذا بقي فرق.
## D. الجداول اليتيمة
أي جداول أو فهارس موجودة دون سجل Migration مقابل، مع الأدلة.
## E. الاعتماديات
ترتيب التنفيذ المبرر لكل Migration.
## F. CapVer
سبب الغياب، ما إذا أُصلح، ونتائج الاختبارات.
## G. Scientific Persistence
المخازن الفعلية ومصدر الحقيقة والقرار المطلوب.
## H. النسخة الاحتياطية
المسار والتاريخ والحجم والبصمة ونتيجة الفحص وقابلية الاستعادة المثبتة.
## I. سجل التنفيذ
لكل Migration:
- `NOT RUN`
- `READY`
- `EXECUTED SUCCESSFULLY`
- `FAILED — STOPPED`
- `BLOCKED`
- `REQUIRES OWNER DECISION`
## J. التحقق بعد التنفيذ
المخطط وسجلات Migrations والاختبارات وHealth.
## K. تحديث ADR
ما إذا تم التحديث بعد النجاح أو بقي معلقًا، ولماذا.
## L. تأكيد السلامة
حالة قاعدة البيانات والحاويات والملفات المحلية وGit، وفق الأدلة الفعلية.
ميّز بين:
- `CONFIRMED`
- `INFERRED`
- `UNRESOLVED`
- `NOT TESTED`
---
# 16. شرط الإنهاء
ابدأ بالفحوص الآمنة، واكتشف جميع الفجوات، وأكمل الإصلاحات التي استوفت شروط التفويض والموافقة فقط.
لا تتوقف عند اكتشاف الملفين؛ يجب تفسير وضعهما وتحديد الإجراء المناسب لكل ملف.
لا تنفّذ Migration على قاعدة التشغيل دون:
- إثبات الهدف الصحيح.
- مراجعة الملف واعتمادياته.
- نجاح المعاينة.
- استيفاء بوابة النسخة الاحتياطية.
- موافقة التنفيذ المطلوبة.
إذا غاب أي شرط، توقف عن التغيير الدائم وحدد المانع بدقة.
**الهدف هو إغلاق الفجوات فعليًا وبأمان، وليس جعل عدد سجلات Migrations يساوي عدد الملفات شكليًا.**
احتفظ بجميع الأعمال المحلية، ولا تنفّذ Commit أو Push.
