<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17547). Message timestamp as recorded in the conversation: Wednesday, Oct 21, 2026, 12:09 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17548; it is archived once. -->

---
نفّذ تدقيقًا وخطة مصالحة Git لمشروع WSA-Enterprise بعد إنشاء الالتزام المحلي:

`f0ac89700b7a822bd83b2cd821a18e493089d786`

الفرع المستهدف:
`phase-18-m18-ai-marketing-communications`

الحالة المعروفة:
- المحلي يحتوي الالتزام `f0ac897` فوق `3eeba40`.
- البعيد يشير إلى `8dd6827`.
- التاريخ متباعد: المحلي ahead 2 وbehind 4.
- `3eeba40` و`8dd6827` يحتويان تصحيح الواجهة نفسه على تاريخين مختلفين.
- توجد تغييرات كثيرة غير ملتزم بها في مجلد العمل الأصلي.

المطلوب الآن هو الفحص والتخطيط فقط، دون تنفيذ المصالحة أو Push.

1. تحقق من حالة Git الحالية والـ HEAD والفرع البعيد.
2. افحص شجرة الالتزامات المشتركة والاختلافات بين `3eeba40` و`8dd6827`.
3. تحقق من تطابق رقعة الواجهة فعليًا، ولا تعتمد على عنوان الالتزام فقط.
4. حدد كيف يمكن الاحتفاظ بالالتزامات البعيدة الأربعة والالتزام `f0ac897` دون تكرار تغييرات الواجهة.
5. قيّم استخدام worktree منفصل لإنشاء فرع تكاملي معزول.
6. حدد الاختبارات المطلوبة قبل أي Push، مع منع لمس قاعدة SQLite التشغيلية أو PostgreSQL التشغيلية.
7. لا تعدّل ملفات المشروع، ولا تغيّر الفرع الأصلي، ولا تنفذ Commit أو Push أو merge أو rebase أو cherry-pick.
8. لا تستخدم reset أو clean أو stash.
9. لا تحذف worktree أو فرعًا مؤقتًا موجودًا.
10. قدم خطة واحدة واضحة تتضمن الأوامر المقترحة بالترتيب، والمخاطر، وشروط التوقف.

أرسل تقريرًا عربيًا واحدًا، ولا تنفذ خطة المصالحة حتى تتم مراجعتها والموافقة عليها صراحةً.
