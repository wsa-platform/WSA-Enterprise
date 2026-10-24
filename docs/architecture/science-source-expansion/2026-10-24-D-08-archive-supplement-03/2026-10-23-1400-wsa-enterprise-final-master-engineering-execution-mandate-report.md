<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 18017). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
**الحالة النهائية:** `BLOCKED — HISTORICAL DATA PRESERVATION DECISION REQUIRED`

لم يُنشأ مخزن CapVer فارغ، ولم يُشغَّل `scientific:capver-accepted`. الرجوع إلى SQLite لا يُلغي أربع خلايا PostgreSQL سجّلها تقرير 2026-10-22، ومصيرها غير محسوم. التقرير في `docs/2026-10-23-CAPVER-SQLITE-HISTORICAL-DATA-GATE-REPORT.ar.md`. لم يُستخدم Docker، ولم يُنفَّذ ترحيل ولا التزام ولا دفع.

## 1. ما الذي أوقف الكتابة

تقرير `docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md` يسجّل أربع خلايا `VERIFIED` / `CURRENT` للمقاعد `G1-02` و`G6-20` و`G1-01` و`G1-17`. هذا دليل تاريخي ولم يُعد قراءته اليوم. `G5-04` لم يكن بينها.

النسخة الوحيدة في `backups/` هي `wsa_pre_migrate_20261019_215350.dump` (4 600 454 بايت، 2026-10-19 18:54:04 UTC). قائمة TOC لا تحتوي `cghia_capability_cell_records`، و§8.61.2 سبق أن سجّل غياب الجدول لأن النسخة أقدم من الترحيل. لا نسخة PostgreSQL أحدث في المجلد.

`127.0.0.1:5432` رفض الاتصال صباحاً بخطأ Windows 10061، ثم أعاد الفحص خطأ 10060. لا جلسة، فلا عدّ للصفوف الحية.

إنشاء `backend/storage/app/capver.sqlite` فارغاً كان سيتجاهل تلك الخلايا. الملف لم يُنشأ.

## 2. خط الأساس

المستودع `G:\WSA-Enterprise`، الفرع `phase-18-m18-ai-marketing-communications`، HEAD `fb60b60746ed487e2bfa04e3d335827d91cda446`. `git diff --cached` فارغ. الملف العلمي بقي طوله `1393659904` ووقته `2026-10-22T09:53:58Z`.

## 3. ما تم فحصه دون كتابة دائمة

`CghiaCapabilityCell` بلا `$connection`، والمستودع يكتب على الاتصال الافتراضي `pgsql`. هذا يفسّر مسار الكتابة التاريخية. الربط إلى ملف مخصص مؤجَّل حتى تُحسم البوابة.

الترحيل القائم متوافق مع SQLite 3.53.4 وLaravel v12.69.2 على قاعدة الاختبار فقط. الأمر:

`php.exe vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --do-not-cache-result --filter "test_schema_table_exists_with_required_columns|test_database_partial_unique_indexes_enforce_single_current|test_second_current_for_same_natural_tuple_rejected" tests\Unit\Agriculture\Research\Capability\Persistence\CapabilityStorePersistenceTest.php`

`OK (3 tests, 35 assertions)`، خروج 0. الفهرس الجزئي لصف `CURRENT` اشتغل على قاعدة الاختبار. لم يُطبَّق على ملف دائم.

`storage/app/.gitignore` يتجاهل `*` مسبقاً، فملف `capver.sqlite` لو أُنشئ لاحقاً لن يظهر في Git. القاعدة لم تُغيَّر.

## 4. ADR

أُضيفت §8.68. نص §8.67 الذي فوّض PostgreSQL بقي كما هو.

## 5. القرار المطلوب

أحد أمرين:

1. تصدير للقراءة فقط للخلايا الأربع من PostgreSQL الذي استقبل كتابة 2026-10-22، ثم نقلها إلى مخزن SQLite مخصص بعد الترحيل القائم.
2. أو التصريح الصريح بأن المخزن الدائم يبدأ فارغاً مع بقاء تلك الصفوف غير مستردة.

بعد ذلك يُربط اتصال ضيق بـ `backend/storage/app/capver.sqlite`، ويُشغَّل الأمر، ثم القراءة والتكرار. `G6-06` يبقى خارج القائمة.
