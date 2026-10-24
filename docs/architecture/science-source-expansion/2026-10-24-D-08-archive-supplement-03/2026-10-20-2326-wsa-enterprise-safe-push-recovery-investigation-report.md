<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17505). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
إعادة ترتيب الالتزام المحلي فوق الأصل آمنة من حيث المحتوى، لأن الملفين لا يتقاطعان مع توثيق الفرع البعيد. تنفيذها داخل مجلد العمل الحالي غير آمن، لأن الملف نفسه يحمل تعديلات محلية غير ملتزم بها.

## 1. شجرة الالتزامات

القاعدة المشتركة هي `f2025315d8990f52f3c658c968a3ec8252ac18b0`. الحالة: متقدم بالتزام واحد ومتأخر بثلاثة. HEAD لم يتغير، وما زال `149` مسارًا محليًا غير ملتزم به.

```text
* 3eeba40 (HEAD) fix(frontend): accept crop profiles that have no presentation message
| * 32eb117 (origin) docs(architecture): archive 109-source reconciliation report
| * fba78bc docs(architecture): record automatic prompt and report archiving policy
| * 067fe80 docs(architecture): append scientific search continuity record
|/
* f202531 test(ic): verify PostgreSQL persistence isolation in CI
```

`git merge-tree --name-only` بين HEAD والأصل خرج بالرمز 0 ومن دون ملفات متعارضة. هذا يفحص الالتزامات فقط، لا التعديلات غير الملتزم بها.

## 2. ملفات كل التزام

| الالتزام | العنوان | الملفات |
|---|---|---|
| `3eeba4012d40a3bddf9318470406b8c8000694b7` | `fix(frontend): accept crop profiles that have no presentation message` | `frontend/src/public/fieldCropCanonicalAnswer.test.ts` و`frontend/src/public/scientificUserPresentation.ts` فقط. `+15 / -1` |
| `067fe8060f415a82b6f24b86313af3ed62c75d28` | `docs(architecture): append scientific search continuity record` | `docs/architecture/ADR-023-scientific-source-expansion.md` فقط. `+93`. يلحق القسم 8.27 عن سجل الاستمرارية بتاريخ 2026-10-14 |
| `fba78bc9f067f783934b6add648737eafd8de1f4` | `docs(architecture): record automatic prompt and report archiving policy` | الملف نفسه. `+34`. يلحق القسم 8.28 عن أرشفة المحادثات |
| `32eb11747b0507d98ff8984a8b22c5f7945bdb1a` | `docs(architecture): archive 109-source reconciliation report` | الملف نفسه. `+120`. يلحق القسم 8.29 عن تقرير مطابقة المصادر الـ 109 |

مجموع الأصل من `f202531` إلى `32eb117` هو `+247` في ADR-023 وحده. ملفات الواجهة الأمامية متطابقة بين `f202531` والأصل.

## 3. التداخل والمخاطر

الالتزامات الأربعة لا تشترك في أي مسار. الالتزام المحلي لا يمس ADR.

الخطر في مجلد العمل الحالي: `docs/architecture/ADR-023-scientific-source-expansion.md` معدّل محليًا وغير ملتزم به بمقدار `+3602 / -1` مقارنة بـ HEAD. الأصل يغيّر الملف نفسه عند حدود الأقسام 8.27–8.29. أي rebase أو merge داخل هذا المجلد سيحاول تحديث هذا الملف بينما يحمل نصًا محليًا غير ملتزم به.

هناك تصادم ترقيم لاحق، منفصل عن التزام الواجهة: النسخة المحلية غير الملتزم بها تستخدم أرقام 8.27 وما بعدها لنص مختلف عن أقسام 8.27–8.29 الموجودة على الأصل. دمجهما قرار توثيقي مستقل، وليس جزءًا من رفع إصلاح الواجهة.

## 4. هل إعادة الترتيب آمنة؟

إعادة تطبيق رقعة `3eeba40` فوق `32eb117` آمنة من حيث المحتوى. الرقعة تمس ملفين لم يغيّرهما الأصل، و`merge-tree` لم يبلغ عن تعارض.

تنفيذ rebase أو merge في مجلد العمل الحالي غير آمن. Git سيحتاج إلى كتابة نسخة الأصل من ADR-023 فوق تعديل محلي كبير في الملف نفسه. `stash` و`reset` ممنوعان، لذلك لا توجد طريقة آمنة لإخلاء المجلد ثم استعادته ضمن القيود الحالية.

## 5. الخطة المقترحة، من دون تنفيذ

1. الإبقاء على HEAD الحالي `3eeba40` وعلى الـ 149 مسارًا غير الملتزم بها كما هي.
2. إنشاء worktree منفصل عند `32eb117`، خارج `G:\WSA-Enterprise`، حتى لا يُمس المجلد الحالي.
3. داخل ذلك الـ worktree فقط، تنفيذ `git cherry-pick 3eeba4012d40a3bddf9318470406b8c8000694b7`. الناتج التزام جديد بالرقعة نفسها وأب جديد هو `32eb117`؛ الالتزام الأصلي يبقى محفوظًا.
4. التحقق من أن الالتزام الجديد يحتوي الملفين الأماميين فقط، ثم رفعه بـ fast-forward من دون `--force`.
5. عدم تحريك فرع المجلد الحالي إلى الالتزام الجديد إلا في خطوة لاحقة معتمدة، بعد حل اختلاف أقسام ADR-023 المحلية عن أقسام الأصل 8.27–8.29.

لم يُنفَّذ push ولا rebase ولا merge ولا cherry-pick ولا commit، ولم يتغير الفرع أو HEAD أو أي ملف.
