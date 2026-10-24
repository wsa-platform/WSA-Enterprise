<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 14986). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
## تقرير ما قبل الالتزام: حزمة حلّ P1-EXIT-01(c)

### A. الحالة

**`PHASE_1_BLOCKED_BY_SPECIFIC_REMAINING_REQUIREMENTS`** — لم تتغيّر.

أعددتُ حزمة الحلّ المفوَّضة كوثيقة جديدة واحدة، ولم أكتب أي كود.

السبب في عدم كتابة الكود هو السجلات المعتمدة نفسها:
- §8.28.4 تنص: "No Gate A record and no Gate B record exist in this ADR".
- §8.18.2 ما زالت سارية: "NO NEXT IMPLEMENTATION UNIT IS AUTHORIZED".

أي كود الآن سيتجاوز البوابتين. لذلك جاءت التغييرات المقترحة مواصفاتٍ كاملة بالملفات والاختبارات، تُنفَّذ بعد قرارك.

لا staging ولا commit ولا push. لم أعدّل ADR ولا IC-ER v1 ولا أي مصفوفة أو أرشيف، ولا `backend/.env` ولا إعداد اختبار FAOSTAT.

---

### B. أهم النتائج

**1. حدّ Builder/Projection محسوم فعلًا في سجلات معتمدة سابقة.** هذه ليست مسألة مفتوحة كما بدت.
- **تفويض تنفيذ IU-09** (موجّه منك بتاريخ 2026-10-01 17:54، والوحدة مغلقة) ينص تحت "AUTHORITY MODEL — MUST REMAIN INTACT" على: "Lexical query: ScientificSearchQueryBuilder". ويشترط أن تكون مدخلات الـ Projection "Builder-produced source_query".
- **مصفوفة السلطات في OD-06**، المسجلة في الموجّه نفسه بحالة "DESIGN COMPLETE"، تنص على: "Source query (lexical) … Final Authority: **Builder**"، و"No dual final authority on any row".
- **الكود الملتزم في HEAD يطابق ذلك:** السطر 52 في المنسّق يبني الاستعلام عبر الـ Builder، ثم يُمرَّر كما هو إلى `ProjectionEnvelope.sourceQuery`.
- عبارة "Projection owns query" لا توجد إلا في المستخرَجين غير المتتبَّعين، وهما ليسا مرجعًا.

**2. لا يوجد سجل معتمد يمنح الـ Projection سلطة توليد `expanded_terms`.**
- تفويض IU-04 (§19) يجعلها "explicit Projection output". المقصود مخرَج محاسبة وإفصاح: تسجيل أن توسيعًا حدث، وهل هو مكافئ تمامًا أم لا.
- §16 من التفويض نفسه تمنع الـ Projection من أن "become the global lexical search builder".
- الـ Builder هو من يولّد التوسيعات اليوم: "sense synonyms" ومتغيرات genus.
- **لذلك تبقى `expanded_terms` تحت سلطة الـ Builder كما طلبت.** يقتصر دور الـ Projection على تسجيلها كسجلات `EXPANDED` وتطبيق قاعدة عدم المكافأة (species→genus ليست EXACT أبدًا).

**3. مصدر حقيقة جاهز للقيود.** الـ Capability المعتمدة (IU-02) تملك بُعدًا لكل facet هو `SCIENTIFIC_FACET_QUERY_CONSTRAINABLE`. القيود تُشتق منه بالقراءة فقط، دون أي دلالات كتابة لـ CapVer.

---

### C. تدقيق عدم الحلول الضمني

| السجل | الموقع | أثر الحزمة |
|---|---|---|
| D-02 | ADR 401–403 | محفوظ |
| D-04 | ADR 409–411 | محفوظ ومؤكَّد صراحةً |
| D-10.9.1–6 | ADR 1448–1455 | محفوظ؛ لا حذف صامت لأي facet، والـ Compiler يبقى metadata-only |
| D-10.12 / D-10.14 / D-10.15 | ADR 1470–1499 | محفوظ؛ لا مخطط بيانات ولا migration ولا تفعيل ولا SAME_AS |
| تفويض IU-04 (§15، §16، §19) | أرشيف 10-01 14:27، الأسطر 359–431 | محفوظ؛ "produced by Projection" تُقرأ على أنها سجل التمثيل لا نص الاستعلام. القراءة نفسها تحتاج تأكيدك (DR-1) |
| OD-06 وتفويض IU-09 | أرشيف 10-01 17:40 و17:54 | محفوظان، والحزمة تعتمدهما |
| الخيار B (§8.26.3) و§8.28.6 | ADR 5070–5090 | الحزمة تحقق الشرطين 1 و3 كمقترح |
| §8.28.4 | ADR 4989–5031 | المسودات تتبع بنود Gate A/B حرفيًا |
| CapVer: قرار C+A في §8.20 | أرشيف 10-09 21:30، الأسطر 116–139 | لا كتابة Capability، ولا دلالات تفويض جديدة |

الخيار C غير معتمد. لا يُتجاوَز أي سجل معتمد؛ التعديل الوحيد يقع على صياغة T2 في المستخرَج، وهو ليس مرجعًا.

---

### D. التعريفات المقترحة لـ T10 وT8 وT2

- **T10:** كما في المستخرَج دون تغيير. اختيار المسار الأساسي بين P01 وP17 يتم بقيود الـ Capability، ويملكه IU-03 وIU-09.
- **T8:** كما في المستخرَج دون تغيير. المسار البديل يمر عبر الطبقات L1 ثم L2 ثم L3، ولا يرفع درجة الدليل ولا درجة C9 أبدًا.
- **T2 بعد التعديل:**
  - الـ Builder يبني الاستعلام، بما فيه أي توسيع.
  - الـ Projection يُصدر قيودًا لكل مصدر **قبل** البناء.
  - **بعد** البناء يسجّل الـ Projection سلسلة الـ Builder، ومحاسبة الـ facets، و`fidelity_class`.
  - الـ Projection لا يبني الاستعلام ولا يعيد كتابته.

---

### E. عقد القيود الوصفية v1 (مقترح)

الكائن المقترح هو `ProjectionQueryConstraints`، داخل حزمة IU-04.

**الحقول:**

| الحقل | النوع | المصدر | يؤثر على الاستعلام؟ |
|---|---|---|---|
| `schema_version` | ثابت `projection_query_constraints.v1` | العقد | لا |
| `canonical_query_id` | غير null | CSQ | لا |
| `adr_id` | غير null | السجل | لا |
| `canonical_identity_id` | nullable، ولا يُستنتج أبدًا | IU-01 | لا |
| `capability_decision_id`، `path_decision_id`، `path_id` | يجب أن تتوافق (`assertSubjectAlignment`) | IU-02 وIU-03 | لا |
| `facet_constraints[]`: `facet_code`، `identity_critical` (مشتق)، `capability_state` (أو `MISSING`)، `constraint` | قائمة مرتبة وفريدة | CSQ × Capability | **نعم** |
| `retrieval_method`، `warnings` | أنواع قائمة | Path / Projection | لا |

**الحقول المستبعدة عمدًا:** `source_query`، و`expanded_terms`، و`fidelity_class`، وأسماء الحقول الأصلية لكل مصدر.

**تحويل حالة الـ Capability إلى قيد:**
- `VERIFIED` ← `CONSTRAINABLE`.
- `UNAVAILABLE` و`NOT_APPLICABLE` ← `NOT_CONSTRAINABLE`.
- `UNVERIFIED` و`MISSING` ← `UNKNOWN`.
- `PARTIAL` ← قرار منك (DR-2).

**مسؤوليات المستهلك (الـ Builder):**
- يحاسب كل facet بحالة واحدة بالضبط بعد البناء.
- لا يضع `MAPPED` لقيد `NOT_CONSTRAINABLE`.
- لا يعطي مساهمة EXACT لقيد `UNKNOWN`.
- يسجّل كل توسيع كـ `EXPANDED`.

**التسلسل:** `toArray()` حتمي، ومفاتيحه بصيغة snake_case، ويجتاز `assertProjectionPayloadClean`. يبقى في الذاكرة والمراقبة فقط، بلا قاعدة بيانات.

**التوافق الخلفي:**
- أنواع مضافة فقط، ومفاتيح `ProjectionEnvelope` لا تتغير.
- وضع `LEGACY_ONLY` لا يتغير.
- المصادر الخمسة الحالية بلا Capability، فقيودها كلها `UNKNOWN` ولن يتغير ناتج الـ Builder.

**نقطة مفتوحة:** لا يوجد سجل معتمد يحدد كيف تُستخرج رموز الـ facets من الـ CSQ. لذلك يأخذها المصنع في الخطوة B-1 مدخلًا صريحًا، وتعريفها مؤجل إلى Gate A الخاص بـ B-2.

---

### F. تغييرات الكود المقترحة (لم يُكتب منها شيء)

**الخطوة B-1: عقد مجال فقط، دون أي ربط بمسار التشغيل.**

خمسة ملفات جديدة:
- `Projection/ProjectionFacetConstraintState.php`
- `Projection/ProjectionFacetConstraint.php`
- `Projection/ProjectionQueryConstraints.php`
- `Projection/ProjectionQueryConstraintsFactory.php`
- `tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php`

**المبرر:** لا يوجد اليوم نوع يحمل قيودًا لكل facet مشتقة من الـ Capability.

**الاختبارات:**
1. كل صف من جدول التحويل، بما فيه `MISSING`.
2. اشتقاق `identity_critical` دون أن يمرره المستدعي.
3. رفض الرموز المكررة أو الفارغة.
4. رفض عدم التوافق بين قرار الـ Capability وقرار الـ Path.
5. حتمية التسلسل.
6. عدم لمس الـ Capability أو أي repository.
7. بقاء حزم Projection وCapability وPath وIdentity وCoexistence وDisclosure الحالية خضراء.

**الخطوة B-2: الربط مع الـ Builder.** تأتي فقط مع أول محوّل لمقعد، وتحت Gate B مستقل. تعدّل ثلاثة ملفات:
- `ScientificSearchQueryBuilder.php`: معامل قيود اختياري.
- `MultiSourceScientificSearchOrchestrator.php`: اشتقاق القيود قبل البناء في أوضاع CGHIA فقط.
- `Stage3CghiaCoexistenceBoundary.php`: تمرير محاسبة الـ facets، والمعامل موجود أصلًا.

اختبارها الحاسم اختبار golden: ناتج الـ Builder بقيود `null` يساوي ناتج HEAD حرفيًا. ومعه اختبارات الإبقاء على كل facet، وعدم ترقية الـ fallback، والانحدار الكامل. اختبار `fao_stat` المرتبط بـ `.env` يُبلَّغ عنه منفصلًا.

---

### G. مسودتا البوابتين

**Gate A لهذا النطاق:**
- يختار الخيار B بتعريفات D وعقد E، ويحفظ D-04 وD-10.9.2.
- بقية بنود §8.28.4 خارج النطاق صراحةً: الـ Registry، وCapVer، والتعبئة، وترتيب IC، وGO-1، والـ staging.
- العقد التشغيلي (Onboarding) مطلوب لـ B-2 فقط.

**Gate B للخطوة B-1:**
- النطاق: الملفات الخمسة فقط، بلا أي `adr_id`.
- محظور: الـ Builder، والمنسّق، والـ Boundary، والـ Selector، والـ Registry، والمحوّلات، والـ Composer، وCSQ/QUS، وكتابة Capability، وCapVer، والربط، وSAME_AS، والتفعيل، والـ migrations، و`.env`، وإعداد FAOSTAT، والمرحلتان 2 و3.
- الاختبارات: SQLite في حاوية معزولة بلا شبكة.
- أسباب التعليق: شروط §8.28.4.

Gate B للخطوة B-2 لم أصغه، لأنه يعتمد على البندين P1-EXIT-07 وP1-EXIT-08.

---

### H. طلبات القرار (الحد الأدنى)

| الرقم | السؤال | توصيتي |
|---|---|---|
| DR-1 | قبول قراءة الحد (القسم B) وتعريفات T10 وT8 وT2 المعدَّلة | قبول |
| DR-2 | قيد خلية `PARTIAL` | `UNKNOWN` |
| DR-3 | facet من نوع `NOT_CONSTRAINABLE` يضعه الـ Builder كلفظ نصي فقط | `APPROXIMATED` مع ذكر السبب |
| DR-4 | هل تنطبق الخطوة 1 من D-10.15 على B-1، وهي لا تخص أي مقعد؟ | لا تنطبق على B-1، وتنطبق على B-2 |
| DR-5 | تسجيل Gate A وGate B للخطوة B-1 | التسجيل بعد DR-1 إلى DR-4 |

إغلاق P1-EXIT-01(c) يحتاج DR-1 وDR-2 وDR-3 ثم Gate A مسجّل. الشرط 2 مستوفى أصلًا في §8.31. تنفيذ B-1 يحتاج فوق ذلك DR-4 وDR-5.

---

### I. الملفات وGit

**الملف الجديد الوحيد:** `docs/architecture/science-source-expansion/2026-10-14-PHASE-1-RESOLUTION-PACKAGE-P1-EXIT-01c.md`
- SHA-256 ‏`880E34B9…325D`، حجمه 24,809 بايت، 236 سطرًا، CRLF=235، bare LF=0.
- أُنشئ بوضع CreateNew بعد التحقق من أن الوجهة غير موجودة.

**حالة المستودع:**
- HEAD ‏`f2025315d8990f52f3c658c968a3ec8252ac18b0`.
- الإجمالي 1130 (كان 1129): 1113 untracked، و17 معدّلة، و0 مرحلية.
- قائمة ملفات الكود والاختبارات المعدّلة مقابل HEAD هي نفسها دون تغيير.

**الملفات المحمية، كلها دون تغيير:**

| الملف | البصمة |
|---|---|
| ADR | `4DB55A26` |
| `composer.lock` | `D6EE17A7` |
| IC-ER v1 | `877C1604` |
| مصفوفة 10-12 | `587801A7` |
| مصفوفة 10-14 v1 | `3E88B1FD` |
| مصفوفة 10-14 v2 | `0FCB6592` |
| السجل | `78303518` |
| `SOURCE-CAPABILITY-CONTRACT.md` | `E842E757` |

أعداد أرشيف D-08: الأصلي 436، و`supplement-01` ‏95، و`supplement-02` ‏12.

**مسوّدة خارج المستودع:** `%TEMP%\wsa-resolution\package.md`.

---

### J. الاختبارات

**NOT RUN في هذه الجلسة.** السبب أنه لم يتغير أي كود، ولا يوجد ما يُختبر. أحدث الأدلة من الجلسة السابقة:
- حزم المجال: 245 اختبارًا و1266 تأكيدًا، OK.
- الواجهة الأمامية: 362 اختبارًا ناجحًا.

اختبارات B-1 وB-2 محددة في F وتُنفَّذ بعد Gate B. وFlutter ‏NOT RUN.

---

### K. تحديث قائمة الالتزام المقترحة

تُضاف وثيقة الحزمة إلى القائمة السابقة. هي ملف جديد غير مختلط، وآمنة كملف كامل.

لا تتغير بقية القائمة. ملف ADR يبقى مختلطًا (§8.27 إلى §8.32)، وملفات CSQ/QUS والاختبارات المعدلة و`.env.example` تبقى مستثناة.

---

### L. التسليم

1. أجب عن DR-1 إلى DR-5.
2. بعد ذلك، وبتفويض صريح بتعديل ADR، يُلحق قسم §8.33 فيه سجل Gate A، وGate B للخطوة B-1 إن قررت.
3. يلي ذلك تنفيذ الخطوة B-1 وحدها واختباراتها في حاوية معزولة.
4. هذا التقرير غير مؤرشف بعد في D-08. يُؤرشف في `supplement-03` بعد التحقق من أن الوجهة غير موجودة.

توقفت هنا، ولن أُجري أي تعديل بعد هذا التقرير.

I didn't record the QUS/CSQ hashes earlier, so I'll just report the current ones and note I made no edits since the diff stats and file list remain unchanged. Now I need to write the final comprehensive Arabic pre-commit report, keeping the status as PHASE_1_BLOCKED_BY_SPECIFICATION.

I'm structuring it with sections covering status, completed work, authority audit, boundary resolution, constraint metadata, proposed-but-unwritten code changes and rationale, Gate A/B drafts, decision requests, file/git state, tests (none run since no code changed), commit manifest update, and handoff — keeping it readable but thorough.
