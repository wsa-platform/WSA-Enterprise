<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17534). Message timestamp as recorded in the conversation: Tuesday, Oct 20, 2026, 11:54 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17535, 17537; it is archived once. -->

---
# WSA-Enterprise — MissionArtifactArchive Commit & Push
## 1. المهمة والتفويض
تصرف كمهندس برمجيات خبير في Git وسلامة المستودعات.
نفّذ Commit واحدًا ثم Push عاديًا إلى الفرع البعيد، لمجموعة `MissionArtifactArchive` فقط، بعد اجتياز جميع الفحوص المطلوبة.
المستودع المحلي:
`G:\WSA-Enterprise`
الفرع المستهدف:
`phase-18-m18-ai-marketing-communications`
عنوان الالتزام المطلوب حرفيًا:
`feat(archive): add a mission artifact store separate from the scientific index`
التفويض يسمح بـ:
- فحص الحالة الحالية.
- تشغيل اختبار المجموعة المحددة إذا كانت بيئة الاختبار آمنة.
- تجهيز الملفات المصرح بها فقط.
- إنشاء Commit واحد.
- تنفيذ Push عادي للفرع المستهدف.
- التحقق من نتيجة Push وحالة Git النهائية.
لا يسمح بأي Commit إضافي أو Push إجباري أو تعديل لتاريخ Git.
## 2. فحص ما قبل التنفيذ
ابدأ من `G:\WSA-Enterprise`، وليس من worktree مؤقت.
نفّذ وسجّل النتائج الفعلية:
- `git status --short`
- `git status -sb`
- `git branch --show-current`
- `git log -5 --oneline --decorate`
- `git remote -v`
- `git diff --stat`
- `git diff --cached --stat`
- `git diff --check`
تأكد من أن الفرع الحالي هو:
`phase-18-m18-ai-marketing-communications`
لا تفترض أن بيانات التقارير السابقة ما زالت مطابقة للحالة الحالية.
إذا كان الفرع مختلفًا، أو كانت هناك عملية Git جارية، أو كان الالتزام المقصود موجودًا بالفعل، فتوقف واعرض الحالة. لا تغيّر الفرع تلقائيًا.
## 3. الملفات المسموح بها
يقتصر الالتزام على مجموعة `MissionArtifactArchive` التالية:
1. `backend/app/Services/Architecture/MissionArtifactArchive/`
   - ملفات التنفيذ السبعة التي تمت مراجعتها.
2. `backend/app/Console/Commands/MissionArtifactArchiveCommand.php`
3. `backend/config/mission_artifact_archive.php`
4. `backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`
افحص الملفات الفعلية داخل مجلد التنفيذ، وراجع محتواها وفرقها قبل إضافتها.
لا تستخدم:
- `git add .`
- `git add -A`
- أي أمر يضيف مجلدات واسعة أو ملفات خارج المجموعة.
إذا اختلفت الملفات الحالية عن المجموعة التي تمت مراجعتها سابقًا، فلا تفترض أنها سليمة. توقف إذا كان الاختلاف جوهريًا أو غير مفسر.
## 4. حماية الأعمال الأخرى
يجب أن تبقى جميع التعديلات الأخرى كما هي، سواء كانت معدّلة أو غير متعقبة.
ممنوع تضمين أي مما يلي:
- `docs/architecture/ADR-023-scientific-source-expansion.md`
- `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md`
- ملفات `docs/architecture/science-source-expansion/`
- `docs/adr/README.md`
- أي ملفات Scientific Search أو LocalIndex أو Capability أو CapVer أو Provenance أو Registry أو Projection أو Onboarding أو Membership أو Evaluation.
- أي ملفات Migration، بما فيها `backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php`.
- `backend/composer.lock`
- `.gitignore`
- `backend/.env.example`
- ملفات `e2e-tmp` أو بيانات تسجيل الدخول أو الرموز أو كلمات المرور.
- أي تقارير أو ملفات مؤقتة أو ملفات غير مذكورة صراحةً ضمن المجموعة المصرح بها.
لا تصلح ملفات أخرى، ولا تحذفها، ولا تعِدها إلى نسخة سابقة.
## 5. إعادة تشغيل الاختبار
إذا كانت بيئة الاختبار المعتمدة متاحة، شغّل الاختبار المحدد فقط من دليل Backend الصحيح:
`phpunit --configuration phpunit.xml tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`
تحقق من النتيجة الفعلية، بما فيها عدد الاختبارات والتأكيدات ورمز الخروج.
لا تشغّل أمر الأرشفة على قاعدة البيانات التشغيلية لمجرد التحقق.
لا تشغّل اختبارات LocalIndex أو PostgreSQL ضمن هذه المهمة.
إذا تعذر تشغيل الاختبار، فلا تدّعِ نجاحه. استخدم نتيجة الاختبار السابقة كدليل تاريخي فقط، وقيّم إمكانية المتابعة بناءً على سبب التعذر. إذا ظهر فشل جديد أو خلل، فتوقف قبل Commit.
## 6. التجهيز والتحقق
أضف المسارات المصرح بها فقط باستخدام مسارات دقيقة.
بعد التجهيز، نفّذ:
- `git diff --cached --name-status`
- `git diff --cached --stat`
- `git diff --cached --check`
- `git diff --cached`
راجع الفرق المجهز كاملًا.
تحقق من أن كل ملف في منطقة التجهيز ينتمي إلى مجموعة `MissionArtifactArchive`، وأنه لا توجد أسرار أو ملفات مؤقتة أو تغييرات غير مقصودة.
إذا ظهر أي مسار خارج النطاق، فلا تنفّذ Commit. أزل المسار الزائد من منطقة التجهيز فقط باستخدام أمر محدد لذلك المسار، دون تغيير محتوى ملف العمل. ثم أعد الفحص.
إذا تعذر إثبات أن منطقة التجهيز مطابقة للنطاق المصرح به تمامًا، فتوقف.
## 7. إنشاء Commit
بعد اجتياز جميع الفحوص، أنشئ Commit واحدًا بالعنوان التالي حرفيًا:
`feat(archive): add a mission artifact store separate from the scientific index`
ثم تحقق من:
- `git log -1 --format="%H%n%s"`
- `git show --stat --oneline HEAD`
- `git diff HEAD^ HEAD --check`
- `git status --short`
- `git status -sb`
تأكد من أن الالتزام يحتوي المجموعة المصرح بها فقط.
إذا فشل إنشاء Commit، لا تنشئ التزامًا بديلًا أو إضافيًا دون تشخيص السبب.
## 8. التحقق من الفرع البعيد قبل Push
قبل Push، افحص حالة الفرع البعيد دون تغيير تاريخ Git:
- `git fetch origin`
- `git status -sb`
- `git rev-parse HEAD`
- `git rev-parse origin/phase-18-m18-ai-marketing-communications`
- `git log --oneline --left-right HEAD...origin/phase-18-m18-ai-marketing-communications`
نفّذ `fetch` فقط لتحديث معلومات التتبع؛ لا تستخدم `pull`.
### شرط السماح بالـ Push
لا تنفّذ Push إلا إذا ثبت أن الالتزام المحلي الجديد يمكن دفعه بطريقة Fast-forward عادية دون استبدال أو حذف أي التزامات بعيدة.
إذا كان الفرع البعيد يحتوي التزامات غير موجودة محليًا، أو ظهر تباعد في التاريخ، فتوقف بعد إنشاء الالتزام وأرسل التقرير. لا تنفّذ merge أو rebase أو cherry-pick أو reset تلقائيًا.
لا تستخدم `--force` أو `--force-with-lease` تحت أي ظرف في هذه المهمة.
## 9. تنفيذ Push
إذا اجتاز الفرع جميع الشروط السابقة، نفّذ Push عاديًا فقط:
`git push origin phase-18-m18-ai-marketing-communications`
لا تدفع أي فرع آخر أو Tag.
إذا رفض الخادم عملية Push، توقف واعرض رسالة الخطأ. لا تحاول تجاوز الرفض بإعادة كتابة التاريخ أو تنفيذ عمليات دمج تلقائية.
## 10. التحقق بعد Push
بعد نجاح Push، نفّذ:
- `git fetch origin`
- `git status -sb`
- `git rev-parse HEAD`
- `git rev-parse origin/phase-18-m18-ai-marketing-communications`
- `git show --stat --oneline HEAD`
- `git diff HEAD^ HEAD --check`
تأكد من أن `HEAD` المحلي يساوي `origin/phase-18-m18-ai-marketing-communications`، وأن الالتزام الجديد موجود على الفرع البعيد، وأن الملفات الأخرى لم تدخل في الالتزام.
لا تدّعِ نجاح Push ما لم تؤكده نتائج الأوامر الفعلية.
## 11. الإجراءات الممنوعة
ممنوع تنفيذ:
- `git push --force` أو أي Push إجباري.
- `git pull`
- `git merge`
- `git rebase`
- `git cherry-pick`
- `git reset`
- `git clean`
- `git stash`
- تغيير الفرع تلقائيًا.
- أي Migration أو تعديل لقاعدة البيانات.
- أي تعديل على ADR-021 أو ADR-023.
- أي تعديل أو حذف لملفات خارج المجموعة المستهدفة.
- أي Commit إضافي.
## 12. التقرير النهائي بالعربية
أرسل تقريرًا واحدًا شاملًا يتضمن:
1. حالة الفرع وHEAD قبل التنفيذ.
2. نتائج الاختبارات الفعلية.
3. قائمة الملفات التي دخلت الالتزام.
4. عنوان الالتزام ومعرّف SHA الكامل.
5. حالة الفرع البعيد قبل Push.
6. نتيجة Push الفعلية.
7. قيم `HEAD` و`origin/phase-18-m18-ai-marketing-communications` بعد Push.
8. حالة Git النهائية وأي تعديلات أخرى بقيت غير ملتزم بها.
9. تأكيد عدم تعديل أو حذف أي من الأعمال الأخرى.
10. أي فحوص تعذرت أو مخاطر لم تُحسم.
**قاعدة القرار النهائية:** أنشئ Commit واحدًا، ثم ادفعه فقط إذا كان Push عاديًا آمنًا ومسموحًا. إذا تعذر إثبات ذلك، توقف دون إعادة كتابة تاريخ Git، واحتفظ بجميع الملفات والتغييرات كما هي.
