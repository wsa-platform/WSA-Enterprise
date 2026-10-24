<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17997). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
**الحالة النهائية:** `BLOCKED — DATABASE CONNECTION`

تفويض PostgreSQL لخلايا CapVer مسجَّل في ADR-023 §8.67. الأمر لم يُشغَّل لأن `127.0.0.1:5432` يرفض الاتصال. لا كتابة، ولا قراءة راجعة، ولا `ALREADY_CURRENT`. التقرير في `docs/2026-10-23-CAPVER-POSTGRESQL-CONNECTION-BLOCKER-REPORT.ar.md`. لم يُنفَّذ ترحيل ولا التزام ولا دفع، ولم يُستخدم Docker.

## 1. الملخص

مسار CapVer في الكود يصل إلى الاتصال الافتراضي: `pgsql` نحو `127.0.0.1:5432` وقاعدة `wsa_enterprise` والمستخدم `wsa`. فحص TCP عند `2026-10-23T10:07:23Z` أعاد `errno=10061`. فحص الصباح بـ `Test-NetConnection` أعاد `TcpTestSucceeded=False`. لا مستمع على المنفذ، فتوقفت بوابة الهوية قبل أي كتابة.

## 2. خط الأساس

المستودع `G:\WSA-Enterprise`، الفرع `phase-18-m18-ai-marketing-communications`، HEAD قبل وبعد `fb60b60746ed487e2bfa04e3d335827d91cda446`. `git diff --cached` فارغ. شجرة العمل السابقة محفوظة.

PHP `G:\tools\php-8.4.26-nts\php.exe` إصدار 8.4.26، و`pdo_pgsql` محمّل. لا كاش إعدادات في `backend/bootstrap/cache/config.php`. ملف السجلات العلمية بقي طوله `1393659904` ووقته `2026-10-22T09:53:58Z`.

## 3. السبب

`CghiaCapabilityCell` و`EloquentCapabilityStoreRepository` و`scientific:capver-accepted` تستخدم اتصال Laravel الافتراضي. القيمة الفعلية `DB_CONNECTION=pgsql`. هذا هو الهدف الذي فوّضه المالك. العيب ليس في الربط: لا عملية تقبل المنفذ 5432. بحث الصباح لم يجد خدمة Windows باسم PostgreSQL ولا `pg_dump.exe` تحت `C:\Program Files\PostgreSQL`. `docker-compose.yml` ينشر المنفذ نفسه، وتشغيل Docker ممنوع هنا فلم يُستخدم.

## 4. الاتصال والمخطط

| البند | النتيجة |
|---|---|
| الاتصال | `default` ← `pgsql` |
| الهدف في الإعداد | `wsa_enterprise` على `127.0.0.1:5432`، المستخدم `wsa` |
| إصدار الخادم والجدول | غير مفحوصين. PDO لم يُفتح |
| عقد الملف | `cghia_capability_cell_records` في الترحيل `2026_10_09_140000_create_cghia_capability_cell_records_table.php` |

وجود الجدول داخل القاعدة الحية غير مثبت اليوم. سجل §8.60 تاريخي فقط.

## 5–6. النسخ والترحيل

لم تُنشأ نسخة ولم تُطبَّق ترحيلة. بوابة النسخ تسبق الترحيل، والاتصال سقط قبلها. إن فُتح الخادم لاحقاً والجدول غائب: `pg_dump` أولاً إلى مسار نسخ معتمد، ثم الترحيل القائم وحده. لا إسقاط جدول ولا حذف من `migrations`.

## 7. الملفات

- `docs/architecture/ADR-023-scientific-source-expansion.md`: إضافة §8.67 فقط. `git diff --check` خروج 0.
- `docs/2026-10-23-CAPVER-POSTGRESQL-CONNECTION-BLOCKER-REPORT.ar.md`: هذا التقرير.
- لا تغيير في النموذج ولا المستودع ولا الأمر.

## 8–11. التنفيذ

السكربت توقف عند `TCP=closed errno=10061` وخرج 0 من غير محاولة مصادقة. `scientific:capver-accepted` لم يُشغَّل. لا صفوف، ولا قراءة، ولا تشغيل ثانٍ.

## 12. الاختبارات

`php.exe vendor\phpunit\phpunit\phpunit --configuration phpunit.xml --do-not-cache-result tests\Unit\Agriculture\Research\Capability\CapVer\CapVerSeatVerificationServiceTest.php`

`OK (5 tests, 48 assertions)`، خروج 0. المخزن في هذه الاختبارات مزيّف. هي تثبت حدود التفويض، ومنها رفض `G6-06`، ولا تثبت إصرار PostgreSQL.

## 13. المقاعد

`G5-04` و`G1-02` و`G6-20` و`G1-01` و`G1-17` داخل `capVerAuthorizedSeats()`. لم يُنفَّذ أي منها. `G6-06` بقي خارج القائمة.

## 14. ADR

§8.67 تسجّل تفويض PostgreSQL لخلايا CapVer فقط، وتُبقي مخزن السجلات على SQLite حسب §8.62، ولا تعيد كتابة §8.66. الإصرار نفسه غير متحقق في هذا القسم.

## 15. القبول

هوية المستودع والسلامة ووحدة مسار الكود وعزل المخزن العلمي واختبار الوحدة: `PASS`. فحص الجدول والأمر والقراءة والتشغيل الثاني والإصرار الفعلي: `FAIL` أو `BLOCKED` لأن المنفذ مغلق. النسخة والترحيل: `NOT VERIFIED` لأنهما لم يُطلبا بعد فشل الاتصال.

## 16. الخطوة التالية

أصغر تصحيح: مستمع PostgreSQL على `127.0.0.1:5432` لقاعدة `wsa_enterprise` بالمستخدم الموجود في `.env`. بعد نجاح الاتصال تُعاد بوابة الهوية، ثم فحص الجدول، ثم النسخ إن لزم الترحيل، ثم `scientific:capver-accepted` والقراءة والتكرار.

## 17. Git

التوقف قبل `git add` و`git commit` و`git push`. الفهرس فارغ.
