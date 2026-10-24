<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 14987). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 6:22 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 14988; it is archived once. -->

---
# WSA-Enterprise — Phase 1
## Strictly Authorized Execution: ADR-023 §8.33 + B-1 Only
### 1. التفويض وحدوده
أفوّضك بتنفيذ المهمة التالية حصريًا:
1. إعادة فحص أحدث تقرير Phase 1 وسجلات القرارات المعمارية الأصلية المعتمدة.
2. تعديل `docs/architecture/ADR-023-scientific-source-expansion.md` بإضافة §8.33 فقط، لتسجيل القرارات التي اعتمدها مالك المشروع، وتوثيق Gate A وGate B الخاصتين بـ B-1.
3. تنفيذ B-1 فقط: إنشاء عقد المجال `ProjectionQueryConstraints` وأنواعه ومصنعه واختباراته المحددة.
4. تشغيل الاختبارات الآمنة والمسموح بها فقط، ثم تقديم تقرير عربي شامل قبل الالتزام.
لا يُسمح بإغلاق Phase 1 بالكامل، أو اعتبار P1-EXIT-01(c) مغلقًا تلقائيًا، أو البدء في B-2 أو Phase 2 أو Phase 3.
### 2. إجراءات الحماية الإلزامية
**قبل أي تعديل:**
- تحقق من مسار المستودع `G:\WSA-Enterprise` والفرع وHEAD الكامل وحالة Git.
- افحص الملفات المعدلة وغير المتتبعة والمتجاهلة والملفات المؤقتة وworktrees دون تعديلها.
- أنشئ جردًا مسبقًا قابلًا للمقارنة لحالة الملفات وبصماتها حيث يلزم.
- افحص ADR-023 وسجلات القرارات الأصلية، ولا تعتمد على الملخصات وحدها.
- تحقق من وجود كل ملف مستهدف قبل إنشاء أي ملف جديد.
- إذا اختلفت الحالة الفعلية عن التقرير السابق، فتوقف عن الكتابة وقدّم الفروق أولًا.
**الأوامر والعمليات المحظورة قطعًا:**
- `git add .` و`git add -A` وأي staging.
- `git commit` و`git push`.
- `reset` و`clean` و`restore` و`stash` و`checkout` و`switch` و`rebase` و`merge` و`cherry-pick` و`revert` و`amend` وأي إعادة كتابة للتاريخ.
- حذف أو نقل أو إعادة تسمية أو استبدال أي ملف خارج النطاق المصرح به.
- تعديل إعدادات البيئة أو الأسرار أو بيانات الاعتماد أو إعدادات FAOSTAT.
- تشغيل Docker أو Compose أو تغيير الحاويات أو الخدمات أو قواعد البيانات.
- أي اتصال شبكي أو تنزيل أو إرسال بيانات خارج المستودع.
- ترقية الحزم أو تعديل `composer.lock` أو ملفات التبعيات.
- تعديل CSQ/QUS أو `CanonicalScientificQuestion` أو `QueryUnderstandingService` أو `ScientificSearchQueryBuilder` أو المنسّق أو Boundary أو Selector أو Registry أو Source Adapters أو Composer أو CapVer.
لا تستخدم أمرًا قد يغير ملفات جانبية أثناء الفحص أو الاختبار. إذا تعذر إثبات سلامة الأمر، فلا تنفذه.
### 3. الملفات المسموح بتعديلها فقط
1. `docs/architecture/ADR-023-scientific-source-expansion.md` — إضافة §8.33 فقط.
2. `backend/app/Services/Agriculture/Research/Projection/ProjectionFacetConstraintState.php`
3. `backend/app/Services/Agriculture/Research/Projection/ProjectionFacetConstraint.php`
4. `backend/app/Services/Agriculture/Research/Projection/ProjectionQueryConstraints.php`
5. `backend/app/Services/Agriculture/Research/Projection/ProjectionQueryConstraintsFactory.php`
6. `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php`
إذا كان أي ملف موجودًا مسبقًا، فلا تستبدله؛ افحصه أولًا. إذا كان يحتوي على عمل سابق أو يتعارض مع النطاق، فتوقف واطلب قرارًا.
لا تنشئ ملفات مساعدة إضافية داخل المستودع، ولا تمسح أي ملف مؤقت موجود.
### 4. القرارات المعمارية الملزمة
**Builder / Projection**
- `ScientificSearchQueryBuilder` هو السلطة النهائية لبناء الاستعلام النصي، بما فيه التوسعات المعجمية.
- لا يبني `Projection` الاستعلام ولا يعيد كتابته.
- يسجل Projection قيود facets ومعلومات التمثيل والتوسعات بوصفها مخرجات للمحاسبة والإفصاح.
- لا تنشئ سلطتين نهائيتين للاستعلام نفسه.
**expanded_terms**
- تبقى سلطة إنتاج التوسعات لدى Builder وفق العقد المعماري المعتمد.
- يسجل Projection التوسعات ولا يتولى توليدها.
- لا تعدّل قواعد التوسيع القائمة ضمن B-1.
**تحويل حالات Capability**
- `VERIFIED` ← `CONSTRAINABLE`.
- `UNAVAILABLE` و`NOT_APPLICABLE` ← `NOT_CONSTRAINABLE`.
- `UNVERIFIED` و`MISSING` ← `UNKNOWN`.
- `PARTIAL` ← `UNKNOWN`.
إذا استُخدم قيد `NOT_CONSTRAINABLE` كنص فقط، فلا تعرضه باعتباره مطبقًا بنيويًا. يجب أن يعبّر التصنيف عن `APPROXIMATED` مع توضيح السبب، وفق العقد المعتمد. لا تضف منطق تنفيذ الاستعلام لهذا السلوك في B-1.
**الحدود المعمارية**
- لا تغيّر D-04 أو D-10.9.2 أو أي قرار معتمد آخر.
- لا تنشئ مخطط قاعدة بيانات أو migration.
- لا تكتب Capability ولا تعدّل CapVer.
- لا تنشئ `SAME_AS` ولا تفعّل مصادر.
- لا تعتبر هذه الموافقة إغلاقًا لبقية متطلبات Phase 1 أو تفويضًا لـ B-2.
إذا ظهر تعارض حقيقي بين السجلات الأصلية، فلا تخترع تسوية؛ أوقف الجزء المتعارض وحدد القرار المطلوب بدقة.
### 5. عقد B-1 المطلوب
نفّذ عقد مجال مستقلًا فقط، دون ربطه بمسار التشغيل.
الحقول المقترحة:
- `schema_version`: القيمة الثابتة `projection_query_constraints.v1`.
- `canonical_query_id`: قيمة غير فارغة من CSQ، دون اختراع معرف.
- `adr_id`: قيمة غير فارغة من سجل العضوية المعتمد، دون تخمين.
- `canonical_identity_id`: nullable، ولا تُستنتج قيمته.
- `capability_decision_id` و`path_decision_id` و`path_id`: تحقق من الاتساق وفق العقد المعتمد.
- `facet_constraints`: قائمة مرتبة وفريدة تشمل `facet_code` و`identity_critical` و`capability_state` و`constraint`.
- `retrieval_method` و`warnings`: استخدم الأنواع المعتمدة المتاحة.
لا تضف `source_query` أو `expanded_terms` أو `fidelity_class` أو أسماء الحقول الأصلية للمصادر إلى هذا العقد.
لا تخترع آلية لاستخراج facets من CSQ. يجب أن تكون القيم مدخلات صريحة في B-1 إلى أن يُعتمد عقد استخراجها في النطاق المناسب.
إذا تعذر تحديد نوع حقل أو مصدره أو قواعد التحقق دون اختراع دلالة جديدة، فتوقف عند هذه النقطة وقدّم قرارًا محددًا.
### 6. متطلبات الاختبارات
أنشئ اختبارات مركزة تشمل:
1. جميع تحويلات حالات Capability، بما فيها `PARTIAL` و`MISSING`.
2. اشتقاق `identity_critical` من المصدر المعتمد، لا من قيمة يختارها المستدعي.
3. رفض القيم الفارغة والرموز المكررة.
4. التحقق من اتساق قرارات Capability وPath.
5. ثبات ترتيب التسلسل وحتميته.
6. ثبات صيغة `snake_case` وتوافق `toArray()` مع `assertProjectionPayloadClean`.
7. عدم الكتابة إلى Capability أو أي repository.
8. عدم تغيير العقود الحالية لحزم Projection وCapability وPath وIdentity وCoexistence وDisclosure.
اختبر النتائج الفعلية وشروط الرفض، ولا تكتفِ باختبار عدم وقوع استثناء.
### 7. قواعد الاختبار والأمان التشغيلي
- لا تشغّل Docker أو Compose أو PostgreSQL أو خدمات مشتركة.
- لا تغيّر `.env` أو `phpunit.xml` أو إعدادات FAOSTAT.
- لا تستخدم الشبكة.
- استخدم فقط وسيلة اختبار محلية معزولة موجودة مسبقًا ومصرحًا بها، بعد إثبات أنها لا تعدّل الملفات المحمية أو البيئة المشتركة.
- إذا تطلب الاختبار Docker أو تعديل البيئة أو تغيير التبعيات، فلا تشغّله؛ سجله `NOT RUN` مع السبب.
- لا تخفِ رموز الخروج خلف `tail` أو pipe يخفي الفشل.
- افصل فشل البيئة عن فشل الكود، ولا تغيّر الإعدادات لإجبار الاختبارات على النجاح.
- لا تدّعِ نجاح اختبار لم
