<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 16500). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير اختبار الترحيل إلى G: — دون حذف

**CURSOR_G_MIGRATION_TEST_20261017_01**

## التوصية

**الترحيل غير ناجح حاليًا — لا تعتمد على G كقاعدة تشغيل.**  
Cursor ما زال يعمل من قاعدة **C الأصلية**. لا يوجد Directory Junction. على G توجد نسخة **جزئية غير مكتملة** (~23.2 GiB) من محاولة نسخ أُجهضت بعد اكتشاف خاطئ أن Cursor توقف.  
**لم يُحذف شيء من C. لم يُنفَّذ FinalizeCDelete.**

---

## نتائج الاختبارات

### 1) مسار التشغيل الفعلي (Junction → G)
**FAIL**

| دليل | قيمة |
|------|------|
| `C:\Users\ahmed\AppData\Roaming\Cursor\User\globalStorage` | مجلد عادي (`Attributes=Directory`, `LinkType` فارغ) |
| `fsutil reparsepoint query` | `Error 4390: The file or directory is not a reparse point` |
| مسار G المقصود | `G:\CursorData\User\globalStorage\` موجود لكن **ليس** مربوطًا عبر Junction |
| نشاط الكتابة الحالي | `state.vscdb-wal` على **C** يتحدّث (مثلاً ~394 MB، mtime حديث) |

### 2) ظهور المحادثات/الإعدادات عبر مسار G
**INCONCLUSIVE / عمليًا غير قابل للإثبات عبر G**

المحادثة الحالية تعمل لأن Cursor يستخدم **C**. لا يمكن إثبات أن المسار على G يخدم الواجهة لأن Junction غير مفعّل.

### 3) اختبار الكتابة (`CURSOR_G_MIGRATION_TEST_20261017_01`)
**FAIL** (بالنسبة للكتابة على G)

العلامة وُضعت في هذه المحادثة أعلاه، لكن أي حفظ يتم على قاعدة **C** النشطة، وليس على G.

### 4) الاستمرارية (إغلاق → إعادة فتح → بقاء الرسالة)
**NOT RUN**

لم يُغلق Cursor عمدًا لإكمال هذا الاختبار: الترحيل غير مكتمل، وإعادة الفتح لن تختبر G. إغلاق قسري وسط نسخة جزئية سابقًا كان سبب الحادثة.

### 5) إثبات الكتابة على G
**FAIL**

| دليل | قيمة |
|------|------|
| `G:\...\state.vscdb` | ~24,870,518,784 بايت — **نسخة جزئية** من `sqlite3.backup` بدأت 00:50:36 |
| سجل الترحيل | وصل إلى `SQLite backup API...` ثم توقف؛ **لم** يُسجَّل نجاح Junction ولا Gate E |
| بعد إيقاف النسخ | حجم G ثابت؛ بينما WAL على **C** يستمر في التغيّر |
| النسخة الاحتياطية السليمة | `G:\CursorBackups\state.vscdb.backup-20261016-220630.sqlite` ≈ 45.44 GiB ما زالت موجودة |

لا يوجد دليل أن Cursor يكتب قاعدة التشغيل على G.

### 6) أخطاء SQLite / تلف / فشل حفظ
**INCONCLUSIVE**

- سجلات `AppData\Roaming\Cursor\logs`: لم تُلتقط إصابات حديثة واضحة لـ SQLITE/Corrupt في عيّنة الملفات المفحوصة (`log_hit_files=0`).
- حالة G: ملف غير مكتمل + `state.vscdb-journal` صغير بعد إجهاض النسخ — **غير صالح كقاعدة تشغيل**.
- C: ما زال يُفتح ويُكتب (WAL نشط) — لا دليل تلف من هذا الفحص المحدود.

---

## ماذا حدث (للشفافية)

1. السكربت المنتظر ظنّ أن Cursor توقف (00:50:35).  
2. بدأ نسخ SQLite من C → G.  
3. Cursor عاد للتشغيل أثناء النسخ.  
4. Junction **لم يُنشأ**.  
5. أوقفتُ الـ waiter وعملية النسخ لحماية C ومنع الربط لاحقًا بقاعدة ناقصة.  
6. سُجّلت الحادثة في:  
   `G:\CursorBackups\migration-logs\INCIDENT-partial-backup-aborted.txt`

---

## الخلاصة

| الاختبار | النتيجة |
|----------|---------|
| 1. Junction → G | **FAIL** |
| 2. المحادثات عبر G | **INCONCLUSIVE** |
| 3. الكتابة على G | **FAIL** |
| 4. الاستمرارية بعد إعادة التشغيل | **NOT RUN** |
| 5. أدلة الكتابة على G | **FAIL** |
| 6. أخطاء SQLite | **INCONCLUSIVE** |

**نجاح الترحيل: لا.** استمر على C. احتفظ بالنسخة `backup-20261016-220630`. لا تحذف أصول C. أي ترحيل لاحق يجب أن يبدأ فقط وCursor مغلق بالكامل ويبقى مغلقًا حتى اكتمال النسخ + Junction + التحقق.
