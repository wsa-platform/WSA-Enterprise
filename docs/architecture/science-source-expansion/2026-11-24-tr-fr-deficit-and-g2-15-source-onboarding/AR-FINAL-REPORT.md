# التقرير النهائي — استعادة الري الناقص TR/FR وإضافة مصدر G2-15

**التاريخ:** 2026-11-24  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**المستودع:** `wsa-platform/WSA-Enterprise`

---

## A. الملخص التنفيذي

نُفّذ مساران مستقلان: (أ) إصلاح مسار فهم/بناء الاستعلام وتركيب الإجابة لأسئلة الري الناقص بالتركية والفرنسية؛ (ب) تقييم مصدر Andalasian Livestock (G2-15) مع مقارنة G4-02 دون تفعيل.

**ما تحسّن:** إجابات TR وFR للري الناقص أصبحت ذات مادة علمية حية (MAD/DI وWUE) بدل `NOT_RESPONSIVE` / chrome محدود. EN وAR بقيتا مكتملتين موضوعيًا على المسبار الحي.

**ما لم يكتمل:** إضافة/تفعيل G2-15 محجوبة بالترخيص CC BY-NC-SA للاستخدام التجاري؛ لا حصاد ولا فهرسة.

| مهمة | التصنيف |
|---|---|
| Mission A | `TR_FR_DEFICIT_RECOVERY_VERIFIED` |
| Mission B | `BLOCKED_LICENSE` |
| الإجمالي | `COMBINED_MISSION_PARTIALLY_COMPLETED` |

---

## B. Git Baseline والحالة النهائية

| الحقل | القيمة |
|---|---|
| Starting SHA | `f09e2b8b56e484dcb05e8d635deaf318f69134f5` |
| WIP | محمي؛ ملفات غير مرتبطة مستبعدة من الـ commits |
| Feature Commit (Mission A) | `09b5764de4b5f8a59ad1ac807bfbb56c4802fe27` |
| Docs Commit | `d06d1f7e1347e1e332ca66ffa40a855a21c3d64f` |
| Final Local / Remote | `d06d1f7e1347e1e332ca66ffa40a855a21c3d64f` (Local = Remote بعد fetch) |

---

## C. Mission A — استعادة الري الناقص TR/FR

### Baseline
EN/AR: COMPLETE · TR/FR: NOT_RESPONSIVE (انظر `00-MISSION-BASELINE.md`).

### Root Cause المثبت
1. انهيار `process_surface` التركي إلى `sulama` بسبب regex الملكية أحادي الكلمة.  
2. `compileTerm` أعاد السطح اللاتيني قبل English scholarly fallback → الاستعلام بلا `deficit irrigation`.  
3. غياب `original_question` في سياق `RetrievalSpecification`.  
4. فلاتر اختيار سابقة لـ MAD/DI (عولجت في AnswerComposer).

### المكونات المعدَّلة
`AgriculturalEntityCatalog`, `RetrievalSpecification`, `ScientificQueryCompiler`, `ScientificSearchQueryBuilder`, `AnswerComposer`, اختبارات `TrFrDeficitIrrigationRecoveryTest`.

### جودة الإجابات بعد الإصلاح (حي)
| لغة | الحالة |
|---|---|
| EN | SUBSTANTIVELY_COMPLETE |
| AR | SUBSTANTIVELY_COMPLETE |
| TR | SUBSTANTIVELY_COMPLETE |
| FR | SUBSTANTIVELY_COMPLETE |

الدليل: `tr_fr_deficit_live_probe.json`.

### Acceptance Gates (A)
Gates 1–18: PASS للمعالجة الحية والاختبارات المستهدفة؛ React/Flutter: NOT_APPLICABLE لإعادة التشغيل الكامل.

---

## D. Mission B — إضافة المصدر

| بند | النتيجة |
|---|---|
| هوية G2-15 | Andalasian Livestock / `andalasian_livestock` / OJS OAI |
| الوصول / WAF | Identify + ListRecords HTTP 200 — ليس BLOCKED_ACCESS |
| الترخيص | CC BY-NC-SA 4.0 → **BLOCKED_LICENSE** للاستخدام التجاري Enterprise |
| G4-02 | قاعدة جينوم دودة القز — ليست بديلاً لثروة حيوانية إنتاجية |
| القرار | لا تفعيل؛ لا Membership flip؛ لا حصاد |
| التصميم | موثّق فقط في `07`؛ التنفيذ غير مسموح قبل حسم الترخيص |

### Acceptance Gates (B)
Access PASS · License FAIL-closed · Integration NOT_APPLICABLE · Activation NOT performed.

---

## E. الانحدار عبر المهمتين

مصفوفة الري العمومي + Claim Attribution: PASS.  
لا تأثير LocalIndex من G2-15.  
EN/AR/TR/FR deficit على المسبار الحي: substantive.  
CWR إنجليزي: substantive مع قيد خليط ناجٍ معروف سابقًا.

---

## F. أدلة الاختبارات

| Test Suite | Command | Result | Passed | Failed | Skipped | Exit |
|---|---|---|---:|---:|---:|---:|
| TrFrDeficitIrrigationRecoveryTest | `docker compose exec -T backend php artisan test --filter=TrFrDeficitIrrigationRecoveryTest` | PASS | 5 | 0 | 0 | 0 |
| Irrigation + Claim + TR/FR | `--filter="TrFrDeficitIrrigationRecoveryTest\|IrrigationGeneralitySemanticMatrixTest\|ClaimAttributionLedgerTest"` | PASS | 43 | 0 | 0 | 0 |
| Live deficit probe | `php storage/app/e2e-tmp/_tr_fr_deficit_live_probe.php` | PASS (manual completeness) | 5 cases | 0 | — | 0 |

Flutter: NOT_RUN · React Playwright: NOT_RUN (لا تغيّر شكل payload يفرض إعادة إثبات UI).

---

## G. القيود

انظر `11-LIMITATIONS-AND-UNVERIFIED-AREAS.md`.

---

## H. Git والإصدار

| Commit | SHA |
|---|---|
| Start | `f09e2b8b56e484dcb05e8d635deaf318f69134f5` |
| Mission A feature | `09b5764de4b5f8a59ad1ac807bfbb56c4802fe27` |
| Documentation | `d06d1f7e1347e1e332ca66ffa40a855a21c3d64f` |
| Remote HEAD (post-push) | `d06d1f7e1347e1e332ca66ffa40a855a21c3d64f` |

`git push` عادي إلى `phase-18-m18-ai-marketing-communications`؛ `LOCAL_EQ_REMOTE=YES` بعد `git fetch`. لا Force Push.

---

## I. القرار الهندسي النهائي

- **Mission A:** `TR_FR_DEFICIT_RECOVERY_VERIFIED`  
- **Mission B:** `BLOCKED_LICENSE` (تقييم مكتمل؛ إضافة غير منفَّذة عن قصد)  
- **Combined:** `COMBINED_MISSION_PARTIALLY_COMPLETED`

---

## 17. الخطوة التالية المقترحة (خطة فقط — لا تنفيذ)

### 17.1 متبقي TR/FR
لا إعادة تنفيذ الاستعادة. عمل لاحق اختياري: توحيد طول/تغطية FR مع EN؛ تنقية ناجيات CWR من wastewater عند سؤال الاحتياج فقط (~0.5–1 يوم).

### 17.2 متبقي المصدر
الخيار الأفضل علميًا للثروة الحيوانية يبقى **G2-15** إذا حُسم الترخيص لغير التجاري أو وُجد إذن إعادة استخدام. وإلا ابحث عن مصدر Livestock بترخيص مفتوح تجاريًا (CC BY / CC0 / ناشر يسمح بالفهرسة). G4-02 مرفوض كنطاق. تقدير بوابة ترخيص قانونية + تصميم حصاد: 1–2 يوم بعد الإذن.

### 17.3 خطة المهمة التالية الموحدة

| بند | المحتوى |
|---|---|
| Mission Name | Livestock OA Source Onboarding under Cleared License **أو** CWR Survivor Hygiene |
| Objective | إما تفعيل مصدر ثروة حيوانية مرخّص، أو تنقية إجابات CWR من أدلة غير مطابقة للمعاملة |
| Current State | TR/FR deficit recovered; G2-15 blocked on NC license |
| Gaps | License clearance; optional CWR ranking hygiene |
| Architecture | OAI→LocalIndex path unchanged; no WAF bypass |
| Workstreams | Legal/reuse gate → harvest design → retrieval proof **∥** CWR selection polish |
| Dependencies | Written reuse permission or alternate CC BY source |
| Risks | NC ShareAlike contamination of LocalIndex |
| Testing | Access/license evidence; harvest idempotency; livestock live probe; CWR paired EN/AR |
| Acceptance | `SOURCE_ADDITION_VERIFIED` فقط بعد استرجاع علمي مسند؛ أو `CWR_HYGIENE_VERIFIED` |
| Deliverables | ADR-023 seat update only if activated; mission docs; probes |
| Estimate | License path 1–3d; CWR hygiene 0.5–1d |
| DoD | Gates مستقلة؛ لا تفعيل بلا ترخيص |
| Readiness | `READY_FOR_PLANNING` |
| Parallelism | يمكن توازي CWR hygiene مع انتظار الترخيص؛ لا تفعيل مصدر بلا بوابة ترخيص |
