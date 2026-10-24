<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18276). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 8:01 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18277; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION PROMPT v8.2
## Round 2 — Safe Closure of Layers A, C, E, F, G
### Source-Verified Archive · ADR Integrity · Isolated Tests · Protected Work · No Commit

**الإصدار:** v8.2  
**التاريخ:** 2026-10-23  
**المستودع:** `wsa-platform/WSA-Enterprise`  
**المسار المحلي:** `G:\WSA-Enterprise`  
**الفرع المتوقع:** `phase-18-m18-ai-marketing-communications`  
**HEAD المتوقع:** `1e4d7a62a1054b82e4eec655bd6dcd630e837cf6`  
**Upstream المتوقع وفق آخر تقرير:** `1e4d7a62a1054b82e4eec655bd6dcd630e837cf6`  
**القرار المعماري الأساسي:** `docs/architecture/ADR-023-scientific-source-expansion.md`

---

# 0. المهمة والسلطة التنفيذية

تصرّف كمهندس برمجيات أول، ومراجع معماري، ومدقق أدلة مستقل. المطلوب تنفيذ الأعمال الآمنة المصرح بها فعليًا، لا الاكتفاء بخطة، مع توثيق النتائج الحقيقية.

لا تختلق نتائج، ولا تفترض أن ملفات أو اختبارات أو قرارات معمارية موجودة دون فحصها. لا تصف أي اختبار بأنه ناجح ما لم يُنفذ فعلًا وتُسجل نتيجته.

## 0.1 نطاق الجولة الثانية

الطبقات المسموح بها:

- **Layer A:** D-08 Archive and Provenance.
- **Layer C:** ADR-023 Append-Only Integrity.
- **Layer E:** Knowledge Persistence.
- **Layer F:** IC Persistence.
- **Layer G:** Mission Artifact Archive.
- إنشاء تقرير نهائي ونقطة توقف موثوقة.

غير مسموح:

- Layer H أو أعمال CapVer التنفيذية.
- بدء Round 3 تلقائيًا.
- أي `git add` أو staging أو commit أو push.
- أي حذف أو استبدال لملفات العمل السابق.
- أي تغيير في قواعد البيانات التشغيلية المحمية.
- أي تعديل تلقائي على الأقسام المعمارية المحمية.
- أي تعديل خارج النطاق لمجرد إصلاح فشل أو إكمال معيار قبول.

**الحالة النهائية يجب أن تعكس الأدلة الفعلية:** `ROUND_2_READY` أو `PARTIAL` أو `BLOCKED`.

## 0.2 التقديرات الزمنية

هذه تقديرات للتخطيط وليست مواعيد إلزامية. قد يتجاوز التنفيذ المدة إذا تعذر الوصول إلى المصادر الأصلية أو ظهرت مخاطر عزل.

| المهمة | تقدير تقريبي |
|---|---:|
| Baseline | 5–15 دقيقة |
| Layer A | 45–120 دقيقة أو أكثر |
| Layer C | 15–45 دقيقة |
| Layer E | 45–120 دقيقة |
| Layer F | 30–90 دقيقة |
| Layer G | 45–90 دقيقة |
| Checkpoint والتقرير | 20–40 دقيقة |

لا تتجاوز أي بوابة سلامة لتوفير الوقت. انتهاء المدة لا يعني إغلاق الطبقة.

---

# 1. المبادئ المعمارية وقواعد حماية العمل السابق

احترم القرارات المعمارية المعتمدة وسياق المشروع السابق.

يجب الحفاظ على:

- الهوية العلمية للسؤال CSQ وعدم تحويله إلى بحث بالكلمات المفتاحية فقط.
- العقود المعمارية ومصدرية الأدلة.
- الفصل بين العضوية والاكتشاف والتحقق من endpoint والتكامل والجاهزية الإنتاجية.
- حفظ الملفات الأصلية دون تغيير بايتاتها.
- سجل العضوية الحالي `v2` والمصفوفات التاريخية.
- جميع التغييرات المتتبعة وغير المتتبعة التي كانت موجودة قبل الجولة.
- التمييز بين الاختبار المحلي والاعتماد المعماري والجاهزية الإنتاجية.

لا تستنتج أن قرارًا معماريًا غير موجود لمجرد غياب نسخة محلية من ملف. استخدم السياق المتاح، ووثّق أي تعارض غير محسوم.

## 1.1 أوامر Git المحظورة

لا تستخدم:

- `git reset --hard`
- `git clean`
- `git stash`
- `git checkout --`
- `git restore` لإسقاط التعديلات
- `git add .`
- `git add -A`
- `git commit -a`
- `git commit --amend`
- `git push --force`
- أي إعادة كتابة للتاريخ
- أي أمر يحذف أو يستبدل العمل السابق

لا تنفذ `git add` لأي ملف خلال هذه الجولة.

---

# 2. المرحلة صفر — Baseline Gate

ابدأ بالفحص للقراءة فقط.

```powershell
Set-Location G:\WSA-Enterprise

git rev-parse --show-toplevel
git branch --show-current
git rev-parse HEAD
git rev-parse origin/phase-18-m18-ai-marketing-communications
git status --porcelain=v1
git diff --stat
git diff --check
git ls-files --others --exclude-standard
```

سجّل المخرجات الفعلية ووقت الفحص.

## 2.1 بوابة HEAD

يجب أن يطابق HEAD:

`1e4d7a62a1054b82e4eec655bd6dcd630e837cf6`

إذا اختلف:

1. توقف قبل أي تعديل.
2. سجّل القيمة الفعلية والمتوقعة.
3. لا تغيّر HEAD ولا تحاول إصلاحه.
4. لا تنفذ reset أو checkout أو rebase أو cherry-pick.
5. أبلغ عن الاختلاف وانتظر قرارًا صريحًا.

## 2.2 بوابة الفرع

يجب أن يكون الفرع:

`phase-18-m18-ai-marketing-communications`

إذا اختلف، توقف قبل التعديل.

## 2.3 بوابة Upstream

قارن HEAD المحلي بالمرجع البعيد الفعلي.

إذا اختلفا:

- توقف عن أي تعديل.
- لا تكتفِ بطباعة تحذير.
- لا تتابع تنفيذ الطبقات.
- لا تسحب أو تدفع أو تدمج أو تعيد كتابة التاريخ.
- وثّق القيمتين والاختلاف الظاهر بينهما.

**يجب أن يكون التوقف فعليًا في تدفق التنفيذ، لا مجرد تعليق أو رسالة.**

## 2.4 جرد العمل السابق

آخر تقرير تاريخي ذكر:

- 51 ملفًا متتبعًا معدلًا.
- 116 سطرًا في porcelain للملفات غير المتتبعة.
- 1,443 ملفًا غير متتبع بعد توسيع الجرد.
- فشل `git diff --check` بسبب whitespace في:
  `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md:357`

هذه أرقام تاريخية وليست قيمًا حالية مؤكدة. أعد القياس.

لا تصلح الملاحظة في ADR-021 ضمن هذه الجولة، ولا تنسبها إلى تغييرات الجولة الحالية دون دليل.

لا تخلط بين عدد أسطر porcelain وعدد الملفات الفعلي.

---

# 3. حماية قواعد البيانات والآثار الجانبية

قواعد البيانات المحمية تشمل، دون حصر:

- `scientific_record_store.sqlite`
- `capver.sqlite`
- أي قواعد تشغيلية أو فهارس دائمة تستخدمها خدمات المشروع.

البصمات التاريخية المسجلة:

- `scientific_record_store.sqlite`:
  `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`
- `capver.sqlite`:
  `66CA7C1497BA84E3FF9ECCBF01D6A5E2BC193571469EEAED1A5D10B19C00B875`

هذه القيم مراجع تاريخية وليست تحققًا جديدًا.

## 3.1 القواعد الإلزامية

1. لا تفتح قاعدة تشغيلية إذا لم يكن ذلك ضروريًا.
2. افحص إعدادات الاتصال والتهيئة ومسارات التخزين قبل تشغيل أي اختبار.
3. لا تفترض أن اختبار Unit معزول لمجرد اسمه.
4. لا تفترض أن استخدام SQLite يعني استخدام قاعدة مؤقتة.
5. تحقق من أن الخدمات لا تفتح اتصالًا بديلًا أو تكتب إلى تخزين مشترك.
6. لا تشغّل اختبارات harvesting أو LocalIndex أو retrieval أو migrations على قواعد تشغيلية.
7. لا تكتب إلى أي قاعدة محمية بغرض الاختبار.
8. إذا تعذر إثبات العزل، لا تشغّل الاختبار؛ سجّل `BLOCKED` أو `PARTIAL`.
9. إذا تغيرت بصمة قاعدة محمية دون تفسير، أوقف العمليات التي قد تؤثر فيها فورًا.

ثبات الحجم ووقت التعديل وحدهما لا يثبت ثبات محتوى قاعدة البيانات.

---

# 4. Layer A — D-08 Archive and Provenance

## 4.1 الهدف والجرد المرجعي

الهدف هو التحقق من سلامة الأرشيف ومصدره، وليس مجرد عد الملفات أو مطابقة الأسماء.

الأعداد التاريخية المبلغ عنها:

| المجموعة | الملفات | صفوف البيان |
|---|---:|---:|
| الأرشيف الأساسي | 436 | 218 |
| الملحق 01 | 95 | 94 |
| الملحق 02 | 12 | 11 |
| الإجمالي | 543 | 323 |

أعد الجرد الفعلي. لا تفترض أن الأعداد الحالية مطابقة.

الاستثناءات الخمسة التاريخية المذكورة:

- ثلاثة تقارير من 2026-10-09.
- تقرير تنفيذي نهائي من 2026-10-14.
- مطالبة من 2026-10-14.

تحقق من المسارات الفعلية والأسماء والمحتوى قبل اعتماد أي من هذه الأوصاف.

## 4.2 جرد الأرشيف وبياناته

لكل أرشيف على حدة:

1. حدّد جذر الأرشيف.
2. اكتشف ملف `MANIFEST.md` التابع لذلك الأرشيف.
3. اقرأ البيان وحدد صيغته الفعلية.
4. احسب عدد الملفات وصفوف البيان.
5. حدد الملفات المفقودة والزائدة والتكرارات.
6. استخدم المسارات النسبية الصحيحة داخل كل أرشيف.
7. لا تستخدم أول `MANIFEST.md` في المجلد الأساسي لجميع الأرشيفات.
8. لا تطابق ملفين يحملان الاسم نفسه دون التحقق من مساريهما.
9. احسب SHA-256 للملفات المطلوبة.
10. سجّل كل نتيجة على مستوى الملف الواحد.

الحقول المطلوبة في سجل الجرد:

- الأرشيف.
- المسار النسبي.
- اسم الملف.
- وجود الملف.
- الحجم الفعلي.
- البصمة المتوقعة.
- البصمة الفعلية.
- نتيجة مطابقة البيان.
- مرجع المصدر الأصلي.
- نتيجة مطابقة المصدر الأصلي.
- سبب الاستثناء.

## 4.2.1 أوامر PowerShell للجرد وحساب SHA-256

ابدأ باكتشاف المسارات الفعلية. لا تفترض أن مجلدًا معينًا هو الأرشيف الصحيح لمجرد أن اسمه متوقع.

```powershell
$ErrorActionPreference = "Stop"

$base = "G:\WSA-Enterprise\docs\architecture\science-source-expansion"

if (-not (Test-Path -LiteralPath $base -PathType Container)) {
    throw "Archive root not found: $base"
}

# Inventory directories.
Get-ChildItem -LiteralPath $base -Directory |
    ForEach-Object {
        $count = (
            Get-ChildItem -LiteralPath $_.FullName -File -Recurse
        ).Count

        [PSCustomObject]@{
            Directory = $_.FullName
            FileCount = $count
        }
    } |
    Format-Table -AutoSize

# Discover all manifests; do not assume only one exists.
$manifests = @(
    Get-ChildItem -LiteralPath $base -Recurse -File |
        Where-Object { $_.Name -eq "MANIFEST.md" }
)

if ($manifests.Count -eq 0) {
    Write-Host "NO_MANIFEST: no MANIFEST.md found under $base"
}

foreach ($manifest in $manifests) {
    Write-Host "`n=== MANIFEST: $($manifest.FullName) ==="
    Get-Content -LiteralPath $manifest.FullName -TotalCount 60
}

# Compute hashes for inventory. This does not prove equality
# with the original source.
$inventory = @(
    Get-ChildItem -LiteralPath $base -Recurse -File |
        ForEach-Object {
            $hash = Get-FileHash `
                -LiteralPath $_.FullName `
                -Algorithm SHA256

            [PSCustomObject]@{
                FullPath     = $_.FullName
                RelativePath = $_.FullName.Substring(
                    $base.Length
                ).TrimStart('\')
                SizeBytes    = $_.Length
                SHA256       = $hash.Hash
            }
        }
)

$inventory | Format-Table -AutoSize
```

### حدود هذه الأوامر

- الجرد يحصي الملفات الموجودة تحت جذر محدد.
- اكتشاف البيان لا يثبت صحته.
- حساب SHA-256 لا يثبت المطابقة مع المصدر الأصلي.
- لا تقارن البصمات قبل فهم صيغة البيان وربط كل سجل بالمسار الصحيح.
- لا تستخدم نتائج `Format-Table` بديلًا عن سجل تفصيلي إذا احتجت إلى حفظ كل الصفوف.
- إذا لزم تصدير CSV، اختر مسارًا خارجيًا موجودًا وآمنًا، وتحقق من أن اسم الملف جديد. لا تستبدل ملفًا سابقًا.
- لا تنشئ بيانًا جديدًا لتجاوز نقص في الأدلة.

## 4.2.2 مقارنة البصمات

بعد تحديد صيغة البيان، أنشئ مقارنة صريحة بين المسار المتوقع والبصمة المتوقعة والبصمة الفعلية.

لا تستخدم `Select-Object -First 1` لمطابقة الملفات عند وجود أسماء متكررة.

لا تقارن الأسماء وحدها.

إذا لم يكن البيان يحتوي على SHA-256، لا تستنتج البصمة المتوقعة من الملف نفسه ثم تعلن نجاح المطابقة؛ فهذا تحقق دائري لا يثبت سلامة المصدر.

## 4.3 التفريق بين مطابقة البيان ومطابقة المصدر الأصلي

هناك نوعان مستقلان من التحقق:

**أ. Manifest verification**

يثبت أن الملف يطابق البصمة المسجلة في البيان، إذا كان البيان يحتوي على بصمة موثوقة وصحيحة.

**ب. Original-source verification**

يتطلب الوصول إلى المصدر الأصلي أو نسخة موثوقة منه ومقارنة البايتات أو SHA-256.

لا تخلط بين النتيجتين.

إذا لم يكن المصدر الأصلي متاحًا، سجّل `NOT VERIFIED`.

## 4.3.1 حالة غياب MANIFEST أو SHA-256

إذا لم يوجد `MANIFEST.md`:

- سجّل `NO_MANIFEST`.
- صنّف الحالة على أنها فجوة توثيق.
- أجرِ جردًا بنيويًا فقط إذا كان ذلك ممكنًا.
- لا تدّعِ مطابقة البايتات أو المصدر الأصلي.
- لا تعِد بناء البيان تلقائيًا.
- لا تغلق الطبقة بسبب اكتمال الجرد وحده.

إذا كان البيان موجودًا لكنه لا يحتوي على SHA-256:

- سجّل `INVENTORY_ONLY` لمستوى التحقق البنيوي.
- لا تدّعِ byte verification.
- لا تخترع بصمات متوقعة.
- ابحث عن مصدر موثوق للبصمات إذا كان متاحًا، دون تغيير الأرشيف.

إذا كان البيان موجودًا لكنه لا يطابق الملفات:

- سجّل كل اختلاف.
- لا تصلح البيان أو الملفات تلقائيًا.
- لا تعلن المطابقة.
- استخدم `PARTIAL` أو `BLOCKED` وفق أثر المشكلة.

## 4.4 الاستثناءات الخمسة

افحص كل استثناء على حدة:

- هل الملف موجود؟
- هل هو في البيان الصحيح؟
- هل الاختلاف موثق؟
- هل المصدر الأصلي متاح؟
- هل يوجد تفسير مدعوم بالأدلة؟
- هل يحتوي الملف على معلومات حساسة تستوجب حماية خاصة؟

لا تطبع أسرارًا أو مفاتيح أو قيمًا حساسة.

لا تحاول إعادة بناء محتوى محجوب أو استبدال `[REDACTED]`.

وجود `[REDACTED]` لا يثبت وحده أن الملف صحيح أو خاطئ.

## 4.5 معيار قبول Layer A

لا تستخدم نسبة نجاح إجمالية أو شرطًا عدديًا منفردًا لإغلاق الطبقة.

لا تعتبر 430/435 دليلًا كافيًا على سلامة الأرشيف.

تكون Layer A `CLOSED` فقط إذا:

1. اكتمل الجرد لكل أرشيف.
2. تمت مطابقة صفوف البيان مع المسارات الصحيحة.
3. لم يبق ملف مفقود أو زائد دون تفسير.
4. ثبتت المطابقة مع المصدر الأصلي لكل ملف مطلوب منه ذلك.
5. وثّقت الاستثناءات المعروفة كلًا على حدة.
6. لم يُعدّل أي أصل أو أرشيف لتجاوز فشل المطابقة.
7. لم تبق فجوة provenance غير محسومة تؤثر في القبول.

إذا تعذر الوصول إلى المصدر الأصلي أو بقيت استثناءات غير محسومة، سجّل `PARTIAL`.

---

# 5. Layer C — ADR-023 Append-Only Integrity

الملف:

`docs/architecture/ADR-023-scientific-source-expansion.md`

التغيير المحلي التاريخي المبلغ عنه:

- §§8.66–8.71
- 231 إضافة
- صفر حذف وفق التقرير السابق

أعد قياس هذه القيم. لا تفترض أنها لا تزال صحيحة.

## 5.1 الحماية

لا تعدّل أو تحذف أو تعِد إنشاء أي قسم من ADR-023 خلال هذه الجولة.

خصوصًا:

- لا تغيّر §§8.60–8.70.
- لا تحذف أو تستبدل §§8.66–8.71.
- لا تعِد ترتيب الأقسام التاريخية.
- لا تحوّل سجل CapVer المحلي إلى قرار منشور.
- لا تعتبر وجود القسم في diff موافقة معمارية.

## 5.2 طريقة التحقق

1. اقرأ نسخة الملف من HEAD.
2. اقرأ النسخة المحلية دون تعديلها.
3. افحص diff الفعلي.
4. تحقق من سلامة الأقسام المحمية باستخدام مقارنة دقيقة.
5. حافظ على الترميز ونهايات الأسطر عند فحص البايتات.
6. تحقق من عدد الإضافات والحذوفات.
7. تحقق من أن كل إضافة تقع في النطاق المسموح.
8. راجع محتوى الإضافات وحالتها المعمارية.
9. لا تكتب إلى ADR أثناء الفحص.

## 5.2.1 أوامر المقارنة

استخدم نسخة HEAD المستخرجة إلى ملف مؤقت جديد، لا ملفًا سابقًا. إذا كان مسار الإخراج موجودًا، اختر اسمًا جديدًا.

```powershell
$ErrorActionPreference = "Stop"

$adr = "docs/architecture/ADR-023-scientific-source-expansion.md"
$tempRoot = Join-Path $env:TEMP (
    "wsa-adr-check-" + [guid]::NewGuid().ToString("N")
)

New-Item -ItemType Directory -Path $tempRoot | Out-Null

$headFile = Join-Path $tempRoot "adr-head.md"
$localFile = Join-Path $tempRoot "adr-local.md"

# Preserve the exact committed bytes for the HEAD version.
$headBytes = [System.IO.File]::ReadAllBytes(
    (Join-Path (Get-Location).Path $adr)
)

$headBlob = git rev-parse "HEAD:$adr"
if ($LASTEXITCODE -ne 0) {
    throw "Unable to resolve ADR blob from HEAD."
}

# Read the committed blob as bytes through git.
$headContent = git cat-file blob $headBlob
if ($LASTEXITCODE -ne 0) {
    throw "Unable to read ADR blob from HEAD."
}

# Save the committed content for inspection only.
# This text output is not a byte-identical comparison.
[System.IO.File]::WriteAllText(
    $headFile,
    ($headContent -join "`n"),
    [System.Text.UTF8Encoding]::new($false)
)

# Preserve the local file bytes unchanged.
Copy-Item -LiteralPath $adr -Destination $localFile

# Inspect the actual diff without changing the ADR.
git diff --no-ext-diff -- $adr

Write-Host "HEAD blob: $headBlob"
Write-Host "Local file copied to temporary inspection directory."
Write-Host "Temporary directory: $tempRoot"
```

### قيود مهمة على هذا المثال

- نسخة HEAD المحفوظة كنص عبر مخرجات `git` لا تعتبر دليلًا على التطابق البايتي؛ قد تتأثر نهايات الأسطر أو طريقة تمثيل النص.
- نسخة الملف المحلي المنسوخة تحفظ بايتات الملف المحلي.
- لا تحسب hash للأقسام المستخرجة قبل التأكد من أن استخراج كل قسم نجح.
- لا تعتبر سلسلتين فارغتين متطابقتين دليلًا على سلامة الأقسام.
- لا تستخدم إعادة الترميز UTF-8 لإثبات أن بايتات الأصل لم تتغير.

**إذا كان المطلوب إثباتًا بايتيًا للأقسام، استخدم محللًا يحافظ على الإزاحات والبايتات الأصلية ويحدد حدود الأقسام في المصدرين، أو استخدم مقارنة diff دقيقة للملف كاملًا مع التحقق من نطاق التغييرات.** لا تعتمد على regex غير متحقق منه أو نص معاد ترميزه.

## 5.2.2 التحقق من عناوين الأقسام

قبل مقارنة الأقسام، تحقق من وجود عناوين البداية والنهاية المتوقعة في نسخة HEAD والنسخة المحلية.

إذا كان عنوان بداية أو نهاية غير موجود أو متكررًا على نحو يجعل الحدود ملتبسة:

- افشل التحقق.
- سجّل `SECTION_EXTRACTION_FAILED`.
- لا تفسر غياب القسم على أنه تطابق.
- لا تُنشئ القسم ولا تعِد ترتيبه.
- لا تعدّل ADR لإصلاح الفحص.

## 5.3 معيار قبول Layer C

يكون تحقق append-only ناجحًا فقط إذا ثبت أن:

- الأقسام المحمية لم تتغير.
- لا توجد حذوفات غير مسموح بها.
- الإضافات محصورة في §§8.66–8.71.
- لا يوجد تعديل غير متوقع خارج النطاق.
- لم تحدث أي كتابة إلى ADR أثناء الفحص.

هذا القبول يتعلق بسلامة append-only فقط، ولا يعني اعتماد محتوى CapVer أو نشره.

إذا ظهر اختلاف غير مفسر، سجّل `PARTIAL` أو `BLOCKED`.

---

# 6. Layer E — Knowledge Persistence

الخدمة المستهدفة:

`backend/app/Services/Agriculture/Research/Knowledge/ScientificKnowledgePersistenceService.php`

الاختبار المقترح:

`backend/tests/Unit/Agriculture/Research/Knowledge/ScientificKnowledgePersistenceServiceTest.php`

## 6.1 الفحص قبل كتابة الاختبارات

اقرأ التنفيذ والعقود والاختبارات الحالية أولًا.

تحقق من:

- وجود الخدمة والواجهات الفعلية.
- تواقيع الدوال.
- قواعد حفظ البيانات.
- اتصال قاعدة البيانات.
- طريقة حفظ الملف الأصلي.
- حدود الحجم والمهلة والأخطاء.
- عزل المستأجرين إذا كان العقد يدعمه.
- إعدادات PHPUnit وLaravel.
- أي اختبارات قائمة أو ملفات غير متتبعة تحتوي على عمل سابق.

لا تفترض أن أسماء الدوال أو الاستثناءات أو التواقيع المذكورة في مثال تطابق التنفيذ الفعلي.

## 6.2 حالات الاختبار المطلوبة

اكتب الاختبارات الناقصة بناءً على العقد الفعلي، لا بناءً على افتراضات.

غطِّ الحالات التالية حيث ينص العقد عليها:

1. رفض المنظمة الفارغة.
2. رفض الادعاءات غير الكافية.
3. رفض الاستشهادات غير الكافية.
4. نجاح حفظ أدلة مؤهلة.
5. حفظ بايتات الملف الأصلي دون تغيير.
6. رفض URL غير صالح.
7. التعامل مع استجابة كبيرة الحجم.
8. التعامل مع فشل التنزيل.
9. التعامل مع المهلة.
10. idempotency.
11. عزل المستأجرين إذا كان جزءًا من العقد.

لا تستخدم `assertTrue(true)` أو placeholder لإعلان نجاح.

إذا لم يدعم التنفيذ الحالي سلوكًا مطلوبًا، وثّق الفجوة ولا تختلق عقدًا جديدًا. لا تعدّل العقد المعماري لتجاوز فشل الاختبار.

## 6.2.1 منهجية كتابة الاختبارات

الاختبارات المطلوبة موصوفة وظيفيًا، وليست كودًا جاهزًا للنسخ.

قبل كتابة أي اختبار:

1. اقرأ الكود الفعلي.
2. حدد السلوك المتوقع من العقد المعتمد.
3. افحص الاختبارات الموجودة لتجنب التكرار أو الاستبدال.
4. اكتب الاختبار وفق التوقيعات الفعلية.
5. تأكد أن الاختبار يفشل عند كسر السلوك الذي يفترض التحقق منه.
6. لا تنسخ أمثلة من إصدارات قديمة دون التحقق من توافقها.

مثال توضيحي فقط، لا تنفذه قبل التحقق من العقد:

```php
public function test_rejects_empty_organization(): void
{
    // Illustrative only.
    // Verify the actual contract, signature and exception first.
}
```

## 6.3 حفظ الملف الأصلي

استخدم HTTP fake أو fixture محليًا عند اختبار سلوك تنزيل الملف، مع الكتابة إلى مسار اختبار معزول.

تحقق من البايتات الفعلية، بما فيها بايتات غير نصية عند ملاءمة الاختبار.

لا تكتب إلى مجلد بيانات المستخدم أو التخزين الدائم.

استخدم تنظيفًا مضمونًا للملفات الجزئية حتى عند الفشل.

## 6.4 شروط تشغيل الاختبارات

لا تشغّل الاختبارات إلا بعد إثبات:

- اتصال قاعدة البيانات معزول.
- مسارات التخزين معزولة.
- لا توجد كتابة إلى قواعد تشغيلية.
- لا توجد اتصالات بديلة أو آثار جانبية مشتركة.
- لا يتم تشغيل migrations على بيانات تشغيلية.

إذا تعذر إثبات العزل، لا تشغّل الاختبارات وسجّل `BLOCKED` أو `PARTIAL`.

## 6.5 معيار القبول

لا تسجّل `TESTED` إلا بعد تنفيذ الاختبارات فعليًا ونجاحها، مع تسجيل الأمر والنتيجة وعدد الاختبارات.

نجاح الاختبارات لا يساوي تلقائيًا `PRODUCTION_READY`.

---

# 7. Layer F — IC Persistence

المستودع المستهدف:

`backend/app/Services/Agriculture/Research/Integration/EloquentIntegrationClassificationRepository.php`

الاختبار المقترح:

`backend/tests/Unit/Agriculture/Research/Integration/EloquentIntegrationClassificationRepositoryTest.php`

## 7.1 الفحص

اقرأ التنفيذ والعقد الفعلي قبل كتابة الاختبارات.

تحقق من:

- قواعد التصنيف والتحقق.
- قيود uniqueness.
- سياسة التعارض.
- idempotency.
- اتصال قاعدة البيانات.
- طريقة التحقق من endpoints.
- الاختبارات الحالية وmigrations.
- الفرق بين المحاكاة والتحقق الحي.

## 7.2 الاختبارات المطلوبة

اكتب أو أكمل الاختبارات التي يغطيها العقد:

1. إنشاء سجل تصنيف صالح.
2. idempotency.
3. تعارض التصنيفات وفق سياسة معتمدة.
4. نجاح التحقق المحاكى من endpoint.
5. فشل التحقق المحاكى من endpoint.

حدد أولًا السلوك المتوقع من العقد. لا تعتبر قبول التعارض ورفضه نتيجتين صحيحتين في الاختبار نفسه.

استخدم HTTP fake، ولا تنفذ live calls.

نجاح الاختبار المحاكى يثبت سلوك الكود تجاه الاستجابة الاصطناعية فقط؛ ولا يثبت أن endpoint الحقيقي متاح أو صالح.

## 7.3 حدود الطبقة

حتى إذا نجحت الاختبارات، لا تدّعِ تحقق 109 مصادر.

لا تفعّل أي مصدر ولا تعدّل سجل العضوية.

لا تعدّل ملف IC evidence review التاريخي. لا تنشئ وثيقة جديدة إلا إذا كان ذلك داخل التفويض القائم.

الحالة لا تتجاوز:

`PARTIAL — STRUCTURE TESTED, VERIFICATION PENDING`

إلا إذا توفرت أدلة إضافية معتمدة تبرر خلاف ذلك.

## 7.4 شروط تشغيل الاختبارات

تنطبق جميع شروط العزل في القسم 3.

إذا لم يثبت العزل، لا تشغّل الاختبارات.

---

# 8. Layer G — Mission Artifact Archive

الصنف المستهدف:

`backend/app/Services/Agriculture/Research/Mission/FilesystemMissionArtifactArchive.php`

الاختبار المقترح:

`backend/tests/Unit/Agriculture/Research/Mission/FilesystemMissionArtifactArchiveTest.php`

## 8.1 الفحص

اقرأ التنفيذ والعقد الفعلي، بما في ذلك:

- إنشاء قاعدة SQLite.
- مسارات التخزين.
- `ingestFile`.
- content hash.
- القراءة من الأرشيف.
- الاستمرارية.
- إعادة التشغيل.
- معالجة الأخطاء.
- منع path traversal.
- سياسة التنظيف.

لا تفترض أن تمرير مسار مؤقت يضمن عزل جميع الاتصالات.

## 8.2 الاختبارات المطلوبة

اكتب أو أكمل الاختبارات التالية وفق العقد:

1. إدخال artifact جديد.
2. صحة البيانات الوصفية.
3. deduplication حسب المحتوى.
4. idempotency.
5. التعامل مع محتوى مختلف.
6. سلامة القراءة بعد الكتابة.
7. الاستمرارية بعد إنشاء instance جديد.
8. فشل الكتابة أو التخزين.
9. رفض path traversal.
10. رفض المسار الفارغ أو غير الصالح.
11. تطابق المحتوى مع اختلاف المصدر.
12. سلامة التنظيف وعدم ترك آثار غير مرغوبة.

تحقق من السلوك المتوقع قبل كتابة كل اختبار.

## 8.3 عزل الاختبارات

استخدم قاعدة SQLite مخصصة للاختبار ومسار تخزين منفصلًا ومجلدًا مؤقتًا معروفًا.

قبل التشغيل تحقق من:

- أن الصنف يستخدم المسارات المعطاة فعلًا.
- أنه لا يفتح اتصالًا آخر إلى قاعدة تشغيلية.
- أن ملفات WAL/SHM مرتبطة بقاعدة الاختبار فقط.
- أن التنظيف لا يحذف ملفات خارج مجلد الاختبار.
- أن الفشل لا يترك ملفات دائمة.
- أن إعادة تشغيل instance تستخدم قاعدة الاختبار نفسها لا قاعدة تشغيلية.

إذا تعذر إثبات العزل، لا تشغّل الاختبارات.

## 8.4 معيار القبول

لا تعلن `TESTED` إلا بعد نجاح الاختبارات المنفذة فعليًا، وإثبات العزل وسلامة التنظيف.

إذا فشل اختبار، شخّص السبب. لا تخفِ الفشل أو تغيّر العقد لمجرد جعل الاختبار ينجح.

---

# 9. قواعد تنفيذ الاختبارات

قبل التشغيل:

1. اقرأ إعدادات PHPUnit وLaravel.
2. افحص إعدادات الاختبار دون كشف الأسرار.
3. تحقق من اتصال قاعدة البيانات الفعلي.
4. تحقق من مسارات التخزين.
5. تأكد من عدم تشغيل migrations على قاعدة تشغيلية.
6. افحص الاختبارات الموجودة والملفات غير المتتبعة.
7. حدد الملفات التي ستنشأ أو تتغير.
8. شغّل الاختبارات المحددة في هذه الجولة فقط.

لا تشغّل مجموعة الاختبارات كاملة دون ضرورة.

لا تستخدم Docker؛ البيئة المحلية السابقة لا تسمح باستخدامه.

إذا لم يكن PHP أو بيئة الاختبار متاحًا، نفّذ الفحص الساكن المسموح فقط، وسجّل الاختبارات `NOT RUN`. لا تصف الفحص الساكن بأنه تنفيذ اختبار.

إذا فشل الاختبار:

- احتفظ برسالة الخطأ اللازمة للتشخيص.
- حدد إن كان السبب الكود أو البيئة أو العزل.
- أصلح فقط إذا كان الإصلاح داخل النطاق ومتوافقًا مع العقد وآمنًا.
- لا تصلح ملفات خارج النطاق تلقائيًا.
- لا تعاود التشغيل إذا كان قد يكتب إلى بيانات تشغيلية.

---

# 10. Checkpoint — نقطة التوقف

أنشئ نقطة توقف بعد إكمال ما أمكن من الجولة.

الموضع الافتراضي خارج المستودع، مثل:

`C:\Users\ahmed\AppData\Local\Temp\wsa-checkpoints\round-2-YYYYMMDD-HHMMSS.md`

تحقق من وجود المجلد قبل الكتابة، واستخدم اسمًا جديدًا غير موجود.

لا تستبدل checkpoint سابقًا.

لا تنشئ مجلد checkpoints داخل المستودع ولا تعدّل `.gitignore` دون تفويض صريح.

## 10.1 قالب Checkpoint إلزامي

املأ القالب بالقيم الفعلية. لا تنسخ القيم التاريخية أو المتوقعة على أنها نتائج جديدة.

```markdown
---
round: 2
date: <actual ISO-8601 timestamp with timezone>
branch: <actual branch>
baseline_head: <actual HEAD before execution>
final_head: <actual HEAD after execution>
upstream_before: <actual upstream SHA or NOT VERIFIED>
upstream_after: <actual upstream SHA or NOT VERIFIED>
commit_created: false
push_performed: false
staging_performed: false
overall_status: ROUND_2_READY | PARTIAL | BLOCKED
---

# Baseline

- Repository root:
- Branch:
- HEAD before:
- HEAD after:
- Upstream before:
- Upstream after:
- Working tree before:
- Working tree after:
- Index before:
- Index after:
- Tracked modifications before/after:
- Untracked inventory before/after:
- Pre-existing changes preserved:
- git diff --check result:
- Notes on pre-existing whitespace findings:

# Protected Databases

- scientific_record_store.sqlite:
  - Baseline SHA-256:
  - Final SHA-256:
  - Measurement status:
- capver.sqlite:
  - Baseline SHA-256:
  - Final SHA-256:
  - Measurement status:
- Other protected stores:
- Any unexpected change:
- Verification limitations:

# Layer A — D-08 Archive

- Archive roots:
- File counts:
- Manifest counts:
- Manifest verification:
- Original-source verification:
- Files missing:
- Files extra:
- Exceptions:
- NOT VERIFIED items:
- Status: CLOSED | PARTIAL | BLOCKED
- Evidence:

# Layer C — ADR-023

- Protected sections checked:
- Extraction status:
- HEAD/local comparison method:
- Additions:
- Deletions:
- Out-of-scope differences:
- §§8.66–8.71 approval status:
- Status: CLOSED | PARTIAL | BLOCKED
- Evidence:

# Layer E — Knowledge Persistence

- Files inspected:
- Tests actually executed:
- Command:
- Passed:
- Failed:
- Skipped:
- Database isolation:
- Storage isolation:
- Status: TESTED | PARTIAL | BLOCKED | NOT RUN
- Evidence and limitations:

# Layer F — IC Persistence

- Files inspected:
- Tests actually executed:
- Command:
- Passed:
- Failed:
- Mock mode:
- Live verification:
- Database isolation:
- Status: TESTED | PARTIAL | BLOCKED | NOT RUN
- Evidence and limitations:

# Layer G — Mission Artifact Archive

- Files inspected:
- Tests actually executed:
- Command:
- Passed:
- Failed:
- Temporary SQLite path:
- Temporary storage path:
- Cleanup verification:
- Status: TESTED | PARTIAL | BLOCKED | NOT RUN
- Evidence and limitations:

# Test Summary

- Test name:
- Exact command:
- Actual exit code:
- Actual result:
- Assertions, if available:
- Reason not run, if applicable:

# Candidate Manifest

For each file:
- Path:
- Classification:
- Reason:
- Evidence:
- Risk:
- ADR relationship:

Allowed classifications:
CANDIDATE_FOR_COMMIT
KEEP_UNTRACKED
IGNORE_WITH_JUSTIFICATION
EXCLUDE_FROM_COMMIT
INVESTIGATE

# Unresolved Risks

- Risk:
- Evidence:
- Impact:
- Safe next action:

# Next Action

- Exact next safe action:
- Round 3 authorized: NO
- Commit authorized: NO
- Push authorized: NO
```

إذا لم تتوفر نتيجة، اكتب `NOT MEASURED` أو `NOT VERIFIED` أو `NOT RUN` حسب الحالة. لا تضع `unchanged` أو `passed` دون دليل.

## 10.2 سلامة checkpoint

بعد إنشاء الملف:

- تحقق من وجوده.
- تحقق من أن اسمه لم يكن موجودًا قبل الكتابة.
- اقرأ الملف وتحقق من اكتماله.
- لا تستبدل نقطة توقف سابقة.
- لا تعتبر إنشاء checkpoint دليلًا على إغلاق الطبقات.

---

# 11. معيار ROUND_2_READY

لا تعلن `ROUND_2_READY` إلا إذا:

- اجتازت بوابة baseline.
- استوفت Layer A جميع معايير القبول.
- ثبتت سلامة append-only في Layer C.
- اجتازت Layer E الاختبارات الآمنة المطلوبة.
- اجتازت Layer F الاختبارات المعزولة، مع تسجيل أي تحقق خارجي غير منجز.
- اجتازت Layer G الاختبارات المعزولة المطلوبة.
- لم تتغير قواعد البيانات المحمية نتيجة الجولة.
- لم يُفقد أي عمل سابق.
- لم تحدث تعديلات خارج النطاق.
- أُنشئ checkpoint موثوق دون استبدال ملف سابق.
- لا توجد ادعاءات غير مدعومة عن الاختبارات أو الاعتماد أو الجاهزية الإنتاجية.

لا تستخدم معيارًا عدديًا منفردًا لإغلاق Layer A.

إذا تعذر شرط واحد، استخدم `PARTIAL` أو `BLOCKED` وحدد الشرط غير المستوفى.

نجاح الاختبارات لا يعني تلقائيًا `PRODUCTION_READY`.

---

# 12. شروط التوقف الفوري

توقف عن العمل المتأثر وأبلغ المالك إذا:

1. اختلف HEAD عن القيمة المتوقعة.
2. اختلف الفرع عن المتوقع.
3. اختلف upstream عن HEAD.
4. ظهر سر أو محتوى حساس يحتمل كشفه.
5. تغيرت بصمة قاعدة تشغيلية محمية دون تفسير.
6. ظهر حذف أو تغيير غير مسموح في ADR محمي.
7. تبين أن اختبارًا قد يكتب إلى قاعدة تشغيلية.
8. تعذر إثبات عزل قاعدة الاختبار أو مسار التخزين.
9. ظهر فقدان أو تغيير غير مقصود للعمل السابق.
10. تطلب الإجراء reset أو clean أو stash أو force push أو إسقاط تعديلات.
11. ظهر تعارض معماري لا يمكن حله من القرارات المعتمدة.
12. تطلب الإكمال تعديل ملفات خارج النطاق.
13. تعذر الوصول إلى دليل أصلي مطلوب لإثبات ادعاء حاسم.
14. تعذر استخراج قسم ADR المطلوب بصورة موثوقة.
15. كان غياب البيان أو البصمات يمنع التحقق من سلامة المصدر.

عند التوقف:

- لا تتجاوز البوابة.
- لا تحاول إصلاح الحالة بالقوة.
- احفظ الحالة دون تعديل إضافي.
- وثّق السبب والأدلة.
- حدد الإجراء الآمن التالي.

---

# 13. التقرير النهائي — بالعربية

أصدر تقريرًا هندسيًا واحدًا شاملًا، دون تكرار التحليل في عدة تقارير.

## A. الحالة العامة

`ROUND_2_READY` أو `PARTIAL` أو `BLOCKED`.

اشرح السبب بناءً على الأدلة.

## B. خط الأساس

- الجذر والفرع.
- HEAD قبل وبعد.
- upstream قبل وبعد.
- حالة الفهرس وشجرة العمل.
- أعداد الملفات المتتبعة وغير المتتبعة قبل وبعد.
- التغييرات السابقة المحفوظة.

## C. Layer A

- عدد الملفات الفعلي لكل أرشيف.
- عدد صفوف البيان.
- نتائج Manifest verification.
- نتائج Original-source verification.
- الاستثناءات الخمسة ونتيجة كل منها.
- الملفات المفقودة والزائدة.
- حالات `NO_MANIFEST` و`INVENTORY_ONLY` و`NOT VERIFIED`.
- حالة الطبقة وأسبابها.

## D. Layer C

- الأقسام المحمية.
- طريقة المقارنة.
- نجاح أو فشل استخراج الأقسام.
- عدد الإضافات والحذوفات.
- التغييرات خارج النطاق.
- حالة §§8.66–8.71 من ناحية الاعتماد.
- حالة الطبقة وأسبابها.

## E. Layer E

- الملفات التي تمت مراجعتها.
- الاختبارات المنفذة فعليًا.
- الأوامر والنتائج.
- العزل.
- الفجوات المتبقية.

## F. Layer F

- الملفات التي تمت مراجعتها.
- نتائج الاختبارات.
- نتائج المحاكاة.
- سياسة التعارض.
- ما لم يتم التحقق منه حيًا.
- الحالة وأسبابها.

## G. Layer G

- سلامة مسارات الاختبار.
- عزل SQLite والتخزين.
- نتائج الاختبارات.
- نتائج التنظيف.
- الحالة والمخاطر.

## H. البيانات المحمية

- هل أعيد قياس البصمات؟
- القيم الفعلية إن أمكن قياسها بأمان.
- أي تغير غير متوقع.
- القيود على التحقق.

لا تستخدم بصمة تاريخية على أنها قياس جديد.

## I. سجل الاختبارات

لكل اختبار: الاسم، الأمر، النتيجة الفعلية، exit code، عدد الاختبارات والـ assertions إن توفرت، وسبب عدم التشغيل.

## J. Checkpoint

- المسار الفعلي.
- وقت الإنشاء.
- التحقق من عدم استبدال ملف سابق.
- اكتمال محتواه.

## K. Candidate Manifest

حدد الملفات التي قد تكون مرشحة للالتزام مستقبلًا، مع التصنيف والتبرير والدليل والمخاطر.

لا تضف الملفات إلى الفهرس.

## L. الإجراء التالي

حدد ما بقي بدقة، واذكر ما إذا كانت الجولة جاهزة للانتقال إلى الجولة التالية.

لا تبدأ Round 3 تلقائيًا.

---

# 14. أمر البدء

ابدأ الآن بالمرحلة صفر، للفحص للقراءة فقط والتحقق من:

- جذر المستودع.
- الفرع.
- HEAD.
- upstream.
- حالة شجرة العمل والفهرس.
- قائمة الملفات المعدلة وغير المتتبعة.

إذا اجتازت البوابة، أكمل Layers A وC وE وF وG بالترتيب المحدد.

إذا فشلت البوابة، توقف وأصدر التقرير دون تعديل.

**نفّذ العمل الآمن المصرح به، ولا ترسل خطة فقط.**

**لا تختلق نتائج. لا تلمس قواعد التشغيل. لا تفقد العمل السابق.**

**لا staging. لا commit. لا push. لا تبدأ Round 3.**

المخرج النهائي: تقرير عربي شامل ونقطة توقف موثوقة، مع حالة تعكس الأدلة الفعلية فقط.
