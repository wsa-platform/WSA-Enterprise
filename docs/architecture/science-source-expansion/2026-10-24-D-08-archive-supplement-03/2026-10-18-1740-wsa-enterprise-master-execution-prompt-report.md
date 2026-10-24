<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17063). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## A) الملخص التنفيذي

نُفِّذ تفعيل **نطاق محدود** لـ AGRIS و**إسقاط محدود** لـ Dr. Duke: تسجيل قرار النطاق، onboarding ACTIVE، CapVer، تسجيل محكوم، وبحث LocalIndex فعلي.

| المصدر | الحالة |
|---|---|
| **G1-01 AGRIS** | **ACTIVE** (مجموعة فرعية مرخّصة — ليس الجسم الكامل) |
| **G1-17 Dr. Duke** | **ACTIVE** (إسقاط بحث محدود — ليس الإسقاط الكامل) |
| G5-04 / G1-02 / G6-20 | ACTIVE محفوظة |
| Cap Store Postgres | `STORE_UNAVAILABLE` — لا ادّعاء persist إنتاجي |
| PRODUCTION_READY | **لا** |

التقرير الكامل: `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-AGRIS-DUKE-ACTIVATION-COMPLETION-REPORT.ar.md`

---

## B) AGRIS — G1-01

- **النطاق:** `ACCEPT_BOUNDED_SUBSET` — GEN=4 / **1317** صفًا / مؤشر **10/1244** / CC-BY-4.0 لكل أثر مُدخل  
- **السجل:** `2026-10-18-OWNER-DECISION-RECORDED-G1-01-G1-17-SCOPE.v1.json` + ADR-023 §8.54  
- **CapVer:** `cap-r12-20261018-G1-01-search` — `activation_pass=yes`، Cap Store = `STORE_UNAVAILABLE`  
- **الكتالوج:** `has(agris)=true`، `GOVERNED_ONBOARDED`  
- **بحث دائم:** `wheat` → `success` (هويات/عناوين محفوظة)  
- **التصنيف:** ACTIVE محدود ≠ تغطية كاملة ≠ PRODUCTION_READY

---

## C) Dr. Duke — G1-17

- **النطاق:** `ACCEPT_LIMITED_PROJECTION` — 16 جدولًا / GEN=2 / **1500** صفًا  
- **FARMACY ≠ FARMACY_NEW**؛ ETHNOBOT = استخدام تقليدي فقط  
- **CapVer:** `cap-r12-20261018-G1-17-search` — نفس فصل Cap Store  
- **الكتالوج:** `has(dr_duke)=true`  
- **بحث دائم:** `Mentha` → `success`، هوية `fnftax:2008`  
- **التصنيف:** ACTIVE محدود ≠ إسقاط كامل ≠ PRODUCTION_READY

---

## D) البنية المشتركة

كتالوج نهائي:  
`openalex,crossref,semantic_scholar,consensus,fao_stat,agris,dr_duke,gbif,organic_eprints,bhl`  
انحدار GBIF / OE / BHL أخضر في الحزمة المركّزة.

---

## E) السجلات

- ADR-023 **§8.54**  
- مصفوفة `2026-10-18-FIVE-SOURCE-COMPLETION-MATRIX.v3.json`  
- عقود ACTIVE في `NextGateEligibleSeatCatalog`  
- توسيع CapVer + ملفات تعريف + جسر الهوية  

---

## F) الاختبارات

| الأمر | النتيجة |
|---|---|
| `scientific:capver-accepted` | 0 — G1-01/G1-17: pass مع STORE_UNAVAILABLE |
| PHPUnit مركّز | **48 / 432** — OK |
| مسبار LocalIndex AGRIS/Duke | success |

---

## G) العوائق المتبقية

1. **Cap Store Postgres** — سائق/مضيف غير متاح → إغلاق عند `PERSISTED`  
2. **OD-PGSQL LocalIndex** — sqlite محلي ≠ إنتاج  
3. **جسم AGRIS الكامل** و**إسقاط Duke الكامل** — معالم منفصلة عن التفعيل المحدود  

---

## H) Git

- الفرع: `phase-18-m18-ai-marketing-communications`  
- HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
- **لا commit / push / Docker / reset**  
- WIP محفوظ؛ أرشيف المهام **645** إدخالًا
