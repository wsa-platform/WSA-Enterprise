# القرارات المعمارية لمدير المنصه

**الحالة:** APPROVED
**النظام:** WSA Enterprise
**النطاق:** Platform Administration / Admin Program

## 1. استقلال برنامج المدير
برنامج مدير المنصة (Admin Program) نظام مستقل عن واجهة منصة WSA.
- له مشروع وواجهة ومسارات مستقلة.
- لا يُدمج داخل واجهة المستخدم العامة للمنصة.
- يمكن تطويره ونشره وتحديثه بشكل مستقل.
- يتصل بمنصة WSA عبر Admin APIs مؤمنة.
- يمكن أن توجد له Clients مستقلة مثل Web/Desktop/Mobile تتصل بنفس طبقة Admin API.

## 2. هوية Platform Administrator
Platform Administrator هو هوية إدارية على مستوى المنصة، مستقلة عن Organization Roles.
لا يجوز اعتبار صلاحية `*` داخل Organization دليلًا على أن المستخدم Platform Administrator.
- Organization Admin: صلاحيات كاملة داخل Organization الخاصة به.
- Platform Administrator: صلاحيات إدارية على مستوى منصة WSA بالكامل.

## 3. Organization System
نظام Organizations الحالي محفوظ ولا تتم إعادة تصميمه.
تبقى أدوار المؤسسة: Owner, Admin, Manager, Member, Viewer.
ويظل Organization Admin مسؤولًا عن الإدارة الكاملة داخل مؤسسته فقط.
لا يتم حذف Organizations أو تغيير نموذج العضوية أو تحويل Organization إلى Publisher.

## 4. نطاق Platform Administrator
Platform Administrator يملك الإدارة العامة لمنصة WSA، بما في ذلك المستخدمون، Organizations، أدوار وصلاحيات المنصة، إعدادات المنصة، الخدمات والوحدات، Monitoring، Reports/Analytics، Audit، Notifications، Communications، Jobs/Recruitment، Marketplace، وLibrary.
يشمل ذلك الاطلاع الإداري الكامل والوظائف الإدارية والنشر/الإدارة حيث تسمح العقود الحالية بذلك.

## 5. Jobs / Recruitment
لا تتم إعادة تصميم نظام Jobs / Recruitment. يحصل برنامج المدير على طبقة إدارية مستقلة للوصول والإدارة مع الحفاظ على العقود وقواعد العمل والبنية الحالية.

## 6. Marketplace
لا تتم إعادة تصميم Marketplace. Platform Administrator يحصل على وصول إداري كامل وفق الطبقة الإدارية، بما في ذلك الاطلاع والإدارة والنشر حيث تسمح العقود الحالية.

## 7. Library
عقد Library المعتمد يبقى كما هو ولا تتم إعادة تصميمه.
- الوصول إلى صفحة Library يتطلب Authentication فقط.
- أي مستخدم موثق يستطيع الاطلاع على كامل Library.
- لا يوجد قيد حسب Organization أو Owner أو Supervisor أو Uploader أو Creator أو Publisher أو Company أو Department أو Research Type أو Item Type.
- Organization ليست Publisher.
- لا يوجد نشر يدوي داخل Library بواسطة مؤسسة أو مستخدم.
- نتائج البحث العلمي الموثقة يتم حفظها تلقائيًا في Library داخل المجلد المخصص لها.
Platform Administrator يحصل على طبقة إدارية كاملة فوق Library، لكن ذلك لا يغيّر عقد Library الأساسي.

## 8. Admin Program وAdmin API
Admin Program هو تطبيق مستقل يتصل بالمنصة عبر Admin APIs مؤمنة. Authentication وAuthorization يتمان على الخادم. إخفاء وحدة من الواجهة ليس حماية أمنية. Admin Client لا يقرر بنفسه أن المستخدم Platform Administrator.

## 9. Platform RBAC
سيتم بناء Platform RBAC مستقل عن Organization RBAC.
لا تستخدم صلاحيات Organization مثل `*` لإثبات Platform Administrator.
المسار المقصود: Admin Authentication → Platform Administrator Identity → Platform RBAC → Admin API Authorization → WSA Platform.

## 10. واجهة Admin Program
يمكن أن تشمل Dashboard، Users، Organizations، Communications، Jobs/Recruitment، Marketplace، Library، Reports، Settings، Roles/Permissions، Audit، Monitoring، Marketing، AI، Notifications.
ظهور الوحدة في الواجهة ليس Authorization؛ Backend هو مصدر الحقيقة النهائي.

## 11. ما لا يجوز تغييره
- حذف Organizations.
- إعادة تصميم Organization model.
- تغيير نظام عضوية Organizations دون قرار مستقل.
- تحويل Organization إلى Publisher.
- إعادة تصميم Library أو تغيير عقد Library Page.
- إعادة تصميم Marketplace.
- إعادة تصميم Jobs / Recruitment.
- تغيير قواعد أعمال هذه الأنظمة دون قرار معماري مستقل.

## 12. الهدف المعماري النهائي
Organization Administration = نطاق المؤسسة.
Platform Administration = نطاق المنصة بالكامل.
Admin Program = تطبيق مستقل يتصل بالمنصة لإدارة هذا النطاق.

## 13. الحالة
هذا المستند يجمع القرارات المعمارية المعتمدة الخاصة بمدير المنصة حتى هذه النقطة. أي تغيير لاحق يحتاج إلى قرار معماري مستقل قبل التنفيذ.