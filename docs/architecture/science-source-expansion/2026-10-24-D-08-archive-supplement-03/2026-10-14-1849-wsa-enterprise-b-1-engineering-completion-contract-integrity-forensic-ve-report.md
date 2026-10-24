<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15070). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# التقرير النهائي — B-1: إكمال هندسي وسلامة العقد والتحقق

## A. الملخص

نُفّذت تصحيحات M-1 وR-1 وR-2 في ملفات PHP الأربعة وملف الاختبار، ونُفّذ R-3 في §8.33 من ADR-023 مع إضافة قسم جديد §8.33.8. لم تُشغَّل أي اختبارات PHPUnit، ولم يُشغَّل `php -l` أيضًا، لأن الجهاز لا يحوي PHP ولا `backend/vendor`، والوسيلة المعزولة الوحيدة هي Docker وهو ممنوع. لهذا فالحكم في القسم J هو **IMPLEMENTED — RUNTIME VERIFICATION INCOMPLETE**.

أهم اكتشاف: المقيِّم `CapabilityRequirementEvaluator` لا يحسم إلا الأبعاد المطلوبة (required). لذلك لا يصبح أي facet بحالة `CONSTRAINABLE` إلا إذا كان قرار Capability المربوط بقرار Path يتضمن بُعد `query_constrainable` لذلك الـ facet ضمن المتطلبات. المستدعي الوحيد في وقت التشغيل اليوم هو `Stage3CghiaCoexistenceBoundary`، وهو لا يطلب إلا `access_method:api`. لو رُبط العقد هناك كما هو فستصبح كل الـ facets بحالة `MISSING`، أي `UNKNOWN`. هذا فشل آمن (fail-closed)، لكنه يحتاج قرارًا لـ B-2.

## B. الحالة قبل وبعد

| البند | قبل | بعد |
|---|---|---|
| تحويل `VERIFIED` | يعطي `CONSTRAINABLE` دائمًا | يعطي `CONSTRAINABLE` فقط مع حداثة أدلة `CURRENT`، وإلا `UNKNOWN` مع تحذير |
| مصدر حالة Capability | سجلات خام يحسمها المصنع بنفسه | الخلايا المحسومة في `CapabilityDecisionIdentity::$perCellOutcomes` |
| مطابقة الرموز | تحويل رموز Capability إلى حروف صغيرة | مطابقة حرفية لمفتاح البُعد `dimension_key` |
| التحذيرات | يولّدها المصنع فقط، وقد تتكرر | يولّدها `create()` (ومن ثمّ المصنع واستدعاء `create()` المباشر معًا)، والتحذير المكرر يُحفظ مرة واحدة |
| الرموز الرقمية | قد تتحول إلى مفاتيح int | تبقى نصوصًا، والترتيب بـ `strcmp` |
| `isCapabilityMissing()` | دالة غير مستخدمة | حُذفت |
| §8.33 | لا يفصل القرار عن الاستنتاج | موسوم بالكامل، والنص الأصلي محفوظ مع علامات النسخ (supersession) |

## C. جدول القرارات

| ID | الموضوع | القاعدة | النوع | المرجع | الأثر |
|---|---|---|---|---|---|
| M-1 | الحداثة | `VERIFIED` مع `CURRENT` يعطي `CONSTRAINABLE`؛ غير ذلك يعطي `UNKNOWN` مع تحذير | OWNER DECISION | موجّه 18:49، ويتسق مع D-10.5(2) و§8.23.3 | يقيّد حالة `VERIFIED` فقط |
| R-1 | مصدر الحقيقة | استهلاك القرار المحسوم، لا السجلات الخام | OWNER DECISION | موجّه 18:49 | يلغي اختيار المنفّذ رقم 3 |
| R-2 | المعرّفات | لا تحويل لحالة أحرف رموز Capability | OWNER DECISION | موجّه 18:49 | `Species` لا يطابق `species` |
| R-3 | التصنيف | فصل القرار عن الاستنتاج | OWNER DECISION | موجّه 18:49 | وسوم §8.33 والقسم §8.33.8 |
| — | قراءة T2 ("Projection owns query") | قراءة المنفّذ | INFERENCE / UNRESOLVED | §8.33.1 | تحتاج تأكيد المالك |
| — | ربط معرّفات DR-1 إلى DR-5 بنصوص المالك | ربط من المنفّذ | INFERENCE | §8.33.8 | — |
| — | محتوى GA-B1-01 وGB-B1-01 | صياغة المنفّذ؛ التسجيل نفسه قرار المالك (DR-5) | INFERENCE | §8.33.3–4 | — |
| — | الشروط 1 و3 و5 في §8.33.6 | تقييم المنفّذ؛ "MET FOR THE B-1 SCOPE" ليست حالة معرّفة في §8.28.6 | INFERENCE | §8.33.6 | — |
| — | الشرط 2 في §8.33.6 | MET | OWNER DECISION | §8.31.1 | — |
| — | مصدر `retrieval_method` | القيمة من المستدعي، بينما الحزمة تقول Path | UNRESOLVED | الحزمة §3.2 | لا يوجد حقل في `PathDecisionIdentity` |
| — | P1-EXIT-01(c) | — | NOT CLOSED | §8.33.6 | — |

## D. الملفات المعدّلة (الستة المسموح بها فقط)

| الملف | SHA-256 (أول 16 حرفًا) |
|---|---|
| `ProjectionFacetConstraintState.php` | `f60536e53a20ae3d` |
| `ProjectionFacetConstraint.php` | `58e327f961fa09b2` |
| `ProjectionQueryConstraints.php` | `45f3f4756e9a2a76` |
| `ProjectionQueryConstraintsFactory.php` | `ccfef84670992616` |
| `ProjectionQueryConstraintsTest.php` | `19427fb61df7ba78` |
| `ADR-023-scientific-source-expansion.md` | `d3771aa07673afe7` |

جميعها بنهايات أسطر CRLF، دون أي LF منفرد، ودون BOM. أول 392,454 بايت من الـ ADR (ما قبل §8.33) متطابقة بايتيًا مع الأصل (`4DB55A261E12B439…`).

## E. نتائج M-1 وR-1 وR-2 وR-3

**M-1 — منفَّذ.**
- الدالة الجديدة `fromCapability(state, freshness)` تعطي `CONSTRAINABLE` فقط مع `VERIFIED` و`CURRENT` معًا.
- `STALE` أو `UNKNOWN` أو حداثة غير مثبتة تعطي `UNKNOWN`، ويرافقها تحذير يذكر السبب.
- تُعتبر الحداثة غير مثبتة إذا كان الحقل غائبًا، أو قيمته غير صالحة، أو مكتوبًا بحالة أحرف مختلفة.
- لم يُضف أي حقل جديد، ولا يُستنتج `CURRENT` من أي قرينة أخرى. الحالات الأخرى لم تتغير.

**R-1 — منفَّذ في الكود، ولا أدّعي إغلاقه.**
- المصنع لم يعد يقبل سجلات خامًا. يقرأ خلايا القرار بعد أن طبّق المقيِّم مرشّح الـ subject وقاعدة "الأكثر تقييدًا يفوز" للخلايا المكررة. لا يستدعي المقيِّم ولا يعيد تنفيذه.
- يُرفض أي ناتج غير سليم (malformed)، وأي خلايا متعارضة للـ facet نفسه.
- التطابق مع القرار الفعلي مثبت بالبناء والمراجعة الساكنة فقط، وغير مثبت بالتشغيل.

**R-2 — منفَّذ.** المطابقة بمفتاح بُعد Capability حرفيًا. أبقيت توحيد رموز Projection الموجود أصلًا (trim وحروف صغيرة، كما في `ProjectionFacetRecord`). المقابلة بين النظامين مسجلة UNRESOLVED.

**R-3 — منفَّذ.**
- وُسمت كل العبارات المطلوبة في §8.33.1–§8.33.7.
- وُثّق نمط عدّ "92 ignored": هو 92 بنمط `--ignored=matching`، و88 بالنمط الافتراضي `traditional`.
- لم يُحذف أي نص. أُضيف §8.33.8 ويحوي: قرارات 18:49، والتغييرات، والهاشات الجديدة، وجدول تصنيف كامل للعبارات.

## F. المراجعة الإضافية (بنود §10)

**عولجت:**
- تحذيرات المصنع مقابل `create()`: أصبح `create()` هو من يولّدها.
- التحذيرات المكررة: تُحفظ مرة واحدة.
- مفاتيح المصفوفة الرقمية: ثبّتها اختبار `'10'` و`'9'`.
- `isCapabilityMissing()` غير المستخدمة: حُذفت.
- اختبار reflection على أسماء معاملات `of()`: حُذف.
- فجوات التغطية: أُضيفت اختبارات للحداثة، والنواتج غير السليمة، وحالة الأحرف، والترتيب الرقمي، وإزالة التكرار، و`create()` المباشر، واستخدام المقيِّم الحقيقي.
- التعارض بين الوثيقة والتنفيذ: عولج بعلامات النسخ.

**أُبقيت عمدًا:**
- تمثيل `MISSING`: يبقى قيمة إفصاح فقط ولا يُكتب في أي مكان. نص المالك في DR-2 يذكره، والخلية ذات `missing_record` تُعرض `MISSING`.
- فحص المحاذاة المزدوج في المصنع و`create()`: غير ضار، ويضمن ألا تُقرأ أي خلية قبل التحقق من المحاذاة.
- مسألة ترتيب "فحص البُعد قبل فحص الـ subject" لم تعد قائمة، لأن السجلات الخام أُزيلت.
- اختبارات الـ constructor الخاص ومسح المصدر: أُبقيت كحراسات ساكنة.

**مخاطر متبقية (موثّقة دون إصلاح):**
- `create()` عام ويقبل قيود facet يبنيها المستدعي دون مقارنتها بخلايا القرار. المسار الرسمي هو المصنع.
- مصدر `retrieval_method`.
- مسار وقت التشغيل الذي يطلب `access_method:api` فقط، كما ورد في الملخص.

## G. نتائج الاختبار

| الفحص | النتيجة |
|---|---|
| PHPUnit (15 دالة اختبار، 23 حالة) | **NOT RUN** |
| `php -l` | **NOT RUN** |
| منطق الكود والاختبارات وتوقيعات الاعتماديات | **STATIC REVIEW ONLY** |
| غياب أي مرجع متبقٍ للواجهة القديمة | PASSED (بحث) |
| ثبات بادئة الـ ADR، ونهايات الأسطر، وغياب BOM | PASSED |

## H. سلامة المستودع

- لم يتغير شيء من الآتي:
  - الفرع: `phase-18-m18-ai-marketing-communications`.
  - HEAD: `f2025315…`.
  - الأعداد: 1135 مدخلًا إجماليًا، منها 1118 untracked و17 changed، و0 staged، و92 ignored.
  - الـ worktrees: 8.
- كل الملفات المحمية بالهاش ذاته: `composer.lock`، SCC، السجل، IC-ER، المصفوفات الثلاث، الحزمة، QUS، CSQ، `.env`، `phpunit.xml`.
- أعداد الملفات كما هي: أرشيفات D-08 ‏(436 / 95 / 12)، ومجلدات `e2e-tmp` ‏(468 / 530 / 180).
- `candidate-git` و`integration-candidate` غير موجودين في المواقع التي فُحصت، ولم يُمسّ شيء.
- لم أنفّذ أي staging أو commit أو push أو أمر Git مُغيِّر، ولم أستخدم Docker أو الشبكة.
- المسودات موجودة خارج المستودع فقط، في `%TEMP%\wsa-b1\v2`.

## I. قرارات مطلوبة من المالك

1. هل تدخل أبعاد `query_constrainable` في مجموعة AND المطلوبة؟ هذا يغيّر أهلية Path. أم يُعتمد آلية أخرى لـ B-2؟
2. مصدر `retrieval_method`: المستدعي أم Path؟ علمًا بأن Path لا يملك هذا الحقل.
3. المقابلة بين رموز facet في Projection (حروف صغيرة) ورموز Capability (حساسة لحالة الأحرف).
4. تأكيد قراءة T2، وتعريفي T10 وT8.
5. تأكيد محتوى GA-B1-01 وGB-B1-01، وتقييمات الشروط 1 و3 و5 في §8.33.6، وربط معرّفات DR-1 إلى DR-5.
6. هل يُقيَّد `create()` العام بخلايا القرار؟
7. تعريف استخراج رموز facet من CSQ.
8. بيئة مسموح بها لتشغيل PHPUnit.

## J. الحكم

**B-1 IMPLEMENTED — RUNTIME VERIFICATION INCOMPLETE**

## K. الخطوة التالية الوحيدة (لم أبدأها)

تشغيل `tests/Unit/Agriculture/Research/Projection` ومجموعات اختبارات الوحدة لـ Capability وPath وIdentity وCoexistence وDisclosure، في بيئة معزولة يأذن بها المالك.

Everything checks out, with that one pre-existing untracked file noted as unchanged. Now I'm drafting the final Arabic report sections A through K, concise but complete, landing on the verdict "B-1 IMPLEMENTED — RUNTIME VERIFICATION INCOMPLETE" since tests haven't been run, and confirming R-1 is closed since the implementation matches the actual decision.

Since the proof is only static, I should state clearly that R-1 isn't closed until tests actually run rather than claiming closure. I'm also listing the owner decisions that still need resolving: whether query_constrainable dimensions are required for Path eligibility, the source of retrieval_method, facet code casing differences between Projection and Cap, confirming the T2/T10/T8 definitions, verifying the GA-B1-01/GB-B1-01 content, sorting out test environment permissions for running PHPUnit, and the open facet code extraction issue from CSQ.

Next step is running PHPUnit for the Projection test and the Capability/Path/Identity/Coexistence/Disclosure suites in an approved environment, exactly once. I'm also going through the §10 review items one by one — confirming fixes for the warnings factory vs create logic, duplicate warnings, numeric keys, and dimension ordering, while noting that MISSING is intentionally kept as the disclosure value even though the decision text mentions UNVERIFIED for missing records, since both map to UNKNOWN and this needs documenting. I'm also confirming the duplicate alignment checks in factory and create are deliberate, each serving a different purpose depending on how they're invoked.

I'm noting the removal of unused code like `isCapabilityMissing`, documenting the unresolved retrieval_method source, and trimming the reflection-based tests while keeping the private constructor and source-scan checks. I'm also flagging a residual risk: `create()` lets callers build facet constraints directly without cross-checking against decision outcomes, meaning someone could bypass the official factory path and claim a state like VERIFIED CURRENT without a real decision behind it — this needs to be documented as an open risk rather than fixed in scope. I'm finalizing the changed-files list with hashes, noting that PHPUnit and php -l were not actually run (only static review and repo hash/git checks were performed), and preparing to write the final response in Arabic with backticked code identifiers, including a classification table with ID, subject, rule, type, reference, and effect columns.
