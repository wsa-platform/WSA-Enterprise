# تقرير AGRIS G1-01 — حزمة القبول LocalIndex → Validation → AnswerComposer
## Revision 11.0.16 (Master Prompt Revision 3)

## A. Executive Summary
- **الهدف:** إثبات مسار AGRIS `G1-01` عبر LocalIndex مع حفظ الهوية العلمية للسؤال/الإجابة وجودة استعلام مشتق من CSQ.
- **المنفَّذ:** `AgrisLocalIndexQuerySpecializer`، `AgrisG101AcceptanceHarness`، `AgrisG101ReadOnlyGuard`، عيّنة LocalIndex قراءة فقط (gen 2088)، ربط الاستعلام في `variantsForAdapter`، اختبارات قبول، أدلة C1–C15.
- **النتيجة:** `PARTIALLY_COMPLETED` — مسار التطبيق قابل للتكرار على corpus معزول مأخوذ من LocalIndex؛ **لم يُمنح** `SCIENTIFIC_INTEGRATION_VERIFIED`.
- **هل وصل AGRIS إلى Composer؟** نعم عبر harness القبول (validate→compose) مع حفظ الهوية.
- **هل ثبتت الهوية العلمية؟** نعم لقطات الهوية متطابقة عبر البحث/التحقق/الصياغة؛ استعلام لا يوسّع المحصول إلى نوع آخر.

## B. Baseline & Git
- **الفرع:** `phase-18-m18-ai-marketing-communications`
- **HEAD الابتدائي:** `36c4b7e` (متزامن مع origin)
- **HEAD النهائي:** `6ad8a3c4bae55e2f3c191277581c755eb4cacb22`
- **Working Tree:** WIP سابق كثيف — لم يُمس ولم يُدرج في Commit
- **قاعدة LocalIndex:** `backend/storage/app/scientific_record_store.sqlite` (~9.8GB)، جيل AGRIS عند العيّنة = **2088**
- **Push:** نجح إلى `origin/phase-18-m18-ai-marketing-communications` (`36c4b7e..6ad8a3c`)
- **CI:** `CI_STATUS_UNAVAILABLE` (لم يُتحقق من نجاح CI في هذه الجلسة)

## C. Architecture Trace
```
Original Question → KnowledgeQueryPlan (CSQ surface)
  → AgrisLocalIndexQuerySpecializer (retrieval query versions; CSQ untouched)
  → GovernedScientificSourceEligibility / ScientificSourceSelector
  → MultiSourceScientificSearchOrchestrator::variantsForAdapter (AGRIS prepend)
  → LocalIndexScientificSourceAdapter::search
  → AgriculturalScientificValidationService::validate
  → AnswerComposer::compose
```
الملفات: المكونات أعلاه تحت `backend/app/Services/Agriculture/Research/...`.

## D. Scientific Question Identity
- **السؤال:** What are deficit irrigation effects on wheat yield?
- **CSQ surface:** Deficit irrigation effects on wheat yield
- **عناصر محفوظة:** crop=wheat، sense=irrigation، factors تتضمن deficit irrigation (كمقيّد استعلام منفصل)
- **النتيجة:** `identity.preserved=true` في dump القبول

## E. Query Engineering
- **القدرة الفعلية:** FTS5 / postings — تقاطع AND بعد توكنة؛ لا Boolean/wildcard/fuzzy مُدعى
- **الاستعلام الأساسي:** `wheat irrigation yield`
- **تحسين مضبوط:** نسخة `deficit` منفصلة؛ مرادف علمي/شائع مسجّل عند التوسع
- **الحقول:** title / abstract / original_identifier
- **حدود:** زيادة النتائج ليست معيار نجاح؛ الأولوية لحفظ النطاق

## F. Root Cause & Implementation
| فجوة | تصنيف | إصلاح |
|---|---|---|
| Composer الحي كان fixture فقط | `TEST_HARNESS_GAP` / `MISSING_EVIDENCE` | Acceptance Harness + عيّنة LocalIndex RO |
| استعلام طويل يفرّغ FTS AND | `IMPLEMENTATION_DEFECT` | Specializer بترميز FTS-safe |
| خطر الكتابة التشغيلية | سلامة | `AgrisG101ReadOnlyGuard` + corpus معزول |
| غياب استعلام AGRIS في المسار العام | `MISSING_FEATURE` | ربط في `variantsForAdapter` |

## G. C1–C15 Acceptance Matrix
انظر `2026-11-19-AGRIS-G1-01-ACCEPTANCE-EVIDENCE.v11.0.16.json` و`2026-11-19-PHASE-1-FIVE-SOURCE-FIFTEEN-CONDITIONS.v11.0.16.json`.
**C2 ظاهر صراحةً: PASS** (عقد القدرة/الوصول LocalIndex).
**C12: PARTIAL** (harness قابل للتكرار ≠ live agent E2E كامل).

## H. Composer & Answer Identity Evidence
- استرجاع ناجح (مثال dump: 8 survivors) مع `original_identifier`
- Validation وComposer مُستدعيان؛ سلسلة الأدلة مرتبطة
- الإجابة لا تُمنح VERIFIED؛ نقص الأدلة يُ留َى صادقًا عند عدم الكفاية

## I. Database Safety
- استخراج FTS/PK بـ `SQLITE_OPEN_READONLY` + `PRAGMA query_only`
- `db_unchanged=true` عند العيّنة؛ اختبار الحارس يثبت منع الكتابة
- اختبارات PHPUnit تستخدم InMemory من العيّنة المعزولة — لا Persist/FTS/activate

## J. Test Results
- `AgrisG101AcceptanceHarnessTest`: **6 passed / 44 assertions**
- `GovernedActiveSourceProductionPathIntegrityTest`: **3 passed / 26 assertions**
- البيئة: Docker `backend`

## K. Git Delivery
- **Commit:** `6ad8a3c` — `feat(agris): G1-01 LocalIndex acceptance harness with CSQ-safe query specialization`
- 11 ملف مهمة فقط؛ WIP السابق غير ملتزم
- **Remote HEAD بعد الدفع:** `6ad8a3c4bae55e2f3c191277581c755eb4cacb22`
- **CI:** `CI_STATUS_UNAVAILABLE`
- **Deployment:** لم يُنفَّذ

## L. Final Classification
**`PARTIALLY_COMPLETED`**

## M. Remaining Gaps
1. C12: مسار وكيل حي كامل ضد LocalIndex التشغيلي تحت سياسة عزل PHPUnit.
2. `SCIENTIFIC_INTEGRATION_VERIFIED` مؤجّل حتى قبول الحزمة التراكمية.
3. المقاعد الأربعة الأخرى خارج هذه المهمة.

## N. Next Step
تكرار قالب القبول لمصدر واحد تالٍ ضمن الخمسة (بعد قرار ترتيب المالك)، أو إغلاق فجوة C12 لـ AGRIS بسياسة اختبار RO تشغيلية معتمدة — دون ingest/FTS.
