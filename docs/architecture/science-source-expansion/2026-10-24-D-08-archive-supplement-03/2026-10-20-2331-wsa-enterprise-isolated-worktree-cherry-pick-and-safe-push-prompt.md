<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17506). Message timestamp as recorded in the conversation: Tuesday, Oct 20, 2026, 11:31 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17507; it is archived once. -->

---
# WSA-Enterprise — Isolated Worktree Cherry-pick and Safe Push

## الهدف
رفع إصلاح الواجهة الأمامية فقط فوق أحدث فرع بعيد، باستخدام worktree معزول، دون المساس بمجلد العمل الحالي.

Repository: `G:\WSA-Enterprise`

Branch:
`phase-18-m18-ai-marketing-communications`

Remote target:
`origin/phase-18-m18-ai-marketing-communications`

Source commit:
`3eeba4012d40a3bddf9318470406b8c8000694b7`

Expected remote base:
`32eb11747b0507d98ff8984a8b22c5f7945bdb1a`

## المرحلة 1 — Preflight

1. تحقق من أن HEAD المحلي ما زال `3eeba4012d40a3bddf9318470406b8c8000694b7`.
2. تحقق من أن الفرع البعيد ما زال عند `32eb11747b0507d98ff8984a8b22c5f7945bdb1a` بعد `git fetch origin`.
3. سجّل `git status --short` وعدد مسارات التغييرات المحلية.
4. تأكد أن منطقة التجهيز في مجلد العمل الحالي لم تتغير.
5. إذا تغيّر HEAD أو الفرع البعيد، فتوقف وأعد التقييم.

## المرحلة 2 — إنشاء worktree معزول

استخدم مسارًا جديدًا خارج `G:\WSA-Enterprise`، مثل:

`G:\WSA-Enterprise-push-isolated`

قبل الإنشاء، تأكد أن المسار غير موجود. لا تحذف أو تستبدل أي مسار موجود.

أنشئ worktree على الفرع البعيد باستخدام فرع عمل مؤقت فريد، دون تحريك الفرع الأصلي:

`git worktree add -b temp/frontend-crop-profile-push G:\WSA-Enterprise-push-isolated origin/phase-18-m18-ai-marketing-communications`

إذا كان اسم الفرع المؤقت مستخدمًا أو المسار موجودًا، فتوقف واختر اسمًا ومسارًا جديدين بعد التحقق، دون حذف شيء.

## المرحلة 3 — Cherry-pick للالتزام المحدد

داخل worktree المعزول فقط:

`git cherry-pick 3eeba4012d40a3bddf9318470406b8c8000694b7`

إذا ظهر أي تعارض، فتوقف واعرضه دون حل تلقائي ودون تنفيذ أوامر مدمرة.

تحقق من أن الالتزام الناتج:
- مبني على `32eb11747b0507d98ff8984a8b22c5f7945bdb1a`.
- يحتوي فقط:
  - `frontend/src/public/fieldCropCanonicalAnswer.test.ts`
  - `frontend/src/public/scientificUserPresentation.ts`
- يحمل التغيير المتوقع فقط.

## المرحلة 4 — الاختبار والتحقق

من مجلد `frontend` داخل worktree، شغّل:

`npx vitest run src/public/fieldCropCanonicalAnswer.test.ts`

إذا فشل الاختبار، فلا ترفع شيئًا.

ثم تحقق من:
- `git status --short`
- `git diff-tree --no-commit-id --name-only -r HEAD`
- `git show --stat --oneline HEAD`
- `git rev-parse HEAD`
- `git rev-parse origin/phase-18-m18-ai-marketing-communications`

تأكد أن الـ worktree لا يحتوي تعديلات غير ملتزم بها أو ملفات بيانات مؤقتة غير مقصودة.

## المرحلة 5 — Push

لا تنفّذ Push إلا إذا:
- نجح الاختبار.
- كان الالتزام الجديد مبنيًا مباشرة على رأس الفرع البعيد الحالي.
- كانت قائمة الملفات مطابقة تمامًا للملفين المحددين.
- لم يتغير الفرع البعيد منذ بدء العملية.

عند استيفاء الشروط، ارفع الفرع المؤقت إلى الفرع المستهدف باستخدام:

`git push origin HEAD:refs/heads/phase-18-m18-ai-marketing-communications`

لا تستخدم `--force` أو `--force-with-lease`.

إذا رفض الخادم الرفع لأن الفرع البعيد تغيّر، فتوقف وأعد التحقق بدلًا من محاولة تجاوز الرفض.

## المرحلة 6 — التحقق النهائي

بعد نجاح الرفع:
- نفّذ `git fetch origin`.
- تحقق من تطابق HEAD المرفوع مع `origin/phase-18-m18-ai-marketing-communications`.
- تأكد أن الـ Commit المرفوع يحتوي الملفين فقط.
- تحقق أن مجلد العمل الأصلي ما زال على HEAD `3eeba4012d40a3bddf9318470406b8c8000694b7`.
- تحقق أن التغييرات المحلية الأصلية ما زالت محفوظة.

لا تحذف الـ worktree أو الفرع المؤقت في هذه الجولة؛ اتركهما للفحص اللاحق.

## القيود

- لا تغيّر HEAD أو الفرع في `G:\WSA-Enterprise`.
- لا تلمس `ADR-023` أو أي ملفات غير الملفين المحددين.
- لا تستخدم `git reset` أو `git clean` أو `git restore` أو `git stash`.
- لا تنفّذ Rebase أو Merge.
- لا تنفّذ Force Push.
- لا تنشئ أي Commit إضافي غير الـ Cherry-pick المحدد.
- لا تدّعِ نجاح أي خطوة دون مخرجات فعلية.

## التقرير النهائي

قدّم تقريرًا عربيًا يتضمن:
1. مسار worktree.
2. SHA الالتزام الجديد.
3. نتيجة الاختبار.
4. الملفات الموجودة في الالتزام.
5. نتيجة Push والتحقق من HEAD البعيد.
6. تأكيد بقاء HEAD والتغييرات المحلية في مجلد العمل الأصلي دون تغيير.
