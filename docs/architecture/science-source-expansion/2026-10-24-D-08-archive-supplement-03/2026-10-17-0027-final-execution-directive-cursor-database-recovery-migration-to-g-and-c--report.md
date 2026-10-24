<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 16488). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير مرحلي — ترحيل قاعدة Cursor إلى G:

## الملخص التنفيذي

النقل **لم يُكمل بعد** لأن **Cursor ما زال يعمل** (~18 عملية). بوابات **A–C** جاهزة، وسكربت انتظار يعمل في الخلفية سيكمل **D→E** تلقائيًا بعد إغلاق Cursor بالكامل. **أصل C لن يُحذف** في هذه الجولة.

| البوابة | النتيجة |
|---------|---------|
| A جرد | **PASS** |
| B صلاحية نسخة G | **PASS** (تحقق محدود؛ `quick_check` الكامل **NOT RUN**) |
| C طريقة التشغيل على G | **PASS** (Directory Junction) |
| D ترحيل | **NOT RUN** — بانتظار إغلاق Cursor |
| E اختبار التطبيق | **NOT RUN** |
| F حذف C | **NOT RUN** (يتطلب موافقة لاحقة بعد نجاح E) |

---

## المطلوب منك الآن

1. **احفظ عملك.**
2. **أغلق Cursor بالكامل** (Exit / Quit — ليس تصغير النافذة).
3. انتظر انتهاء النسخ إلى G (~45 GiB؛ قد يستغرق وقتًا).
4. سيعاد تشغيل Cursor تلقائيًا؛ تحقق أن المحادثات والإعدادات ظاهرة.
5. بعد التأكد، أرسل موافقة صريحة على حذف أصول C (مثلاً: «وافق على FinalizeCDelete»).

السكربت المنتظر: `G:\CursorBackups\migrate_cursor_db_to_G.ps1`  
السجلات: `G:\CursorBackups\migration-logs\`  
النسخة الاحتياطية الباقية (لن تُمس): `G:\CursorBackups\state.vscdb.backup-20261016-220630.sqlite`

---

## النتائج المؤكدة حتى الآن

**القرص:** C ≈ **2.56 GiB** حرة | G ≈ **145 GiB** حرة  

**الأصل:** `state.vscdb` ≈ **45.46 GiB** (+ WAL نشط)  

**نسخة G:** تفتح للقراءة، نفس الجداول، ItemTable=319 وcomposerHeaders=451 مع نفس بصمة المعرفات، ووجود `bubbleId`/`checkpointId`. C أحدث بـ ~20 MB من وقت النسخة — لذلك بعد الإغلاق ستُنشأ قاعدة نشطة جديدة عبر **SQLite backup API** على  
`G:\CursorData\User\globalStorage\`  
وليس استبدال النسخة الاحتياطية القديمة.

**الطريقة:** `mklink /J` لمجلد `globalStorage` → `G:\CursorData\User\globalStorage`  
(Symlink ملف: فشل بدون صلاحية مسؤول). التراجع: حذف الـ junction ثم إعادة تسمية `globalStorage.pre-migrate-*`.

لا تعديل على ملفات WSA-Enterprise.
