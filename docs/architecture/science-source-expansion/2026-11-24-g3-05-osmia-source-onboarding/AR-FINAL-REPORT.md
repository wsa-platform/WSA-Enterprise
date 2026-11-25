# التقرير النهائي — إكمال وتفعيل مصدر OSMIA (G3-05)

**التاريخ:** 2026-11-25  
**الفرع:** `phase-18-m18-ai-marketing-communications`  
**المهمة:** Complete / Verify / Activate → `SOURCE_ADDITION_VERIFIED`

## A. الملخص التنفيذي

أُكمل تكامل **G3-05 OSMIA** (`osmia`) كم مصدر علمي دائم داخل WSA-Enterprise: فهرسة FTS5 على volume دوكر لينكس، استرجاع Stage-3 حي، تحقق أدلة، إسناد ادعاءات، ووصول الأدلة إلى AnswerComposer.

**التصنيف النهائي:** `SOURCE_ADDITION_VERIFIED`

**ما تبقى (غير حاجز):** تحقق FR لبعض أسئلة Nomada يعيد `validated_count=0` (قيود لغة/ملاءمة Stage-4، ليست غياب المصدر). استعلام Osmia+Megachile المركب قد يُفرغ FTS دون إصابة — يُعالَج جزئيًا عبر `OsmiaLocalIndexQuerySpecializer`.

## B. جذر FTS5 والحل

| عنصر | النتيجة |
|---|---|
| السبب الفعلي | SQLite FTS5 على **Docker Desktop bind-mount** (Windows) يفسد/يعلّق الكتابة (`SQLSTATE[HY000]`؛ صورة bind-mount ~9.8GB malformed) |
| التشخيص | المسار القديم تحت `storage/app/...` غير صالح لـ FTS؛ النسخ إلى volume دون فحص سلامة أنتج DB تالفًا |
| الحل | `SCIENTIFIC_RECORD_STORE_DATABASE=/var/lib/wsa/scientific_records/scientific_record_store.v2.sqlite` على named volume `scientific_records_data`؛ entrypoint يرفض DB تالف؛ مخطط نظيف + حصاد idempotent |
| الثبات | 80 سجلًا + 80 صف FTS؛ `integrity_check=ok`؛ البحث بعد `docker compose restart` ناجح (Nomada/abeille) |
| سياسة | لا PostgreSQL بديل؛ لا in-memory دائم؛ SQLite محفوظ |

## C. تكامل OSMIA

| عنصر | الحالة |
|---|---|
| الهوية | `osmia` / ADR G3-05 / NearReady ACTIVE / CapVer |
| الترخيص | CC BY 4.0 على مستوى عمل Crossref؛ `FULL_TEXT_STORAGE=PERMITTED` لتجريدات + بيانات وصفية (لا أرشفة PDF)؛ `REDISTRIBUTION=CONDITIONAL` |
| السجلات القائمة | **80** (لا إعادة حصاد شبكي؛ snapshot موجود) |
| سجلات جديدة | **0** (إعادة استخدام JSONL) |
| LocalIndex | generation ≥ 1؛ ACTIVE؛ abstracts محتفظ بها (79/80) |
| FTS5 | نشط على volume v2 |
| التفعيل | أهلية bee/pollinator؛ catalog + `OSMIA_ENABLED`؛ knowledge type = `SCHOLARLY_EVIDENCE` |

## D. الاسترجاع الحي (Stage-3)

**المسار:** Scientific Question → Planner → Source Selection → MultiSourceOrchestrator → OSMIA LocalIndex → Validation → AnswerComposer

| استعلام | osmia selected | hits | ids (عيّنة) |
|---|---|---:|---|
| What is known about Nomada wild bee host associations and habitat? | نعم | 7 | `10.47446/osmia13.3`, `osmia10.7`, `osmia9.11`, … |
| Quelles données sur les abeilles sauvages Nomada et leur habitat? | نعم | 7 | نفس عائلة DOI |
| classification Melittidae Apis mellifera bee (forced compose) | — | 1 | `10.47446/osmia1.7` |

المصدر في النتائج: `source_key=osmia`؛ المعرفات DOI حقيقية من المجلة.

## E. إسناد الادعاءات

| مقياس | قيمة (EN Nomada) |
|---|---|
| أدلة مسترجعة osmia | 7 |
| validated_count | 7 |
| validated OSMIA ids | `osmia13.3` … `osmia4.6` |
| claim_count | ≥1 |
| composer | `synthesis_completed_partial` |
| osmia_attribution_signal | **true** |

عينة تصنيف النحل: `validated_count=1`, `citation_count=1`, `synthesis_completed`, DOI `10.47446/osmia1.7`.

## F. AnswerComposer

- الأدلة القابلة للتركيب كانت SUPPORTING بسبب `topic_matched=false` على `general_knowledge`.
- أُضيف مسار faunistic/biodiversity occurrence (موازٍ لمسار الهوية التصنيفية) → DIRECT عند تطابق الكيان + إشارات faunistic في الورقة.
- الناتج: إجابة علمية مع ادعاء وإسناد إلى وثائق OSMIA دون اختصار خاص بالمصدر في Composer.

## G. مصفوفة البوابات

| بوابة | الحكم | دليل |
|---|---|---|
| A Persistent FTS5 | **PASS** | volume v2؛ integrity ok؛ بحث بعد restart |
| B Permanent integration | **PASS** | 80 سجل؛ ترخيص؛ activation |
| C Live Stage-3 | **PASS** | orchestrator؛ osmia في successful؛ 7 ids |
| D Claim attribution | **PASS** | validated≥1؛ claims→DOI OSMIA |
| E AnswerComposer | **PASS** | synthesis_completed(_partial) + attribution |
| F Regression | **PASS** | OsmiaG305 5/5؛ DirectnessAssessor 4/4 |
| G Production readiness | **PASS** | تخزين دائم؛ لا workaround مؤقت؛ لا تدمير DB أساسي |

## H. الاختبارات

| Suite | Command | Passed | Failed | Exit |
|---|---|---:|---:|---:|
| OsmiaG305OnboardingTest | `php artisan test --filter=OsmiaG305OnboardingTest` | 5 (23 assert) | 0 | 0 |
| ScientificEvidenceDirectnessAssessorTest | `php artisan test --filter=ScientificEvidenceDirectnessAssessorTest` | 4 | 0 | 0 |
| Stage-3 live probe | `php e2e-tmp/_osmia_stage3_live_probe.php` | any_hit/validated/attribution=true | — | 0 |
| Compose attribution | `php e2e-tmp/_osmia_compose_attribution_probe.php` | validated=1, synthesis_completed | — | 0 |

UI React/Flutter: **NOT APPLICABLE** (لا تغيير عقود واجهة).

## I. القيود

1. FR Nomada قد يفشل التحقق رغم استرجاع OSMIA (قيود لغة Stage-4).
2. استعلامات مركبة قد تفرّغ FTS قبل التخصص — الاعتماد على `OsmiaLocalIndexQuerySpecializer`.
3. Bind-mount legacy DB تالف؛ البيانات الأخرى السابقة غير مستعادة من ذلك الملف — OSMIA أُعيدت من JSONL.
4. لا أرشفة PDF؛ التجريدات فقط تحت CC BY مع الإسناد.

## J. Git والإصدار

| | SHA |
|---|---|
| Starting HEAD (جلسة الإكمال) | `6e60c76b74ea7273280e6f16e27bbb4182a0b1d8` |
| Feature + docs commit | `0bfea3be5af9e4ad8fe428cd3395d4e50e4dd647` |
| Local HEAD | `0bfea3be5af9e4ad8fe428cd3395d4e50e4dd647` |
| Remote HEAD | `0bfea3be5af9e4ad8fe428cd3395d4e50e4dd647` |
| Push | SUCCESS (`6e60c76..0bfea3b` → `origin/phase-18-m18-ai-marketing-communications`) |
| Local/remote sync | **MATCH** |

## K. التصنيف

**`SOURCE_ADDITION_VERIFIED`** — البوابات A–G محققة بأدلة تشغيل حية.

---

## L. المهمة الهندسية التالية — مقترح فقط (لا تنفيذ)

### 1. اسم المهمة
**M-S3-ML-FAUNISTIC-VALIDATION — Multilingual Stage-4 Validation for Faunistic / Pollinator Evidence**

### 2. الهدف الهندسي
جعل مسار التحقق Stage-4 وAnswerComposer يعاملان أسئلة الملقحات/الحيوانات البرية متعددة اللغات (خصوصًا FR/AR/TR) بنفس جودة EN عند وجود أدلة OSMIA/علمية مطابقة.

### 3. الحالة المتحققة
- OSMIA Stage-3 EN: استرجاع + تحقق + إسناد PASS.
- نفس عائلة الاستعلام FR: استرجاع OSMIA ناجح لكن `validated_count=0` و`no_validated_evidence`.

### 4. المشكلة المحددة
فشل بوابة التحقق/الملاءمة اللغوية للاستيرادات faunistic غير الإنجليزية رغم تطابق الكيان والمصدر.

### 5. فرضية السبب (منفصلة عن الوقائع)
**فرضية:** AgriculturalScientificValidationService / RelevanceGate يعتمد إشارات حسّ إنجليزية أو `definitional_statement` ترفض الملخصات الفرنسية.  
**وقائع:** الاسترجاع ينجح؛ التحقق يُصفّر العدّاد؛ Composer يعيد رسالة عدم كفاية الأدلة.

### 6. المعمارية المقترحة
توسيع عقود الملاءمة متعددة اللغات للإشارات faunistic (host/habitat/occurrence) دون إضعاف بوابات المحاصيل الحرارية/الكمية؛ إعادة استخدام مسار `faunistic_occurrence_answerable` مع معاجم FR/AR/TR.

### 7–8. الاستراتيجية ومسارات العمل
1. تشخيص رفض Stage-4 لكل لغة على نفس DOI.  
2. توحيد `sense`/`topic` للأسئلة faunistic في QUS.  
3. توسيع answerability متعددة اللغات.  
4. مسبارات حية EN/FR/AR/TR + انحدار Directness/Relevance.

### 9. الاعتماديات
OSMIA LocalIndex الدائم؛ لا مصدر جديد مطلوب.

### 10. ملفات متوقعة
`AgriculturalScientificValidationService`, `ScientificEvidenceRelevanceGate`, `QueryUnderstandingService`/`ResearchPlanner`, اختبارات عقد متعددة اللغات.

### 11–12. المخاطر والتخفيف
خطر إضعاف بوابات المحاصيل → عزل المسار بمعرفة النوع BIODIVERSITY_OCCURRENCE؛ اختبارات انحدار الحرارة/الري إلزامية.

### 13–14. اختبارات وقبول
مسبار FR يعطي `validated_count≥1` و`osmia_attribution_signal=true`؛ لا انحدار على DirectnessAssessorTest / OsmiaG305.

### 15–18. التسليمات / الجهد / Definition of Done / الجاهزية
تقرير أدلة متعددة اللغات؛ ~2–4 أيام هندسية؛ DoD = EN+FR (+لغة واحدة إضافية) PASS على نفس عقد OSMIA؛ الجاهزية: **READY** بعد دمج OSMIA الحالي.
