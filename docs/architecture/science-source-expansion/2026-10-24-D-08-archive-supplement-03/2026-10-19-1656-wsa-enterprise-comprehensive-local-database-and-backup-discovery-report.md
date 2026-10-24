<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17241). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير اكتشاف قواعد البيانات والنسخ الاحتياطية داخل `G:\WSA-Enterprise`

**طبيعة العمل:** قراءة فقط · لا استعادة · لا تعديل · لا Docker · لا commit  

**مرجع خارجي للمقارنة (خارج المستودع):**  
`G:\New folder (3)\...\2026-09-11T22_27Z\wsa_enterprise_db` — PostgreSQL directory dump (~9.8 MB، لقطة Laravel رئيسية بتاريخ 2026-09-11).

---

## 1. نطاق البحث وما تعذّر

| النطاق | الحالة |
|---|---|
| `G:\WSA-Enterprise` تكراريًا (امتدادات DB/نسخ/أرشيف + أسماء backup/dump/wsa_enterprise + `toc.dat`) | نُفِّذ |
| استثناءات عملية من المسح الثقيل | `vendor/`, `node_modules/`, `.git/objects/` |
| `backups/production` داخل المستودع | **غير موجود** |
| أي `toc.dat` / PGDMP داخل المستودع | **لا يوجد** |
| `.sql` / `.sql.gz` / `.dump` / `.backup` لنسخ PG | **لا يوجد** داخل المستودع |
| ملفات/مجلدات محمية أو دائرية | لم تُبلَّغ أخطاء وصول مانعة للجرد الرئيسي؛ المسح اكتمل |

---

## 2. المرشحون (قواعد / نسخ)

| # | المسار | الحجم | آخر تعديل | النوع الفعلي | المحرك | الدور | الثقة |
|---|---|---:|---|---|---|---|---|
| A | `backend\storage\app\scientific_record_store.sqlite` | ~1292 MB | 2026-10-19 | SQLite 3 · `quick_check=ok` | SQLite | **Scientific LocalIndex** (`scientific_source_records` + FTS؛ بلا `users`/`jobs`) | عالية |
| B | `backend\storage\app\mission_artifact_archive\archive.sqlite` | ~6.3 MB | 2026-10-19 | SQLite 3 · ok | SQLite | **Mission Artifacts** (`mission_artifacts`) | عالية |
| C | `backend\storage\framework\testing\scientific_records_durability.sqlite` | ~53 KB | 2026-10-18 | SQLite 3 · ok | SQLite | **اختبار LocalIndex** | عالية |
| D | `admin-mobile\.dart_tool\...\*.db` | كيلوبايتات | 2026-08-16 | SQLite (Chrome) | SQLite | أداة تطوير متصفح — **ليست** Laravel | عالية (استبعاد) |
| E | `...\AGRIS.ODS.FR0.zip` وغيره من XML/JSONL | كبير | 2026-10-18/19 | أرشيف/بيانات حصاد | غير DB تطبيق | مجموعات AGRIS خام | عالية (استبعاد كـ Laravel DB) |
| F | `scripts\backup-production.sh` + docs M12.5 | نص | 2026-08 | سكربت | يشير إلى `pg_dump`→`./backups/production/*.sql.gz` | مسار نسخ **غير مملوء** محليًا | مؤكد كتصميم فقط |
| G | `e2e-tmp\*backup*` | PHP/نصوص | أيلول 2026 | نسخ WIP شيفرة | — | ليست قواعد بيانات | عالية (استبعاد) |
| H | `backend\database\database.sqlite` | — | — | **غائب** | — | لا قاعدة sqlite افتراضية للتطبيق | مؤكد |
| — | داخل المستودع: dump PostgreSQL كامل | — | — | — | — | **غير موجود** | مؤكد الغياب هنا |

لا Capability Store (`cghia_capability_cell_records`) في أي SQLite مفحوص.

---

## 3. ترتيب احتمال «قاعدة Laravel الرئيسية»

1. **لا مرشح داخل المستودع** يطابق مخطط Laravel الرئيسي (`users` + `migrations` التطبيق + جداول المجال التشغيلي كما في dump سبتمبر).  
2. أقوى بيانات حية داخل المشروع = **LocalIndex (A)** ثم **Mission archive (B)** — مخازن فرعية معمارياً.  
3. النسخة الخارجية المعروفة (11 سبتمبر) تبقى **أفضل مرشح معروف لقاعدة Laravel الرئيسية**، وهي **خارج** `G:\WSA-Enterprise`.

---

## 4. مقارنة تواريخ / أحجام / مخططات

| المصدر | التاريخ | الحجم | مخطط رئيسي؟ |
|---|---|---:|---|
| Dump Render الخارجي `wsa_enterprise_db` | 2026-09-11 | ~10 MB | **نعم** (PostgreSQL، 147 جدولًا بما فيها `users`/`migrations`) |
| LocalIndex SQLite في المشروع | 2026-10-19 | ~1.3 GB | **لا** — جداول علمية فقط (+ جدول `migrations` خاص بمخزن السجلات) |
| Mission archive | 2026-10-19 | ~6 MB | **لا** |
| Test durability sqlite | 2026-10-18 | ~53 KB | **لا** (اختبار) |

---

## 5. هل وُجدت نسخة أحدث من 11 سبتمبر 2026 لقاعدة Laravel الرئيسية؟

**داخل `G:\WSA-Enterprise`: لا.**  
لم يُعثر على dump/SQL/PG directory أحدث يحتوي مخطط التطبيق الرئيسي.  
ما هو أحدث زمنياً داخل المشروع هو LocalIndex/أرشيف/حصاد علمي — **وليست** استبدالاً لقاعدة Laravel الرئيسية.

---

## 6. ما ثبت / ما لم يثبت

**ثبت:**
- لا نسخة PostgreSQL لقاعدة التطبيق مخزّنة داخل المستودع.
- مجلدات `backups/production` المحلية فارغة/غير موجودة رغم السكربت.
- SQLite الثلاث ذات الصلة بالمنصة مصنّفة: LocalIndex / Mission / اختبار.
- لا `toc.dat` داخل المشروع.

**لم يثبت:**
- وجود نسخة Laravel رئيسية أحدث من لقطة 2026-09-11 في أي مكان آخر على الجهاز (خارج نطاق هذا البحث).
- محتوى volumes Docker أو نُسخ على خادم Render غير المستخرجة هنا.
- اكتمال/حداثة أي `.sql.gz` إنتاجي خارج المستودع.

---

## 7. الخطوة التالية الآمنة الوحيدة المقترحة

**الاعتماد على النسخة الخارجية المفحوصة سابقًا** (`...\2026-09-11T22_27Z\wsa_enterprise_db`) كمرشح وحيد مثبت لقاعدة Laravel الرئيسية، ثم — بعد موافقة منفصلة فقط — تخطيط استعادة معزولة على PostgreSQL متوافق مع إصدار المصدر (18.x)، مع الإبقاء على LocalIndex SQLite الحالي منفصلاً دون دمجه تلقائياً.

**توقف.** لا استعادة ولا تغيير في هذه المهمة.
