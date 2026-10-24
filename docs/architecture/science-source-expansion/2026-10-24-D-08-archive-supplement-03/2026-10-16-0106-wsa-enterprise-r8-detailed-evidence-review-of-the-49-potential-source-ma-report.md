<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 16328). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# تقرير مراجعة أدلة المطابقات المحتملة (R8) — انتظار قرار المالك

وحدة العد هنا **علاقة ثنائية** (`legacy_62:master_id` ↔ `membership_109:adr_id`)، وليست «49 مصدراً مستقلاً».

**المستخرج فعلياً من بيان التسوية:** 49 علاقة، و49 معرّف إرث فريد، و49 مقعد فريد، **بلا تداخل** (لا master_id يشير لأكثر من مقعد، ولا مقعد يستقبل أكثر من master_id ضمن هذه المجموعة). العدد يطابق تقرير R8 تماماً؛ رقم «46» الذي ظهر في استكشاف سابق كان عداً وسيطاً لأسماء مطبّعة تحت ترميز مختلف، وليس عدد العلاقات في البيان.

**لم يُنفَّذ أي دمج.** الملفات المحمية (62، 109، البيان، السجل الكانوني) بلا تغيير. أُنشئ ملف مراجعة منفصل فقط:
`docs/architecture/science-source-expansion/2026-10-16-R8-POSSIBLE-MATCH-EVIDENCE-REVIEW.v1.json`  
والجدول التفاعلي في Canvas: `r8-possible-match-review`.

---

## حدّ الدليل (مهم)

| نوع الدليل | المعنى |
|---|---|
| **مباشر** | مرشح مقعد الـ109 في مراجعة IC: URL رسمي، ISSN، ناشر، وثائق API |
| **استنتاج** | الربط بسجل الـ62 عبر تطابق الاسم المطبع + استمرارية السجل التاريخي |

تشابه الاسم وحده لا يكفي لـ`CONFIRMED_SAME_SOURCE`. حتى توصيات «الدمج الموصى» **تتطلب موافقتك الصريحة** قبل أي `historical_62_xref`.

---

## ملخص التصنيفات

| التصنيف | العدد | وحدة العد |
|---|---|---|
| `APPROVE_SAME_SOURCE_RECOMMENDED` | 44 | علاقات |
| `OWNER_DECISION_REQUIRED` | 3 | علاقات |
| `CONFLICTING_EVIDENCE` | 1 | علاقات |
| `INSUFFICIENT_EVIDENCE` | 1 | علاقات |
| `CONFIRM_DISTINCT_RECOMMENDED` | 0 | علاقات |
| **المجموع** | **49** | علاقات |
| مرشح URL موجود | 49/49 | مقاعد 109 |
| جُلبت الصفحة في مراجعة IC | 41/49 | مقاعد 109 |

---

## الحالات ذات الأولوية (قرار مالك / منع دمج)

### PM-02 — `#3 AgroSpace` ↔ `G1-04`
- **تصنيف:** `CONFLICTING_EVIDENCE`
- **مباشر:** مرشح بلغراد `http://aspace.agrif.bg.ac.rs/` + مرساة كلية الزراعة، مع فشل جلب 403
- **تعارض:** بدائل بنفس الاسم (منصة تشيلي agrospace.cl؛ خدمات آسيا الوسطى على بوابة FAO) في سجل IC
- **توصية:** لا دمج حتى تحدد أي كيان هو المقصود
- **CURRENT:** لا تغيير

### PM-08 — `#12 European Poultry Science` ↔ `G2-03`
- **تصنيف:** `OWNER_DECISION_REQUIRED`
- **مباشر:** `https://www.european-poultry-science.com/` (جُلب) + Elsevier/ScienceDirect + DOI `10.1016/j.eups`
- **غموض:** حقبة ما قبل ScienceDirect غير موثّقة في IC
- **توصية:** حسم مظهر الناشر/الحقبة قبل تثبيت alias تاريخي
- **CURRENT:** لا تغيير

### PM-22 — `#29 Aquaculture Journal` ↔ `G2-20`
- **تصنيف:** `INSUFFICIENT_EVIDENCE`
- **محاولة:** `https://www.mdpi.com/journal/aquacj` — 403، بلا مراسٍ مثبتة
- **توصية:** يُمنع الدمج
- **CURRENT:** لا تغيير

### PM-26 — `#33 IDEAL Project` ↔ `G2-24`
- **تصنيف:** `OWNER_DECISION_REQUIRED`
- **مباشر:** لقطة DataShare DOI `10.7488/ds/2768`؛ التطبيق الحي `http://data.ctlgh.org/ideal/` غير متاح
- **توصية:** هل الهوية هي قاعدة IDEAL الحية أم لقطة البيانات المستقرة؟
- **CURRENT:** لا تغيير

### PM-27 — `#35 APIS` ↔ `G3-01`
- **تصنيف:** `OWNER_DECISION_REQUIRED`
- **مباشر:** مجلة مجرية ISSN `3058-0382` على `https://ojs.mtak.hu/index.php/Apis`
- **قيود ADR/62:** `#35` هوية خاصة ≠ `agrisignal`؛ ADR يبقي `#35` دليلاً تاريخياً دون إعادة كتابة؛ بديل نشرة فلوريدا APIS في IC
- **توصية:** قرار مالك صريح قبل أي xref
- **CURRENT:** لا تغيير

---

## فهرس التوصيات لكل العلاقات الـ49

| الحالة | 62 ↔ 109 | التوصية | رابط المرشح الرسمي (IC) |
|---|---|---|---|
| PM-01 | #2 ↔ G1-02 Organic Eprints | APPROVE… | https://www.orgprints.org/ |
| PM-02 | #3 ↔ G1-04 AgroSpace | CONFLICTING | http://aspace.agrif.bg.ac.rs/ |
| PM-03 | #4 ↔ G1-03 | APPROVE… | https://journals.tubitak.gov.tr/agriculture/ |
| PM-04 | #5 ↔ G1-11 | APPROVE… | researcherslinks…/Sarhad-Journal-of-Agriculture… |
| PM-05 | #9 ↔ G1-10 JISHS/eJHS | APPROVE… | https://www.cabidigitallibrary.org/journal/ejhs |
| PM-06 | #10 ↔ G2-01 Poultry Science | APPROVE… | https://www.sciencedirect.com/journal/poultry-science |
| PM-07 | #11 ↔ G2-02 | APPROVE… | …/journal-of-applied-poultry-research |
| PM-08 | #12 ↔ G2-03 European Poultry Science | OWNER | https://www.european-poultry-science.com/ |
| PM-09 | #13 ↔ G2-04 | APPROVE… | https://facta.org.br/rbca-bjps/ |
| PM-10 | #14 ↔ G2-05 | APPROVE… | https://www.jstage.jst.go.jp/browse/jpsa/-char/en |
| PM-11–PM-21 | (مجلات/قواعد ماشية وأحياء مائية) | APPROVE… | روابط ScienceDirect / DergiPark / CIRAD / إلخ في JSON |
| PM-19 | #26 ↔ G2-17 REMVT | APPROVE… | https://revues.cirad.fr/index.php/REMVT/about (+ تثبيت D-09) |
| PM-22 | #29 ↔ G2-20 | INSUFFICIENT | https://www.mdpi.com/journal/aquacj |
| PM-23–PM-25 | LIVEMAP / LivestockDev / … | APPROVE… | روابط IC |
| PM-26 | #33 ↔ G2-24 IDEAL | OWNER | http://data.ctlgh.org/ideal/ + DataShare DOI |
| PM-27 | #35 ↔ G3-01 APIS | OWNER | https://ojs.mtak.hu/index.php/Apis |
| PM-28–PM-43 | (نحل / حشرات) | APPROVE… | USDA-ARS، Frontiers، Springer، ISSN لـEUNK، إلخ |
| PM-44 | #56 ↔ G5-04 GBIF | APPROVE… | https://www.gbif.org + https://techdocs.gbif.org/en/openapi/ |
| PM-45–PM-49 | (أمراض نبات / نبات) | APPROVE… | APS، BMC، Springer، EKB |

التفاصيل السبعة عشر حقلاً لكل حالة (الأثر على الكانوني، CURRENT، العوائق، المراسي) موجودة في ملف المراجعة JSON وفي Canvas.

---

## أثر أي موافقة لاحقة (إن أعطيتها)

- **على الكانوني:** إضافة `historical_identifiers: legacy_62:N` للمقعد المعني فقط بعد قرارك  
- **على CURRENT:** تبقى 109 ما لم تقرر خلاف ذلك صراحةً  
- **على الترخيص/التفعيل:** لا يترتب تفعيل ولا قبول ترخيص من التوصية وحدها

---

## ما أحتاجه منك الآن

قرار صريح لكل حالة (أو لمجموعات)، مثلاً:

1. أوافق على دمج الهويات الموصى بها ما عدا PM-02 / PM-08 / PM-22 / PM-26 / PM-27  
2. أو قائمة معرّفات `PM-xx` للموافقة / الرفض / التأجيل  

** fortظر قرارك قبل أي تنفيذ.
