# التقرير النهائي — إضافة وتفعيل مصدر OSMIA (G3-05)

**التاريخ:** 2026-11-24  
**الفرع:** `phase-18-m18-ai-marketing-communications`

## A. الملخص

اختير **G3-05 OSMIA** كأسهل مصدر زراعي مؤهل تجاريًا بعد رفض **G2-17 REMVT** و**G2-15** بسبب تراخيص NC على مستوى السجلات. نُفّذ مسار Crossref→JSONL→LocalIndex مع تفعيل كتالوج NearReady وأهلية محدودة للنحل/الملقحات.

**التصنيف:** `READY_FOR_ONBOARDING`  
لا يُمنح `SOURCE_ADDITION_VERIFIED` لأن فهرسة FTS الدائمة فشلت في هذه البيئة (`SQLSTATE[HY000]`)، ومسار التركيب الحي لم يُثبت إسناد ادعاءات إلى وثائق osmia بعد بوابة التحقق.

## B. Git

| | SHA |
|---|---|
| البداية | `879df1fa6b8516b5cc08a5ca41b772f8825623aa` |
| Feature | `df82d31d4bfc410bb06da2588c0a2bf189bb7bda` |
| Docs tip | `04b10b37cba7a27096b07320a71c7d1f754762ec` |
| Tip HEAD note | `c2884f02fdc718bf1a6a28948a50d09b5447283c` |
| Remote HEAD | `c2884f02fdc718bf1a6a28948a50d09b5447283c` |
| WIP | محمي ومستبعد من هذا الإصدار |

## C. الاختيار والخطة (§6.1–6.5)

| وثيقة | المحتوى |
|---|---|
| `00-SOURCE-SELECTION-AND-PLAN.md` | الاختيار، 6.1، مسار 6.2، ملخص المراحل |
| `01-LICENSE-AND-ACCESS.md` | CC BY 4.0 / رفض REMVT |
| `02-IMPLEMENTATION-AND-TESTS.md` | المكونات والأوامر |
| `03-EVIDENCE.json` | أدلة الحصاد/البحث/التركيب |
| `04-RISKS-DEPENDENCIES-PARALLELISM.md` | §6.5 |
| `05-PHASED-EXECUTION-DETAIL.md` | مراحل A–H بالتفصيل |

## D. ما نُفّذ

- حصاد 80 عملاً CC BY من Crossref ISSN 2727-3806
- تخزين دائم LocalIndex (generation ≥1، 80 ACTIVE)
- تسجيل `osmia` في ScientificSourceCatalog + CapVer + config `OSMIA_ENABLED`
- بحث ناجح عبر LocalScientificIndex (Nomada / abeille)
- أهلية محكومة (bee/pollinator فقط؛ قمح غير مؤهل)
- اختبارات وحدة: **4 PASSED / 17 assertions**

## E. ما لم يكتمل (يمنع SOURCE_ADDITION_VERIFIED)

- إعادة بناء Sqlite FTS5: `SQLSTATE[HY000]` على bind-mount Docker Desktop
- ظهور osmia في Stage-3 الحي الافتراضي (المؤشر بارد → UNAVAILABLE)
- إثبات Attribution بعد Evidence Validation (`validated_count=0` على عينة التركيب)

## F. الاختبارات والأدلة

| Suite | Command | Result | Passed | Exit |
|---|---|---|---:|---:|
| OsmiaG305OnboardingTest | `php artisan test --filter=OsmiaG305OnboardingTest` | PASS | 4 | 0 |
| Harvest+memory probe | `scientific:harvest-osmia-crossref --reuse-snapshot --memory --probe-query=Nomada` | PASS | — | 0 |
| Durable store count | localindex probe | PASS — 80 ACTIVE | — | 0 |
| FTS rebuild | `_osmia_fts_rebuild.php` | FAIL HY000 | — | 0 |
| Compose attribution | `_osmia_compose_attribution_probe.php` | PARTIAL (hit, 0 validated) | — | 0 |

## G. القرار (§6.6–6.7)

**`READY_FOR_ONBOARDING`** — التكامل القانوني والحصاد والكتالوج جاهزة؛ بوابة الفهرسة الدائمة والإسناد العلمي الحي متبقية.

لم يُنتقل إلى مرشح تالٍ لأن عائق FTS بيئي عام (ليس خاصًا بـOSMIA)، وREMVT/Andalasian ما زالا `BLOCKED_LICENSE`.

## H. الخطوة التالية

1. إعادة بناء FTS على Linux/CI غير مربوط بـ bind-mount Windows (~2–4س).  
2. مسبار حي إجباري لـ osmia بعد جاهزية المؤشر + سؤال يطابق عناوين OSMIA.  
3. عند نجاح الاسترجاع والتحقق والإسناد والانحدار: ترقية التصنيف إلى `SOURCE_ADDITION_VERIFIED`.
