<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17486). Message timestamp as recorded in the conversation: Tuesday, Oct 20, 2026, 11:14 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17487; it is archived once. -->

---
# WSA-Enterprise — Surgical Commit of Ready Frontend Files
## الهدف
تنفيذ Commit واحد للملفات الجاهزة فقط، بعد مراجعة نهائية للتغييرات والتحقق من سلامة Git.
Repository: `G:\WSA-Enterprise`  
Branch expected: `phase-18-m18-ai-marketing-communications`  
Last reported HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`
## 1. Preflight — إلزامي قبل أي تعديل
1. تحقق من مسار المستودع والفرع الحالي وHEAD.
2. نفّذ:
   - `git status --short`
   - `git diff -- frontend/src/public/scientificUserPresentation.ts frontend/src/public/fieldCropCanonicalAnswer.test.ts`
   - `git diff --cached --stat`
   - `git diff --check`
3. افحص أي تغييرات staged مسبقًا، ولا تفترض أنها تخص هذا العمل.
4. راجع التغييرات الكاملة في الملفين وتأكد أنها تحقق الغرض التالي فقط:
   - قبول crop profile حتى عندما لا توجد presentation message.
   - اختبار السلوك المقابل.
5. إذا تغيّر الفرع أو HEAD بصورة غير متوقعة، أو ظهرت تعارضات أو تغييرات غير مفهومة في الملفين، فتوقف واعرض التقرير دون Commit.
## 2. الاختبارات
من مجلد `frontend`، شغّل:
`npx vitest run src/public/fieldCropCanonicalAnswer.test.ts`
سجّل نتيجة الاختبار الفعلية ورمز الخروج. لا تكتفِ بنتيجة قديمة. إذا فشل الاختبار، فلا تنفّذ Commit.
## 3. Surgical staging
لا تضف أي ملفات أخرى. استخدم المسارين المحددين فقط:
`frontend/src/public/scientificUserPresentation.ts`
`frontend/src/public/fieldCropCanonicalAnswer.test.ts`
قبل الالتزام:
- تحقق من `git diff --cached --name-only`.
- يجب أن يحتوي الناتج على هذين المسارين فقط.
- راجع `git diff --cached` كاملًا.
- تحقق من `git diff --cached --check`.
- تأكد من عدم وجود أي تغييرات أخرى ضمن الـ staging.
إذا كانت هناك ملفات staged مسبقًا خارج النطاق، فلا تُلغِ staging لها ولا تعدّلها. أوقف العملية واطلب معالجة منفصلة حتى لا تدخل في Commit بالخطأ.
## 4. Commit
إذا نجحت جميع الفحوص السابقة وكان الـ staging مطابقًا تمامًا، نفّذ Commit بالعنوان:
`fix(frontend): accept crop profiles that have no presentation message`
لا تستخدم `git add .` أو `git add -A`.
## 5. Post-commit verification
بعد نجاح Commit، تحقّق من:
- `git log -1 --format="%H%n%s"`
- `git show --stat --oneline HEAD`
- `git diff-tree --no-commit
