# تقرير تنفيذ مزدوج — GBIF G5-04 واستعادة البحث العلمي
## Revision 11.0.20 — Master Prompt Revision 4.2

## A. Executive Summary
| المسار | النتيجة | التصنيف |
|---|---|---|
| **A — GBIF G5-04** | قبول محدود لمسار species-search الحي + harness؛ provenance مثبت؛ لا ادّعاء سببية | `ACCEPTED_WITH_LIMITATIONS` |
| **B — استعادة البحث والإجابة** | جذر 64→0: رفض ملاءمة صحيح لنتائج كيمياء + عيب استعلام؛ جذر منتج مستقل: فشل process-pool concurrency يلفّق `request_exception` | `PARTIALLY_REPAIRED` / `BACKEND_RESTORED_PARTIAL_UI_PENDING` |

لا `SCIENTIFIC_INTEGRATION_VERIFIED`؛ لا إغلاق Phase 1؛ لا بدء BHL.

---

## B. Baseline and Git State
| الحقل | القيمة |
|---|---|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Initial HEAD | `6b97b3b1a26283027622d2a6cd10af97f65647fd` |
| Final HEAD | `97045a6c0bc2f655d5c2ab6db5e341ed53e13651` |
| Remote HEAD | `97045a6c0bc2f655d5c2ab6db5e341ed53e13651` (`REMOTE_SYNC_VERIFIED`) |
| WIP | محمي خارج Staging |

---

## C. Contract Findings
ADR-022 / ADR-023 حاكمان دون تعديل. عقد G5-04: REST `species/search` عام، ACTIVE، إسقاط تصنيفي. عناوين C1–C15 من مصفوفات Five-Source. AGRIS/OE/Duke محمولة.

---

## D. GBIF G5-04
تنفيذ سابق Partial (C5–C12). أُضيف `GbifSpeciesQuerySpecializer` و`GbifG504AcceptanceHarness`. Live: binomial عاري ينجح؛ لاحقة `species classification` تفرّغ النتائج. Provenance RestJson. C7 N/A (لا مفتاح). Occurrence ≠ causation.

---

## E. Scientific Search Root Cause
**السؤال الموثّق:** `ما هو تأثير درجة الحرارة على نبات الزنجبيل؟` (`acc-q1-validate.json`).

1. **64→0:** النتائج كيمياء/طبي/دواجن؛ بوابة الملاءمة رفضت بصحة علمية.
2. **عيب استعلام:** ذيول `thermal growth` تجذب ضوضاء كيميائية.
3. **عائق منتج مستقل:** `Concurrency::driver('process')` عند الفشل كان يضع `request_exception` بدون محاولات حقيقية → `all_sources_failed` في الواجهة.

---

## F. Search Restoration
| السيناريو | raw | survivors | validated | إجابة |
|---|---|---|---|---|
| Ginger scholarly-only | 66 | 2 | 2 | لا (honest insufficient) |
| Wheat scholarly-only | 58 | 20 | 20 | نعم (`synthesis_completed_with_partial_conflicts`) |
| UI قمح (موجة قبل اكتمال الإصلاح) | — | — | — | `no_search_results` معروض |

---

## G. AnswerComposer
Wheat: إجابة مع استشهادات عند كفاية الأدلة. Ginger: لا اختلاق. Taxonomy GBIF: استدعاء Composer دون ادعاء تجريبي.

## H. Model / External
لا استبدال بنموذج وهمي. OpenAlex/Crossref/GBIF عامة. لا اشتراك مدفوع جديد.

## I. API and UI
UI على `http://127.0.0.1:8079` يعمل ويعرض حالة عدم كفاية. **لم يُثبت** عرض إجابة علمية صالحة بعد إصلاح concurrency في نفس موجة UI.

## J. Tests
`GbifG504AcceptanceHarnessTest` (3 incl. live)، `GbifOrchestratorEndToEndTest` (4)، `PlantGrowthTemperatureQueryAndRelevanceTest` (2)، NSR classification (2)، Restoration harness (1). أوامر عبر `docker compose exec -T backend php artisan test --filter=...`.

## K. Regression
AGRIS/OE/Duke: لم تُعاد؛ تعديل Orchestrator مشترك مع fallback تسلسلي + افتراضي concurrency=false.

## L. Data Safety
لا migrations؛ لا rebuild تخريبي؛ SQLite محمية.

## M. Git
| الحالة | القيمة |
|---|---|
| COMMITTED_LOCALLY | `97045a6` |
| PUSHED | نعم — `6b97b3b..97045a6` |
| REMOTE_SYNC_VERIFIED | نعم |

## N. Remaining Gaps
| فجوة | شدة |
|---|---|
| UI بعد إصلاح concurrency غير مُعاد إثباته بإجابة خضراء | Critical |
| التكامل التراكمي للمصادر الخمسة | Critical |
| BHL لم يبدأ | High |
| Ginger قد يسترجع لكن لا يمر Composer بClaims كافية | Medium |
| Process-pool لا يزال معطوبًا إن أُفعّل | Medium |

## O. Final Classification
1. GBIF G5-04: `ACCEPTED_WITH_LIMITATIONS`
2. Search Restoration: `PARTIALLY_REPAIRED`
3. AGRIS / OE / Duke: محمولة دون إعادة تصنيف
4. Cumulative: `BLOCKED` / `scientific_integration_verified=false`
5. Phase 1: `PHASE_1_GAP_CLOSURE_PARTIAL`

---

## P. NEXT RECOMMENDED ENGINEERING STEP
### Proposed Task Name
**Home Scientific Search UI Re-Verification after Sequential Orchestrator Default**

### Reason
إصلاح concurrency/query مثبت على المسار الخلفي (wheat scholarly answer)، لكن الواجهة ما زالت أظهرت `no_search_results` قبل اكتمال تفعيل الإصلاح — هذا أعلى أولوية منتجًا من بدء BHL.

### Evidence
- UI snapshot: حالة `no_search_results` لسؤال قمح.
- `restore_live2.json`: wheat 58→20→20→إجابة.
- `agent_fail_debug.json`: process-pool → request_exception صفر محاولات.

### Scope
إعادة تشغيل مسار Home `/public/research-agent/query` + UI لأسئلة القمح/الزنجبيل؛ إثبات B7–B8؛ لا بدء BHL؛ لا تخفيف Validation.

### Dependencies
Backend/nginx بعد إعادة التحميل؛ مصادر OpenAlex/Crossref؛ الإصلاحات في 11.0.20.

### Acceptance Criteria
UI يعرض إجابة مسنودة عند كفاية الأدلة؛ يميّز `all_results_rejected_by_relevance` / `no_search_results`؛ اختبارات انحدار خضراء.

### Risks of Postponement
المستخدم يرى فشلًا مضللًا رغم صلاحية المسار الخلفي.

### Authorization Boundary
ضمن تفويض إصلاح البحث الحالي؛ **لا** يفوّض بدء BHL أو إغلاق Phase 1 تلقائيًا.
