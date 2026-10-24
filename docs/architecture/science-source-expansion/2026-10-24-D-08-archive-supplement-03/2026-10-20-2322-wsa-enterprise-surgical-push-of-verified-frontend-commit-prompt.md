<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17495). Message timestamp as recorded in the conversation: Tuesday, Oct 20, 2026, 11:22 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17496; it is archived once. -->

---
# WSA-Enterprise — Surgical Push of Verified Frontend Commit
## الهدف
رفع الـ Commit المنجز والمختبَر إلى GitHub، دون رفع أي ملفات محلية أخرى.
Repository: `G:\WSA-Enterprise`
Branch:
`phase-18-m18-ai-marketing-communications`
Expected commit:
`3eeba4012d40a3bddf9318470406b8c8000694b7`
Expected commit message:
`fix(frontend): accept crop profiles that have no presentation message`
## 1. Preflight — قبل Push
نفّذ من داخل المستودع:
1. تحقق من المسار:
   `git rev-parse --show-toplevel`
2. تحقق من الفرع:
   `git branch --show-current`
3. تحقق من HEAD:
   `git log -1 --format="%H%n%s"`
4. تحقق من حالة العمل:
   `git status --short`
5. تحقق من إعداد التتبع:
   `git status -sb`
6. تحقق من أن الـ Commit يحتوي الملفين المقصودين فقط:
   `git diff-tree --no-commit-id --name-only -r HEAD`
7. افحص حالة الفرع البعيد:
   `git fetch origin`
   ثم:
   `git rev-list --left-right --count origin/phase-18-m18-ai-marketing-communications...HEAD`
لا تنفّذ Push إذا كان الفرع الحالي مختلفًا، أو كان HEAD لا يطابق الـ Commit المتوقع، أو لم تكن هوية الملفات صحيحة.
## 2. حماية التغييرات المحلية
يوجد عمل محلي آخر غير ملتزم به. يجب الحفاظ عليه بالكامل.
- لا تستخدم `git add` أو `git commit`.
- لا تستخدم `git stash` أو `git reset` أو `git clean` أو `git restore`.
- لا تعدّل أو تحذف أي ملف.
- لا ترفع أي Commit آخر غير الـ Commit المستهدف.
- لا تستخدم `--force` أو `--force-with-lease`.
إذا كانت هناك Commitات محلية إضافية غير مرفوعة على الفرع، فتوقف ولا ترفعها ضمن هذه العملية.
إذا كان الـ Commit المستهدف موجودًا بالفعل في الفرع البعيد، فأبلغ عن ذلك ولا تكرر العملية بلا داعٍ.
## 3. تنفيذ Push
بعد نجاح جميع الفحوص، إذا كان الـ Commit المستهدف هو Commit المحلي الوحيد المتقدم على الفرع البعيد، نفّذ:
`git push origin HEAD:refs/heads/phase-18-m18-ai-marketing-communications`
## 4. التحقق بعد Push
تحقق من:
- `git fetch origin`
- `git rev-parse HEAD`
- `git rev-parse origin/phase-18-m18-ai-marketing-communications`
- `git status -sb`
- `git status --short`
يجب أن يتطابق HEAD المحلي مع HEAD البعيد عند نجاح الرفع، وأن يظل العمل المحلي غير الملتزم به محفوظًا كما كان.
## 5. التقرير النهائي
أرسل تقريرًا بالعربية يتضمن:
1. الفرع الذي تم رفعه.
2. Commit SHA وعنوانه.
3. نتيجة Push الفعلية.
4. تأكيد تطابق HEAD المحلي والبعيد.
5. عدد ومسارات التغييرات المحلية المتبقية، دون تعديلها.
6. تأكيد عدم استخدام Force Push وعدم إنشاء Commit جديد.
إذا فشل أي فحص أو كانت هناك Commitات أخرى ستدخل ضمن الرفع، فتوقف واعرض السبب دون محاولة إصلاحه تلقائيًا.
