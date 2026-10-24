<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17549). Message timestamp as recorded in the conversation: Wednesday, Oct 21, 2026, 12:09 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17550; it is archived once. -->

---
# WSA-Enterprise — Safe Git Reconciliation, Verification & Push

## 1. التفويض والهدف

تصرف كمهندس برمجيات خبير مسؤول عن سلامة Git والتكامل الآمن.

نفّذ خطة المصالحة المعتمدة أدناه لإنهاء Push الالتزام المحلي الخاص بـ MissionArtifactArchive إلى الفرع البعيد، دون إعادة كتابة التاريخ أو المساس بالتعديلات غير الملتزم بها.

المستودع الأصلي:
`G:\WSA-Enterprise`

الفرع البعيد المستهدف:
`phase-18-m18-ai-marketing-communications`

الالتزام المحلي الذي يجب الاحتفاظ به كما هو:
`f0ac89700b7a822bd83b2cd821a18e493089d786`

المرجع البعيد المتوقع:
`8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`

شجرة الأساس المشتركة:
`f2025315d8990f52f3c658c968a3ec8252ac18b0`

**التفويض يشمل إنشاء Worktree جديد، وإنشاء التزام دمج واحد عليه، وتشغيل الاختبار المعزول، ثم Push عادي إلى الفرع البعيد إذا اجتازت جميع شروط السلامة.**

لا تنفذ أي Commit أو Merge داخل مجلد العمل الأصلي.

## 2. شروط الحماية المطلقة

يجب الالتزام بما يلي طوال المهمة:

- الحفاظ على جميع الملفات المعدّلة وغير المتعقبة في `G:\WSA-Enterprise`.
- عدم حذف أو تعديل أي ملف خارج نطاق Worktree المصالحة.
- عدم استخدام `git push --force` أو `--force-with-lease`.
- عدم استخدام `git reset` أو `git clean` أو `git stash`.
- عدم استخدام `git pull` أو `git rebase`.
- عدم تغيير الفرع الحالي في مجلد العمل الأصلي.
- عدم حذف أو تعديل Worktree الموجود:
  `G:\WSA-Enterprise-push-isolated`
- عدم حذف أو تعديل الفرع المؤقت الموجود:
  `temp/frontend-crop-profile-push`
- عدم تشغيل أي Migration.
- عدم الكتابة إلى SQLite العلمية التشغيلية أو PostgreSQL التشغيلية.
- عدم تعديل ADR-021 أو ADR-023.
- عدم إنشاء أي التزام إضافي غير التزام الدمج المحدد في هذه الخطة.

إذا تعذر إثبات سلامة أي خطوة، توقف واحتفظ بالحالة كما هي.

## 3. المرحلة الأولى — التحقق من الحالة الحالية

من `G:\WSA-Enterprise` نفّذ:

- `git status -sb`
- `git branch --show-current`
- `git rev-parse HEAD`
- `git fetch origin`
- `git rev-parse origin/phase-18-m18-ai-marketing-communications`
- `git status --short`

تحقق من أن:

- HEAD المحلي ما زال `f0ac89700b7a822bd83b2cd821a18e493089d786`.
- الفرع البعيد ما زال `8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`.
- لا توجد عملية دمج أو إعادة قاعدة أو انتقاء جارية.
- التغييرات غير الملتزم بها في مجلد العمل الأصلي ما زالت محفوظة.

إذا تحرك الفرع البعيد عن SHA المتوقع، توقف ولا تحاول التكيف مع التغيير تلقائيًا.

## 4. المرحلة الثانية — إنشاء Worktree جديد

استخدم مسارًا جديدًا لم يسبق استخدامه:

`G:\WSA-Enterprise-archive-reconcile`

واستخدم الفرع المؤقت الجديد:

`temp/archive-reconcile`

تحقق أولًا من أن المسار والاسم غير مستخدمين. لا تحذف أي مسار موجود لتوفير الاسم.

أنشئ Worktree من الالتزام البعيد المتوقع:

`git worktree add -b temp/archive-reconcile G:\WSA-Enterprise-archive-reconcile 8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`

داخل Worktree الجديد تحقق من:

- الفرع الحالي هو `temp/archive-reconcile`.
- HEAD يساوي `8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`.
- شجرة العمل نظيفة.
- لا توجد ملفات من التعديلات غير الملتزم بها في المجلد الأصلي.

إذا لم يكن Worktree نظيفًا أو لم يتحقق أي شرط، توقف.

## 5. المرحلة الثالثة — الدمج مع الاحتفاظ بالتاريخ

داخل Worktree الجديد، ادمج الالتزام المحلي:

`f0ac89700b7a822bd83b2cd821a18e493089d786`

باستخدام Merge عادي غير Fast-forward، مع رسالة واضحة مثل:

`merge: preserve mission artifact archive commit on mainline`

الهدف هو إنشاء التزام دمج واحد تكون خصائصه:

- الأب الأول: `8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`.
- الأب الثاني: `f0ac89700b7a822bd83b2cd821a18e493089d786`.

تحقق باستخدام:

- `git rev-parse HEAD^1`
- `git rev-parse HEAD^2`
- `git show --no-patch --format=fuller HEAD`

يجب أن يبقى الالتزام `f0ac897` نفسه في التاريخ، لا أن يُعاد إنشاؤه عبر cherry-pick.

إذا حدث تعارض، لا تحلّه تلقائيًا. اعرض أسماء الملفات المتعارضة وتوقف دون إسقاط تغييرات أو تعديل الملفات يدويًا.

## 6. المرحلة الرابعة — التحقق من محتوى الدمج

يجب أن يحتوي الفرع النهائي على الالتزامات البعيدة الأربعة والالتزام المحلي `f0ac897`.

نفّذ:

- `git merge-base HEAD 8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10`
- `git diff --name-status 8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10 HEAD`
- `git diff --stat 8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10 HEAD`
- `git diff 8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10 HEAD --check`

شروط القبول:

1. الأب الأول للدمج هو الالتزام البعيد المتوقع.
2. الأب الثاني هو `f0ac897`.
3. الفرق الصافي مقارنة بـ `8dd6827` يقتصر على ملفات MissionArtifactArchive العشرة التالية، دون أي ملف آخر:

   - `backend/app/Console/Commands/MissionArtifactArchiveCommand.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/AdrEmbeddedExcerptImporter.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/ControlledMissionArtifactImporter.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/FilesystemMissionArtifactArchive.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/MissionArtifactArchiveFactory.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/MissionArtifactClassifier.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/MissionArtifactRecord.php`
   - `backend/app/Services/Architecture/MissionArtifactArchive/MissionArtifactSecretFilter.php`
   - `backend/config/mission_artifact_archive.php`
   - `backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`

4. لا يوجد اختلاف غير متوقع في ملفي الواجهة مقارنة بالفرع البعيد.
5. لا تدخل أي تعديلات محلية من ADR-023 أو أي ملفات غير ملتزم بها في المستودع الأصلي.

إذا ظهر أي اختلاف خارج النطاق، توقف ولا تدفع.

## 7. المرحلة الخامسة — تجهيز الاختبار بأمان

شغّل اختبار MissionArtifactArchive المحدد فقط داخل Worktree المصالحة:

`php vendor/bin/phpunit --configuration phpunit.xml tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`

إذا لم يتوفر `vendor` داخل Worktree:

- لا تنفذ `composer update`.
- لا تنفذ تثبيتًا يغيّر ملفات التبعيات أو يقفل الإصدارات.
- تحقق من توافق تبعيات `vendor` الموجودة في المستودع الأصلي، واستخدمها للقراءة فقط إذا أمكن دون تعديل أي ملفات أو تشغيل سكربتات Composer.
- إذا تعذر تشغيل الاختبار بأمان، توقف ولا تنفذ Push.

### متطلبات عزل قاعدة البيانات

قبل تشغيل الاختبار، افحص `MissionArtifactArchiveFactory::forTesting()` وإعدادات الاختبار الفعلية.

يجب التأكد من أن:

- إعداد قاعدة التطبيق للاختبار يستخدم SQLite في الذاكرة، أو قاعدة اختبار مؤقتة مستقلة.
- `SCIENTIFIC_RECORD_STORE_DATABASE` يشير إلى مسار مؤقت مستقل.
- `MISSION_ARTIFACT_ARCHIVE_SQLITE` و`MISSION_ARTIFACT_ARCHIVE_BLOB_ROOT` يشيران إلى مسارات مؤقتة داخل مجلد الاختبار.
- لا تُستخدم قواعد البيانات التشغيلية أو مجلدات التخزين التشغيلية.
- لا يُشغّل أمر الأرشفة على قاعدة بيانات حقيقية.
- لا يُشغّل اختبار LocalIndex أو مجموعة PostgreSQL.

لا تعتمد على تعيين متغيرات البيئة وحدها؛ تحقق من أن الاختبار يقرأها فعلًا ولا يتجاوزها بإعدادات ثابتة.

معيار القبول:

- 9 اختبارات ناجحة.
-
