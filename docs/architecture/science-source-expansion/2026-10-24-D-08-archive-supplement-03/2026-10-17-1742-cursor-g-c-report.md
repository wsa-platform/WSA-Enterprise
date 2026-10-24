<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 16564). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير تشخيصي فقط — نقل Cursor إلى G / استعداد لاستعادة C

**لم يُحذف أو يُعاد تسمية أي شيء. لم يُمسّ الاحتياطي على G.**  
أوقفت التنفيذ بانتظار موافقتك.

---

## 1) الـ Junction

| البند | النتيجة | الدليل |
|---|---|---|
| موجود؟ | **نعم** | [EXECUTED] |
| المسار الظاهر | `C:\Users\ahmed\AppData\Roaming\Cursor\User\globalStorage` | [EXECUTED] |
| النوع | Directory Junction (`Reparse Tag 0xa0000003`) | [EXECUTED] `fsutil reparsepoint query` |
| الهدف الحقيقي | **`G:\CursorData\Staging`** (`\??\G:\CursorData\Staging`) | [EXECUTED] |
| وقت إنشاء الـ Junction | 2026-10-17 15:32:04 | [EXECUTED] |

ملاحظة سجل قديم: محاولة MIGRUN عند 14:32 سجّلت `junction_failed` ثم تراجعاً — [DOCUMENTED]. الحالة **الحالية** مختلفة: الـ Junction قائم الآن.

---

## 2) المسار الفعلي لـ globalStorage / القاعدة

| فحص | نتيجة |
|---|---|
| مسار التطبيق الظاهر | `...\User\globalStorage` → Junction → **`G:\CursorData\Staging`** |
| `state.vscdb` عبر المسار الظاهر و`G:\...\Staging` | **نفس File ID** `0x...000487f3` |
| Hard link إضافي على G | `G:\CursorData\Runtime\globalStorage\state.vscdb` → **نفس File ID** |
| نسخة C الأصلية (قبل الـ Junction) | `...\globalStorage.pre-junction-20261017-142822\state.vscdb` → File ID **مختلف** `0x...000981ff` |

أثناء تشغيل Cursor الآن (~18 عملية منذ ~17:41): ملفات `state.vscdb` / `-wal` / `-shm` و`storage.json` على **G:\CursorData\Staging** تتحدّث (WAL كان ~254–448 MiB أثناء الفحص) — [EXECUTED].  
هذا يدل بقوة على أن الكتابة الحية تتم على بيانات G عبر الـ Junction، وليس على مجلد `pre-junction` على C.

`G:\CursorData\User\globalStorage` موجود لكنه صغير (~2 MiB) وليس هدف الـ Junction الحالي — [EXECUTED].

---

## 3) المحادثات والاستمرارية بعد إعادة التشغيل

| الادعاء | الحالة |
|---|---|
| قراءة خفيفة للجداول على Staging سابقاً (`ItemTable` / `composerHeaders` / …) | [DOCUMENTED] `light_ro: PASS` في سجلات الهجرة |
| اختبار رسمي موثّق: «المحادثات ظاهرة بعد إعادة التشغيل» | **[NOT RUN] / غير مسجّل كـ PASS** |
| اختبار رسمي موثّق: «البيانات تُحفظ ثم تظهر بعد restart مضبوط» | **[NOT RUN] / غير مسجّل كـ PASS** |
| دليل حي الآن: Cursor يعمل ويكتب WAL على Staging | [EXECUTED] — ليس بديلاً عن اختبار restart موثّق |

**لا أدّعي نجاح اختبار استمرارية لم يُنفَّذ أو يُوثَّق.**

---

## 4) ملفات القاعدة: C الأصلي مقابل ما يشير إلى G

### أ) عبر الـ Junction (= بيانات على G، لا تُحسب مساحة C قابلة للحذف عبر هذا المسار)

المسار الظاهر `...\globalStorage\*` يشير إلى `G:\CursorData\Staging\`:

| ملف | ملاحظة |
|---|---|
| `state.vscdb` | ~45.56 GiB — على قرص **G** (File ID مشترك مع Runtime hardlink) |
| `state.vscdb-wal` | حيّ / يتغيّر — على **G** |
| `state.vscdb-shm` | حيّ — على **G** |
| `conversation-search.db` (+ wal/shm) | على **G** |
| `storage.json` | على **G** |

### ب) أصول حقيقية على C (ليست Junction)

**1) `...\globalStorage.pre-junction-20261017-142822\`** — مجلد عادي على C (لا ReparsePoint):

| عنصر | الحجم التقريبي |
|---|---:|
| `state.vscdb` | 49,076,502,528 بايت (~45.706 GiB) |
| `state.vscdb-wal` | 26,689,392 |
| `state.vscdb-shm` | 65,536 |
| `conversation-search.db` (+ wal/shm) | ~0.001 GiB |
| `anysphere.cursor-agent-worker\` | ~3.625 GiB |
| `anysphere.cursor-retrieval\` | ~0.002 GiB |
| `storage.json` | صغير |
| **مجموع المجلد** | **52,998,975,939 بايت ≈ 49.359 GiB** |

**2) `...\globalStorage.pre-migration-20261017-152454\`** — مجلد عادي صغير على C:

| عنصر | الحجم |
|---|---:|
| `state.vscdb` + wal/shm + backup + conversation-search + storage.json | **3,793,573 بايت ≈ 0.004 GiB** |

### ج) احتياطي G — لا يُمسّ

| ملف | الحجم |
|---|---:|
| `G:\CursorBackups\state.vscdb.backup-20261016-220630.sqlite` | ~45.44 GiB |
| (+ shm/wal/journal مرافقة) | صغيرة |

---

## 5) ما يمكن حذفه بأمان لاستعادة المساحة (بعد موافقتك فقط)

### مرشّحون للحذف على C فقط

| # | المسار | الحجم المتوقع المسترد | شروط الأمان |
|---|---|---:|---|
| **A** | `C:\Users\ahmed\AppData\Roaming\Cursor\User\globalStorage.pre-junction-20261017-142822\` **(المجلد كاملاً)** | **≈ 49.359 GiB** | بعد اقتناعك أن الـ Junction يعمل؛ يُفضَّل إغلاق Cursor أولاً؛ هذا آخر نسخة كاملة على C قبل النقل |
| **B** | `C:\Users\ahmed\AppData\Roaming\Cursor\User\globalStorage.pre-migration-20261017-152454\` **(المجلد كاملاً)** | **≈ 0.004 GiB** | بقايا محاولة ترحيل لاحقة؛ ليست القاعدة الحية |

**المجموع المتوقع:** **≈ 49.363 GiB**  
**C الحر الآن:** ≈ **4.54 GiB** → بعد الحذف المتوقع ≈ **~53.9 GiB** حرة (تقديري).

### ممنوع حذفه (سيحذف بيانات G أو يكسر التشغيل)

| مسار | السبب |
|---|---|
| `...\User\globalStorage` (الـ Junction نفسه أو محتوياته عبره) | يحذف/يكسر بيانات **`G:\CursorData\Staging`** |
| `G:\CursorData\Staging\*` | القاعدة الحية |
| `G:\CursorData\Runtime\globalStorage\state.vscdb` | hard link لنفس القاعدة الحية |
| `G:\CursorBackups\state.vscdb.backup-20261016-220630.sqlite*` | الاحتياطي الأصلي — **لا يُمسّ** |

---

## خلاصة جاهزية استعادة C

| السؤال | الجواب |
|---|---|
| هل النقل إلى G عبر Junction قائم؟ | **نعم** → `G:\CursorData\Staging` |
| هل Cursor يكتب على G الآن؟ | **نعم بقوة** (WAL/shm حيّة على Staging) — [EXECUTED] |
| هل اختبار restart/المحادثات موثّق كـ PASS؟ | **لا** — [NOT RUN] |
| أكبر استرداد آمن على C؟ | حذف مجلد **`pre-junction-...`** فقط (~49.36 GiB) بعد موافقتك |
| هل نُفِّذ أي حذف؟ | **لا — متوقف بانتظار موافقتك** |

إذا وافقت، حدّد صراحةً: حذف **A فقط**، أو **A+B**، وهل تريد إغلاق Cursor أولاً قبل التنفيذ.
