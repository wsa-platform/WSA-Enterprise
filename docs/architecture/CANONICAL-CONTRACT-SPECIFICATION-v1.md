# WSA-Enterprise — Canonical Contract Specification v1

> الحالة: **DRAFT — للمراجعة والاعتماد**
>
> هذا المستند يحول القرارات المعمارية المعتمدة R1–R7 إلى عقود تشغيلية قابلة للمراجعة قبل أي تنفيذ للكود.
> لا يُعد هذا المستند تفويضًا لبدء التنفيذ.

## 1. نطاق المستند
1. Tenant / Public Organization Context
2. Language
3. Home Research API
4. Crop Farming Needs API
5. Home/Crop Shared Research Foundation
6. Evidence Semantics
7. KnowledgeQueryPlan Serialization
8. Research Auto-Save / Library
9. AI Positive Feedback
10. FAOSTAT integration terminology

## 2. Tenant / Public Organization Contract
البحث العام متاح دون أن يختار المستخدم Organization.
العميل العام لا يحدد Organization لعمليات الكتابة.
إذا احتاجت العملية إلى tenant context داخلي، يحدده الخادم.
لا تعتمد عملية الكتابة العامة على organization_id أو organization slug وارد من العميل باعتباره مصدر الحقيقة.
يجب ألا يستطيع مستخدم عام توجيه كتابة بحث أو LibraryItem إلى Organization يختارها من الطلب.

## 3. Language Contract
المنصة تدعم ar وen وfr وtr.
لغة واجهة المستخدم مستقلة عن لغة الإجابة، والواجهة الافتراضية هي لغة جهاز المستخدم مع إمكانية تغييرها.
لغة السؤال مفهوم مستقل عن UI locale.
لغة الإجابة = لغة سؤال المستخدم.
لا يجوز أن يؤدي UI locale أو Platform locale إلى استبدال لغة الإجابة.
لغة المصدر العلمي مستقلة عن لغة السؤال والإجابة والواجهة.
عند الحفظ: يُحفظ الملف الأصلي نفسه دون ترجمة أو إعادة صياغة أو تغيير لغة أو تعديل للمحتوى.

## 4. Home Research API Contract
Home يسمح بسؤال بحث علمي حر.
Endpoint: POST /api/v1/public/research-agent/query
السؤال الحر هو مدخل البحث الأساسي.
UI locale لا يحدد answer language.
public tenant لا يعتمد على Organization يختارها العميل.
يجب أن تعكس الاستجابة الإجابة، وحالة التحقق/الأهلية ذات الصلة، والأدلة والمصادر، والقيود عند عدم كفاية الأدلة، وحقول evidence semantics المنفصلة.

## 5. Crop Farming Needs API Contract
مسار Crop لا يستخدم سؤالًا حرًا.
التدفق: اختيار Crop → عرض أسئلة محددة مسبقًا → اختيار السؤال → البحث العلمي والتحقق.
Endpoint: GET /api/v1/public/field-crops/farming-needs-profile
يمكن لـHome وCrop مشاركة البحث العلمي وجمع الأدلة وتقييم الأدلة والتحقق والحفظ التلقائي والمصدر الأصلي والروابط.
لا يجب افتراض تطابق واجهة الطلب أو شكل الاستجابة في المسارين لمجرد اشتراكهما في الأساس الداخلي.

## 6. Evidence Semantics Contract
يجب فصل ثلاثة محاور: directness وclaim_relation وdisposition.
directness يصف علاقة الدليل المباشرة بالموضوع/الادعاء وفق vocabulary المعتمد لاحقًا في التنفيذ.
claim_relation يصف علاقة الدليل بالادعاء ولا يستخدم كبديل لـ directness.
disposition يصف حالة/مصير الدليل أو دورة البحث ذات الصلة ولا يستخدم كبديل للمحورين الآخرين.
لا يجوز استخدام supported كحالة عامة تجمع معاني مختلفة من المحاور الثلاثة.

## 7. Evidence Verification and Auto-Save Contract
تقييم الأدلة هو صاحب قرار كفاية التحقق وأهلية الحفظ التلقائي.
Composer مسؤول عن صياغة الإجابة وليس وحده صاحب قرار الحفظ.
عند تحقق شروط التوثيق: تُعرض الإجابة، وتُعرض مصادرها وروابطها القابلة للنقر، ويُحفظ البحث تلقائيًا.
عند عدم تحقق شروط التوثيق: تُعرض الإجابة مع القيود المناسبة ولا يتم الحفظ التلقائي.
لا يوجد قرار حفظ يدوي مطلوب من المستخدم.

## 8. Library Storage Contract
يتم حفظ ملف البحث الذي تم تنزيله، وليس الرابط فقط.
يبقى الملف بالمحتوى واللغة الأصليين.
يُنظم تحت: Crop → Scientific Research / الأبحاث العلمية.
إذا لم يوجد مجلد Crop، يتم إنشاؤه عند الحاجة.
روابط المصادر المعروضة للمستخدم تفتح المصدر الأصلي.

## 9. KnowledgeQueryPlan Serialization Contract
يجب ألا يؤدي التحويل إلى array/DTO إلى إسقاط contextInput.
أي حقل دلالي موجود في KnowledgeQueryPlan ويُستخدم في مراحل البحث يجب أن يبقى محفوظًا عبر سلسلة QUS → Plan → Search → Validate → Compose.
يجب التحقق من عدم وجود silent semantic drop بين هذه المراحل.

## 10. Positive AI Feedback Contract
يتم تسجيل Feedback الإيجابي فقط.
لا يتم تسجيل Feedback سلبي.
يمكن سؤال المستخدم بعد نتيجة البحث: هل كانت الإجابة مفيدة لك؟ 👍 نعم.
عند اختيار الإيجابي، يسجل النظام الإشارة المرتبطة بالنتيجة.
يُستخدم Feedback الإيجابي لتحليل ما نجح في الإجابة والبحث والمصادر والأنماط ذات النتائج الجيدة والأساليب التي ينبغي المحافظة عليها.
Feedback ليس حقيقة علمية، ولا يجوز أن يؤدي وحده إلى تعديل فوري وغير منضبط في المعرفة أو سلوك النظام.
دورة التحسين: جمع البيانات → التحليل → اكتشاف الأنماط → اقتراح التحسين → الاختبار → الاعتماد المقصود → التطبيق.

## 11. FAOSTAT Contract
Runtime contract المرجعي هو FAOSTAT Developer Portal.
المصطلحات canonical هي Area وElement وItem وYear.
FENIX يُذكر فقط باعتباره تاريخًا/عقدًا قديمًا عند الحاجة إلى التوثيق.
لا يُعاد إدخال FENIX كعقد runtime لمجرد وجود وثائق قديمة تشير إليه.
اختبارات FAOSTAT المستقبلية يجب أن تستهدف Developer Portal contract.

## 12. Error Contract Direction
الاتجاه المعماري هو تقليل الاختلافات غير الضرورية بين Home وCrop مع الحفاظ على خصوصية كل domain.
الهدف المشترك للخطأ: error.code وerror.http_status وerror.message وerror.details (اختياري).
أي انتقال يجب أن يكون additive أو transitional عندما يكون ذلك ممكنًا، دون كسر WIP غير المرتبط.

## 13. Non-Goals
هذا الإصدار لا يبدأ Phase 3، ولا يطلب حذف أو إعادة إنشاء WIP، ولا يغير Git history، ولا يستخدم force push.
لا يفرض إعادة تصميم شامل للواجهات.
لا يدمج Home وCrop في endpoint واحد دون قرار إضافي.
لا يجعل Composer صاحب القرار الوحيد في أهلية الحفظ.
لا يسجل Feedback سلبي.

## 14. Required Review Before Implementation
1. vocabulary النهائي لـ directness.
2. vocabulary النهائي لـ claim_relation.
3. vocabulary النهائي لـ disposition.
4. شكل Home success response النهائي.
5. شكل Crop success response النهائي.
6. mapping النهائي للأخطاء المشتركة.
7. حقول API الدقيقة الخاصة بـ positive feedback.
8. تفاصيل tenant resolver/config التي ستطبق على HEAD وWIP.
9. مصفوفة اختبارات اللغة.
10. مصفوفة اختبارات عزل tenant.
11. اختبارات عدم إسقاط contextInput.
12. اختبارات حفظ الملف الأصلي دون تعديل.

## 15. Status
- R1–R7: Approved
- Phase 1: Complete
- Phase 2 Forensic Audit: Complete
- Phase 2 Contract Remediation Design: Complete
- Canonical Contract Specification v1: DRAFT — Awaiting Review
- Code implementation: Not started
- Phase 3: Not started