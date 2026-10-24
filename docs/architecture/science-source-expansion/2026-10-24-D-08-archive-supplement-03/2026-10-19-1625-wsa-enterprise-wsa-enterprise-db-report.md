<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17224). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير فحص `wsa_enterprise_db` (قراءة فقط)

## النتيجة

**لم يُعثر** على ملف أو مجلد باسم `wsa_enterprise_db` (أو يحتوي الاسم) داخل `G:\WSA-Enterprise`.

| الفحص | النتيجة |
|---|---|
| بحث بالمسار تحت المستودع | 0 مطابقات |
| ذكر الاسم في الإعدادات/الوثائق | لا يوجد |
| مجلدات `backups/` المعتادة تحت المشروع | غير موجودة |

لذلك **لا يمكن** تحديد الامتداد أو الحجم أو النوع أو إصدار PostgreSQL المصدر — لا يوجد هدف للفحص داخل المشروع.

## ملاحظة من الوثائق (ليست النسخة المطلوبة)

الوثائق تصف نمط نسخ احتياطي مختلف، مثل:
`wsa_enterprise_{POSTGRES_DB}_{UTC_TIMESTAMP}.sql.gz`  
(مثال في `docs/phase-12-m12-5-backup-and-rollback.md`) — **وليس** الاسم `wsa_enterprise_db`.

## ما لم يُنفَّذ

لا `pg_restore` / لا استعادة / لا بحث على كامل القرص / لا تعديل ملفات.

---

**الخطوة التالية منك:** حدّد المسار الكامل للنسخة (مثلاً `D:\...\wsa_enterprise_db.backup` أو مجلد)، لأفحص الترويسة والبيانات الوصفية فقط بعد ذلك.
