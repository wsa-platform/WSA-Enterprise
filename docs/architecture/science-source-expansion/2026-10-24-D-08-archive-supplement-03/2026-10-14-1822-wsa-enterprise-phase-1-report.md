<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15024). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
The difference is only the counting mode. `--ignored=matching` gives 92, matching the pre-check, and the default (`traditional`) gives 88. No ignored entry has changed since 10-09.

# تقرير Pre-Commit — ‏ADR-023 §8.33 + B-1

**الخلاصة:** نُفّذ B-1، وأُضيف §8.33 إلى ADR-023، ولم يُلمس أي ملف آخر في المستودع. **لم أشغّل أي اختبار PHP، فلا أدّعي نجاح شيء منها.** السبب أنه لا توجد وسيلة اختبار محلية معزولة لا تحتاج Docker المحظور. ‏P1-EXIT-01(c) ما زال **غير مغلق**، وB-2 غير مفوّض. لا staging ولا commit ولا push.

## 1. الحالة قبل الكتابة
طابقت الحالة التقرير السابق، ولم تظهر فروق:
- الفرع `phase-18-m18-ai-marketing-communications`، و‏HEAD هو `f2025315d8990f52f3c658c968a3ec8252ac18b0`.
- الملفات: 1113 غير متتبع، و17 معدّلًا، و0 مُجهَّزًا (staged)، و8 worktrees.
- الملفات الخمسة المستهدفة لم تكن موجودة.
- بصمة ADR كانت `4DB55A26…` بحجم 392,454 بايت، دون §8.33.

## 2. القرار الذي احتجته منك
لم تحسم تعليمات 6:22 النقطة DR-4: هل تنطبق الخطوة 1 من D-10.15 على B-1؟ فسألتك قبل أي كتابة، واخترتَ: **لا تنطبق على B-1** لأنها لا تربط أي مقعد. تبقى سارية على B-2 وعلى أي ربط لمقعد، ويبقى تأكيدها العام مفتوحًا (§8.28.4). لم يُعدَّل D-10.15.

## 3. الملفات التي أنشأتها
أُنشئت كلها بوضع الإنشاء فقط (CreateNew)، بنهايات أسطر CRLF ودون BOM، كبقية ملفات الحزمة.

| الملف | SHA-256 |
|---|---|
| `Projection/ProjectionFacetConstraintState.php` | `0DB108D6…` |
| `Projection/ProjectionFacetConstraint.php` | `684B6000…` |
| `Projection/ProjectionQueryConstraints.php` | `9E6FC206…` |
| `Projection/ProjectionQueryConstraintsFactory.php` | `662C0967…` |
| `tests/Unit/.../Projection/ProjectionQueryConstraintsTest.php` | `D9331468…` |

**ملخص العقد `projection_query_constraints.v1`:**
- **الحقول:** هي الحقول المحددة في تعليماتك، دون `source_query` أو `expanded_terms` أو `fidelity_class`.
- **التحويل:** VERIFIED ← CONSTRAINABLE. ‏UNAVAILABLE وNOT_APPLICABLE ← NOT_CONSTRAINABLE. ‏UNVERIFIED وPARTIAL وMISSING ← UNKNOWN.
- **حقلان مشتقّان لا يحددهما المستدعي:** `identity_critical` يأتي من `ProjectionIdentityCriticalFacets`، و`constraint` يأتي من التحويل أعلاه.
- **اتساق القرارات:** يُفحص اتساق قرار Capability مع قرار Path عبر `assertSubjectAlignment` الموجودة أصلًا.
- **رموز facets:** تبقى مدخلًا صريحًا من المستدعي، ولا يوجد أي استخراج من CSQ.

**خيارات اتخذتُها ضمن قراراتك، ومسجّلة في §8.33.2 لتعدّلها إن شئت:**
1. `MISSING` قيمة إفصاح فقط، لا حالة Capability تُكتب. وهي متسقة مع الثابت "Missing Cap = UNVERIFIED"، لأن كلتيهما تتحول إلى UNKNOWN.
2. لا يُقرأ إلا بُعد `SCIENTIFIC_FACET_QUERY_CONSTRAINABLE`. سجل `content_about` لا يجعل facet قابلًا للتقييد.
3. يُرفض سجل Capability لموضوع مختلف، ويُرفض سجلّان للـ facet نفسه، بدل اختيار أحدهما. حسم التعارض من مسؤولية Capability لا Projection.
4. تُضاف تلقائيًا تحذيرات `QUERY_CONSTRAINT_UNKNOWN` لكل facet غير معروف، تطبيقًا للبند 3.4 من حزمة الحل ولمبدأ D-10.9.6 (لا فقد صامت للـ facets).

## 4. §8.33 في ADR-023
أُلحق القسم بالإضافة في آخر الملف فقط:
- البادئة الأصلية (392,454 بايت) لم تتغير، ولا توجد LF منفردة.
- البصمة الجديدة `235319ED…`، والحجم 408,820 بايت.

يسجّل القسم:
- قراراتك، وقرار DR-4.
- عقد v1 بالكامل.
- ‏Gate A ‏(GA-B1-01) و‏Gate B ‏(GB-B1-01) لنطاق B-1.
- سجل التنفيذ وحالة الاختبارات.
- وضع شروط إغلاق P1-EXIT-01(c):

| الشرط | الحالة |
|---|---|
| 1. تعريفات T10/T8/T2 | مستوفى جزئيًا: الحدّ الذي اعتمدتَه يحلّ محل "Projection owns query" في بناء الاستعلام، لكن T10 وT8 لم يُعتمدا |
| 2. اختيار خيار صريح | مستوفى (Option B، ‏§8.31) |
| 3. تعريف بيانات القيود | مستوفى لنطاق B-1 |
| 5. القرار ضمن Gate A | مستوفى لنطاق B-1 |
| **النتيجة** | **P1-EXIT-01(c) غير مغلق** |

## 5. الاختبارات
كتبتُ 14 دالة اختبار (19 حالة) تغطي البنود الثمانية المطلوبة. اختبارات الرفض تتحقق من نص رسالة الاستثناء بالضبط، لا من وقوع استثناء فقط.

| الاختبار | النتيجة | السبب |
|---|---|---|
| PHPUnit (الملف الجديد ومجموعات Projection/Capability/Path/Identity/Coexistence/Disclosure) | **NOT RUN** | لا يوجد PHP على الجهاز، ولا `backend/vendor`. توزيعة WSL الوحيدة هي docker-desktop، والوسيلة المعزولة السابقة حاوية Docker، وهي محظورة |
| فحص صياغة PHP (`php -l`) | **NOT RUN** | لا يوجد PHP |

هذا قيد بيئة لا نتيجة على الكود. لم أعدّل أي إعداد لتجاوزه.

## 6. التدقيق بعد الكتابة
- **Git:** غير المتتبعة 1118 (أي +5، وهي الملفات الخمسة الجديدة بالضبط)، والمعدّلة 17 كما هي (ADR كان معدّلًا من قبل)، و0 staged. ‏HEAD والفرع وworktrees الثمانية لم تتغير.
- **البصمات المحمية لم تتغير:** ‏`composer.lock`، ‏IC-ER v1، المصفوفات الثلاث، سجل العضوية، ‏`SOURCE-CAPABILITY-CONTRACT.md`، ‏QUS، ‏CSQ، ‏`backend/.env`، ‏`phpunit.xml`، ‏Builder، ‏`ProjectionEnvelope`، وحزمة الحل.
- **أرشيفات D-08:** ما زالت 436 و95 و12 ملفًا.
- **لا مراجع للعقد خارج ملفاته:** لا يوجد في `backend` أي ملف يذكر الأصناف الجديدة سوى الملفات الأربعة نفسها، فلا ربط بمسار التشغيل.
- **الملفات المتجاهلة:** ظهرت 88 مقابل 92 في الفحص المسبق. تحققت فثبت أنه فرق في وضع العدّ فقط: ‏`--ignored=matching` يعطي 92، والوضع الافتراضي يعطي 88. آخر تعديل على أي ملف متجاهل كان في 10-09.
- **ملفات مؤقتة خارج المستودع:** المسودات في `%TEMP%\wsa-b1\`، ونص تعليمات 6:22 في `%TEMP%\wsa-622-prompt.txt`. تُركت كما هي.

## 7. ما يلزم بعد ذلك (قرارات لك)
1. إتاحة بيئة اختبار تسمح بها لتشغيل الاختبارات المكتوبة. هذا شرط قبول GB-B1-01.
2. اعتماد T10 وT8 أو تعديلهما، ثم قرار إغلاق صريح لـ P1-EXIT-01(c).
3. قبل أي B-2:
   - عقد لاستخراج facets من CSQ؛
   - الخطوة 1 من D-10.15؛
   - ‏Gate A و‏Gate B خاصّان بـ B-2؛
   - مقعد بعائلة وصول موثّقة.

توقفتُ هنا، ولن أجري أي تعديل بعد هذا التقرير.

The `--ignored=matching` count matches the precheck at 92, confirming no ignored entries were recently modified, so this is just a counting-mode difference rather than an actual change. I'm done here — I'll leave the temp files I created outside the repo in place since they don't violate the no-delete rule, and note their location before finishing up.

Now I'm writing the Arabic pre-commit report, aiming for something comprehensive yet readable: outcome, the DR-4 decision, file list with hashes, design choices, which tests weren't run and why, audit results, the status of P1-EXIT-01(c), next steps needed, and a note about the temp files located outside the repo.
