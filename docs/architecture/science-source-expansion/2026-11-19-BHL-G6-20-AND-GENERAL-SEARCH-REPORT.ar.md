# تقرير تنفيذ مزدوج — BHL G6-20 واستعادة البحث العلمي العام
## Revision 11.0.22 — Master Prompt Revision 4.6

## 1. Executive Summary
| المسار | النتيجة | التصنيف |
|---|---|---|
| **A — BHL G6-20** | قبول محدود: مفتاح محلي صالح، PublicationSearch حي، harness fixture+live، C1–C15 PASS ضمن حدود التراث | `ACCEPTED_WITH_LIMITATIONS` |
| **B — البحث العام** | مصفوفة 16 حالة + early-stop + إصلاح REST specializers؛ أسئلة محصولية تُنتج إجابات؛ taxonomy/nonsense تُعامل بصدق دون اختلاق | `BACKEND_VERIFIED` (ليس `RESTORED_AND_VERIFIED`) |

لا `SCIENTIFIC_INTEGRATION_VERIFIED`؛ لا إغلاق Phase 1؛ لا التزام مالي؛ لا كشف أسرار.

---

## 2. Repository Baseline
| الحقل | القيمة |
|---|---|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Initial HEAD | `b473803780493c3da6bd6ca8effa624715f36efa` |
| Final HEAD | `b2dc7e87dc64c4afdbd5f52d458842830c80c4b0` |
| Remote HEAD | `b2dc7e87dc64c4afdbd5f52d458842830c80c4b0` (`REMOTE_SYNC_VERIFIED`) |
| WIP | محمي خارج Staging (QUS/CapVer/Feature tests غير المرتبطة) |

إعادة تحقق 11.0.21: GBIF بلا مفتاح؛ قمح UI/API ناجح؛ BHL كان NEXT.

---

## 3. Owner Decisions Applied
- OD/CapVer G6-20 ACTIVE عبر `CurrentMembershipNearReadySeatCatalog` (وليس قائمة NextGate-17).
- ADR-022/023 دون إعادة كتابة عضوية 110.
- فصل Membership/Capability/Eligibility/Activation محفوظ.

---

## 4. API Credentials Audit — Local `.env` First
| البند | النتيجة |
|---|---|
| جذر `.env` | لا متغيرات BHL |
| `backend/.env` | `BHL_API_KEY` **موجود وغير فارغ** |
| Config | `agricultural_intelligence.bhl.api_key` |
| Adapter | query param `apikey` على `api3?op=PublicationSearch` |
| بحث إنترنت عن مفتاح | **لم يُنفَّذ** (المفتاح موجود محليًا) |
| اتصال حي | HTTP 200، Provider Status=`ok`، نتائج >0 |
| قيمة المفتاح | **غير مكشوفة** |

أثر: ضمن `2026-11-19-BHL-G6-20-ACCEPTANCE-EVIDENCE.v11.0.22.json`.

---

## 5. Workstream A — BHL
### التنفيذ
- `BhlPublicationQuerySpecializer`
- `BhlG620AcceptanceHarness` (fixture + live)
- ربط REST specializers في `variantsForAdapter` (إصلاح مشترك: GBIF/BHL كانا داخل بوابة LocalIndex فقط)
- اختبارات قبول + انحدار credential/envelope

### C1–C15
C1–C15 = **PASS** وفق التعريفات الأصلية لعناوين Five-Source؛ C7 يثبت اعتمادًا محليًا حيًا.  
التفاصيل في Evidence JSON. التصنيف: `ACCEPTED_WITH_LIMITATIONS` (OCR/سببية حديثة غير مُدَّعاة؛ القبول التراكمي محجوب).

---

## 6. Workstream B — General Search Restoration
### إصلاحات عامة
1. **REST specializer wiring** — GBIF/BHL يطبّقان خارج LocalIndex.
2. **Sequential adequate early-stop** — بعد ≥2 scholarly بمخزون كافٍ؛ اختبار `SequentialAdequateResultsEarlyStopTest`.
3. **مصفوفة عامة** — 12 تطوير + 4 held-out عبر مجالات ولغات متعددة.

### Live matrix subset
| ID | status | raw→dedup | validated | ملاحظة |
|---|---|---|---|---|
| GS-01 قمح EN | scientific_generated | 60→21 | 21 | إجابة |
| GS-03 طماطم ري | scientific_generated | 60→57 | 53 | إجابة |
| GS-06 تصنيفي Mentha | all_results_rejected_by_relevance | 60→0 | 0 | صادق؛ لا اختلاق |
| GS-10 nonsense | no_validated_evidence | 60→59 | 0 | لا إجابة ملفّقة |
| GS-H2 blight (held-out) | insufficient_evidence | 60→10 | 4 | صادق |
| GS-H4 Olea (held-out) | insufficient_evidence | 60→50 | 50 | أدلة دون Claims كافية |

أثر: `2026-11-19-GENERAL-SEARCH-LIVE-MATRIX-SUBSET.v11.0.22.json`.

### لماذا ليس RESTORED_AND_VERIFIED
- مسار التصنيف الكامل عبر الوكيل ما زال يرفض ملاءمة ببليوغرافية.
- لم تُتحقق UI لكل حالات المصفوفة في هذه الموجة.
- Held-out أظهر insufficient رغم استرجاع.
- Nonsense ما زال يسترجع ضوضاء علمية عامة ثم يرفضها Validation (سلوك مقبول لكن قابل للتحسين).

---

## 7. Root Cause Analysis
| Finding | السبب | الإصلاح |
|---|---|---|
| BHL C5–C12 جزئية تاريخيًا | لا harness مسار تطبيق كامل مربوط بالمقعد الحالي | Harness + live credential |
| GBIF/BHL specializers ميتة | داخل `instanceof LocalIndex` | نقل REST قبل البوابة |
| مهلة Home / مصادر زائدة | استمرار adapters بعد مخزون كافٍ | early-stop تسلسلي |

تحليل 64→0 التاريخي: محمّل من 11.0.20 — رفض ملاءمة صحيح لضوضاء كيميائية + عيب variants؛ لا يُعاد اختلاقه.

---

## 8–9. Architecture / Test Matrix
لا تعديل ADR. مصفوفة العقد: `GeneralScientificSearchMatrixContractTest` (3).  
اختبارات القبول/الانحدار: **13 passed (109)** + regression GBIF/NSR/plant-growth **7 passed (37)**.

---

## 10–14. Evidence-to-Claim / Live / API / UI
- BHL live: identifiers + provenance + composer invoked؛ قد تكون الإجابة insufficient بصدق للتراث.
- API: مسار research-agent للحالات الستة أعلاه.
- UI: قمح مُثبت في 11.0.21؛ لم تُعد موجة UI كاملة لكل المصفوفة هنا (`UI_PARTIAL`).

---

## 15. Data Safety
لا migrations؛ لا حذف DB؛ SQLite محمية؛ لا كشف مفاتيح في Git.

---

## 16. ADR Compliance
ADR-022/023 محفوظان. عضوية 110 دون إعادة فتح. فصل Traditional/OCR عن السببية الحديثة لـ BHL.

---

## 17. Git Commit and Push
| الحالة | القيمة |
|---|---|
| COMMITTED_LOCALLY | `b2dc7e8` — `feat(bhl,search): G6-20 live acceptance and general scientific search matrix` |
| PUSHED | نعم — `b473803..b2dc7e8` |
| REMOTE_SYNC_VERIFIED | نعم — Local = origin = `b2dc7e87dc64c4afdbd5f52d458842830c80c4b0` |

---

## 18. Remaining Gaps
| فجوة | شدة |
|---|---|
| التكامل التراكمي Five-Source VERIFIED | Critical |
| Relevance للجداول التصنيفية/التراثية في المسار العام | High |
| UI matrix كامل | Medium |
| Nonsense ما زال يسترجع ضوضاء قبل الرفض | Medium |
| Process-pool opt-in معطوب إن فُعّل | Medium |

---

## 19. Final Classification
| المحور | التصنيف |
|---|---|
| BHL G6-20 | `ACCEPTED_WITH_LIMITATIONS` |
| General Search | `BACKEND_VERIFIED` |
| Cumulative Five-Source | `BLOCKED` / `scientific_integration_verified=false` |
| Phase 1 | `PHASE_1_GAP_CLOSURE_PARTIAL` |

---

## 20. NEXT RECOMMENDED ENGINEERING STEP
### Task Name
**Taxonomy/Heritage Relevance Path — Preserve Bibliographic Identity Survivors without Weakening Scholarly Gates**

### Reason
المصادر الخمسة محدودة القبول مغلقة بحدودها، لكن المسار العام ما زال يصفّر survivors للأسئلة التصنيفية (GS-06) رغم استرجاع خام — هذا أعلى عائق أمام `RESTORED_AND_VERIFIED`.

### Evidence
- Live matrix GS-06: raw 60 / dedup 0 / `all_results_rejected_by_relevance`.
- BHL/GBIF harness يتجاوز الملاءمة العلمية التجريبية بصدق للهوية؛ المسار العام لا يفعل ذلك.

### Exact Scope
تصميم قاعدة Relevance/Sufficiency تميّز أدلة الهوية الببليوغرافية/التصنيفية عن الادعاءات التجريبية دون تخفيف بوابات القمح/الري؛ اختبارات مصفوفة GS-06/GS-H4؛ لا Phase جديدة.

### Dependencies
ADR-022؛ Validation/AnswerComposer contracts؛ أدلة GBIF/BHL 11.0.20–11.0.22.

### Acceptance Criteria
أسئلة taxonomy/heritage تُنتج إجابة هوية مسنودة أو insufficient صريح مع survivors>0 عند وجود سجلات مصدر مؤهل؛ Regression المحاصيل تبقى خضراء.

### Risks of Postponement
المستخدم يرى رفض ملاءمة مضللاً لأسئلة تصنيفية رغم توفر GBIF/BHL.

### Authorization Boundary
توصية فقط؛ لا تخفيف Validation العام؛ لا إعلان VERIFIED تراكمي.
