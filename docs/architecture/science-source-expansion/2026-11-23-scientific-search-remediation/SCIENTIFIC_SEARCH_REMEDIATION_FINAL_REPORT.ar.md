# تقرير الإصلاح النهائي — البحث العلمي والإجابة (عربي/متعدد اللغات)

## 1) الملخص التنفيذي

تم تنفيذ إصلاحات القضايا المعتمدة (P0 العربي + عقود الحالة واللغة والاختبارات)، مع اختبارات مركّزة ناجحة (12/12)، ومسبار AGRIS حي يثبت نجاح العربية والإنجليزية معًا (raw=16 لكل منهما)، وcommit/push إلى الفرع المعتمد دون المساس بـ WIP السابق.

## 2–4) Git قبل/بعد

| | القيمة |
|---|---|
| الفرع | `phase-18-m18-ai-marketing-communications` |
| HEAD قبل | `d655c40` |
| HEAD بعد (محلي/بعيد) | `d8f4749` |
| WIP | محفوظ ومستثنى من الـ commit |

## 5) الأسباب الجذرية المؤكدة

انظر `01-ROOT-CAUSE-ANALYSIS.md` (Sense، تسرّب الأسطح العربية، FTS AND، أهلية OE، مفاهيم organic/soil، خلط لغة الإجابة، أسماء الحالات، غياب عقد لغة المصدر، اختبارات R2 القديمة).

## 6–7) التصميم والقرارات

- الالتزام بـ ADR-022: الأسطح عربية؛ التجميع الاسترجاعي إنجليزي.
- عدم تخفيف Validation/Relevance.
- عقد لغة المصدر منفصل عن Membership/Eligibility/Activation.

## 8) التنفيذ لكل قضية

| القضية | الحالة | الحل | الاختبارات |
|---|---|---|---|
| ISS-AR-003 | VERIFIED | تفضيل قصد الري + Sense عبر asksExplicitIrrigation | RemediationTest |
| ISS-AR-001/FTS-001 | VERIFIED | joinTerms بلا عربي + AGRIS specializer بالاسم العلمي | مسبار AGRIS AR success |
| ISS-AR-006 | VERIFIED | compileTerm/specExecutable بلا عربي | Unit |
| ISS-AR-002 | VERIFIED | إشارات عضوية متعددة اللغات | OE eligible AR |
| ISS-AR-004 | VERIFIED | عوامل organic/soil | Unit |
| ISS-AR-005 | TESTED | وسم المقتطفات الأجنبية | LanguageContract |
| ISS-EVD-002 | TESTED | EvidencePipelineStatus | Unit canonicalize |
| ISS-SRC-002 | TESTED | LanguageCapabilityCatalog | Unit |
| ISS-TEST-001 | VERIFIED | اختبارات R2 | LanguageContract 6 PASS |

## 9) الملفات

انظر `03-IMPLEMENTATION-REGISTER.md` (+ وثائق المجلد الحالي).

## 10–12) الاختبارات

- PHPUnit مركّز: **12 passed / 142 assertions**.
- تكامل مسار الفهم→variants→أهلية: ضمن الاختبارات أعلاه.
- حي: AGRIS+OpenAlex قمح AR/EN — `mission_post_agris_probe.json`.

## 13) مقارنة عربية/إنجليزية

| | قبل (QUS) | بعد (QUS + AGRIS) |
|---|---|---|
| Sense قمح AR | plant_growth | crop_water_requirement |
| عربي في variants | نعم | لا |
| OE للعربية العضوية | لا | نعم |
| AGRIS قمح AR | فارغ (تاريخي/أول مسبار بعد التجميع) | **نجاح raw=16** |

## 14) التركية والفرنسية

كشف اللغة وعقود R2 تغطي tr/fr في الاختبارات؛ **استرجاع حي tr/fr: NOT_RUN**.

## 15–17) الأدلة والجودة والأداء

- المراجع: لم تُضعّف؛ تغطية الاقتباس لم تُعاد قياسها بالكامل بعد الإصلاح.
- جودة الإجابة: وسم المقتطفات الأجنبية؛ التركيب ما زال استخراجياً.
- الأداء: مسبار AR AGRIS ~1.6s في تشغيل محدود المصادر؛ لا percentiles.

## 18–19) المتبقي / غير المختبر

- تحقق tr/fr الحي.
- بعض فشل ScientificUpstreamRetrievalFixTest (ملف WIP سابق) — UNDETERMINED.
- عمق التركيب العلمي الكامل — PARTIALLY_VERIFIED.

## 20–22) الإصدار

- Commit: `d8f4749aa7738374d1c5574b5cf13c6e31bf7729`
- Push: ناجح إلى origin (تطابق HEAD)
- الحالة النهائية: الفرع متزامن؛ WIP غير المرتبط بالمهمة ما زال محلياً

**النتيجة المطلوبة:** مسار بحث علمي متعدد اللغات يفهم السؤال العربي، يبني استعلامات مناسبة للمصادر، يسترجع أدلة (بما فيها AGRIS)، ويحافظ على العقود المعمارية — مع أدلة اختبار ورفع إلى المستودع.
