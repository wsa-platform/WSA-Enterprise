# تقرير المهمة النهائي — MASTER 75193
## استعادة والتحقق من البحث العلمي متعدد المصادر

## 30.1 الملخص التنفيذي

| الحقل | القيمة |
|---|---|
| اسم المهمة | Multi-Source Scientific Search Restoration, Verification & Completion (75193) |
| التصنيف النهائي | `PARTIALLY_COMPLETED` |
| Starting HEAD | `df9fb45d564f17b4651cf7071ca0c81153806eae` |
| Ending HEAD | `7598bab8fae6f5181d9ca4a72db51f35b05aba76` |
| الفرع | `phase-18-m18-ai-marketing-communications` |
| المزامنة Local/Remote | كانت متطابقة عند البداية (`ahead/behind 0`)؛ تُعاد بعد Push |
| النتيجة العامة | إصلاحات جذرية لمسار LocalIndex وComposer + إثبات حي L7 لـ AGRIS/OE والـ Ledger؛ Full Suite غير أخضر تاريخيًا |

**ما أُثبت:** LocalIndex READY؛ AGRIS وOrganic Eprints وصلا L7 عبر مسار التطبيق؛ الـ API الحي يعيد `claim_attribution_ledger` مأهولًا مع إسناد AGRIS/OE؛ استقلال الأدلة (DOI) ناجح؛ سياسات FAOSTAT/Consensus محفوظة؛ Playwright (واجهة مُحاكاة) ناجح.

**ما يبقى:** Dr Duke عند L4 للأسئلة الكيميائية المختبرة؛ FAOSTAT/Consensus live `NOT_RUN`؛ Full PHPUnit فيه إخفاقات سابقة/بيئية؛ Playwright ليس مسار Agent حيًا.

## 30.2 نتائج Pre-Flight

| Check | Expected | Actual | Status | Evidence |
|---|---|---|---|---|
| Git branch | phase-18-… | مطابق | PASS | `00-PREFLIGHT.txt` |
| Baseline edd1193 | في التاريخ | موجود | PASS | `git merge-base --is-ancestor` |
| Remote sync | متزامن | HEAD=REMOTE df9fb45 | PASS | preflight |
| WIP | محفوظ | لم يُمس | PASS | status dirty unrelated |
| LocalIndex | READY | 2088/19/5 gens متطابقة | PASS | store/FTS meta |
| Claim Ledger | حقل موجود | بعد الإصلاح: POPULATED + L7 | PASS | live API |
| Playwright file | قابل للاكتشاف | `frontend/e2e-rev65/tests/...` | PASS | Test-Path |

## 30.3 مصفوفة التحقق من المصادر

| Source | Policy | Selected | Executed | L4 | L5 | L6 | L7 | Highest | Remaining Gap |
|---|---|---|---|---|---|---|---|---|---|
| OpenAlex | INTERNET_FIRST | نعم (حي) | نعم | نعم | نعم | نعم | نعم | L7 | — |
| Crossref | INTERNET_FIRST | نعم | نعم | نعم | — | — | — | L4+ (في successful) | إثبات L7 منفصل غير مطلوب لكل سؤال |
| Semantic Scholar | INTERNET_FIRST | نعم | نعم | نعم | — | — | — | L4+ | — |
| Consensus | OPTIONAL_EXPLICIT | لا (افتراضي) | — | — | — | — | — | L0–L1 سياسة | Live NOT_RUN |
| FAOSTAT | STATISTICAL / disabled | لا | — | — | — | — | — | سياسة | Live NOT_RUN |
| AGRIS | GOVERNED | نعم | نعم | نعم | نعم | نعم | نعم | **L7** | — |
| Dr Duke | GOVERNED | نعم | نعم | نعم | لا* | لا | لا | **L4** | مطابقة ادعاء كيميائي/تحقق |
| GBIF | GOVERNED | (سابقًا) | — | — | — | — | — | عقد تصنيفي | لم يُعاد تشغيله هذه الجولة |
| Organic Eprints | GOVERNED | نعم | نعم | نعم | نعم | نعم | نعم | **L7** | — |
| BHL | GOVERNED | — | — | — | — | — | — | سابق PARTIAL | لم يُعد هذه الجولة |

\*Dr Duke: نتائج مسترجعة؛ التحقق رفضها لادعاء phytochemical scholarly في السيناريو المختبر.

## 30.4 استقلال الأدلة

- `independent_document_count` ≠ `independent_provider_count` عند تكرار DOI عبر مزوّدين.
- اختبارات الوحدة: Same DOI → وثيقة واحدة؛ DOI مختلفان → وثيقتان.
- لا قضايا متبقية في نطاق المهمة.

## 30.5 Claim Attribution Ledger

| جانب | النتيجة |
|---|---|
| التوفر في API | موجود |
| التصنيف الحي | `LEDGER_ATTRIBUTION_VALIDATED` |
| الربط Claim→Evidence | attributions مع evidence_id / source_key / url |
| Provenance | AGRIS وorganic_eprints ظاهران في attributions |
| المساهمة النهائية | `l7_final_claim_attribution_proven: true` |

## 30.6 FAOSTAT و Consensus

| | FAOSTAT | Consensus |
|---|---|---|
| السياسة | STATISTICAL_CONSIDERATION | OPTIONAL_EXPLICIT_ONLY |
| الافتراضي | disabled | غير تلقائي |
| Live | NOT_RUN — FREE-ONLY / لا تفعيل مدفوع | NOT_RUN — ACCESS_OR_COST_CONSTRAINT |
| اختبار السياسة | PASS (وحدة) | PASS (وحدة) |

## 30.7 PHPUnit

| | العدد |
|---|---|
| Total | 3647 |
| Passed | 3647 − 197 − 8 − 14 ≈ 3428 (تقريبي من الملخص) |
| Failed | 197 |
| Errors | 8 |
| Skipped | 14 |
| Blocked | 0 |
| Not Run | 0 (الشوط اكتمل بعد رفع memory_limit) |

- مجموعة المهمة المستهدفة (51 اختبارًا): **PASS**
- تصنيف الإخفاقات العريضة: PRE_EXISTING / ENVIRONMENT / EXTERNAL (Membership، CapVer، FaoStat Phase3، HTTP stray، LocalIndex فارغ في بيئة الاختبار)
- إصلاحات المهمة: لا انحدار مؤكد في المجموعة المرتبطة

## 30.8 Browser E2E

| | |
|---|---|
| Discovery | نعم — `frontend/e2e-rev65/tests/claim-attribution-ledger.spec.ts` |
| Execution | نعم |
| Result | 2 passed |
| نوع | **Mocked route** (عقد واجهة؛ ليس Real Agent Path) |
| قيد | لا يغني عن إثبات API الحي |

## 30.9 التغييرات والـ Commits

| ملف / موضوع | السبب |
|---|---|
| SqliteFts + Adapter readiness | إزالة COUNT/EXISTS من المسار الساخن |
| AgriculturalScientificSearchService | احترام force_execute |
| AnswerExpressionAccuracyGate + AnswerComposer | قبول yield/irrigation لعائلة quantity |
| اختبارات انحدار جديدة | تثبيت الإصلاحات |

*(تفاصيل SHA بعد الإنشاء في §commits)*

## 30.10 القضايا المتبقية

| الشدة | القضية |
|---|---|
| High | Full PHPUnit غير أخضر (تاريخي/بيئي) |
| Medium | Dr Duke دون L5–L7 لادعاءات phytochemical المختبرة |
| Medium | Playwright مُحاكى وليس حيًا |
| External | Consensus/FAOSTAT live NOT_RUN |
| Low | بطء FTS على ربط Windows (~10–90ث/استعلام) رغم READY |

## 30.11 مبرر التصنيف (§17.1)

`COMPLETE_VERIFIED` مرفوض لأن: Full Suite فاشل جزئيًا؛ مسار Dr Duke لم يبلغ L7؛ مسارات FAOSTAT/Consensus الحية غير منفذة؛ E2E المتصفح مُحاكى.

`PARTIALLY_COMPLETED` مناسب: تقدّم جوهري مثبت (إصلاحات جذرية + L7 AGRIS/OE + Ledger حي + سياسات + استقلال أدلة + Pre-Flight)، مع فجوات إلزامية موثّقة وغير حاجبة لكل تقدم إضافي.

## إصلاحات السبب الجذري هذه الجولة

1. **تعليق Stage-3 على COUNT(*) لـ FTS متعدد الجيجابايت** → الاعتماد على تطابق أجيال الـ meta.
2. **force_execute يُتجاهل داخل SearchService** → مواءمة مع Agent.
3. **أدلة AGRIS الموثّقة تُرفض في Composer** لأن عائلة `quantity` لا تعترف بـ yield/irrigation → توسيع مصطلحات العنوان دون إضعاف بوابات الصلة.
