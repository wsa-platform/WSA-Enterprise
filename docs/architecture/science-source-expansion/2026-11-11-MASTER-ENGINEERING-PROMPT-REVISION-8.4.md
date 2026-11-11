# WSA-Enterprise
# MASTER ENGINEERING PROMPT — REVISION 8.4
## COMPLETE PHASE 1 GAP CLOSURE
## GATE A / GATE B OWNER PRE-APPROVAL
## REGISTRY WIP / STASH / DEPENDENCY SAFETY
## VERIFIED GIT DELIVERY
## AUTOMATIC EXECUTION RESUMPTION
**التاريخ المرجعي:** 2026-11-11
**Repository:** `wsa-platform/WSA-Enterprise`
**Local Path:** `G:\WSA-Enterprise`
**Expected Branch:** `phase-18-m18-ai-marketing-communications`
**Historical HEAD from previous report:**
`6e13c856dd65592ac05e9671bb8c257f06963034`
**Previous Official Status:**
`PHASE_1_GAP_CLOSURE_PARTIAL`
**Previous Informal Progress Description:**
`SUBSTANTIAL`
---
# القسم 1 — التكليف الرسمي والهدف النهائي
أنت المهندس المعماري الرئيسي وقائد التنفيذ الهندسي لمشروع WSA-Enterprise.
أنت مكلف باستكمال جميع الفجوات المتبقية في Phase 1 وإغلاق المرحلة رسميًا وفق معايير الخروج الأصلية.
هذه مهمة تنفيذ هندسي فعلية، وليست مهمة تحليل تقارير أو تقديم خطط نظرية.
الهدف النهائي:
`PHASE_1_COMPLETE`
بعد استيفاء جميع معايير الخروج الإلزامية وفق تعريفاتها وأدلتها واختباراتها المعتمدة.
يشمل التكليف:
1. مراجعة الحالة الفعلية للمستودع.
2. مراجعة ADR-023 والقرارات المعمارية الأصلية.
3. حسم Gate A وGate B بموجب موافقة المالك المسبقة.
4. اتخاذ القرارات التقنية اللازمة لإغلاق Phase 1.
5. التصميم وإعادة التصميم عند الحاجة.
6. كتابة الأكواد وتنفيذ التعديلات.
7. إنشاء العقود والواجهات والأنواع والخدمات.
8. تطوير Backend وReact وFlutter عند الحاجة.
9. استكمال تكامل المكونات والطبقات.
10. استكمال Source Adapters وAccess-family adapters.
11. استكمال CapVer وCapability Enforcement.
12. استكمال IC وStaging وRunner.
13. معالجة G2-26 وفق الأدلة الأصلية.
14. استكمال متطلبات PostgreSQL.
15. معالجة GAP-01 إلى GAP-08.
16. معالجة Registry/ServiceProvider/Onboarding dependencies.
17. حماية WIP وStash والأعمال السابقة.
18. تطوير الاختبارات وتشغيلها.
19. إصلاح الأخطاء وRegression.
20. تحديث ADR والوثائق والسجلات.
21. استكمال FilesystemMissionArtifactArchive.
22. حفظ التقارير والبرومبتات وProject State Snapshots.
23. تنفيذ Commit وPush تلقائيًا عند استيفاء شروط الجودة والحماية.
24. التحقق الفعلي من Remote Sync.
25. العودة تلقائيًا إلى آخر نقطة توقف بعد معالجة أي Git Hazard.
26. إجراء المراجعة النهائية لجميع معايير Phase 1.
لا تعتبر المهمة مكتملة لمجرد نجاح مجموعة اختبارات.
لا تعتبر المهمة مكتملة لمجرد إغلاق Gate A وGate B.
لا تعلن COMPLETE إذا بقي شرط إلزامي غير مستوفى.
إذا بقيت متطلبات إلزامية غير مكتملة، استخدم:
`PHASE_1_GAP_CLOSURE_PARTIAL`
---
# القسم 2 — موافقة المالك المسبقة على القرارات
## 2.1 الموافقة الرسمية
يعلن مالك المشروع موافقته المسبقة على اتخاذ وتنفيذ القرارات المعمارية والتقنية اللازمة لإغلاق Phase 1، بما في ذلك القرارات التي كانت تتطلب سابقًا موافقة المالك، وخصوصًا:
- Gate A المرتبط بـ P1-EXIT-02.
- Gate B المرتبط بـ P1-EXIT-03.
- القرارات المعمارية التابعة لهما.
- القرارات التنفيذية اللازمة لاستكمال P1-EXIT-05.
- القرارات اللازمة لاستكمال P1-EXIT-06.
- القرارات اللازمة لاستكمال P1-EXIT-08.
- القرارات المتعلقة بالعقود والـ Adapters والتكامل.
- القرارات اللازمة لمعالجة الفجوات السابقة.
هذه موافقة مالك مسبقة لتجنب تعطيل التنفيذ بسبب انتظار موافقات متكررة على القرارات الهندسية الواقعة ضمن نطاق Phase 1.
## 2.2 طريقة استخدام الموافقة
قبل حسم Gate A أو Gate B:
1. استخرج التعريف الأصلي للبوابة من ADR-023.
2. راجع القرارات السابقة المرتبطة بها.
3. افحص التنفيذ الحالي.
4. حدد المشكلة المعمارية الفعلية.
5. حدد البدائل الممكنة.
6. حلل آثار كل بديل.
7. اختر الحل الأكثر اتساقًا مع معمارية المشروع.
8. وثق أسباب الاختيار.
9. نفذ القرار.
10. أضف الاختبارات المطلوبة.
11. حدث ADR-023 وسجل القرارات.
12. اربط القرار بالمعايير التي يؤثر فيها.
لا تطلب من المالك إعادة اعتماد خيار هندسي يقع ضمن نطاق هذه الموافقة.
## 2.3 حدود الموافقة
الموافقة المسبقة لا تسمح بما يلي:
- حذف قواعد البيانات التشغيلية المحمية.
- حذف السجلات التاريخية.
- إتلاف الأرشيف أو استبدال نسخه.
- إعادة كتابة Git History.
- Force Push.
- حذف WIP مجهول أو غير مراجع.
- حذف Stash يحتوي على أعمال لم يتم التحقق من استعادتها.
- كشف Secrets أو نقلها.
- تغيير نطاق المشروع إلى Phase 2 أو Phase 3.
- إجراء التزامات مالية أو تعاقدية خارجية.
- اختلاق Evidence أو مستندات تاريخية.
- تغيير معايير القبول الأصلية بصمت.
إذا ظهر إجراء من هذه الفئات، حافظ على البيانات وتوقف عن الإجراء المحدد فقط، ثم وثق السبب وأكمل الأعمال المستقلة.
## 2.4 عدم اختلاق القرار الأصلي
موافقة المالك لا تسمح باختلاق نص Gate A أو Gate B.
يجب استخراج النص الأصلي من ADR-023 ومراجعة سياقه.
إذا لم تجد النص الأصلي:
- ابحث في Git History.
- افحص الوثائق المرتبطة.
- افحص القرارات السابقة.
- افحص التقارير والأرشيف المتاحين.
- حدد موضع النقص.
- لا تنشئ تعريفًا مزيفًا للبوابة.
## 2.5 مسار التعامل مع النص الأصلي المفقود
إذا لم يوجد النص الأصلي بعد البحث الموثق في:
1. ADR-023.
2. Git History.
3. الوثائق المرتبطة.
4. القرارات المعمارية السابقة.
5. التقارير والأرشيف المتاحين.
سجل إحدى الحالتين:
`GATE_A_TEXT_NOT_FOUND`
أو:
`GATE_B_TEXT_NOT_FOUND`
وسجل:
- الأماكن التي تم البحث فيها.
- نطاق البحث وتاريخه.
- الملفات والمراجع التي تم فحصها.
- ما تم العثور عليه.
- ما تم استبعاده وأسباب استبعاده.
- هل البحث شامل أم محدود بسبب عدم توفر مصدر.
بعد ذلك:
1. لا تخترع النص الأصلي.
2. لا تتوقف عن بقية العمل.
3. حدد المشكلة الهندسية التي يمكن إثباتها.
4. استخرج المتطلبات المنطقية من القرارات المعتمدة الأخرى.
5. صمم قرارًا هندسيًا جديدًا عند الحاجة.
6. صنف القرار:
   `NEW_ENGINEERING_DECISION`
7. وثق مصادر المتطلبات والافتراضات وحدود المعرفة.
8. نفذ القرار واختبره إذا كان قابلًا للتنفيذ.
9. اربط القرار بالمعايير التي يعالجها.
القرار الهندسي الجديد ليس نصًا أصليًا مسترجعًا ولا يمنح PASS تلقائيًا.
يجوز إعلان PASS فقط إذا أمكن إثبات استيفاء معيار الخروج من تعريف أصلي أو مصدر معماري معتمد آخر أو تعريف بديل اعتمده المالك صراحةً.
إذا تعذر إثبات التكافؤ، تبقى الحالة PARTIAL أو BLOCKED وفق طبيعة النقص.
---
# القسم 3 — التفويض الهندسي الكامل
أنت مفوض بالكامل للعمل كمهندس برمجيات ومهندس تصميم ومعماري رئيسي.
## 3.1 التصميم المعماري
مسموح لك:
- مراجعة المعمارية الحالية.
- إعادة تصميم المكونات.
- تحسين حدود المسؤوليات بين الطبقات.
- إنشاء Domain Contracts.
- إنشاء Interfaces.
- إنشاء DTOs.
- إنشاء Factories.
- إنشاء Services.
- إنشاء Repositories.
- إنشاء Adapters.
- إنشاء Runners.
- إنشاء Staging Workflows.
- إعادة تنظيم الاعتماديات.
- تحسين الفصل بين الطبقات.
- معالجة التكرار.
- إصلاح العقود غير الصحيحة.
- إنشاء Architecture Tests.
اختر الحل الصحيح والمستدام، وليس مجرد أصغر تعديل.
## 3.2 البرمجة والتنفيذ
مسموح لك كتابة وتعديل الأكواد في:
- Laravel 12 / PHP Backend.
- React Frontend.
- Flutter Application.
- PostgreSQL Integration.
- SQLite Test Environments.
- APIs.
- Scientific Search.
- Source Registry.
- Capability Resolution.
- Projection.
- CapVer.
- Evidence Processing.
- Answer Synthesis.
- Archive Infrastructure.
- Testing Infrastructure.
## 3.3 إعادة التصميم
إذا أثبت التحقيق وجود عيب معماري:
1. حدد السبب الجذري.
2. صمم البديل.
3. افحص تأثيره على المكونات الأخرى.
4. حافظ على التوافق المطلوب.
5. نفذ التعديل.
6. أضف الاختبارات.
7. شغل Regression Tests.
8. وثق التغيير.
## 3.4 التنفيذ المستقل
لا تكتفِ بإرسال توصيات.
نفذ الأعمال التي تقع ضمن التفويض.
لا تطلب موافقة على كل Class أو Interface أو Method أو Test.
لا توقف العمل بسبب قرار تقني عادي يمكن حسمه هندسيًا.
---
# القسم 4 — خط الأساس والتحقق من المستودع
ابدأ بفحص الحالة الحقيقية.
نفذ:
```bash
git status
git status --short
git branch --show-current
git rev-parse HEAD
git log -10 --oneline
git remote -v
git stash list
```
تحقق من:
- الفرع الحالي.
- HEAD.
- Remote Sync.
- الملفات المعدلة.
- الملفات غير المتتبعة.
- الملفات الموجودة في Staging.
- WIP.
- Stashes.
- الالتزامات الجديدة.
- اختلاف الحالة عن التقارير التاريخية.
لا تفترض أن HEAD التاريخي ما زال هو HEAD الحالي.
لا تعِد تنفيذ ما ثبت نجاحه.
لا تبدأ من الصفر.
لا تستبدل الحالة الفعلية للمستودع ببيانات تاريخية.
إذا لم يوجد Upstream للفرع، سجل ذلك بدل افتراض وجوده.
---
# القسم 5 — حماية البيانات وGit
## 5.1 قواعد البيانات المحمية
لا تعدل أو تحذف أو تستبدل:
`backend/storage/app/capver.sqlite`
`backend/storage/app/scientific_record_store.sqlite`
استخدم قواعد اختبار معزولة.
لا تستخدم قواعد الإنتاج للاختبارات.
## 5.2 أوامر Git المحظورة
ممنوع:
```bash
git reset --hard
git clean -fd
git clean -fdx
git push --force
git push --force-with-lease
git checkout .
git restore .
git add .
git add -A
```
ممنوع حذف WIP أو إعادة كتابة التاريخ.
## 5.3 WIP
احفظ كل WIP.
افحص خصوصًا:
- WIP Orchestrator.
- CapVerSeat*.
- Onboarding.
- Registry.
- ServiceProvider.
- IC.
- Staging.
- Runner.
- Access-family adapters.
صنفها:
- `REQUIRED_FOR_PHASE_1`
- `REQUIRED_RUNTIME_DEPENDENCY`
- `REQUIRED_TEST_DEPENDENCY`
- `INCOMPLETE_WIP`
- `OUT_OF_SCOPE`
- `DUPLICATE`
- `OBSOLETE`
- `UNKNOWN`
لا تحذف UNKNOWN.
لا تدمج WIP قبل مراجعة العقود والاعتماديات والاختبارات.
احتفظ بنسخة احتياطية قبل أي دمج.
قبل استعادة نسخة احتياطية:
- تحقق من وجودها.
- احسب SHA-256 عند ملاءمة نوع الملفات.
- قارنها بالنسخة الحالية.
- احفظ نسخة من الحالة الحالية.
- ادمج التغييرات المطلوبة بطريقة آمنة.
## 5.4 حماية WIP وStash واعتماديات الملفات
### 5.4.1 سبب الحماية الإضافية
ظهر أثناء التنفيذ تحذير من Cursor:
`tracked Registry/ServiceProvider depend on untracked Onboarding`
كما ظهر أن Cursor يحاول استعادة Registry WIP من Stash.
هذا يعني احتمال وجود اعتماديات تشغيلية بين ملفات متتبعة وملفات محلية غير متتبعة، أو وجود أعمال محفوظة في Stash لم تتم استعادتها أو دمجها بصورة صحيحة.
يجب معالجة هذا الخطر قبل Push.
### 5.4.2 الإيقاف المؤقت
عند اكتشاف:
- Tracked files depend on untracked files.
- Registry أو ServiceProvider يعتمد على Onboarding غير متتبع.
- وجود WIP في Stash غير مستعاد.
- وجود Stash تم استعادته جزئيًا.
- وجود ملفات محلية يعتمد عليها Commit دون وجودها في المستودع.
- وجود ملفات جديدة لازمة للتشغيل غير مدرجة في Staging.
- وجود تعارض بين الملفات المستعادة والنسخ المتتبعة.
علق Push مؤقتًا.
هذا تعليق احترازي لعملية الإرسال فقط، وليس إلغاءً للتفويض السابق.
استمر في أعمال الفحص والتحليل والإصلاح والتوثيق.
### 5.4.3 فحص Stash
نفذ:
```bash
git stash list
git stash show --stat
git status --short
git status
```
حدد:
- جميع Stashes الموجودة.
- Stash الذي يحتوي على Registry WIP.
- الملفات التي يحتوي عليها.
- الملفات المعدلة أو الجديدة.
- علاقته بالـ HEAD الحالي.
- هل سبق استعادته؟
- هل توجد نسخة منه في Working Tree؟
- هل توجد نسخة أخرى منه في Backup؟
قبل استعادة أي Stash:
1. تحقق من محتواه.
2. احفظ نسخة احتياطية آمنة.
3. سجل SHA-256 عند ملاءمة نوع الملفات.
4. سجل Stash Reference.
5. سجل HEAD الحالي.
6. تحقق من وجود مساحة عمل آمنة.
استخدم `git stash apply` عند الحاجة إلى استعادة WIP مع الاحتفاظ بالنسخة الأصلية.
لا تستخدم `git stash pop` أو `git stash drop` قبل إثبات نجاح الاستعادة وحفظ نسخة احتياطية والتحقق من عدم فقد أي ملف.
لا تحذف أي Stash أثناء التحقيق.
### 5.4.4 استعادة Registry WIP
إذا كان Registry WIP مطلوبًا:
1. استعده بطريقة آمنة.
2. قارن الملفات المستعادة بالملفات المتتبعة.
3. افحص التغييرات المتعارضة.
4. افحص الملفات الجديدة.
5. افحص الاعتماديات بين Registry وServiceProvider وOnboarding.
6. حافظ على النسخ السابقة.
7. لا تستبدل ملفًا قائمًا دون مقارنة.
8. لا تحذف أي عمل غير مفهوم.
9. وثق نتيجة الاستعادة.
إذا ظهرت Conflicts، حلها بطريقة تحافظ على جميع الأعمال السابقة ثم شغل الاختبارات المناسبة.
### 5.4.5 تحليل الاعتماديات
افحص العلاقة بين:
- Registry.
- ServiceProvider.
- Onboarding.
- CapVerSeat.
- CapVer.
- Services.
- Providers.
- Contracts.
حدد لكل اعتماد:
- الملف المستهلك.
- الملف أو Class الذي يعتمد عليه.
- هل الاعتماد مباشر أم غير مباشر؟
- هل الملف موجود في Git؟
- هل الملف Untracked؟
- هل الملف موجود في Stash؟
- هل الملف مطلوب في Runtime؟
- هل الملف مطلوب للاختبارات فقط؟
- هل الملف مطلوب في Production؟
- هل يؤدي غيابه إلى Runtime Failure؟
تحقق من:
- Autoloading.
- Service Provider Registration.
- Dependency Injection.
- Runtime Resolution.
- Composer Autoload.
- Laravel Boot.
لا تكتفِ بالبحث النصي عن أسماء الملفات.
### 5.4.6 قرار إدراج الملفات
صنف الملفات المستعادة أو الجديدة.
إذا كان الملف مطلوبًا لتشغيل Registry أو ServiceProvider أو Onboarding، فلا تترك Remote Repository معتمدًا على وجوده محليًا فقط.
إذا كان WIP غير مكتمل، فلا تدرجه في Commit لمجرد حل خطأ اعتماد مؤقت.
أكمل تنفيذه واختباره إذا كان ضمن Phase 1، أو اعزل الاعتماد بطريقة معمارية صحيحة إذا ثبت أنه غير مطلوب.
لا تستخدم Empty Classes أو ملفات وهمية لإخفاء الاعتماديات المفقودة.
### 5.4.7 التحقق بعد الاستعادة
بعد استعادة WIP وإصلاح الاعتماديات:
- افحص `git status`.
- افحص `git diff`.
- افحص `git diff --cached`.
- افحص الملفات غير المتتبعة.
- شغل الاختبارات المناسبة.
- اختبر Runtime Resolution.
- اختبر Registry Registration.
- اختبر ServiceProvider Boot.
- اختبر Onboarding Integration.
- شغل Regression Tests.
لا تسجل نجاحًا دون تشغيل الاختبارات فعليًا.
---
# القسم 6 — القرارات المعمارية المعتمدة
حافظ على القرارات السبعة الواردة في ADR-023 §8.80:
- OD-C
- OD-A-ID
- OD-H
- OD-07
- OD-10
- OD-CL
- OD-D08
لا تعكس أيًا منها.
خصوصًا:
- Projection Option B.
- T2 Metadata Only.
- عدم بناء HTTP Queries داخل Projection.
- ScientificSearchQueryBuilder مسؤول عن HTTP Query Construction.
- CapVer هو السلطة الوحيدة لمنح VERIFIED.
- غياب الأدلة يعني UNVERIFIED.
- H-2 = NOT_APPLICABLE.
- G2-26 عضو في 110.v1، لكنه لا يحصل على VERIFIED لمجرد العضوية.
- 109.v1 محفوظ Immutable.
- 110.v1 هو سجل العضوية الحالي.
- D-08 لا يغلق دون استيفاء متطلباته.
إذا ظهر تعارض فعلي مع أحد هذه القرارات، وثق التعارض ولا تعكس القرار بصمت.
---
# القسم 7 — Gate A / P1-EXIT-02
الحالة السابقة:
`BLOCKED`
الآن لديك موافقة المالك المسبقة على القرار الهندسي ضمن نطاق التفويض.
المطلوب:
1. استخراج النص الأصلي لـ Gate A.
2. مراجعة المراجع المرتبطة.
3. فحص الكود والعقود المتأثرة.
4. تحديد المشكلة المعمارية.
5. تحديد الخيارات.
6. مقارنة الخيارات.
7. اختيار الحل الأنسب.
8. تنفيذ القرار.
9. تحديث ADR-023.
10. تنفيذ التعديلات البرمجية.
11. إضافة الاختبارات المطلوبة.
12. تشغيل الاختبارات.
13. توثيق الأدلة.
14. إغلاق Blocker بعد استيفاء شروط القبول.
إذا لم يوجد النص الأصلي، اتبع §2.5.
لا تكتفِ بإعداد حزمة قرار ثم التوقف.
## 7.1 Acceptance Contract لـ Gate A
أنشئ عقد قبول يتضمن:
- النص الأصلي أو `GATE_A_TEXT_NOT_FOUND`.
- مصدر كل متطلب.
- المشكلة المعمارية.
- القرار المعتمد.
- تصنيف القرار: أصلي أو جديد.
- شروط القبول الإلزامية.
- التنفيذ.
- الأدلة.
- الاختبارات.
- نتائج الاختبارات.
- Blockers المتبقية.
- أثر القرار على P1-EXIT-02.
حالات القرار التشغيلية:
- `DECISION_PENDING`
- `DECISION_DOCUMENTED`
- `DECISION_IMPLEMENTED`
- `DECISION_VERIFIED`
هذه الحالات ليست بديلًا عن الحالة الرسمية للمعيار.
PASS فقط إذا ثبت استيفاء شروط القبول المعتمدة.
PARTIAL إذا بقيت أدلة أو اختبارات أو متطلبات ناقصة.
BLOCKED إذا بقي مانع إلزامي.
لا تفترض أن Gate يحتاج الاختبارات نفسها في جميع الحالات.
---
# القسم 8 — Gate B / P1-EXIT-03
الحالة السابقة:
`BLOCKED`
الآن لديك موافقة المالك المسبقة على القرار الهندسي ضمن نطاق التفويض.
المطلوب:
1. استخراج تعريف Gate B الأصلي.
2. مراجعة ارتباطه بـ P1-EXIT-06 وP1-EXIT-08.
3. مراجعة Access-family adapters.
4. مراجعة Source Adapter Contracts.
5. مراجعة Capability Enforcement.
6. تحديد التصميم.
7. اختيار الحل المعماري الأنسب.
8. اعتماد القرار.
9. تحديث ADR-023.
10. تنفيذ العقود والواجهات.
11. تنفيذ Adapters.
12. تنفيذ التكامل.
13. إضافة الاختبارات.
14. اختبار النجاح والفشل.
15. توثيق الأدلة.
16. إغلاق Gate B بعد استيفاء شروطه.
إذا لم يوجد النص الأصلي، اتبع §2.5.
## 8.1 Acceptance Contract لـ Gate B
أنشئ عقد قبول يتضمن:
- النص الأصلي أو `GATE_B_TEXT_NOT_FOUND`.
- المراجع المعمارية.
- المشكلة.
- القرار المعتمد.
- تصنيف القرار.
- العقود والواجهات.
- التنفيذ المطلوب.
- Adapters المتأثرة.
- التكامل.
- الأدلة.
- الاختبارات.
- النتائج.
- Blockers.
- أثر القرار على P1-EXIT-03 و06 و08.
PASS فقط بعد إثبات استيفاء جميع شروط القبول المعتمدة.
لا تعتبر اعتماد القرار وحده دليلًا على إغلاق Gate B أو المعايير التابعة.
---
# القسم 9 — P1-EXIT-01 / Projection / CSQ / Orchestrator
الحالة السابقة:
`PARTIAL`
المسار التشغيلي السابق تم تسليمه وفق التقارير التاريخية.
لا تعِد بناءه دون سبب.
المطلوب:
1. استخراج البنود d–f من تعريف P1-EXIT-01 الأصلي.
2. فحص تنفيذ كل بند.
3. ربطه بالأدلة والاختبارات.
4. تحديد النواقص.
5. تنفيذ النواقص فقط.
6. الحفاظ على Projection Runtime الحالي.
7. الحفاظ على CSQ Identity.
8. التأكد من أن Projection لا تبني HTTP Query.
9. التأكد من أن Builder مسؤول عن HTTP Query.
10. التأكد من أن Orchestrator يمرر القرارات الصحيحة.
11. اختبار Facets المدعومة وغير المدعومة.
12. اختبار غياب Capability Decision.
13. تشغيل Integration Tests.
14. تشغيل Regression Tests.
المكونات الأساسية:
- ScientificSearchQueryBuilder
- ScientificQueryCompiler
- MultiSourceScientificSearchOrchestrator
- ProjectionQueryConstraints
- ProjectionSearchOptionsBinder
- Source Adapter
- ScientificResultNormalizer
يجب الحفاظ على:
`CSQ = Canonical Scientific Question`
ممنوع اختزال CSQ إلى Keywords.
---
# القسم 10 — P1-EXIT-05 / IC / Staging / Runner
الحالة السابقة:
`BLOCKED`
حلل:
- تعريف IC الأصلي.
- الوظائف المطلوبة.
- العقود.
- Staging.
- Runner.
- التشغيل الفعلي.
- Integration.
- Error Handling.
- Evidence.
- Logging.
- Retry Behavior إذا كان مطلوبًا.
- اختبارات التشغيل.
لا تفترض أن وجود Class يعني وجود نظام تشغيلي.
نفذ المكونات الناقصة.
إذا كان IC أو Runner يحتاج إعادة تصميم، صمم ونفذ الحل الصحيح.
أنشئ اختبارات تثبت المسار التشغيلي.
لا تكتفِ بـ Unit Tests إذا كان المعيار يتطلب تشغيلًا متكاملًا.
---
# القسم 11 — P1-EXIT-06 / Gate B Dependencies
الحالة السابقة:
`BLOCKED`
استكمل المعيار بعد تطبيق قرار Gate B، مع تنفيذ الأعمال التحضيرية المستقلة بالتوازي.
المطلوب:
- تحديد العقود المطلوبة.
- استكمال التكامل.
- تنفيذ Adapters الناقصة.
- اختبار التوافق.
- اختبار حالات الفشل.
- اختبار Capability Enforcement.
- التحقق من Provenance.
- التحقق من عدم تجاوز العقود.
- توثيق Evidence.
لا تؤجل الأعمال المستقلة.
---
# القسم 12 — P1-EXIT-07 / G2-26
الحالة السابقة:
`BLOCKED`
الحاجز:
`P1-BLK-2026-11-11-G2-26-01`
المطلوب:
1. مراجعة هوية المصدر.
2. مراجعة عضويته في 110.v1.
3. تحديد Capability المطلوب إثباتها.
4. البحث عن الأدلة الرسمية الأصلية.
5. تحديد المصدر الرسمي لكل دليل.
6. حفظ Provenance.
7. تقييم Freshness.
8. تنفيذ Capability Assessment.
9. ربط القرار بالمصدر والقدرة والبعد الصحيح.
10. اختبار النتيجة.
لا تمنح VERIFIED دون Evidence صالحة.
لا تستخدم دليل مصدر آخر.
لا تعتبر وجود المصدر في Registry دليلًا على دعمه.
إذا كانت القدرة غير قابلة للإثبات، احتفظ بالحالة الصحيحة ولا تختلق إثباتًا.
---
# القسم 13 — P1-EXIT-08 / Access-family adapters
الحالة السابقة:
`BLOCKED`
المطلوب:
- تصميم العقود.
- تنفيذ Adapters.
- تنفيذ الاتصال.
- معالجة Authentication وفق التصميم المعتمد.
- معالجة الأخطاء.
- ضبط Timeouts وRetries إذا تطلب العقد ذلك.
- ضبط Capability.
- الحفاظ على Source Identity.
- الحفاظ على Provenance.
- تنفيذ Contract Tests.
- تنفيذ Integration Tests.
- تنفيذ Regression Tests.
لا تنشئ Adapters شكلية.
لا تعلن دعم مصدر دون اختبار حقيقي أو Evidence مقبولة وفق المعيار.
---
# القسم 14 — P1-EXIT-09 / Regression / PostgreSQL
الحالة السابقة:
`PARTIAL`
المطلوب:
1. استخراج المتطلب الأصلي.
2. تحديد هل PostgreSQL إلزامي في تعريف المعيار.
3. إنشاء بيئة PostgreSQL اختبارية معزولة عند الحاجة.
4. مراجعة Migrations.
5. مراجعة Schema Compatibility.
6. مراجعة Transactions.
7. مراجعة Constraints.
8. مراجعة Indexes.
9. مراجعة JSON/JSONB عند استخدامها.
10. مراجعة التعامل مع التواريخ.
11. مراجعة Concurrency إذا كانت ضمن المتطلب.
12. تشغيل الاختبارات.
13. معالجة الفشل.
14. تشغيل Regression Tests.
15. توثيق النتائج.
لا تستخدم قواعد البيانات المحمية.
لا تعتبر نجاح SQLite بديلًا عن PostgreSQL إذا كان المعيار يشترط PostgreSQL.
لا تنشئ متطلبات إضافية خارج تعريف P1-EXIT-09 الأصلي.
---
# القسم 15 — P1-EXIT-10 / Source Matrix 110.v1
الحالة السابقة:
`PARTIAL`
المطلوب:
- التحقق من 110 Canonical IDs.
- التحقق من عدم التكرار.
- التحقق من عدم الفقد.
- التحقق من عدم وجود عناصر غير متوقعة.
- الحفاظ على G2-26.
- الحفاظ على Provenance.
- الفصل بين Membership وCapability.
- الفصل بين Membership وVerification.
- اختبار اتساق البيانات.
- اختبار العلاقة مع المعايير 06–08 وفق الاعتماديات الأصلية.
ممنوع تعديل 109.v1.
لا تجعل عدم اكتمال Capability يفسد نتيجة Membership المستقلة إلا إذا نص المعيار الأصلي على ذلك.
---
# القسم 16 — P1-EXIT-11 / Final Verification
الحالة السابقة:
`NOT_RUN`
استخرج شروط التشغيل الأصلية من ADR.
لا تفترض أن تشغيله يتطلب اجتياز جميع المعايير إلا إذا ثبت ذلك.
جهز البيئة مبكرًا.
بعد اكتمال اعتمادياته:
- شغل الاختبارات.
- سجل الأوامر الفعلية.
- سجل النتائج.
- سجل Exit Code.
- عالج الفشل.
- أعد التشغيل بعد الإصلاح.
- قدم Evidence.
لا تعلن PASS دون تشغيل التحقق المطلوب فعليًا.
---
# القسم 17 — D-08 وSupplement 04
الحالة السابقة:
`BLOCKED`
النتيجة السابقة:
`SUPPLEMENT_04_NOT_FOUND`
المطلوب:
1. البحث في Git History.
2. البحث في Branches.
3. البحث في Tags.
4. البحث في Reflog عند توفره.
5. البحث في ADR References.
6. البحث في FilesystemMissionArtifactArchive.
7. البحث في Backups.
8. البحث في Historical Reports.
9. توثيق مصادر البحث.
10. توثيق النتائج.
التمييز بين:
- `SUPPLEMENT_04_NOT_FOUND`
- `REFERENCED_OR_PLANNED_BUT_NEVER_CREATED`
- `CREATED_THEN_DELETED_OR_LOST`
- `HISTORY_INDETERMINATE`
لا تختلق Supplement 04.
إذا كان هناك مستند بديل مطلوب هندسيًا، أنشئه بهوية جديدة ولا تقدمه على أنه المستند الأصلي.
---
# القسم 18 — AUD-01 إلى AUD-15
الحالة السابقة:
`UNVERIFIED_REFERENCE`
المطلوب:
- مراجعة ADRs.
- مراجعة Git History.
- مراجعة Archive.
- مراجعة التقارير.
- مراجعة Backups.
- تحديد حقيقة كل مرجع.
لا تنشئ سجلات تاريخية بأثر رجعي.
إذا لم يوجد سجل أصلي، وثق:
`UNVERIFIED_REFERENCE`
يمكن إنشاء سجل جديد للحالة الحالية مع تمييزه عن التاريخ المفقود.
---
# القسم 19 — CapVer وCapVerSeat وOnboarding
افحص CapVer بالكامل.
راجع:
- Capability Decision.
- Assessment.
- Evidence.
- Freshness.
- Source Identity.
- Dimension.
- Write Boundary.
- Service Enforcement.
- Database Write Paths.
افحص WIP:
- CapVerSeat*.
- Onboarding.
- Registry.
- ServiceProvider.
- الملفات المرتبطة بحدود الكتابة أو الموافقات.
حدد:
- ما هو مطلوب لـ Phase 1.
- ما هو غير مكتمل.
- ما هو مكرر.
- ما هو خارج النطاق.
- ما يحتاج دمجًا.
نفذ المطلوب لإغلاق Phase 1.
لا تدمج الملفات لمجرد وجودها.
لا تسمح بتجاوز CapVer.
CapVer وحده يمنح VERIFIED.
---
# القسم 20 — GAP-01 إلى GAP-08
## GAP-01 — Git Commit / Push
تحقق من:
- Commit Hash.
- Push Result.
- Remote Sync.
لا تفترض أن عملية Push السابقة نجحت دون Evidence.
## GAP-02 — Staging
نفذ:
```bash
git status --short
git diff --cached --stat
git diff --cached --name-status
```
افحص كل ملف.
## GAP-03 — Snapshot v7.1
- تحديد الملف.
- حساب SHA-256.
- أرشفته.
- الحصول على Artifact ID.
- إعادة قراءته.
- مطابقة Hash.
- التحقق من HEAD والفرع.
## GAP-04 — نتائج 67/534 مقابل 83/942
- تحديد مصدر كل نتيجة.
- تحديد الاختبارات المشمولة.
- تحديد البيئة.
- تحديد الأوامر الفعلية.
- تحديد هل المجموعتان متكاملتان أم مختلفتان في النطاق.
لا تعتبر الفرق Regression دون دليل.
## GAP-05 — Supplement 04
تحقق من الأدلة التاريخية.
إذا لم تكن كافية، استخدم:
`HISTORY_INDETERMINATE`
## GAP-06 — اعتماد P1-EXIT-11
استخرج الشرط من ADR.
إذا لم يكن موجودًا، لا تعتبره متطلبًا رسميًا.
## GAP-07 — PostgreSQL
تحقق من تعريف المعيار الأصلي.
استخدم:
- `REQUIRED`
- `NOT_REQUIRED`
- `UNKNOWN`
لا تفترض متطلبًا غير مثبت.
## GAP-08 — Archive Reconciliation
تحقق من:
- Snapshot v7.1.
- أرشفة التقارير.
- اختلاف الأعداد التاريخية.
- صحة تصنيف COUNT_ONLY_DISCREPANCY.
- Hash.
- Append-Only History.
- إمكانية الاسترجاع.
## 20.1 GAP Report
أنشئ تقريرًا موحدًا:
| GAP | الحالة السابقة | ما تم التحقق منه | الدليل | الإجراء | الحالة النهائية |
|---|---|---|---|---|---|
| GAP-01 | غير مثبت | Git/Remote | Hash + Git Output | فحص/تسليم | VERIFIED / UNVERIFIED |
| GAP-02 | غير معروض | Staging | Status + Diff | مراجعة | VERIFIED / BLOCKED |
| GAP-03 | غير مثبت | Snapshot | Artifact + Hash | أرشفة | VERIFIED / MISSING |
| GAP-04 | غير محسوم | Test Suites | Commands + Logs | مطابقة | RECONCILED / UNRESOLVED |
| GAP-05 | غير مؤكد | Supplement History | Historical Evidence | تحقيق | FOUND / NOT_FOUND / INDETERMINATE |
| GAP-06 | غير مؤكد | P1-EXIT-11 | ADR Reference | تحقق | CONFIRMED / NOT_CONFIRMED |
| GAP-07 | غير مؤكد | PostgreSQL | Original Criterion | تحقق | REQUIRED / NOT_REQUIRED / UNKNOWN |
| GAP-08 | جزئي | Archive | Counts + Hashes | مطابقة | RECONCILED / PARTIAL / BLOCKED |
أضف لكل GAP:
- مرجع ADR.
- الملفات المفحوصة.
- الأمر الفعلي.
- تاريخ التحقق.
- Hash أو Artifact ID.
- الاختبارات ذات الصلة.
- Commit Hash.
- سبب بقاء الحالة غير مكتملة.
لا تسجل VERIFIED دون Evidence قابلة للمراجعة.
---
# القسم 21 — FilesystemMissionArtifactArchive
استكمل الأرشفة بالتوازي مع التنفيذ.
المرجع:
`ADR-023 §8.51`
لا تستخدم LocalIndex كبديل.
لا تنشئ نظامًا موازيًا.
## 21.1 المطلوب حفظه
1. MASTER ENGINEERING PROMPT Revision 8.4.
2. تقارير Revision 7 غير المؤرشفة.
3. Revision 8.3 Addendum.
4. Git Safety Gate Report.
5. Dependency Audit.
6. WIP Coupling Hazard Report.
7. تقرير التحليل الحالي.
8. Project State Snapshot.
9. قرارات Gate A وGate B.
10. نتائج الاختبارات.
11. تقارير الإغلاق.
12. Evidence Records.
13. تقارير Git Delivery.
14. GAP Report.
15. Assumption Register.
## 21.2 قواعد الأرشفة
- Append-Only.
- SHA-256.
- إعادة القراءة.
- مطابقة Hash.
- الحفاظ على Versions.
- الحفاظ على Provenance.
- عدم حذف النسخ التاريخية.
لكل Artifact:
- Artifact ID.
- Title.
- Type.
- Source.
- Created At.
- SHA-256.
- Version.
- Archive Status.
- Verification Status.
## 21.3 ملفات Revision 8.3
تحقق من وجود الملفات التالية وأرشفها إذا لم تكن مؤرشفة:
`docs/architecture/science-source-expansion/2026-11-11-MASTER-ENGINEERING-PROMPT-REVISION-8.3-ADDENDUM.md`
`docs/architecture/science-source-expansion/2026-11-11-PHASE-1-DEPENDENCY-AUDIT-R83.v1.json`
`docs/architecture/science-source-expansion/2026-11-11-PHASE-1-WIP-COUPLING-HAZARD-R83.v1.json`
لا تفترض أن هذه الملفات موجودة أو مكتملة لمجرد ظهور مساراتها في شاشة Cursor.
تحقق من وجودها ومحتواها أولًا.
إذا كانت موجودة:
1. احسب SHA-256.
2. استخدم FilesystemMissionArtifactArchive.
3. أعد قراءة Artifact.
4. احصل على Artifact ID.
5. قارن Content SHA-256 مع SHA-256 الأصلي.
6. سجل النتيجة الفعلية.
إذا كانت غير موجودة، لا تنشئ بيانات تاريخية مزيفة.
أنشئ المستند الجديد فقط إذا كان مطلوبًا، مع تصنيفه كمستند جديد.
## 21.4 Project State Snapshot
يجب أن يتضمن:
- Repository.
- Branch.
- HEAD.
- Remote Sync.
- معايير Phase 1.
- Gate A.
- Gate B.
- Projection.
- Orchestrator.
- CapVer.
- Source Matrix.
- G2-26.
- D-08.
- AUD.
- PostgreSQL.
- الاختبارات.
- WIP.
- Stash.
- Blockers.
- Commits.
- Pushes.
- Assumptions.
- Next Steps.
ميز بين:
- `VERIFIED_FACT`
- `HISTORICAL_REPORT`
- `UNVERIFIED`
- `INFERRED`
- `BLOCKED`
- `NOT_RUN`
## 21.5 Archive Reconciliation
تحقق من الأعداد التاريخية:
- 133 files / 136 total.
- 632 entries / 584 candidates.
- 659 observed after import.
لا تدمجها حسابيًا دون تحديد تعريف كل رقم.
استخدم:
- `COUNT_ONLY_DISCREPANCY`
- `MISSING_ARTIFACTS`
- `CONTENT_CORRUPTION`
- `HASH_MISMATCH`
- `VERSION_INCONSISTENCY`
- `PROVENANCE_GAP`
- `IMPORT_FAILURE`
سجل النتيجة في:
`docs/archive-reconciliation.json`
إذا فشلت الطبقة:
`ARCHIVE_LAYER_BROKEN`
استخدم Fallback مؤقتًا في:
`docs/archive-fallback/`
لا تنشئ نظامًا موازيًا.
## 21.6 التحقق من الحفظ
بعد كل عملية Archive:
1. تحقق من نجاح الكتابة.
2. احصل على Artifact ID.
3. أعد قراءة المحتوى.
4. احسب SHA-256.
5. قارنه بالمحتوى الأصلي.
6. تحقق من Version.
7. تحقق من Provenance.
8. تحقق من Append-Only.
الحالة المقبولة:
`ARCHIVED_AND_VERIFIED`
لا تستخدمها دون إعادة القراءة ومطابقة Hash.
---
# القسم 22 — Progress Register وSchema
حافظ على:
- `docs/phase-1-progress.json`
- `docs/phase-1-progress.schema.json`
- `backend/tests/Unit/Architecture/Phase1ProgressSchemaTest.php`
المطلوب:
- تحديث الحالات الفعلية.
- ربط الأدلة.
- ربط الاختبارات.
- ربط Commit References.
- تحديث Blockers.
- تحديث Dependencies.
- تحديث Snapshot.
- تشغيل JSON Schema Validation.
- تنفيذ Semantic Validation.
الحالات:
- `PASS`
- `PARTIAL`
- `BLOCKED`
- `NOT_RUN`
- `NOT_APPLICABLE`
- `IN_PROGRESS`
- `VERIFIED_PENDING_FINAL_REVIEW`
لا تسجل PASS دون دليل.
لا تستخدم progress_descriptor بديلًا عن الحالة الرسمية.
ميز بين JSON Syntax وJSON Schema Validation وSemantic Validation.
---
# القسم 23 — Dependency Matrix
أنشئ أو حدث Dependency Matrix لكل المعايير.
حدد العلاقات الحقيقية بين:
- P1-EXIT-01
- P1-EXIT-02
- P1-EXIT-03
- P1-EXIT-05
- P1-EXIT-06
- P1-EXIT-07
- P1-EXIT-08
- P1-EXIT-09
- P1-EXIT-10
- P1-EXIT-11
- D-08
- AUD
استند إلى ADR والكود والعقود.
لا تفترض علاقة اعتماد لمجرد أن تقريرًا سابقًا ذكرها.
يجب أن يتضمن Dependency Matrix:
- Source Criterion.
- Dependent Criterion.
- Dependency Type.
- Evidence for Dependency.
- Blocking or Non-blocking.
- Execution Order.
- Current Status.
اعمل بالتوازي في المسارات المستقلة.
---
# القسم 24 — الاختبارات والجودة
أنشئ وشغل ما يلزم من:
- Unit Tests.
- Feature Tests.
- Integration Tests.
- Contract Tests.
- Architecture Tests.
- Regression Tests.
- Security Tests.
- PostgreSQL Tests.
- End-to-End Tests.
اختبر الحالات الطبيعية والحدية والفشل.
لا تحذف اختبارًا صحيحًا لإجبار النتائج على النجاح.
لا تستخدم قواعد البيانات المحمية.
لا تسجل اختبارًا على أنه ناجح دون تشغيله.
عند الفشل:
1. حلل السبب.
2. أصلح السبب الجذري.
3. أعد الاختبار.
4. شغل Regression Tests.
5. سجل النتائج.
افحص PHPUnit Deprecations الحالية.
أصلح ما يرتبط بالتعديلات أو يؤثر على جودة الاختبار.
لا تعتبر Deprecations فشلًا تلقائيًا.
## 24.1 توثيق الاختبارات
لكل تشغيل سجل:
- الأمر الفعلي.
- البيئة.
- Test Suite.
- عدد Tests.
- عدد Assertions.
- عدد Failures.
- عدد Errors.
- عدد Skipped.
- عدد Incomplete.
- Deprecations.
- Exit Code.
- مدة التشغيل.
- النتيجة.
لا تدمج نتائج مجموعات اختبار مختلفة في رقم واحد دون توضيح نطاقها.
## 24.2 Coupled Runtime Cluster
إذا جرى تجميع مكونات مترابطة تحت Foundation أو Runtime Cluster مثل:
`GB-FOUNDATION-01`
فهذا التجميع لا يعني تلقائيًا اجتياز جميع معايير Phase 1.
يجب تحديد:
- المكونات التي دخلت المجموعة.
- العقود المشتركة.
- الاعتماديات.
- ترتيب التشغيل.
- اختبارات المجموعة.
- اختبارات التكامل.
- نتائج Runtime Boot.
- نتائج Regression.
- معايير Phase 1 التي تغطيها المجموعة.
- المعايير التي لم تغطها.
لا تستخدم عبارة `Coupled suite green` كبديل عن نتائج تفصيلية قابلة للتحقق.
---
# القسم 25 — التفويض الكامل لعمليات Git Commit وPush
يمنح مالك المشروع Cursor تفويضًا صريحًا ومسبقًا لتنفيذ Commit وPush تلقائيًا دون طلب إذن إضافي عند استيفاء شروط الجودة والسلامة.
## 25.1 Commit Authorization
يسمح بـ Commit عندما:
1. التغييرات مرتبطة بنطاق Phase 1.
2. التنفيذ متوافق مع ADR-023.
3. الاختبارات المطلوبة ناجحة.
4. Regression ذات الصلة ناجحة.
5. تمت مراجعة الكود وGit Diff.
6. لا توجد أخطاء معروفة غير معالجة.
7. لا توجد Secrets أو Credentials أو `.env`.
8. لا توجد قواعد بيانات محمية.
9. لا توجد WIP غير مراجعة ضمن Staging.
10. الوثائق متوافقة مع التنفيذ.
11. تم تحديد الملفات باستخدام Scoped Staging.
12. تم التحقق من الاعتماديات اللازمة.
إذا استوفت التغييرات الشروط، نفذ Commit مباشرة.
## 25.2 Push Authorization
يسمح بـ Push بعد نجاح Commit والتحقق من:
1. الفرع المستهدف.
2. Remote Repository.
3. عدم وجود تعارض غير معالج.
4. نجاح الاختبارات المطلوبة.
5. مراجعة Commit.
6. عدم وجود بيانات محمية أو أسرار.
7. اكتمال الاعتماديات المطلوبة.
8. عدم اعتماد Commit على ملفات محلية غير موجودة في Remote.
نفذ Push تلقائيًا دون طلب إذن إضافي عند استيفاء الشروط.
## 25.3 Scoped Staging
ممنوع:
```bash
git add .
git add -A
```
قبل Commit افحص:
```bash
git status --short
git diff --cached --stat
git diff --cached --name-status
git diff --cached
```
راجع كل ملف في Staging.
## 25.4 قواعد Commit
استخدم رسائل Commit واضحة.
قسم العمل إلى Commits منطقية عند الحاجة.
لا تعدل تاريخ Git السابق.
ممنوع:
- `git reset --hard`
- `git clean -fd`
- `git clean -fdx`
- `git push --force`
- `git push --force-with-lease`
## 25.5 معالجة Push Conflicts
إذا ظهر اختلاف مع Remote:
1. افحص السبب.
2. حدد الالتزامات والملفات المتعارضة.
3. حافظ على الأعمال المحلية والبعيدة.
4. نفذ الدمج الآمن.
5. لا تستخدم Force Push.
6. أعد الاختبارات المتأثرة.
7. تحقق من سلامة النتيجة.
8. أرسل التغييرات بعد استيفاء شروط السلامة.
إذا تعذر حل التعارض بأمان، لا تخاطر بالتاريخ أو WIP.
أكمل الأعمال المستقلة.
## 25.6 التوثيق بعد Commit وPush
بعد كل عملية ناجحة سجل:
- Commit Hash.
- Commit Subject.
- Branch.
- الملفات الملتزم بها.
- نتائج الاختبارات.
- Push Result.
- Remote Branch.
- Sync Status.
- تاريخ ووقت التنفيذ.
حدث:
`docs/phase-1-progress.json`
واحفظ تقرير Git Delivery داخل FilesystemMissionArtifactArchive.
## 25.7 التفويض لا يلغي الحماية
التفويض لا يسمح بـ:
- Force Push.
- حذف WIP.
- حذف قواعد البيانات.
- الالتزام بالأسرار.
- الالتزام بملفات غير مراجعة.
- تجاوز الاختبارات.
- إخفاء الاختبارات الفاشلة.
- الالتزام بتغييرات خارج نطاق Phase 1 دون مبرر.
## 25.8 تقسيم الالتزامات
يجوز الالتزام بالتغييرات المكتملة المستقلة حتى لو بقيت معايير أخرى BLOCKED، بشرط عدم وجود اعتماد تقني يمنع ذلك.
لا تجعل Blocker مستقلًا يمنع تسليم تغيير مكتمل وآمن.
## 25.9 DELIVERABLE_READY
استخدم:
`DELIVERABLE_READY`
للدلالة على أن مجموعة تغييرات مكتملة وجاهزة للالتزام Git.
هذه ليست حالة PASS ولا تعني إغلاق معيار أو اكتمال Phase 1.
## 25.10 Git Push Safety Gate
قبل كل Push نفذ مراجعة مستقلة.
### A. فحص الحالة
```bash
git status --short
git status
git branch --show-current
git rev-parse HEAD
git log -5 --oneline
git stash list
git diff --stat
git diff --cached --stat
git diff --name-status
git diff --cached --name-status
```
### B. تحديد Remote Branch
لا تفترض أن `origin HEAD` هو الفرع المطلوب.
حدد:
- Current Branch.
- Upstream Branch.
- Remote Name.
- Remote Branch.
- Target Branch.
تحقق من أن الفرع المستهدف هو الفرع المقصود.
إذا لم يوجد Upstream، لا تخمن الفرع البعيد.
استخدم اسم الفرع الصريح بعد التحقق.
### C. فحص الاعتماديات
تحقق من أن جميع الملفات المطلوبة لتشغيل Commit:
- موجودة.
- صحيحة.
- متتبعة في Git إذا كانت مطلوبة في Remote.
- ليست موجودة محليًا فقط.
- ليست محفوظة في Stash دون استعادة.
- ليست مستبعدة بطريقة تسبب Runtime Failure.
### D. فحص Commit
راجع:
```bash
git show --stat --oneline HEAD
git show --name-status --format=fuller HEAD
```
حدد ما إذا كان Commit يحتوي على اعتماديات ناقصة.
### E. شروط السماح بـ Push
يسمح بـ Push عندما:
1. تم حل اعتماديات الملفات المطلوبة.
2. لا توجد ملفات Runtime لازمة غير موجودة في Remote Commit.
3. تم التحقق من Registry/ServiceProvider/Onboarding إذا كانت ضمن التغيير.
4. الاختبارات المطلوبة ناجحة.
5. تمت مراجعة الملفات.
6. لا توجد Secrets أو قواعد بيانات محمية.
7. لا يوجد WIP غير مراجع.
8. لا يوجد Stash مهم مفقود.
9. لا يوجد Git Conflict غير محلول.
10. لا توجد حاجة إلى Force Push.
### F. حالات Push Safety
- `PUSH_SAFETY_PENDING`
- `PUSH_SAFETY_BLOCKED`
- `PUSH_SAFETY_VERIFIED`
- `PUSH_COMPLETED`
- `PUSH_FAILED`
- `REMOTE_SYNC_VERIFIED`
لا تسجل PUSH_SAFETY_VERIFIED دون إتمام الفحوص.
لا تسجل PUSH_COMPLETED دون نتيجة Push فعلية.
لا تسجل REMOTE_SYNC_VERIFIED دون مقارنة Remote فعلية.
## 25.11 التحقق الحقيقي من Push Exit Code
**هذا القسم إلزامي بسبب اكتشاف خطر في سجل Git Safety Gate السابق.**
ظهر في تنفيذ سابق كود يسجل قيمًا ثابتة مثل:
```php
$d['push_result'] = [
    'exit' => 0,
];
$d['status'] = 'PUSH_COMPLETED';
$d['remote_sync_status'] = 'REMOTE_SYNC_VERIFIED';
```
ممنوع تكرار هذا الأسلوب.
لا تضع:
- `exit = 0` ثابتًا.
- `PUSH_COMPLETED` قبل معرفة النتيجة.
- `REMOTE_SYNC_VERIFIED` قبل التحقق من Remote.
يجب التقاط Exit Code الحقيقي مباشرة بعد تنفيذ Push.
مثال PowerShell توضيحي:
```powershell
git push origin HEAD
$pushExit = $LASTEXITCODE
Write-Output "PUSH_EXIT=$pushExit"
```
لا تنفذ أي أمر آخر بين `git push` والتقاط `$LASTEXITCODE`.
احفظ النتيجة الفعلية في متغير مستقل.
إذا كانت العملية ضمن Script أو PHP Wrapper، مرر Exit Code الحقيقي من العملية الأصلية.
لا تستبدله بقيمة ثابتة أو افتراضية.
### 25.11.1 إذا فشل Push
إذا كان:
`pushExit != 0`
سجل:
- Exit Code الحقيقي.
- رسالة الخطأ.
- الفرع.
- Remote.
- HEAD المحلي.
- حالة Remote الحالية.
واستخدم:
`PUSH_FAILED`
أو حالة فشل أدق.
لا تسجل:
`PUSH_COMPLETED`
ولا تسجل:
`REMOTE_SYNC_VERIFIED`
لا تعتبر فشل Push فشلًا في Commit نفسه.
احتفظ بالـ Commit المحلي وأكمل الأعمال المستقلة.
### 25.11.2 إذا نجح Push
إذا كان:
`pushExit == 0`
لا تسجل REMOTE_SYNC_VERIFIED فورًا.
نفذ Fetch للفرع البعيد المقصود، ثم قارن الحالة المحلية بالبعيدة.
مثال توضيحي:
```powershell
git fetch origin
$localHead = (git rev-parse HEAD).Trim()
$remoteHead = (git rev-parse origin/phase-18-m18-ai-marketing-communications).Trim()
$counts = git rev-list --left-right --count HEAD...origin/phase-18-m18-ai-marketing-communications
```
استخدم الفرع البعيد الذي تم التحقق منه فعليًا، ولا تفترض أن المثال يطابق جميع الحالات.
شروط `REMOTE_SYNC_VERIFIED`:
1. نجاح Push الفعلي.
2. نجاح Fetch.
3. Local HEAD يساوي Remote HEAD.
4. Local Ahead = 0.
5. Local Behind = 0.
6. الفرع المحلي والبعيد هما الفرع المقصود.
7. لا توجد أخطاء في عملية المقارنة.
إذا لم تتحقق الشروط، لا تسجل Remote Sync Verified.
### 25.11.3 سجل النتيجة
يجب أن يحتوي سجل Git Safety Gate على:
- `push_exit_code`
- `push_stdout`
- `push_stderr`
- `local_branch`
- `remote_name`
- `remote_branch`
- `local_head`
- `remote_head`
- `ahead_count`
- `behind_count`
- `fetch_exit_code`
- `push_status`
- `remote_sync_status`
- `verified_at`
لا تملأ الحقول التي لا يمكن إثباتها.
لا تخترع قيمًا.
### 25.11.4 الحالات النهائية
`PUSH_COMPLETED` تعني نجاح أمر Push فعليًا.
`REMOTE_SYNC_VERIFIED` تعني أن مقارنة Local/Remote بعد Fetch أثبتت التطابق.
قد يكون Push ناجحًا دون أن يصبح Remote Sync Verified إذا لم تنجح المقارنة.
لا تخلط الحالتين.
### 25.11.5 تحديث الملف بعد التحقق
حدّث ملف Git Safety Gate بعد الحصول على النتائج الفعلية.
لا تكتب قيم النجاح قبل اكتمال العملية.
لا تكتب حالة نهائية قبل اكتمال التحقق.
إذا فشل تحديث ملف السجل بعد نجاح Push، لا تعِد Push لمجرد تحديث السجل.
أصلح السجل وسجل نتيجة Push الأصلية من الأدلة المتاحة.
لا تدّعِ نجاح الأرشفة أو التحقق إذا فشلت العملية.
## 25.12 Commit/Push للأعمال المستقلة
إذا كان هناك Blocker مستقل:
- يجوز Commit للتغييرات المكتملة والمؤهلة.
- يجوز Push إذا كانت الاعتماديات المطلوبة متاحة في Remote.
- لا تنتظر إغلاق جميع معايير Phase 1.
- لا ترسل إصدارًا يعتمد على ملفات محلية غير ملتزم بها.
- لا تسمح بتأثير WIP غير مكتمل على سلامة Remote.
---
# القسم 26 — معالجة جميع الفجوات السابقة
راجع:
- P1-EXIT-01 d–f.
- Gate A.
- Gate B.
- IC / Staging / Runner.
- PostgreSQL Capability.
- G2-26 Evidence.
- Access-family adapters.
- P1-EXIT-10.
- P1-EXIT-11.
- D-08.
- AUD-01…15.
- CapVerSeat.
- Onboarding.
- Registry.
- ServiceProvider.
- Snapshot v7.1.
- Archive Reconciliation.
- اختلاف أرقام الاختبارات.
- Git Delivery Evidence.
- Registry WIP Coupling Hazard.
- Push Exit Code Verification.
حدد كل فجوة.
أصلح ما يمكن إصلاحه ضمن التفويض.
إذا كانت الفجوة تعتمد على مصدر خارجي أو دليل غير متوفر، حدد المطلوب بدقة ولا تختلقه.
---
# القسم 27 — معايير PASS
لكل P1-EXIT:
استخرج التعريف الأصلي.
أنشئ Acceptance Checklist تشمل:
- Original Requirement.
- Source Reference.
- Mandatory Conditions.
- Required Evidence.
- Required Tests.
- Actual Implementation.
- Verification Result.
- Final Criterion Status.
لا تخترع تعريفًا جديدًا.
لا تفرض أعداد اختبارات اعتباطية.
لا تستخدم نسبًا مئوية بديلًا عن استيفاء المتطلبات.
لا تعلن PASS إذا بقي شرط إلزامي ناقص.
نجاح الاختبارات شرط ضروري حيث يتطلب المعيار اختبارات، لكنه ليس بديلًا عن استيفاء بقية المتطلبات والأدلة.
## 27.1 Acceptance Baseline Lock
قبل التنفيذ الفعلي:
1. استخرج تعريف كل معيار من مصدره الأصلي.
2. سجل النص أو المرجع الدقيق.
3. سجل الشروط الإلزامية.
4. سجل الأدلة المطلوبة.
5. سجل الاختبارات المطلوبة.
6. سجل الاعتماديات.
7. اربط كل شرط بمصدره.
أنشئ Acceptance Baseline موثقًا.
لا تعدل معايير القبول لتتناسب مع التنفيذ.
لا تحذف شرطًا صعبًا.
لا تحول شرطًا إلزاميًا إلى اختياري دون قرار معتمد.
إذا ظهر تعارض بين تعريفين:
- وثق التعارض.
- حدد المصدر الأعلى سلطة.
- لا تحسمه بصمت.
- استخدم موافقة المالك المسبقة للقرار الهندسي ضمن نطاقها.
- ارفع أي تغيير في معيار القبول نفسه للمراجعة النهائية.
احتفظ بتاريخ تغييرات Acceptance Baseline.
## 27.2 Assumption Register
أنشئ سجلًا مستقلًا لكل افتراض هندسي غير مثبت.
يجب أن يتضمن:
- Assumption ID.
- Description.
- Source or Reason.
- Confidence.
- Impact.
- Affected Criteria.
- Verification Method.
- Owner Decision Required.
- Status.
- Date.
صنف:
- `VERIFIED`
- `UNVERIFIED`
- `INFERRED`
- `REJECTED`
- `REQUIRES_OWNER_REVIEW`
لا تحول الافتراض إلى حقيقة معمارية لمجرد استخدامه في التنفيذ.
---
# القسم 28 — خطة التنفيذ والأولويات
نفذ بالترتيب التالي مع السماح بالتوازي عندما تكون المسارات مستقلة.
## المرحلة A — التحقق والحماية
- فحص Git.
- فحص WIP.
- فحص Stash.
- فحص قواعد البيانات.
- فحص الأرشيف.
- التحقق من GAP-01 إلى GAP-08.
- معالجة Registry Dependency Hazard.
## المرحلة B — القرارات
- استخراج Gate A.
- استخراج Gate B.
- حسمهما بموجب موافقة المالك المسبقة.
- تحديث ADR-023.
- تحديد أثرهما على المعايير التابعة.
## المرحلة C — التنفيذ
- P1-EXIT-01.
- P1-EXIT-05.
- P1-EXIT-06.
- P1-EXIT-07.
- P1-EXIT-08.
- P1-EXIT-09.
- P1-EXIT-10.
- P1-EXIT-11.
نفذ الأعمال المستقلة بالتوازي.
## المرحلة D — الأدلة التاريخية
- D-08.
- AUD-01…15.
- Supplement 04.
- Evidence Provenance.
## المرحلة E — الأرشفة
- Revision 8.4.
- تقارير Revision 7.
- Revision 8.3 Addendum.
- Dependency Audit.
- WIP Coupling Hazard.
- Snapshot v7.1.
- Snapshot جديد.
- Reconciliation.
- التحقق من Hash.
## المرحلة F — الاختبارات
- Unit.
- Integration.
- Contract.
- Regression.
- PostgreSQL.
- Architecture.
- Security.
- Registry/Onboarding Integration.
## المرحلة G — Git Delivery
- مراجعة الملفات.
- Scoped Staging.
- الاختبارات.
- Commit.
- Push.
- Fetch.
- Remote Sync Verification.
نفذ Git Delivery تلقائيًا عند استيفاء §25.
## المرحلة H — المراجعة النهائية
- مراجعة جميع المعايير.
- مراجعة الأدلة.
- مراجعة Git.
- مراجعة WIP.
- مراجعة Stash.
- مراجعة الأرشيف.
- إعداد التقرير الشامل.
## 28.1 ترتيب الأولويات
### P0 — سلامة المستودع وخط الأساس
- HEAD والفرع وRemote.
- GAP-01.
- GAP-02.
- WIP.
- Stash.
- قواعد البيانات المحمية.
- GAP-03.
- GAP-08.
- Registry Dependency Hazard.
### P1 — القرارات والاعتماديات الحرجة
- Gate A.
- Gate B.
- GAP-06.
- Acceptance Baseline.
- Dependency Matrix.
### P2 — التنفيذ والتحقق المستقل
- P1-EXIT-01.
- GAP-04.
- GAP-07.
- P1-EXIT-09.
- P1-EXIT-10.
- الأعمال المستقلة من P1-EXIT-05.
### P3 — التنفيذ التابع للقرارات
- P1-EXIT-05.
- P1-EXIT-06.
- P1-EXIT-08.
- G2-26 / P1-EXIT-07.
- أي Adapter أو Capability يعتمد على Gate A/B.
### P4 — التحقيقات التاريخية
- D-08.
- Supplement 04.
- AUD-01…15.
- GAP-05.
- مراجعة الأدلة التاريخية.
### P5 — التحقق النهائي والتسليم
- P1-EXIT-11 وفق تعريفه الأصلي.
- Regression الشامل.
- تحديث الوثائق.
- Archive Verification.
- Commit وPush.
- Remote Sync.
- Final Review.
لا تنفذ ترتيبًا جامدًا إذا أثبتت Dependency Matrix وجود ترتيب أفضل.
لا تؤخر الأعمال المستقلة.
## 28.2 تقديرات زمنية مشروطة
التقديرات استرشادية وليست التزامًا زمنيًا:
| المسار | تقدير أولي |
|---|---:|
| P0 — فحص وحماية | 30–90 دقيقة |
| P1 — قرارات وقبول | 2–5 ساعات |
| P2 — الأعمال المستقلة | 4–12 ساعة |
| P3 — التنفيذ التابع | 6–20 ساعة أو أكثر |
| P4 — التحقيق التاريخي | 2–8 ساعات أو أكثر |
| P5 — الاختبارات والتسليم | 3–8 ساعات أو أكثر |
بعد فحص المستودع، أنشئ:
- Optimistic.
- Expected.
- High-Risk.
لا تختصر التنفيذ لإجبار العمل على الانتهاء ضمن تقدير زمني.
## 28.3 هدف الجلسة الأولى
الهدف هو إنشاء نقطة انطلاق تنفيذية موثوقة وإنجاز أكبر قدر ممكن من العمل المؤهل.
الحد الأدنى:
1. فحص المستودع والفرع وHEAD وRemote.
2. حماية WIP وStash وقواعد البيانات.
3. التحقق من GAP-01 و02 و03 و08.
4. معالجة Git Dependency Hazard.
5. استخراج نص Gate A وGate B أو توثيق غيابه.
6. حسم القرارات التي يمكن حسمها ضمن التفويض.
7. تحديث Acceptance Baseline وDependency Matrix.
8. بدء P1-EXIT-01 أو معيار مستقل جاهز.
9. تشغيل الاختبارات المناسبة.
10. Commit وPush للتغييرات المكتملة والمؤهلة.
11. حفظ Checkpoint وSnapshot.
12. تقديم تقرير.
لا تفرض إغلاق Gate A وGate B وP1-EXIT-01 جميعًا في الجلسة الأولى.
استخدم 8–12 ساعة كنقطة مراجعة عند ملاءمة بيئة التنفيذ، لا كموعد إيقاف قسري.
## 28.4 العودة التلقائية لاستكمال الماستر برومبت من نقطة التوقف
### 28.4.1 المبدأ
معالجة Git WIP / Stash / Dependency Hazard مهمة حماية عاجلة ومحددة.
ليست بديلًا عن Phase 1.
بعد معالجة الخطر يجب العودة تلقائيًا إلى استكمال Revision 8.4 من آخر نقطة توقف موثقة.
ممنوع:
- إعادة بدء المشروع من الصفر.
- إعادة تنفيذ أعمال مكتملة دون سبب.
- إعادة تدقيق معماري شامل سبق إغلاقه دون سبب جديد.
- اعتبار معالجة Git Hazard إغلاقًا لـ Phase 1.
- ترك بقية المهام دون متابعة.
### 28.4.2 حفظ نقطة التوقف قبل المعالجة
سجل:
- Current HEAD.
- Current Branch.
- Working Tree Status.
- Staging Status.
- Stash Inventory.
- Last Completed Task.
- Last Verified Criterion.
- Open Tasks.
- Blockers.
- Last Project State Snapshot.
- Last Archive Artifact ID.
إذا كانت هناك تعديلات غير ملتزم بها، احفظها ولا تستبدلها.
### 28.4.3 تحديد نقطة التوقف بعد المعالجة
بعد Git Safety Review:
1. اقرأ Revision 8.4.
2. اقرأ آخر Project State Snapshot.
3. اقرأ `docs/phase-1-progress.json`.
4. اقرأ Gap Closure Matrix.
5. اقرأ Blocker Register.
6. اقرأ Acceptance Baseline.
7. اقرأ Assumption Register.
8. اقرأ Dependency Matrix.
9. قارن الحالة الفعلية بالحالة المحفوظة.
10. حدد آخر مهمة مكتملة بالأدلة.
11. حدد أول مهمة غير مكتملة حسب الأولويات والاعتماديات.
لا تفترض أن آخر نقطة توقف هي آخر أمر ظهر في واجهة Cursor.
استخدم الملفات والسجلات وGit Evidence لتحديدها.
### 28.4.4 ترتيب الاستئناف
بعد معالجة Git Hazard:
- أكمل P0 إذا كان غير مكتمل.
- استكمل Gate A وGate B.
- استكمل P1-EXIT-01.
- استكمل P1-EXIT-05 إلى P1-EXIT-11 حسب Dependency Matrix.
- استكمل GAP-01 إلى GAP-08.
- استكمل D-08 وAUD.
- استكمل Archive Reconciliation.
- نفذ الاختبارات النهائية.
- نفذ Commit وPush للأعمال المؤهلة.
- أكمل مراجعة Phase 1.
لا تعِد فتح مهمة مغلقة إلا إذا ظهر دليل جديد يثبت وجود عيب أو نقص.
### 28.4.5 إذا بقي Push Safety Blocked
- لا تنفذ Push.
- لا تخاطر بـ Git History.
- لا تفقد WIP.
- وثق المانع.
- استمر في الأعمال المستقلة.
- لا تجعل Blocker واحدًا يمنع كل التنفيذ.
إذا كانت هناك تغييرات مكتملة وآمنة، نفذ Commit وفق §25.
### 28.4.6 تقرير استعادة التنفيذ
بعد معالجة الخطر والعودة إلى الماستر برومبت، قدم تقريرًا يتضمن:
1. سبب Git Hazard.
2. ما كان محفوظًا في Stash.
3. ما تم استعادته.
4. الملفات المتأثرة.
5. اعتماديات Registry/ServiceProvider/Onboarding.
6. الاختبارات ونتائجها.
7. هل أصبح Push آمنًا؟
8. Commit Hash وPush Result إن تم التنفيذ.
9. آخر نقطة توقف في Revision 8.4.
10. المهمة التالية التي تم استئنافها.
---
# القسم 29 — التقرير النهائي المطلوب
قدم تقريرًا هندسيًا شاملًا باللغة العربية.
## A. الملخص التنفيذي
ما تم إنجازه وما بقي.
## B. Git
- Initial HEAD.
- Final HEAD.
- Branch.
- Remote Sync.
- Staged Files.
- Modified Files.
- Untracked Files.
- Commit Status.
- Push Status.
- Actual Push Exit Code.
## C. Gate A
- النص الأصلي أو حالة عدم العثور عليه.
- المشكلة.
- القرار المعتمد.
- تصنيف القرار.
- مبرراته.
- أثره.
- الملفات المعدلة.
- الاختبارات.
- Commit.
- حالة P1-EXIT-02.
## D. Gate B
نفس التفاصيل.
## E. P1-EXIT-01 إلى P1-EXIT-11
لكل معيار:
- الحالة السابقة.
- الحالة النهائية.
- المتطلبات.
- التنفيذ.
- الأدلة.
- الاختبارات.
- الملفات.
- Commit.
- Blockers.
## F. G2-26
Evidence Package والنتيجة.
## G. D-08 وAUD
التحقيق التاريخي والنتائج.
## H. PostgreSQL
البيئة والاختبارات والنتائج.
## I. CapVer
حدود الكتابة والإنفاذ والاختبارات.
## J. Registry / ServiceProvider / Onboarding
- WIP.
- Stash.
- Dependency Audit.
- الملفات المستعادة.
- الملفات المطلوبة.
- اختبارات التكامل.
## K. Archive
Artifact IDs وHashes وحالة الاسترجاع.
## L. الاختبارات
الأوامر الفعلية والنتائج وExit Codes.
## M. GAP-01 إلى GAP-08
الحالة السابقة، التحقق، الدليل، الإجراء، الملفات، الاختبارات، والحالة النهائية.
## N. Acceptance Baseline
المعايير ومصادرها والتعارضات وحالة التحقق.
## O. Assumption Register
الافتراضات ومصادرها وأثرها وطريقة التحقق.
## P. Git Delivery
- Commit Hashes.
- Subjects.
- Files.
- Tests.
- Push Exit Codes.
- Remote HEAD.
- Local HEAD.
- Ahead/Behind.
- Remote Sync.
## Q. التصنيف النهائي
`PHASE_1_COMPLETE`
أو:
`PHASE_1_GAP_CLOSURE_PARTIAL`
## R. نقطة التوقف
- آخر HEAD.
- آخر Snapshot.
- Archive Artifact IDs.
- Blockers.
- Next Steps.
لا تستخدم COMPLETE إذا بقي شرط إلزامي غير مستوفى.
---
# القسم 30 — استمرارية العمل والتوجيه التنفيذي النهائي
أنت مفوض من مالك WSA-Enterprise تفويضًا كاملًا في التصميم المعماري والبرمجة والتنفيذ والاختبار والتوثيق والأرشفة وإغلاق Phase 1.
موافقة المالك على Gate A وGate B والقرارات الهندسية التابعة لهما ممنوحة مسبقًا ضمن نطاق هذه المهمة.
أنت مفوض بتنفيذ Commit وPush تلقائيًا دون طلب إذن إضافي متى اجتازت التغييرات الاختبارات المطلوبة واستوفت شروط الحماية والجودة.
لا تتوقف لطلب موافقة متكررة على القرارات التقنية أو عمليات Git المؤهلة.
اعمل كمهندس برمجيات ومعماري رئيسي:
- صمم.
- أعد التصميم عند الضرورة.
- اكتب الأكواد.
- نفذ.
- اختبر.
- أصلح.
- وثق.
- أرشف.
- Commit.
- Push.
- تحقق من Remote.
- أغلق الفجوات.
حافظ على الأعمال السابقة الصحيحة.
لا تحذف WIP.
لا تحذف Stash غير متحقق منه.
لا تمس قواعد البيانات المحمية.
لا تختلق Evidence.
لا تختلق تاريخًا أو مستندات.
لا تتجاوز Capability Verification.
لا تستبدل FilesystemMissionArtifactArchive بـ LocalIndex.
لا تحذف نسخ الأرشيف.
لا تستخدم Force Push.
لا تسجل Push أو Remote Sync ناجحًا دون دليل فعلي.
لا تبدأ Phase 2 أو Phase 3.
## 30.1 استمرارية الجلسات
عند استئناف العمل:
1. اقرأ `docs/phase-1-progress.json`.
2. اقرأ Gap Closure Matrix.
3. اقرأ Blocker Register.
4. اقرأ Acceptance Baseline.
5. اقرأ Assumption Register.
6. اقرأ Dependency Matrix.
7. تحقق من Git HEAD.
8. تحقق من Remote Sync.
9. افحص Stash.
10. استرجع آخر Project State Snapshot.
11. تحقق من Hash.
12. قارن Snapshot بالحالة الفعلية.
13. حدد آخر Checkpoint.
14. استكمل دون إعادة الأعمال المكتملة.
## 30.2 التقرير بعد كل جلسة
قدم تقريرًا يتضمن:
1. ما أنجز والأدلة.
2. ما بقي وسببه.
3. الاختبارات ونتائجها.
4. Commits وPushes.
5. حالة WIP وStash.
6. حالة الأرشيف.
7. حالة GAPs.
8. حالة Gate A/B.
9. حالة Remote Sync.
10. نقطة التوقف.
11. الخطوة التالية.
## 30.3 الإغلاق النهائي
إذا استوفت جميع المعايير الإلزامية:
`PHASE_1_COMPLETE`
وإلا:
`PHASE_1_GAP_CLOSURE_PARTIAL`
لا تعتبر Archive Status بديلًا عن معايير Phase 1 الأصلية.
لا تعتبر نجاح Commit أو Push دليلًا على اكتمال Phase 1.
لا تعتبر نجاح الاختبارات وحده كافيًا للإغلاق.
لا تعتبر وجود الملفات دليلًا على اكتمال التنفيذ.
يجب أن يتوفر:
- تنفيذ صحيح.
- عقود صحيحة.
- تكامل فعلي.
- أدلة صالحة.
- اختبارات ناجحة.
- توثيق متسق.
- حماية البيانات.
- سجل تقدم صحيح.
- أرشفة قابلة للاسترجاع.
- Git Delivery موثق.
- Remote Sync متحقق منه.
## 30.4 أمر التنفيذ الحالي
ابدأ من الحالة الفعلية التي وصل إليها Cursor.
لا تعد إلى بداية المشروع.
لا تعِد تنفيذ الأعمال المكتملة.
ابدأ أولًا بمعالجة Git Push Verification issue الظاهر في التقرير الأخير.
صحح تسجيل Exit Code بحيث يعتمد على نتيجة الأمر الفعلية.
صحح تسجيل PUSH_COMPLETED وREMOTE_SYNC_VERIFIED بحيث لا يحدث قبل إثباتهما.
افحص Registry WIP وStash وOnboarding dependencies.
تحقق من Runtime Cluster ونتائج الاختبارات الفعلية.
أكمل أرشفة ملفات Revision 8.3 وتحقق من SHA-256.
بعد اجتياز Git Safety Gate:
1. نفذ Push المؤهل تلقائيًا.
2. نفذ Fetch.
3. قارن Local HEAD مع Remote HEAD.
4. سجل Ahead/Behind.
5. سجل Remote Sync فقط إذا ثبت التطابق.
6. أرشف تقرير Git Delivery.
بعد ذلك ارجع تلقائيًا إلى آخر نقطة توقف فعلية في Revision 8.2/8.3، واستكمل جميع المهام المتبقية وفق Revision 8.4.
لا تجعل معالجة Git Hazard توقف المشروع.
لا تجعل Push غير الآمن يسبب فقد العمل المحلي.
لا تبدأ Phase 2 أو Phase 3.
استمر حتى إغلاق Phase 1 أو الوصول إلى مانع خارجي حقيقي موثق لا يمكن تجاوزه هندسيًا.
**ابدأ التنفيذ الآن.**
---
# END OF MASTER ENGINEERING PROMPT — REVISION 8.4
**هذه النسخة تحل محل Revision 8.2 وRevision 8.3 Addendum بوصفها المرجع التنفيذي الموحد الحالي.**
جميع القرارات والتفويضات والحمايات السابقة تبقى سارية، ما لم يرد تعديل صريح في هذه النسخة.
