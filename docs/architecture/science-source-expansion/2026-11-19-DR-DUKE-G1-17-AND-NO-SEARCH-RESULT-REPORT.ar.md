# تقرير تنفيذ مزدوج — Dr. Duke G1-17 و`no_search_result`
## Revision 11.0.19 — Master Prompt Revision 3

## A. Executive Summary
نُفِّذ مساران مستقلان على الفرع `phase-18-m18-ai-marketing-communications` (Baseline HEAD `31287a1`):

| المسار | النتيجة | التصنيف |
|---|---|---|
| **A — Dr. Duke G1-17** | قبول محدود وفق `OD-G1-17-SCOPE-LIMITED-PROJECTION`؛ مصالحة 16 جدولًا؛ مسار قبول Fixture + Operational RO | `ACCEPTED_WITH_LIMITATIONS` |
| **B — no_search_result** | جذر مثبت: خلط Validation بين فراغ الاسترجاع ورفض الملاءمة؛ إصلاح تصنيف دون تخفيف البوابات | `REPAIRED_CLASSIFICATION` |

**ما لم يُنجز / لم يُدَّعَ:**
- لا `SCIENTIFIC_INTEGRATION_VERIFIED` (تراكمي للمصادر الخمسة).
- لا تغيير `PHASE_1_GAP_CLOSURE_PARTIAL`.
- لا بدء GBIF/BHL.
- لا تحقق واجهة مستخدم حي لهذا الإصلاح (API/وحدة فقط لمسار B).
- لا Live API لـ Dr. Duke (خارج عقد المالك).

---

## B. Baseline and Git State
| الحقل | القيمة |
|---|---|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Initial HEAD | `31287a1d12b40aebfd5dee5deddaedb3bb39bccd` |
| Final HEAD | `5f9bc36df89cf249f1ab73fa0cca618492c97bb0` |
| Remote HEAD | `5f9bc36df89cf249f1ab73fa0cca618492c97bb0` (`REMOTE_SYNC_VERIFIED`) |
| Remote tracking | `origin/phase-18-m18-ai-marketing-communications` |
| WIP Protection | ملفات غير مرتبطة (QUS، CapVer، ScientificUserPresentation، اختبارات Feature واسعة، `_tmp-*`) بقيت خارج Staging |
| Staging | Path-specific فقط — ممنوع `git add .` / `-A` |

---

## C. Architecture and Contract Findings
| الوثيقة | المسار / الحالة | الاعتماد |
|---|---|---|
| ADR-022 | مرجع هوية السؤال العلمي / CSQ — **Current** | حاكم للهوية؛ لم يُعدَّل |
| ADR-023 | تكامل المصادر — Membership/Identity/Capability/Eligibility/Activation — **Current** | حاكم للفصل؛ لم يُعدَّل |
| OD-G1-17 | `2026-10-18-OWNER-DECISION-RECORDED-G1-01-G1-17-SCOPE.v1.json` — **Current** | حاكم لنطاق Duke |
| C1–C15 titles | كما في أدلة AGRIS 11.0.17 / مصفوفات Five-Source — **Current** | لا إعادة تعريف |
| AGRIS G1-01 | 11.0.17 — Historical/Current seat | محمول؛ لم يُعد فتحه |
| Organic Eprints G1-02 | 11.0.18 / commit `8587ba5` | محمول؛ لم يُعد تنفيذه |

**تعارضات:** لا تعارض يتطلب تغيير معنى العقود. تنبيه حوكم: `SCIENTIFIC_SEARCH_INTEGRATION_VERIFIED_LIMITED_LOCAL_PROJECTION` التاريخي ≠ `SCIENTIFIC_INTEGRATION_VERIFIED` البعثي.

---

## D. Dr. Duke G1-17
### نطاق القرار
- Limited Search Projection؛ STATIC_DATASET / LocalIndex.
- GEN=2 / 1500 = ترميز خط أساس القبول في قرار المالك — **ليس** عدد السجلات التشغيلي الحالي ولا Full Corpus Coverage.
- فصل `FARMACY` ≠ `FARMACY_NEW`؛ `ETHNOBOT` = traditional-use فقط.
- لا Live API؛ لا اختراع FK رقمي لـ TAXON.

### الجداول الستة عشر والمصالحة
الأثر: `2026-11-19-DR-DUKE-RECONCILIATION.v11.0.19.json` — **16/16 حاضرة**.

| جدول | صفوف (عينة/عدّ) | ملاحظة |
|---|---|---|
| FNFTAX | 2376 | مفاتيح تصنيف |
| CHEMICALS | 29585 | CHEM / CHEMID |
| ETHNOBOT | 82873 | اسم TAXON فقط |
| FARMACY | 68844 | CHEM name |
| FARMACY_NEW | 104388 | CHEMID + حقول إضافية |
| COMMON_NAMES | 2920 | FNFNUM كامل المطابقة |
| ACTIVITIES…YIELDS | (انظر JSON) | حاضرة ضمن الإسقاط المحدود |

**علاقات مثبتة (عينات ≤50000):**
- `FARMACY.FNFNUM→FNFTAX`: matched 50000 / unmatched 0
- `FARMACY.CHEM→CHEMICALS.CHEM`: matched 50000 / unmatched 0
- `FARMACY_NEW.FNFNUM→FNFTAX`: matched 50000 / unmatched 0
- `FARMACY_NEW.CHEMID→CHEMICALS.CHEMID`: matched 48248 / unmatched 1752 (مُوثَّق، لا اختراع إصلاح)
- `ETHNOBOT.TAXON↔FNFTAX.TAXON` (اسمي): matched_name 18666 / unmatched 31334
- `COMMON_NAMES.FNFNUM→FNFTAX`: matched 2920 / unmatched 0

### الحدود العلمية
Traditional Use ≠ Clinical Efficacy؛ Plant–Chemical Association ≠ Therapeutic Effectiveness. لا تسريب ادعاءات فاعلية سريرية من ETHNOBOT.

### مكونات التنفيذ (جديدة/محدّثة)
- `DrDukeLocalIndexQuerySpecializer.php`
- `DrDukeG117AcceptanceHarness.php`
- `DrDukeG117OperationalReadonlyBootstrap.php`
- فرع `variantsForAdapter` في Orchestrator لـ `dr_duke`
- `DrDukeG117AcceptanceHarnessTest.php`

---

## E. C1–C15 Acceptance Matrix (Dr. Duke)
التعريفات الأصلية (عناوين AGRIS/Five-Source الحاكمة) دون إعادة صياغة مغيّرة للمعنى:

| ID | Original Definition (title) | Applicable | Previous | Current | Actual Evidence | Gap |
|---|---|---|---|---|---|---|
| C1 | Authoritative Identity and Scope | نعم | PASS | **PASS** | OD-G1-17 + G1-17 seat | — |
| C2 | Capability and Access Contract | نعم | PASS | **PASS** | STATIC_DATASET LocalIndex؛ لا Live API | — |
| C3 | Scientific Query Relevance | نعم | PASS | **PASS** | Specializer Mentha/phytochem؛ CSQ غير مُطفأ | — |
| C4 | Production Routing | نعم | PASS | **PASS** | أهلية phytochem؛ irrigation يحجب Duke | — |
| C5 | Retrieval and Evidence Provenance | نعم | PARTIAL | **PASS** | Fixture + Operational RO مع معرفات | ليس Live API (غير مطلوب) |
| C6 | Scientific Validation and Answer Composition | نعم | PARTIAL | **PASS** | validate→compose على harness؛ إجابة غير كافية مسموحة بصدق | — |
| C7 | Runtime Configuration and Authentication | لا | N/A | **NOT_APPLICABLE_WITH_RATIONALE** | لا Live API تحت OD | — |
| C8 | Retrieval Behavior and Error Handling | نعم | PARTIAL | **PASS** | negative probe؛ RO يرفض rebuild | — |
| C9 | Evidence Normalization | نعم | PARTIAL | **PASS** | source_key / identifiers / provenance | — |
| C10 | Scientific Identity Integrity | نعم | PARTIAL | **PASS** | Mentha piperita؛ لا اختراع efficacy | — |
| C11 | Negative and Boundary Testing | نعم | PARTIAL | **PASS** | negative token + irrigation block | — |
| C12 | Reproducible Production-Path Evidence | نعم | PARTIAL | **PASS** | REAL_APPLICATION_PATH harness (fixture+RO) | UI حي غير مُدَّعى |
| C13 | Source-Specific Limitations and Governance | نعم | PASS | **PASS** | OD + 16-table + FARMACY فصل | — |
| C14 | Regression and Architectural Integrity | نعم | PASS | **PASS** | 5 tests / 28 assertions خضراء | — |
| C15 | Evidence Completeness and Traceability | نعم | PARTIAL | **PASS** | Evidence JSON + Reconciliation + هذا التقرير | — |

التفاصيل: `2026-11-19-DR-DUKE-G1-17-ACCEPTANCE-EVIDENCE.v11.0.19.json`.

---

## F. No-Search-Result Root Cause (Workstream B)
**السؤال الأصلي للمستخدم:** غير مُوثَّق في هذه الجلسة كسلسلة UI كاملة؛ الاعتماد على سيناريوهات مسجّلة (`e2e-tmp/acc-q1-validate.json`: raw 64 / survivors 0) + اختبارات وحدة.

| مرحلة | نتيجة |
|---|---|
| استرجاع خام | نتائج موجودة (`results ≠ []`) |
| مرشح الملاءمة Stage-3 | `deduplicatedResults = []` |
| Validation (قبل الإصلاح) | `no_search_results` — **تصنيف مضلل** |
| Validation (بعد الإصلاح) | `all_results_rejected_by_relevance` + `failure_class=ALL_RESULTS_REJECTED` |
| Composer | عبارة insufficient_irrelevant؛ disposition `retrieved_but_rejected` |
| البوابات | **لم تُضعَّف** — لا إجابة ملفّقة |

**الجذر:** دمج حالتين مختلفتين في حالة واحدة: فراغ استرجاع حقيقي مقابل رفض كل النتائج بالملاءمة.

الأثر: `2026-11-19-NO-SEARCH-RESULT-ROOT-CAUSE.v11.0.19.json`.

**العلاقة بـ Duke:** مستقلة. إصلاح التصنيف مشترك في Validation/Composer ولا يوسّع نطاق G1-17.

---

## G. End-to-End Scientific Search
### Ledger — Workstream A (Mentha phytochem acceptance)
| الحقل | قيمة |
|---|---|
| Original Question | phytochemical / Mentha piperita (harness acceptance) |
| Selected Sources | `dr_duke` (عند الأهلية) |
| Query Specializer | `DrDukeLocalIndexQuerySpecializer` |
| Path classes | FIXTURE_LOCALINDEX + OPERATIONAL_READONLY |
| Composer | يُستدعى؛ قد يُنتج insufficient بصدق إن الأدلة غير كافية |
| Final Classification (seat) | `ACCEPTED_WITH_LIMITATIONS` |

### Ledger — Workstream B (تصنيف الرفض)
| الحقل | قيمة |
|---|---|
| Raw Results Count | ≥1 (اختبار؛ 64 في dump سابق) |
| Normalized / Survivors | 0 بعد الملاءمة |
| Validation Accepted | 0 |
| Validation Status | `all_results_rejected_by_relevance` |
| Composer Valid Answer | لا — insufficient صريح |
| UI Display | غير مُتحقَّق في هذه الجولة |

---

## H. Validation and AnswerComposer
| المقياس | A (Duke harness) | B (relevance wipe) |
|---|---|---|
| Evidence retrieved | نعم (مسار قبول) | نعم (خام) |
| Survivors | حسب المسار | 0 |
| Accepted | وفق Validation | 0 |
| Sufficiency | قد تفشل بصدق | غير كافية (رفض ملاءمة) |
| Composer invoked | نعم | نعم |
| Valid scientific answer | فقط إن سمحت الأدلة | لا |
| Invented claims | ممنوع | ممنوع |

---

## I. Tests and Verification
```
php artisan test --filter="DrDukeG117AcceptanceHarnessTest|NoSearchResultsRelevanceRejectionClassificationTest"
Tests: 5 passed (28 assertions)
```
| اختبار | نتيجة |
|---|---|
| DrDukeG117AcceptanceHarnessTest (3) | PASS |
| NoSearchResultsRelevanceRejectionClassificationTest (2) | PASS |
| UI verification | **NOT_RUN** — لا متصفح حي لهذه الجولة |
| AGRIS/OE full re-run | **NOT_RUN** — لا تغيير مشترك يُبطل أدلتهما السابقة للمسار الحي؛ تعديل Validation تصنيفي فقط مع اختبار سلبي جديد |

---

## J. Data Safety
| البند | الحالة |
|---|---|
| SQLite المشروع | محمية؛ مسار Duke RO |
| Migrations جديدة لهذه المهمة | لا |
| LocalIndex تشغيلي | لا rebuild تخريبي؛ `read_only_search` |
| db_unchanged (قبول Duke) | مُتوقع true على المسار RO |
| PostgreSQL | غير مفروض كقاعدة مشروع |

---

## K. Commits and Remote Verification
| الحالة | القيمة |
|---|---|
| COMMITTED_LOCALLY | `5f9bc36` — `feat(dr-duke): G1-17 limited-projection acceptance and NSR relevance classification` |
| PUSHED | نعم — `31287a1..5f9bc36` |
| REMOTE_SYNC_VERIFIED | نعم — Local HEAD = `origin/phase-18-m18-ai-marketing-communications` = `5f9bc36df89cf249f1ab73fa0cca618492c97bb0` |
| Force push | ممنوع / غير مستخدم |
| CI | يُشغَّل على push؛ لا ادّعاء نجاح دون تحقق (`CI_STATUS_UNAVAILABLE` إن تعذر) |

---

## L. Remaining Gaps and Risks
| الفجوة | الشدة | الأثر |
|---|---|---|
| التكامل التراكمي للمصادر الخمسة غير مُتحقق | Critical (بعثي) | `scientific_integration_verified=false` يبقى |
| GBIF G5-04 / BHL G6-20 لم يبدأ | High (بعثي) | يمنع إغلاق Phase 1 |
| UI لم يُتحقق بعد إصلاح NSR | Medium | المستخدم قد يرى رسالة insufficient دون تمييز نصي كامل في الواجهة |
| FARMACY_NEW unmatched CHEMID (1752/50k) | Medium | فجوة بيانات مصدر؛ لا اختراع ربط |
| ETHNOBOT unmatched name (~31k/50k) | Medium | علاقة اسمية جزئية فقط |
| FTS تشغيلي Duke قد يُرجع 0 لبعض الاستعلامات | Medium | harness يتجاوز بـ fixture/soft-skip عند الفراغ التشغيلي |
| WIP واسع غير مُدرج | Low | حماية متعمدة؛ خطر دمج لاحق إن اختلط Staging |

---

## M. Final Classification
| المحور | التصنيف |
|---|---|
| 1. Dr. Duke G1-17 | `ACCEPTED_WITH_LIMITATIONS` |
| 2. Workstream B | Root cause identified ✓ / reproduced ✓ / repaired ✓ / regression tests ✓ / real app path PARTIAL / UI ✗ |
| 3. C1–C15 (Duke) | C1–C6,C8–C15 **PASS**؛ C7 **NOT_APPLICABLE_WITH_RATIONALE** |
| 4. Cumulative Five-Source | `BLOCKED` — `scientific_integration_verified=false` |
| 5. Phase 1 | `PHASE_1_GAP_CLOSURE_PARTIAL` — **دون تغيير** |

---

## N. NEXT ENGINEERING STEP — إلزامي
### اسم المهمة
**GBIF G5-04 — Source Acceptance Kickoff under ADR-023 (Membership→Capability→Eligibility only; no Phase close)**

### السبب المباشر
بعد AGRIS وOrganic Eprints وDr. Duke ضمن حدودها، المقعد التالي في مصفوفة المصادر الخمسة المتبقية هو GBIF (`G5-04`)؛ التأجيل يُبقي التكامل التراكمي محجوبًا.

### الأدلة المبرِّرة
- مصفوفة 11.0.19: G5-04 / G6-20 unchanged من 11.0.13.
- `fully_verified_count=0`؛ `mission_completion_classification=BLOCKED`.
- هذا التقرير لا يبدأ GBIF تلقائيًا.

### نطاق التنفيذ
استعادة عقد G5-04، تدقيق التنفيذ الحالي، مصفوفة C1–C15 لـ GBIF فقط، دون إعلان VERIFIED تراكمي ودون إغلاق Phase 1.

### الاعتماديات
ADR-023؛ سجل العضوية الحالي؛ عدم المساس بأدلة AGRIS/OE/Duke إلا عند Regression مشترك مثبت.

### معايير القبول
مصفوفة C1–C15 لـ GBIF بأدلة مباشرة؛ لا اختلاق Live/Coverage؛ تقرير عربي + أدلة مؤرخة؛ Commit/Push path-specific.

### مخاطر التأجيل
بقاء `PHASE_1_GAP_CLOSURE_PARTIAL` دون تقدّم مقاعد؛ تراكم دين تكاملي على Occurrence/Taxonomy paths.

### حدود التفويض الحالي
هذا التقرير **لا يفوّض** بدء GBIF أو BHL أو Phase جديدة؛ الخطوة التالية توصية فقط حتى صدور أمر مالك/مهمة مستقلة.
