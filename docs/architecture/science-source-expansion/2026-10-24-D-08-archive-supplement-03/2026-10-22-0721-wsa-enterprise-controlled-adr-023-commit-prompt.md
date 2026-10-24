<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17661). Message timestamp as recorded in the conversation: Thursday, Oct 22, 2026, 7:21 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17662, 17663; it is archived once. -->

---
# WSA-Enterprise — Controlled ADR-023 Commit
## الهدف
إنشاء Commit مستقل للتغييرات المحلية في الملف:
`docs/architecture/ADR-023-scientific-source-expansion.md`
مع الحفاظ على جميع القرارات والسجلات والمحادثات والتقارير والـ Prompts الموجودة، وعدم إدخال أي ملف آخر في الالتزام.
## بيانات المشروع
- Repository: `wsa-platform/WSA-Enterprise`
- Original worktree: `G:\WSA-Enterprise`
- Branch: `phase-18-m18-ai-marketing-communications`
- Local HEAD المتوقع: `dffeed5a7ace309eadddffef02b278bfc5e0fffa`
- Published merge commit: `168edcc79a8eb377a0d69f9c1ea3ccb979b01670`
- Target file: `docs/architecture/ADR-023-scientific-source-expansion.md`
## التفويض
مصرّح بفحص ملف ADR-023 وإعداد Commit مستقل له فقط، بشرط اجتياز جميع بوابات السلامة أدناه.
لا تنفذ Push في هذه المهمة. لا تنفذ Merge أو Rebase أو Pull، ولا تغيّر HEAD في الشجرة الأصلية، ولا تحذف أو تعيد كتابة أي سجل.
## المرحلة 1 — فحص الحالة
1. تحقق من المستودع والفرع وHEAD.
2. تحقق من أن HEAD ما زال `dffeed5a7ace309eadddffef02b278bfc5e0fffa`.
3. افحص حالة Git الحالية، وسجّل قائمة الملفات المعدّلة وغير المتعقبة.
4. افحص `git diff -- docs/architecture/ADR-023-scientific-source-expansion.md` وفرق الملف مقابل HEAD.
5. لا تعدّل أي ملف في هذه المرحلة.
إذا تغيّر HEAD أو الفرع أو كانت هناك عملية Git جارية، فتوقف.
## المرحلة 2 — مراجعة ADR-023
1. حدد بدقة جميع التغييرات المحلية غير الملتزمة في ADR-023.
2. تحقق من أن المحتوى يتضمن السجلات والقرارات والمحادثات والتقارير التي يجب الاحتفاظ بها.
3. راجع تصادم ترقيم الأقسام 8.27–8.29 بين النسخة المحلية والنسخة المنشورة.
4. لا تعِد ترقيم الأقسام تلقائيًا، ولا تحذف أو تختصر محتوى.
5. إذا كان الملف المحلي يحتوي أقسامًا مكررة دلاليًا أو إحالات متعارضة، فوثّقها وتوقف قبل تعديل المحتوى؛ يحتاج الإصلاح التحريري إلى تفويض منفصل.
6. اعرض ملخصًا دقيقًا للتغييرات، ثم تأكد من أن إنشاء Commit للنسخة الحالية لن يؤدي إلى حذف أي محتوى مطلوب.
## المرحلة 3 — حماية التعديلات الأخرى
1. لا تستخدم `git add -A` أو `git add .`.
2. لا تجهّز أي ملف آخر.
3. لا تستخدم `stash` أو `reset` أو `clean` أو `restore`.
4. لا تلمس الملفات غير المتعقبة أو ملفات E2E أو ملفات الاعتماد الحساسة.
5. لا تعدّل `ADR-021` أو `docs/adr/README.md` أو أي ملف آخر.
## المرحلة 4 — تجهيز ADR-023 وحده
بعد اجتياز المراجعة:
1. جهّز الملف المستهدف وحده باستخدام مساره الدقيق.
2. افحص `git diff --cached --name-only`.
3. يجب أن يحتوي الناتج على ملف واحد فقط:
   `docs/architecture/ADR-023-scientific-source-expansion.md`
4. افحص `git diff --cached --check`.
5. راجع `git diff --cached` كاملًا وتأكد من عدم فقد أو استبدال أي محتوى غير مقصود.
6. إذا ظهر أي ملف آخر في الـ staging area، أو ظهر تغيير غير متوقع، فتوقف دون Commit.
## المرحلة 5 — إنشاء Commit
لا تنشئ Commit إلا إذا اجتازت جميع الفحوص السابقة.
استخدم رسالة واضحة مثل:
`docs(architecture): preserve ADR-023 scientific source expansion records`
أنشئ Commit واحدًا فقط، ولا تنفذ Push.
## المرحلة 6 — التحقق بعد Commit
1. سجّل SHA الجديد ورسالة Commit والوالد.
2. تحقق من أن الالتزام يحتوي على ملف واحد فقط.
3. تحقق من أن الفرق في Commit يقتصر على ADR-023.
4. تحقق من أن جميع التعديلات غير المرتبطة ما زالت موجودة.
5. أعد فحص حالة Git والملفات غير المتعقبة.
6. تأكد من عدم تنفيذ Push أو تغيير التاريخ البعيد.
## التقرير النهائي
قدّم تقريرًا واحدًا بالعربية يتضمن:
- HEAD قبل Commit وبعده.
- SHA ورسالة الالتزام.
- قائمة الملفات التي دخلت Commit.
- نتائج `git diff --cached --check`.
- تأكيد الحفاظ على التعديلات غير المرتبطة.
- أي مشكلات في ترقيم ADR-023 أو إحالاته.
- تأكيد صريح بأن Push لم يُنفّذ.
**قاعدة الإيقاف:** إذا كان إنشاء Commit للملف الحالي سيُثبّت تصادمًا معماريًا غير محسوم أو يؤدي إلى فقد سجلات، فتوقف وقدّم الأدلة بدل تعديل المحتوى أو حذف أي سجل من تلقاء نفسك.
