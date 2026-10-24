<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17216). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير اكتشاف قواعد البيانات (قراءة فقط) — WSA-Enterprise

**التاريخ:** 2026-10-19  
**المسار:** `G:\WSA-Enterprise`  
**HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**التعديلات في هذه المهمة:** لا شيء  

---

## A. الملخص التنفيذي

المشروع يعمل بمخزنين منفصلين، وليست قاعدة واحدة:

| المخزن | المحرك المقصود | أين البيانات على هذا الجهاز؟ |
|---|---|---|
| **قاعدة Laravel الرئيسية / Cap Store** | PostgreSQL (`pgsql`) | **غير مثبت وجودها** على PostgreSQL 17 المحلي كقاعدة `wsa_enterprise` |
| **LocalIndex (السجلات العلمية)** | SQLite افتراضياً | **مثبت:** ملف ~1.35 GiB على القرص |
| **أرشيف المهام** | SQLite | **مثبت:** `archive.sqlite` ~6.6 MiB |

**ما ثبت:**
- Laravel مضبوط حالياً على `DB_CONNECTION=pgsql`، `DB_HOST=127.0.0.1`، `DB_DATABASE=wsa_enterprise`، `DB_USERNAME=wsa`.
- لا يوجد `bootstrap/cache/config.php` → لا cache يغيّر الإعداد عن `.env`.
- التصميم والـ Compose وCI يتوقعون PostgreSQL لقاعدة التطبيق (`wsa_enterprise` / مستخدم `wsa`)، غالباً عبر خدمة Docker اسمها `postgres` (صورة 16).
- بيانات LocalIndex موجودة فعلياً في SQLite وليست داخل PostgreSQL المحلي.
- لا ملفات `.sql`/`.dump` للنسخ الاحتياطي ظهرت في مسح المستودع.

**ما لم يثبت:**
- اتصال Laravel الناجح الآن بقاعدة تطبيق PostgreSQL.
- وجود جداول Cap Store/migrations على أي خادم يمكن الوصول إليه من Windows بدون إعداد إضافي.
- محتوى volume Docker `postgres_data` (إن وُجد من تشغيل سابق) — خارج نطاق فحص المستودع / لم يُفتح Docker.

**طبيعة المشكلة (مركّبة):** ليست «محرك خاطئ في الكود».  
المشكلة = **محاذاة بيئة**: إعداد التطبيق يشير إلى PostgreSQL باسم قاعدة ودور غير موجودين على الخادم المحلي الذي اختبره المالك، بينما البيانات العلمية الكبيرة موجودة أصلاً في **SQLite LocalIndex**. نجاح `psql` كـ `postgres`/`postgres` لا يعني اتصال التطبيق بـ `wsa`/`wsa_enterprise`.

---

## B. مصفوفة الأدلة

| العنصر | القيمة / الحالة | مصدر الدليل | الثقة |
|---|---|---|---|
| محرك التطبيق المقصود (إعداد) | `pgsql` | `backend/.env` → `DB_CONNECTION=pgsql` | مؤكد |
| المضيف الحالي | `127.0.0.1` | `.env` (بعد إصلاح سابق من `postgres`) | مؤكد |
| المنفذ | `5432` | `.env` | مؤكد |
| اسم القاعدة المتوقع | `wsa_enterprise` | `.env`، `.env.example`، `docker-compose.yml` | مؤكد |
| المستخدم المتوقع | `wsa` | `.env`، Compose `POSTGRES_USER` | مؤكد |
| كلمة المرور | مضبوطة (لم تُعرض) | `.env` | مؤكد الوجود فقط |
| Configuration cache | غير موجود | `backend/bootstrap/cache/config.php` = False | مؤكد |
| افتراضي `config/database.php` إن غاب `DB_CONNECTION` | `sqlite` محلياً ما لم `DATABASE_URL`؛ `pgsql` في production | `config/database.php` أسطر 6–10 | مؤكد |
| اتصالات معرفة في الكود | `sqlite`, `scientific_records`, `mysql`, `mariadb`, `pgsql`, `pgsql_testing`, `sqlsrv` | نفس الملف | مؤكد |
| LocalIndex driver افتراضي | `sqlite` → `storage/app/scientific_record_store.sqlite` | `config/database.php` | مؤكد |
| ملف LocalIndex | موجود، ~1354821632 بايت | فحص نظام الملفات | مؤكد |
| أعداد LocalIndex (عينة سابقة) | agris≈87984، duke≈211948، OE≈189 | `e2e-tmp/v22_evidence2.json` | مؤكد (قياس سابق) |
| `database/database.sqlite` (افتراضي Laravel sqlite) | **غير موجود** | فحص المسار | مؤكد |
| أرشيف المهام SQLite | موجود ~6.6 MiB | `storage/app/mission_artifact_archive/archive.sqlite` | مؤكد |
| نسخ `.sql`/`.dump` في المستودع | لم يُعثر عليها في المسح | مسح المستودع | مرجح (لا دليل إيجابي) |
| مسار Compose التاريخي | خدمة `postgres:16-alpine`، DB/user = wsa_enterprise/wsa، volume `postgres_data` | `docker-compose.yml` | مؤكد كتصميم |
| CI | `DB_CONNECTION=pgsql` + Postgres 16 للاختبارات؛ suite افتراضي SQLite | `.github/workflows/ci.yml`، `tests/bootstrap.php` | مؤكد |
| اختبارات PHPUnit الافتراضية | تُجبر `sqlite` / `:memory:` | `phpunit.xml` + `tests/bootstrap.php` | مؤكد |
| Migrations Cap Store | جزئياً بصيغة PostgreSQL (فهارس جزئية `WHERE`) | `2026_10_09_140000_…` | مؤكد التوافق مع PG |
| PG المحلي 17.11 يقبل 5432 | نعم | إفادة مالك + `pg_isready` سابق | مؤكد |
| قاعدة `wsa_enterprise` على PG المحلي | **غير موجودة** | إفادة مالك (SQL Shell) | مؤكد (جلسة المالك) |
| دور `wsa` على PG المحلي | **غير ظاهر** | إفادة مالك | مؤكد (جلسة المالك) |
| تعارض سابق: فشل مصادقة `wsa` من PHP | سابقاً `password authentication failed for user "wsa"` | مسبار v4.4 | مرجح تاريخياً / يتعارض مع تقرير المالك الحالي → يحتاج إعادة تحقق إداري |
| اتصال Laravel الحالي بقاعدة التطبيق | غير ناجح / غير مُعاد اختباره في هذه المهمة | قيود §7 + غياب القاعدة/الدور | مؤكد الغياب المنطقي |
| بيانات Cap Store على القرص المحلي | غير مثبتة كجداول PG؛ migrations فقط في الكود | لا dump؛ لا اتصال ناجح | غير متحقق كبيانات حية |

---

## C. رسم مسار الاتصال الحالي (بالإعدادات المكتوبة)

```
Laravel (APP_ENV=local)
  → env: DB_CONNECTION=pgsql
  → config/database.php connections.pgsql
  → host=127.0.0.1  port=5432
  → database=wsa_enterprise  user=wsa
  → محرك متوقع: PostgreSQL
  → هدف فعلي على الخادم المحلي: قاعدة/دور غير موجودين حسب فحص المالك
  → النتيجة: لا اتصال تطبيق ناجح بقاعدة المشروع الرئيسية
```

**مسار منفصل (يعمل على الملفات):**

```
Laravel scientific_records (افتراضي)
  → driver=sqlite
  → file=storage/app/scientific_record_store.sqlite
  → بيانات علمية كبيرة موجودة فعلياً
```

**مسار تاريخي موثّق (Compose — لم يُشغَّل في هذه المهمة):**

```
خدمات Docker → hostname "postgres" → postgres:16
  → POSTGRES_DB=wsa_enterprise / USER=wsa
  → حجم Docker: postgres_data
```

---

## D. موقع قاعدة بيانات المشروع

1. **بيانات البحث العلمي (LocalIndex):**  
   **مثبتة محلياً** في  
   `G:\WSA-Enterprise\backend\storage\app\scientific_record_store.sqlite`  
   هذه ليست قاعدة Laravel الرئيسية وليست Cap Store.

2. **قاعدة Laravel / Cap Store (`wsa_enterprise`):**  
   - **على PostgreSQL 17 الحالي:** غير موجودة حسب المالك.  
   - **الأرجح تاريخياً كتصميم:** داخل volume Docker `postgres_data` إن سبق تشغيل Compose — **وجود البيانات هناك غير متحقق** (لم يُفحص الـ volume؛ Docker محظور هنا).  
   - **لا دليل** على MySQL/MariaDB/SQL Server كمخزن تشغيل حالي.  
   - **لا ملف** `database/database.sqlite` لقاعدة التطبيق الافتراضية.

3. **أرشيف المهام:** SQLite منفصل تحت `mission_artifact_archive/`.

**خلاصة:** ما يمكن إثباته كـ «بيانات مشروع حية على القرص» الآن هو LocalIndex (+ أرشيف)، وليس نسخة PostgreSQL لـ Cap Store/الجداول العامة على الخادم المحلي.

---

## E. سبب عدم اتصال المشروع حالياً

| نجاح المالك | ما يحتاجه التطبيق |
|---|---|
| مستخدم `postgres` + قاعدة `postgres` | مستخدم `wsa` + قاعدة `wsa_enterprise` |
| SQL Shell إداري | Laravel عبر `.env` |

الفجوات المتزامنة:
1. **غياب القاعدة** `wsa_enterprise` على الخادم المحلي.  
2. **غياب الدور** `wsa` (حسب فحص المالك).  
3. سابقاً أيضاً: اسم مضيف Compose `postgres` (أُصلح إلى `127.0.0.1` في `.env`).  
4. حتى مع المضيف الصحيح، لا يوجد هدف قاعدة مطابق لإعداد Laravel.

لذلك نجاح `psql` لا يعني أن CapVer أو migrations يمكنهما العمل.

---

## F. خطة الإصلاح الآمنة (لا تُنفَّذ هنا)

كل خطوة تحتاج موافقة صريحة قبل التنفيذ:

1. **قرار ملكية البيانات:** هل توجد بيانات Cap Store/تطبيق يجب استعادتها من volume Docker/`postgres_data`، أم البدء بقاعدة فارغة جديدة على PG 17؟ *(موافقة)*  
2. إن وُجد volume قديم ويُراد الحفظ: نسخ احتياطي/تصدير قبل أي شيء — **لا migrate:fresh**. *(موافقة + إجراء منفصل)*  
3. إنشاء قاعدة `wsa_enterprise` ودور `wsa` بصلاحيات أدنى على PG المحلي، مع مصادقة متطابقة مع `DB_PASSWORD` عبر `\password` / تحديث `.env` فقط. *(موافقة)*  
4. الإبقاء على `DB_HOST=127.0.0.1` و`DB_CONNECTION=pgsql` بعد إثبات الاتصال.  
5. `scientific:diagnose-persistence` (قراءة/تشخيص).  
6. تشغيل **migrations المطلوبة فقط** على القاعدة الجديدة/المستعادة — ليس `migrate:fresh`. *(موافقة)*  
7. CapVer + قراءة مستقلة لـ Cap Store.  
8. **عدم** خلط LocalIndex SQLite مع قاعدة PostgreSQL إلا بقرار ترحيل صريح منفصل (`SCIENTIFIC_RECORD_STORE_*`).

---

## G. سجل التنفيذ

**ملفات فُحصت (قراءة):**  
`.env`، `.env.example`، `config/database.php`، وجود/غياب `bootstrap/cache/config.php`، `composer.json`، `phpunit.xml`، `tests/bootstrap.php`، `docker-compose.yml`، `.github/workflows/ci.yml`، migration Cap Store، مسح `*.sqlite`، مسح `*.sql`/`*.dump`، `e2e-tmp/v22_evidence2.json`.

**ملفات عُدّلت:** لا شيء.

**أوامر ذات صلة:**  
قراءة `.env` غير السرية؛ فحص وجود ملفات؛ مسح المستودع؛ (أعداد LocalIndex من دليل سابق — إعادة العد التفصيلي عبر PHP فشلت بسبب اقتباس PowerShell، والملف/الأحجام مؤكدة).

**اختبارات اتصال Laravel/PG في هذه المهمة:** لم تُنفَّذ (قيد §7 + عدم الحاجة لإعادة استخدام أسرار).  
**إفادات المالك المعتمدة هنا:** لا `wsa_enterprise`؛ لا دور `wsa` ظاهر؛ اتصال `postgres`/`postgres` ناجح.

**توقف.** لا إنشاء قواعد/أدوار، لا migrations، لا استعادة، لا commit/push.
