# التقرير الهندسي النهائي — استعادة الاسترجاع العلمي متعدد المصادر (R2)

**معرّف المهمة:** WSA-MS-RESTORATION-2026-11-21-R2  
**التصنيف النهائي:** `MULTI_SOURCE_RESTORATION_PARTIALLY_COMPLETED`

## 1. الملخص التنفيذي

تم إصلاح مسار Universal الذي كان يمرّر معرفات مزوّدي القدرة فقط إلى Stage-3، فيُسقط المقاعد المحكومة (GBIF/BHL/…). بعد الدمج مع `ScientificSourceSelector` أصبحت أسئلة التصنيف تختار وتنفّذ مصادر متعددة. تحقق Mentha الحي: استشهادات **OpenAlex + GBIF**، ومحاولة ناجحة لـ **BHL**. Wheat بقي `SCIENTIFIC_VERIFIED`. لم يُعلن اكتمالاً مطلقاً لأن واجهة المتصفح التفاعلية لم تُنفَّذ، وبعض المصادر المفعّلة تُتخطى مبكراً بعد كفاية علمية مبررة، وعنوان المُركِّب ما زال يعرض أحياناً «Plant family members».

## 2–3. الفرع وHEAD

| | قيمة |
|---|---|
| فرع البداية | `phase-18-m18-ai-marketing-communications` |
| HEAD البداية | `052305b02efebee5ef27b35d8d89a0301e11a735` |
| الفرع النهائي | نفس الفرع |
| HEAD النهائي | `7aa039183f3b31f7b24f38763589c6a0a9b223ad` (Local=Remote؛ التنفيذ `1af4514…`) |

## 4. حماية WIP

لم يُمسّ عمل المستخدم غير ذي الصلة (CapVer، membership، e2e-tmp، تقارير أخرى).

## 5. التحقق من الإصلاحات السابقة

P0 `source_key`، قابلية إجابة التصنيف، مواضيع التصنيف، إثراء GBIF — كلها PASS عبر اختبارات الانحدار.

## 6–8. الجرد والفعالية والقدرات

سجل التشغيل السلطوي: **10** مصادر (`ScientificSourceCatalog`). انظر `REGISTERED_SOURCE_INVENTORY.md` و`SOURCE_EFFECTIVENESS_MATRIX.md` و`SOURCE_CAPABILITY_MATRIX.md`. مقاعد next-gate الـ17 موثّقة وليست محذوفة صامتة.

## 9. عيوب التنفيذ/التكوين المكتشفة

1. **حرج (أُصلح):** Universal يتجاوز Selector → إسقاط المصادر المحكومة.  
2. متبقٍ تجميلي: تسمية قسم المُركِّب «Plant family members» لـ Mentha.  
3. Semantic Scholar / AGRIS / DrDuke غالباً early-stop بعد كفاية — سلوك مقصود مع حماية GBIF/BHL.

## 10–14. الاختيار والتنفيذ

Mentha: محدد 8؛ منفّذ openalex/crossref/gbif/bhl؛ مستبعد organic_eprints لسبب عضوي؛ متخطى scholarly إضافي بسبب early-stop بعد حماية التصنيف.  
Wheat: منفّذ openalex/crossref؛ `SCIENTIFIC_VERIFIED`.

## 15–17. GBIF / OpenAlex / Crossref / غيرها

- GBIF: مصدر تصنيفي منظّم — 10 استشهادات؛ ليس حصرياً.  
- OpenAlex: 6 استشهادات Mentha؛ 5 Wheat.  
- Crossref: نجاح استرجاع Mentha.  
- BHL: محاولة ونجاح استرجاع Mentha (محمي من early-stop).  
- AGRIS/DrDuke/FAOSTAT: محددة/معتبرة حسب الأهلية.

## 18–19. عقود الأدلة والهويات

فصل `source_key` / هوية السجل / الكيان / الادعاء / الدليل / المنشأ محفوظ. لا فرض عقد المقالات على GBIF.

## 20–22. Mentha / Wheat / مجالات أخرى

Mentha متعدد المصادر PASS جزئي (استشهادات ≥2 مزوّدين). Wheat انحدار PASS. لا تلوث منطق التصنيف لمسار نمو القمح.

## 23–25. كفاية الأدلة والتكرار والتعارض

Mentha: validated=22، تعارضات جزئية معلنة، `independent_provider_count=2`.

## 26–27. المراقبة وAPI والواجهة

تشخيص الاختيار/التخطي/المساهمات موجود. API العام Mentha 200 مع مصادر متعددة. واجهة المتصفح التفاعلية NOT_RUN.

## 28–31. الاختبارات والاسترجاع والنموذج والمتصفح

وحدة/ميزة PASS؛ استرجاع حي PASS؛ استدلال نموذج مدفوع NOT_RUN؛ متصفح تفاعلي NOT_RUN.

## 32. الملفات المعدّلة (في النطاق)

- `UniversalAnswerOrchestrator.php` — دمج مفاتيح Stage-3  
- `ScientificSearchQueryBuilder.php` — متغيرات تصنيف الأنواع (مساري CSQ والقديم)  
- `ScientificKnowledgeTypeRelevancePolicy.php` — حماية BHL للتصنيف  
- `GbifSpeciesQuerySpecializer.php` — إشارات taxonomy  
- اختبارات MultiSource + Universal feature  
- وثائق المهمة تحت `2026-11-21-multi-source-restoration-r2/`

## 33. قرارات معمارية

Selector + Registry يبقيان سلطة Stage-3. Universal لا يجوز أن يمرّر مجموعة قدرة ناقصة كـ `sourceKeys` حصرية. Early-stop مسموح إذا بقيت المصادر المحمية.

## 34–36. Git

- التنفيذ: `1af45144625c56c49de740f3b03a654217e7bbd5`
- توثيق التسليم: `7aa039183f3b31f7b24f38763589c6a0a9b223ad`
- الدفع: ناجح بدون force
- Local=Remote: نعم على `origin/phase-18-m18-ai-marketing-communications`
- تفاصيل: `GIT_DELIVERY.md`

## 37. مخاطر متبقية

- عنوان المُركِّب غير المثالي لـ Mentha.  
- مصادر محلية قد تُتخطى بعد الكفاية.  
- مقاعد next-gate غير المفعّلة غير مختبرة حياً.  
- متصفح UI غير منفّذ.

## 38. مصفوفة البوابات

| بوابة | الحالة |
|---|---|
| 0 Baseline | PASS |
| 1 Inventory | PASS |
| 2 Effectiveness | PASS (جزئي حي لبعض المصادر) |
| 3 Multi-source | PASS |
| 4 Evidence contracts | PASS |
| 5 Mentha | PASS (متعدد المصادر؛ عنوان مُركِّب جزئي) |
| 6 Other domains / Wheat | PASS |
| 7 Observability | PASS |
| 8 E2E | PARTIAL (API حي؛ UI NOT_RUN) |
| 9 Git delivery | PASS (`1af4514…` Local=Remote) |

## 39. التصنيف

`MULTI_SOURCE_RESTORATION_PARTIALLY_COMPLETED`

لا `COMPLETE_VERIFIED` لأن معيار المتصفح التفاعلي لم يُنفَّذ، وبعض المصادر runtime لم تُثبت حياً خارج الاختيار/العقد، رغم أن Mentha لم يعد GBIF-only وأن الإصلاح الجذري لمسار Universal مُسلَّم ومختبر.
