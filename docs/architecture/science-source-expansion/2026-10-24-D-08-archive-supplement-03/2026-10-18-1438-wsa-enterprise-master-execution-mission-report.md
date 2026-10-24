<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 16934). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير هندسي شامل — مهمة التكامل النهائية لخمسة مصادر علمية (ما قبل الالتزام)

**المسار:** `G:\WSA-Enterprise`  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0` (**يطابق** التقرير السابق)  
**عضوية:** **110.v1**  
**commit / push / deploy / Docker / reset:** **لم يحدث**

النسخة المؤرشفة الكاملة:  
`docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-MASTER-FIVE-SOURCE-FINAL-REPORT.ar.md`

---

## 1) الملخص التنفيذي

| | |
|--|--|
| **GBIF** | يبقى **ACTIVE** — بلا إعادة بناء |
| **Organic Eprints** | GEN=2 / 189 صفًا؛ بحث عبر عملية جديدة ناجح؛ **NOT_READY** |
| **BHL** | PublicationSearch حي Status=ok، 52 نتيجة لـ `Triticum`؛ **NOT_READY** |
| **AGRIS** | حصاد ODS محدود: **5/1244** موفّرًا، **581** سجلًا، GEN=3، بحث `Soils` ناجح؛ **ليس** اكتمال الجسم؛ **NOT_READY** |
| **Dr. Duke** | مصالحة 16 جدولًا؛ FARMACY≠FARMACY_NEW بلا دمج أعمى؛ **NOT_READY** |
| **تفعيلات جديدة** | **0** |
| **PostgreSQL** | **PENDING** |
| **الاختبارات** | **OK — 214 tests, 6902 assertions** |

---

## 2) حالة Git وWorktree

- Worktree غير نظيف (~140 سطر porcelain) — **محفوظ وغير ملتزم**
- لا تفعيل مزيف؛ قرارات المالك السابقة لم تُعاد كتابتها

---

## 3) النتائج حسب المصدر

### G5-04 GBIF — ACTIVE
انحدار ضمن حزمة 214 اختبارًا؛ لا صفوف LocalIndex (نموذج API حي).

### G1-02 Organic Eprints — NOT_READY
`CONN=scientific_records` / sqlite؛ GEN=2 / 189؛ `oai:orgprints.org:100` عبر عملية جديدة. ينتظر onboarding + CapVer + Postgres.

### G6-20 BHL — NOT_READY
مفتاح موجود (طول 36، غير مطبوع)؛ HTTP 200؛ PartID `36242`/`47009`/`150482`؛ `contains_bhl=false`. عزل الفشل: مجس حي لا يمس مخزن OE.

### G1-01 AGRIS — NOT_READY (حصاد محدود PASS)
جذر رسمي: `AGRIS.ODS.xml` (1244 URL). ميزانية 5: BG3, BRI, BRY, R26, TRA — كلها **CC-BY-4.0**. إدخال GEN=3 / 581. لا تعميم ترخيص على باقي الموفّرين. بيان: `2026-10-18-AGRIS-ODS-HARVEST-MANIFEST.v1.json`.

### G1-17 Dr. Duke — NOT_READY (مصالحة PASS)
16 جدولًا؛ FNFTAX/CHEMICALS/ETHNOBOT للبحث؛ FARMACY↔FNFTAX/CHEM مطابقة كاملة على عيّنة 50k؛ FARMACY_NEW.CHEMID: 48248 مطابقة / 1752 غير مطابقة؛ ETHNOBOT بالاسم: 18666 مطابقة / 31334 غير مطابقة. تقرير: `2026-10-18-DR-DUKE-RECONCILIATION.v1.json`.

---

## 4) LocalIndex / SQLite

متانة عبر عمليات مؤكَّدة؛ أجيال OE2 / AGRIS3 / Duke2؛ الافتراضي يبقى `pgsql` بلا تغيير إنتاجي. SQLite = تحقق محلي فقط.

---

## 5) الحوكمة والتفعيل

| مقعد | تفعيل |
|------|--------|
| GBIF | **ACTIVE** |
| الأربعة الأخرى | **NOT_READY** — حواجز مالك قائمة |

أدلة: `2026-10-18-MASTER-ACTIVATION-READINESS.v1.json`  
اقتراح (غير مقبول): `2026-10-18-MASTER-OWNER-DECISIONS-PROPOSAL.v1.json`

---

## 6) الاختبارات المنفَّذة

| | |
|--|--|
| حصاد AGRIS / مصالحة Duke / إدخال / مجسات OE·BHL·AGRIS | نُفِّذت فعليًا |
| PHPUnit Family projectors | OK 5/29 |
| PHPUnit Family+LocalIndex+Onboarding+Registry+Coexistence | **OK 214/6902** EXIT=0 |

---

## 7) الحواجز المتبقية (قرار المالك التالي)

1. Onboarding ACTIVE لـ G1-02 و/أو G6-20  
2. CapVer للمقاعد الأربعة  
3. قبول مسار DCAT لـ AGRIS وحدود التعميم الترخيصي  
4. نطاق إسقاط Duke (FARMACY صفوفًا أم علاقات فقط)  
5. PostgreSQL إنتاجي لـ LocalIndex  

**التوصية:** أقصى جاهزية مشروعة = GBIF وحده ACTIVE؛ الأربعة بأدلة تقنية قوية لكن محظورة حوكميًا. Worktree جاهز لمراجعة بشرية ما قبل الالتزام.
