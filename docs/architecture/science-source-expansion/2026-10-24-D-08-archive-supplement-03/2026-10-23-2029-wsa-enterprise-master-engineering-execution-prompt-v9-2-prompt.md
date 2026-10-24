<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18303). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 8:29 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18304; it is archived once. -->

---
# WSA-Enterprise — MASTER ENGINEERING EXECUTION PROMPT v9.2
## Round 2.5 Gap Closure — Engineering Execution, Security, Tests, Evidence & Final Review

**الإصدار:** 9.2  
**التاريخ:** 2026-10-23  
**المستودع:** `wsa-platform/WSA-Enterprise`  
**المسار المحلي:** `G:\WSA-Enterprise`  
**الفرع المتوقع:** `phase-18-m18-ai-marketing-communications`  
**HEAD الأساسي المتوقع:** `1e4d7a62a1054b82e4eec655bd6dcd630e837cf6`  
**القرار المعماري:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Checkpoint السابق:** `C:\Users\ahmed\AppData\Local\Temp\wsa-checkpoints\round-2-20261023-201230.md`

---

# 0. التفويض الهندسي والهدف

## 0.1 دورك ومسؤوليتك

أنت مهندس برمجيات أول، ومهندس تصميم معماري، ومهندس أمن واختبارات، ومسؤول تكامل وإغلاق فني لمشروع WSA-Enterprise.

**نفّذ العمل فعليًا، ولا تكتفِ بإعداد خطة أو تقرير نظري.**

أُعطيت صلاحيات هندسية واسعة ضمن نطاق الجولة الحالية، تشمل:

1. فحص المستودع والكود والاختبارات والوثائق ذات الصلة.
2. تصحيح المسارات والافتراضات غير الصحيحة في التعليمات السابقة.
3. تتبع تدفق البيانات والاتصالات والتخزين والحدود الأمنية.
4. تحديد العيوب المثبتة بالأدلة وتصميم الحل المناسب.
5. تعديل كود التطبيق والاختبارات والوثائق اللازمة.
6. كتابة الاختبارات وتعديلها وفق العقود الفعلية.
7. تشغيل الاختبارات الآمنة والمعزولة.
8. تشخيص حالات الفشل وإصلاح أسبابها الحقيقية.
9. مراجعة التغييرات السابقة والحالية لحماية العمل القائم.
10. إعداد تقرير هندسي نهائي موثق، مع Checkpoint جديد.

لا تطلب موافقة متكررة على كل خطوة تقنية روتينية. اتخذ القرارات الهندسية المحلية المناسبة، ونفّذ ما يقع ضمن التفويض.

## 0.2 نطاق الجولة الحالية

الجولة الحالية هي **Round 2.5 فقط**، وتهدف إلى سد الفجوات في Layers A وE وF وG، ومراجعة سلامة ADR-023، ثم إعداد تقرير نهائي قابل للمراجعة.

المهام:

- **Layer E:** استكمال اختبارات حفظ المعرفة العلمية والتحقق من سلوك التخزين.
- **Layer G:** فحص وإصلاح مشكلات مسارات الملفات في MissionArtifactArchive عند ثبوتها.
- **Layer A:** استكمال ما يمكن إثباته من أرشيف D-08 وتوثيق حدود التحقق.
- **Layer F:** مراجعة عقد Integration Classification وتحديد ما يمكن إغلاقه بأدلة فعلية.
- **ADR-023:** توثيق النتائج والقيود بطريقة append-only.
- **Checkpoint:** تسجيل الحالة الفعلية بعد التنفيذ.
- **Final Review:** مراجعة الفروق والاختبارات والبيانات المحمية.

## 0.3 ما هو خارج النطاق؟

في هذه الجولة، لا تبدأ:

- Round 3.
- Layer H.
- CapVer Ship.
- استعادة أو تفعيل مقاعد CapVer جديدة.
- عمليات نشر أو release.
- أي عمل يتطلب تحققًا حيًا غير مصرح به من 109 مصادر.
- أي قرار معماري جديد يحتاج اعتمادًا مستقلًا.

**لا تنتقل إلى Round 3 تلقائيًا، حتى إذا اكتملت Round 2.5.**

لا تنفذ `git add` أو `git commit` أو `git push` في هذه الجولة. تنتهي الجولة بتقرير وCheckpoint وقائمة تغييرات قابلة للمراجعة.

## 0.4 التقديرات الزمنية

هذه تقديرات للتخطيط فقط، وليست معايير قبول:

| المهمة | التقدير الأولي |
|---|---:|
| خط الأساس ومراجعة Checkpoint | 10–20 دقيقة |
| Layer E: الفحص والاختبارات والإصلاح | 45–120 دقيقة |
| Layer G: الفحص الأمني والإصلاح والاختبارات | 30–90 دقيقة |
| Layer A: مراجعة أدلة الأرشيف | 15–45 دقيقة |
| Layer F: فحص المسؤوليات والاختبارات | 20–60 دقيقة |
| مراجعة ADR-023 | 15–30 دقيقة |
| الفحوص النهائية وCheckpoint والتقرير | 20–40 دقيقة |

قد يستغرق العمل وقتًا أطول إذا ظهر عيب معماري أو نقص في العزل. لا تختصر الاختبارات ولا تتجاوز بوابة أمان لتلبية التقدير.

---

# 1. خط الأساس والحقائق التاريخية

التقرير السابق، المؤرخ 23 أكتوبر 2026، سجّل:

| العنصر | القيمة السابقة |
|---|---|
| HEAD | `1e4d7a62a1054b82e4eec655bd6dcd630e837cf6` |
| الفرع | `phase-18-m18-ai-marketing-communications` |
| الملفات المتتبعة المعدلة | 51 |
| سطور الملفات غير المتتبعة في porcelain | 116 |
| الجرد بعد التوسيع | 1443 ملفًا |
| الفهرس | فارغ |
| الاختبارات المشتركة | 45 اختبارًا، 192 assertion، exit 0 |
| Layer A | `PARTIAL — INVENTORY_ONLY` |
| Layer C | `CLOSED` لسلامة الإلحاق فقط |
| Layer E | `PARTIAL` |
| Layer F | `PARTIAL — STRUCTURE TESTED, VERIFICATION PENDING` |
| Layer G | `PARTIAL` |
| Layer H | لم يبدأ |

هذه نتائج تاريخية، وليست ضمانًا بأن حالة الملفات الحالية لم تتغير. تحقّق من الحالة التي تحتاجها دون إعادة تنفيذ أعمال سابقة بلا سبب.

المسارات الفعلية المسجلة:

- Layer E: `backend/app/Services/Agriculture/Research/Persistence/ScientificKnowledgePersistenceService.php`
- Layer F: `backend/app/Services/Agriculture/Research/IntegrationClassification/Persistence/EloquentIntegrationClassificationRepository.php`
- Layer G: `backend/app/Services/Architecture/MissionArtifactArchive/FilesystemMissionArtifactArchive.php`

ملفات الاختبارات:

- `backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceServiceTest.php`
- `backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`

---

# 2. قواعد الحماية الإلزامية

## 2.1 حماية التغييرات السابقة

يجب الحفاظ على كل التغييرات الموجودة قبل الجولة، سواء كانت متتبعة أو غير متتبعة.

ممنوع:

- `git reset --hard`
- `git clean`
- `git stash`
- `git checkout --`
- `git restore` إذا كان سيزيل أو يستبدل تغييرات قائمة
- `git add .`
- `git add -A`
- `git commit -a`
- `git commit --amend`
- `git push --force`
- حذف الملفات غير المتتبعة بهدف تنظيف شجرة العمل
- إعادة كتابة التاريخ
- إعادة تنسيق ملفات سابقة غير مرتبطة بالمهمة

لا تعدّل ملفًا غير مرتبط بالمهمة لمجرد تحسينه.

إذا ظهر اختلاف غير متوقع في HEAD أو الفهرس أو الملفات، احفظ الأدلة وحدد نطاق الاختلاف قبل أي تعديل إضافي.

## 2.2 حماية قواعد البيانات

القاعدتان التاليتان محميتان:

- `backend/storage/app/scientific_record_store.sqlite`
- `backend/storage/app/capver.sqlite`

لا تفتحهما لفحص الجداول، ولا تعدلهما، ولا تشغّل migrations عليهما، ولا تنفذ اختبارات قد تكتب إليهما.

قبل تشغيل أي اختبار يتعامل مع قواعد البيانات، افحص:

- `backend/tests/bootstrap.php`
- `backend/phpunit.xml`
- إعدادات الاتصالات.
- `DatabaseSafety`.
- أي خدمة أو مستودع قد يفتح قاعدة تشغيلية.

يجب إثبات أن الاتصال المستخدم ومسارات التخزين معزولة فعليًا. وجود `:memory:` أو `RefreshDatabase` أو `Storage::fake()` لا يكفي وحده لإثبات العزل.

إذا تعذر إثبات العزل، لا تشغّل الاختبار. سجّل `BLOCKED` وواصل الأعمال المستقلة الآمنة.

## 2.3 حماية ADR-023

لا تحذف أو تعِد كتابة أي محتوى قائم في ADR-023.

أي توثيق جديد في هذه الجولة يجب أن يكون append-only، وأن يُضاف بعد مراجعة نهاية الملف والتحقق من عدم وجود عنوان مكرر أو حدود ملتبسة.

تذكّر أن §§8.66–8.71 وُصفت في التقرير السابق بأنها إضافات محلية غير معتمدة وغير منشورة. لا تعاملها تلقائيًا كقرارات معمارية معتمدة.

## 2.4 حماية الأسرار

لا تعرض كلمات مرور أو مفاتيح API أو رموز مصادقة في المخرجات أو التقارير.

إذا اكتشفت سرًا، سجّل مسار الملف ونوع المشكلة دون طباعة القيمة.

---

# 3. بوابة Baseline — أوامر تنفيذ عملية

ابدأ من PowerShell في المستودع:

```powershell
Set-Location 'G:\WSA-Enterprise'

$expectedHead = '1e4d7a62a1054b82e4eec655bd6dcd630e837cf6'
$expectedBranch = 'phase-18-m18-ai-marketing-communications'

$head = (git rev-parse HEAD).Trim()
$branch = (git rev-parse --abbrev-ref HEAD).Trim()

Write-Host "HEAD: $head"
Write-Host "Branch: $branch"

if ($head -ne $expectedHead) {
    Write-Host 'BLOCKED: HEAD differs from expected baseline.'
    exit 1
}

if ($branch -ne $expectedBranch) {
    Write-Host 'BLOCKED: unexpected branch.'
    exit 1
}

git status --short
git diff --cached --name-status
git diff --stat
git diff --check
```

لا تعتبر تحذير `git diff --check` نجاحًا. إذا ظهر خطأ سابق، سجّله ولا تصلحه تلقائيًا إذا كان خارج النطاق.

احفظ قائمة المسارات وحالتها في ملف جديد خارج المستودع. لا تكتفِ بعدّ الأسطر؛ احتفظ بالأسماء والحالات.

## 3.1 فحص بصمات الملفات المحمية

```powershell
$protectedFiles = @(
    'backend/storage/app/scientific_record_store.sqlite',
    'backend/storage/app/capver.sqlite'
)

foreach ($path in $protectedFiles) {
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        Write-Host "BLOCKED: protected file missing: $path"
        exit 1
    }

    $item = Get-Item -LiteralPath $path
    $hash = (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash

    [PSCustomObject]@{
        Path             = $path
        Length           = $item.Length
        LastWriteTimeUtc = $item.LastWriteTimeUtc.ToString('o')
        SHA256           = $hash
    }
}
```

القيم المسجلة سابقًا:

- `scientific_record_store.sqlite`: `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`
- `capver.sqlite`: `66CA7C1497BA84E3FF9ECCBF01D6A5E2BC193571469EEAED1A5D10B19C00B875`

قارن القيم الكاملة. لا تستخدم البادئات المختصرة في بوابة الأمان. إذا اختلفت البصمة عن القيمة المتوقعة، توقف عن الاختبارات الحساسة واحفظ الأدلة.

هذه المقارنة تثبت ثبات بايتات الملف فقط، ولا تثبت سلامة محتوياته الداخلية.

## 3.2 إعادة التحقق من upstream

لا تنفذ `fetch` أو `pull` تلقائيًا. اقرأ المرجع المحلي، ويمكنك الاستعلام عن المرجع البعيد دون تغيير شجرة العمل:

```powershell
git rev-parse origin/phase-18-m18-ai-marketing-communications
git ls-remote origin refs/heads/phase-18-m18-ai-marketing-communications
```

إذا اختلفت القيم أو تعذر التحقق، سجّل الحالة. لا تنفذ مزامنة أو إعادة كتابة للتاريخ.

---

# 4. Layer E — Knowledge Persistence

## 4.1 افحص العقد الفعلي أولًا

افحص:

`backend/app/Services/Agriculture/Research/Persistence/ScientificKnowledgePersistenceService.php`

والاختبار:

`backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceServiceTest.php`

استخدم أوامر القراءة التالية، ثم اقرأ الأجزاء ذات الصلة كاملة:

```powershell
$service = 'backend/app/Services/Agriculture/Research/Persistence/ScientificKnowledgePersistenceService.php'
$test = 'backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceServiceTest.php'

Get-Content $service
Get-Content $test

Select-String -Path $service -Pattern 'function |Storage::|Http::|DB::|timeout|size|organization|tenant' -Context 2,5
Select-String -Path $test -Pattern 'function test|public function test|Storage::fake|Http::fake|assert' -Context 1,4
```

هذه أوامر استكشاف وليست بديلًا عن قراءة الكود. افحص كذلك العقود والمستودعات التي تعتمد عليها الخدمة.

حدّد التواقيع الفعلية، وشروط القبول والرفض، وسياسة الحفظ، ومسارات التخزين، وحد الحجم، والمهلة، والتكرار، وعزل المستأجر.

لا تفترض أن أسماء الدوال الواردة في الأمثلة أدناه موجودة في التنفيذ الحالي.

## 4.2 الاختبارات المطلوبة

صمّم الاختبارات وفق العقد الفعلي، مع تغطية الحالات التالية متى كانت ضمن مسؤولية الخدمة:

1. الحفظ الناجح.
2. صحة السجل والبيانات الناتجة.
3. مطابقة البايتات الأصلية بعد الحفظ.
4. رفض عنوان أو بروتوكول غير صالح.
5. رفض محتوى يتجاوز حد الحجم المعتمد.
6. التعامل مع استجابة HTTP غير ناجحة.
7. التعامل مع المهلة أو فشل الاتصال.
8. منع التكرار وفق العقد.
9. عزل بيانات المستأجر أو المنظمة.
10. الحفاظ على حالات الرفض المبكر الموجودة.

لا تعتبر الوصول إلى عدد محدد من الاختبارات معيارًا للنجاح. يجب أن يكون العدد النهائي نتيجة الاختبارات الفعلية.

لا تستخدم `assertNotNull()` وحده لإثبات نجاح الحفظ.

## 4.2.1 أمثلة إرشادية لاختبارات E

الأمثلة التالية توضح نمط الاختبار فقط. **لا تنسخها مباشرة قبل فحص العقد والتواقيع الفعلية.**

مثال للتحقق من نتيجة الحفظ:

```php
public function test_persist_success_with_fake_storage(): void
{
    Storage::fake('local');

    // استخدم طريقة إنشاء الخدمة ومدخلات صالحة وفق العقد الفعلي.
    $result = $this->invokePersistenceUsingValidFixture();

    $this->assertNotNull($result);

    // استبدل هذه التأكيدات بالحقول الفعلية التي يحددها العقد.
    $this->assertSame('test-org', $result->organization);

    // أثبت وجود السجل والملف إذا كان الحفظ يتطلب كليهما.
}
```

`invokePersistenceUsingValidFixture()` عنصر توضيحي وليس دالة موجودة بالضرورة في المشروع. استبدله بطريقة الاختبار الفعلية.

مثال للتحقق من حفظ البايتات:

```php
public function test_preserved_source_bytes_match_original(): void
{
    Storage::fake('local');

    $binary = "binary\x00content\xFFtest";

    // أنشئ استجابة HTTP مزيفة وفق طريقة التنزيل الفعلية.
    // استدعِ واجهة الحفظ الموجودة فعلًا في الخدمة.
    // استخرج مسار الملف من نتيجة الخدمة أو من العقد.

    // ثم تحقق من:
    // 1. وجود الملف.
    // 2. تطابق البايتات تمامًا مع $binary.
}
```

لا تترك الاختبار النهائي بهذه التعليقات فقط. استخدم الدالة الفعلية، والمسار الفعلي، وتأكيدات قابلة للتنفيذ.

مثال على نمط محاكاة HTTP بعد التأكد من استخدام Laravel HTTP Client:

```php
Http::fake([
    'example.test/*' => Http::response($binary, 200),
]);
```

لا تفترض أن الخدمة تستخدم `Http::`؛ إذا كانت تستخدم عميلًا أو طبقة نقل أخرى، فاستخدم نقطة الحقن المناسبة في العقد الحالي.

## 4.3 العزل

استخدم `Storage::fake()` إذا كان متوافقًا مع طريقة التخزين الفعلية. إذا كانت الخدمة تستخدم قرصًا مختلفًا، أو filesystem مباشرًا، أو خدمة خارجية، فاعزل ذلك وفق البنية الحقيقية.

إذا احتاج الاختبار إلى قاعدة بيانات، أثبت أن الاتصال معزول قبل التشغيل.

لا تغيّر إعداد اتصال بعد إنشاء الخدمة وتتوقع أن جميع الاتصالات المحقونة ستتغير تلقائيًا.

## 4.4 معيار القبول

يجب أن تثبت الاختبارات:

- أن الحفظ الناجح ينتج الأثر المتوقع.
- أن البيانات المحفوظة صحيحة.
- أن البايتات لا تتغير إذا كان حفظ المصدر الأصلي مطلوبًا.
- أن حالات الفشل تعالج وفق العقد.
- أن قواعد البيانات التشغيلية لم تُستخدم.
- أن الاختبارات السابقة ما زالت سليمة.

إذا تعذر اختبار حالة بأمان، سجّلها `BLOCKED` أو `NOT RUN`.

---

# 5. Layer G — MissionArtifactArchive Security

## 5.1 افحص تدفق البيانات

افحص:

`backend/app/Services/Architecture/MissionArtifactArchive/FilesystemMissionArtifactArchive.php`

واختبارات:

`backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`

استخدم:

```powershell
$archive = 'backend/app/Services/Architecture/MissionArtifactArchive/FilesystemMissionArtifactArchive.php'
$test = 'backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php'

Get-Content $archive
Get-Content $test

Select-String -Path $archive -Pattern 'function ingestFile|function exportOriginal|basename|realpath|file_put_contents|fopen|Storage::|blobs|archive.sqlite' -Context 3,8
Select-String -Path $test -Pattern 'function test|public function test|traversal|filename|export|ingest' -Context 2,5
```

تتبّع استخدام `source` و`filename` و`content`، وكيفية بناء مسارات الملفات والكتابة والقراءة والتصدير.

لا تعتبر `basename()` وحده ضمانًا كافيًا ضد اجتياز المسارات.

## 5.2 حالات Path Traversal المطلوبة

افحص الحالات التالية، وحدد السلوك وفق عقد `ingestFile` ومسار التخزين النهائي:

| الحالة | مثال | التوقع الأمني |
|---|---|---|
| اجتياز مسار نسبي | `../../../etc/passwd` | الرفض إذا كان اسم ملف فقط |
| نقطة واحدة | `.` | الرفض |
| نقطتان | `..` | الرفض |
| اسم فارغ | `""` | الرفض |
| فاصل `/` | `sub/file.txt` | الرفض أو معالجة صريحة وفق العقد |
| فاصل `\` | `sub\file.txt` | الرفض أو معالجة صريحة وفق العقد |
| اسم عادي | `file.txt` | القبول |
| اسم عادي بشرطة | `valid-name.txt` | القبول |
| اسم يحوي أحرفًا غير مسموحة | بحسب قواعد النظام | الرفض إذا خالف العقد |

يجب أن تشمل الاختبارات الحالات الإيجابية والسلبية، لا الحالات الخطرة وحدها.

## 5.3.1 مثال إصلاح مشروط

إذا أثبت فحص العقد أن `filename` يجب أن يكون اسم ملف فقط، يمكن أن يكون التحقق الأولي مشابهًا لما يلي:

```php
$safeName = basename($filename);

if (
    $filename === '' ||
    $filename === '.' ||
    $filename === '..' ||
    $safeName !== $filename ||
    str_contains($filename, '/') ||
    str_contains($filename, '\\')
) {
    throw new \InvalidArgumentException('Invalid filename');
}
```

هذا مثال مشروط، وليس إصلاحًا جاهزًا لكل تصميم. يجب التحقق من السلوك على بيئة Windows والأنظمة المستهدفة، ومراجعة مسار الكتابة النهائي، وأي استخدامات مشروعة لأسماء الملفات.

إذا كان العقد يسمح بمسارات داخلية، فصمّم تحققًا يناسب العقد بدل فرض الرفض دون مبرر.

## 5.4 فشل الكتابة والتنظيف

إذا أمكن محاكاة فشل الكتابة في بيئة معزولة، أضف اختبارًا يثبت السلوك المتوقع، وعدم ترك ملفات أو بيانات غير متسقة.

تأكد من أن `forTesting()` يستخدم مساحة اختبار مستقلة، وأن الاختبارات تنظف الموارد التي أنشأتها فقط.

لا تحذف ملفات أو مجلدات سابقة لا تملك دليلًا على أن الجولة الحالية أنشأتها.

## 5.5 معيار القبول

لا تعلن `path traversal fixed` إلا إذا:

- ثبت السيناريو الخطر من الكود.
- نُفذ الإصلاح الصحيح.
- نجحت اختبارات الرفض.
- نجحت اختبارات الأسماء الصحيحة.
- لم يتغير السلوك المشروع دون مبرر.
- لم يُستخدم مسار تشغيل فعلي في الاختبارات.

---

# 6. Layer A — D-08 Archive Verification

## 6.1 الحقائق السابقة

| المجموعة | الملفات | صفوف البيان |
|---|---:|---:|
| الأرشيف الأساس | 436 | 218 |
| الملحق 01 | 95 | 94 |
| الملحق 02 | 12 | 11 |
| الإجمالي | 543 | 323 |

حسب التقرير السابق، لا تحتوي بيانات `MANIFEST` على SHA-256 متوقعة لكل ملف.

كما سجل التقرير:

- حساب SHA-256 للملفات الـ543 في CSV خارج المستودع.
- الملحق 02: 11 اسمًا، صفر مفقود، صفر زائد.
- جدولا الأساس والملحق 01 لا يربطان كل صف بمسار ملف.
- المصدر الأصلي: `NOT VERIFIED`.
- وجود خمسة استثناءات موثقة لا يجوز إعادة كتابتها دون دليل.

## 6.2 ما يمكن إثباته

افحص الملفات والبيانات المتاحة دون تعديل الأرشيفات الأصلية.

إذا وجدت مصدرًا أصليًا مستقلًا موثوقًا، أنشئ مقارنة فردية تسجل:

- معرف المصدر.
- المسار الأصلي.
- المسار المؤرشف.
- البصمة المتوقعة.
- البصمة المحسوبة.
- نتيجة المقارنة.
- دليل موثوقية المصدر.

لا تعتبر البصمة المحسوبة حاليًا إثباتًا على أن الملف لم يتغير منذ إنشائه.

لا تنشئ MANIFEST جديدًا ثم تصفه بأنه بيان أصلي.

إذا لم يتوفر المصدر الأصلي، أبقِ الحالة `NOT VERIFIED`.

الصياغة المعتمدة:

**«لا يمكن حاليًا إثبات المطابقة البايتية للمصدر الأصلي باستخدام الأدلة المتاحة.»**

## 6.3 توثيق ADR

يمكن إضافة §8.72 لتوثيق القيود والنتائج، بعد فحص نهاية ADR-023.

يجب أن يوضح التوثيق أن الجرد والبصمات الحالية لا يثبتان وحدهما المطابقة مع المصدر الأصلي.

لا تستخدم عبارة «مستحيل تقنيًا» بصورة مطلقة، ولا تعلن اكتمال الأرشيف بايتيًا.

---

# 7. Layer F — Integration Classification

افحص:

`backend/app/Services/Agriculture/Research/IntegrationClassification/Persistence/EloquentIntegrationClassificationRepository.php`

والاختبارات ذات الصلة.

حدّد الفصل بين:

1. مسؤولية المستودع في حفظ بيانات التصنيف.
2. مسؤولية الخدمة التي تجري التحقق الحي.
3. مسؤولية مصدر البيانات أو adapter.
4. مسؤولية توثيق نتائج التحقق.
5. مسؤولية التعامل مع فشل الاتصال.

لا تضف HTTP endpoint إلى المستودع لمجرد وجود فجوة في التحقق.

ابحث عن خدمة تحقق موجودة بالفعل قبل اقتراح إنشاء خدمة جديدة.

إذا وجدت مكون التحقق، افحص عقده واختباراته. إذا لم توجد آلية تحقق حية ضمن النطاق، وثّق الفجوة دون اختراع واجهة أو خدمة غير معتمدة.

تحقق من العزل قبل تشغيل الاختبارات. لا تنفذ تحققًا حيًا من 109 مصادر دون تفويض مستقل.

الحالة المقبولة عند بقاء التحقق الحي غير منفذ:

`PARTIAL — STRUCTURE TESTED, VERIFICATION PENDING`

---

# 8. ADR-023 — أوامر عملية للتحقق من الأقسام المحمية

## 8.1 الهدف

قارن §§8.60–8.70 بين نسخة `HEAD` والنسخة المحلية، مع التأكد من أن استخراج الحدود واضح.

هذا فحص نصي للمحتوى المستخرج؛ **ليس إثباتًا للتطابق البايتي الأصلي** إذا اختلف الترميز أو نهايات الأسطر.

نفّذ في PowerShell من جذر المستودع:

```powershell
$adr = 'docs/architecture/ADR-023-scientific-source-expansion.md'

if (-not (Test-Path -LiteralPath $adr -PathType Leaf)) {
    throw "ADR file not found: $adr"
}

$headLines = @(git show "HEAD:$adr")
if ($LASTEXITCODE -ne 0) {
    throw 'Could not read ADR from HEAD.'
}

$headText = $headLines -join "`n"
$localText = [IO.File]::ReadAllText((Resolve-Path $adr))

function Get-RequiredSectionRange {
    param(
        [string]$Text,
        [string]$StartSection,
        [string]$EndSection
    )

    $startPattern = "(?m)^#{1,6}\s+" +
        [regex]::Escape($StartSection) + "(?:\s|$)"

    $endPattern = "(?m)^#{1,6}\s+" +
        [regex]::Escape($EndSection) + "(?:\s|$)"

    $startMatches = [regex]::Matches($Text, $startPattern)
    $endMatches = [regex]::Matches($Text, $endPattern)

    if ($startMatches.Count -ne 1) {
        throw "Expected exactly one start heading: $StartSection; found $($startMatches.Count)"
    }

    if ($endMatches.Count -ne 1) {
        throw "Expected exactly one end heading: $EndSection; found $($endMatches.Count)"
    }

    $start = $startMatches[0].Index
    $end = $endMatches[0].Index

    if ($end -le $start) {
        throw "Invalid section boundaries: $StartSection to $EndSection"
    }

    return $Text.Substring($start, $end - $start)
}

$headProtected = Get-RequiredSectionRange `
    -Text $headText `
    -StartSection '§8.60' `
    -EndSection '§8.71'

$localProtected = Get-RequiredSectionRange `
    -Text $localText `
    -StartSection '§8.60' `
    -EndSection '§8.71'

$headHash = [Convert]::ToHexString(
    [Security.Cryptography.SHA256]::HashData(
        [Text.Encoding]::UTF8.GetBytes($headProtected)
    )
)

$localHash = [Convert]::ToHexString(
    [Security.Cryptography.SHA256]::HashData(
        [Text.Encoding]::UTF8.GetBytes($localProtected)
    )
)

Write-Host "HEAD section hash:  $headHash"
Write-Host "Local section hash: $localHash"

if ($headProtected -cne $localProtected) {
    Write-Host 'FAIL: protected ADR section text differs.'
    exit 1
}

Write-Host 'PASS: protected ADR section text is unchanged.'
```

**مهم:** هذا الأمر يفترض أن عناوين الأقسام تبدأ برقم §8.60 و§8.71 وفق نمط العناوين المحدد. إذا كانت بنية الملف الفعلية مختلفة، افحص العناوين وعدّل حدود الاستخراج صراحةً. لا تتابع إذا كانت الحدود ملتبسة.

كما أن مقارنة النص المستخرج لا تغني عن مراجعة `git diff`، ولا تثبت سلامة كل الأقسام إذا لم تُضمّنها في المقارنة.

## 8.2 مراجعة الفرق الفعلي

```powershell
git diff -- docs/architecture/ADR-023-scientific-source-expansion.md
git diff --check -- docs/architecture/ADR-023-scientific-source-expansion.md
```

راجع التغييرات كاملة. لا تعتمد على عدد الأسطر المضافة وحده.

لا تستخدم `Out-File` لإنشاء نسخة مؤقتة ثم تصف مقارنة النص الناتج بأنها مقارنة بايتية للملف الأصلي.

إذا احتجت إلى إثبات بايتي، قارن الملفات الخام أو البايتات الأصلية ذات المصدر الموثوق. لا تعِد ترميز الملف الأصلي بغرض إجراء هذا الإثبات.

## 8.3 إضافة §8.72

إذا استوفيت شروط الإضافة، وثّق:

- نتائج جرد D-08.
- حالة `INVENTORY_ONLY`.
- سبب بقاء المصدر الأصلي `NOT VERIFIED`.
- نتائج Layer F الفعلية.
- الفصل بين اختبار المستودع والتحقق الحي.
- ما بقي مفتوحًا.
- أن التوثيق لا يعتمد قرارات معمارية جديدة.

قبل الكتابة، تحقق من عدم وجود §8.72 بالفعل. إذا كان موجودًا، راجعه ولا تنشئ عنوانًا مكررًا.

لا تعدّل §§8.60–8.71.

---

# 9. بوابات الاختبار النهائية

## 9.1 PHP Syntax

استخدم نسخة PHP المعتمدة إذا كانت متاحة:

`G:\tools\php-8.4.26-nts\php.exe`

لكل ملف PHP تغير خلال الجولة:

```powershell
$php = 'G:\tools\php-8.4.26-nts\php.exe'

if (-not (Test-Path -LiteralPath $php -PathType Leaf)) {
    throw 'Approved PHP executable not found.'
}

& $php -l 'PATH_TO_CHANGED_FILE.php'

if ($LASTEXITCODE -ne 0) {
    throw 'PHP syntax check failed.'
}
```

استبدل المسار بمسار ملف تغير فعلًا. لا تشغّل هذا المثال قبل استبدال المسار.

## 9.2 PHPUnit

بعد إثبات العزل، شغّل اختبارات E وG بصورة مستقلة، ثم الاختبارات المشتركة اللازمة.

من مجلد `backend`:

```powershell
& 'G:\tools\php-8.4.26-nts\php.exe' `
    vendor\phpunit\phpunit\phpunit `
    --configuration phpunit.xml `
    --do-not-cache-result `
    tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceServiceTest.php

if ($LASTEXITCODE -ne 0) {
    throw 'Layer E tests failed.'
}
```

ثم اختبارات G:

```powershell
& 'G:\tools\php-8.4.26-nts\php.exe' `
    vendor\phpunit\phpunit\phpunit `
    --configuration phpunit.xml `
    --do-not-cache-result `
    tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php

if ($LASTEXITCODE -ne 0) {
    throw 'Layer G tests failed.'
}
```

إذا اختلفت المسارات الفعلية، استخدم المسارات التي أثبتها المستودع، ولا تفترض أن الأمر صالح قبل التحقق.

سجّل المخرجات الفعلية، بما فيها عدد الاختبارات وassertions ورمز الخروج.

لا تعلن نجاح مجموعة لم تُشغّل.

## 9.3 بوابات الأمان

يجب أن تتوقف العملية عند:

- اختلاف غير محسوم في خط الأساس.
- فقد تغيير سابق أو تعديل غير مقصود.
- احتمال اتصال اختبار بقاعدة تشغيلية.
- تغير غير مفسر في بصمة قاعدة محمية.
- فشل اختبار مطلوب.
- فشل فحص syntax.
- حذف أو تعديل في أقسام ADR المحمية.
- غياب الأدلة اللازمة لإغلاق طبقة.

لا تجعل تحذيرًا نصيًا بديلًا عن توقف حقيقي عندما تكون سلامة البيانات أو الاختبار مهددة.

---

# 10. مراجعة Git وقائمة الملفات

لا تنفذ `git add` أو `commit` أو `push`.

في نهاية الجولة، قارن حالة الملفات بخط الأساس، وسجّل:

| الحقل | المطلوب |
|---|---|
| المسار | اسم الملف |
| الحالة السابقة | متتبع معدل، غير متتبع، أو غير موجود |
| التغيير الحالي | وصف التغيير المنفذ |
| السبب | العيب أو الاختبار الذي استدعاه |
| الدليل | اختبار أو مقارنة أو فحص |
| التصنيف | مرشح للمراجعة أو مستبعد أو يحتاج قرارًا |
| المخاطر المتبقية | القيود التي لم تُحسم |

لا تصنف الملف كاملًا باعتباره جديدًا إذا كان يحتوي على عمل سابق.

لا تستخدم `git add .` أو `git add -A` أو أي إجراء جماعي قد يلتقط ملفات غير مقصودة.

إذا كان upstream مختلفًا، وثّق ذلك ولا تنفذ مزامنة أو push تلقائيًا.

---

# 11. Checkpoint — قالب إلزامي

أنشئ ملفًا جديدًا باسم فريد في:

`C:\Users\ahmed\AppData\Local\Temp\wsa-checkpoints`

لا تستبدل أي checkpoint سابق.

استخدم القالب التالي بعد التنفيذ، واملأه بالنتائج الفعلية فقط:

```markdown
---
round: 2.5
date: <actual ISO 8601 timestamp with timezone>
baseline_head: <actual HEAD before execution>
final_head: <actual HEAD after execution>
branch: <actual branch>
index_state: <actual state>
final_status: <ROUND_2_5_CLOSED | PARTIAL | BLOCKED>
---

## Baseline
- HEAD:
- Branch:
- Staged files:
- Tracked modified files:
- Untracked files:
- Baseline manifest path:
- Differences from previous report:

## Layer E — Knowledge Persistence
- Files changed:
- Tests executed:
- Tests passed:
- Tests failed:
- Assertions:
- Exit code:
- Isolation evidence:
- Status:
- Remaining gaps:

## Layer G — Mission Artifact Archive
- Files changed:
- Security scenarios inspected:
- Tests executed:
- Tests passed:
- Tests failed:
- Exit code:
- Path traversal status:
- Write-failure handling:
- Status:
- Remaining gaps:

## Layer F — Integration Classification
- Responsibilities inspected:
- Tests executed:
- Isolation evidence:
- Live verification performed:
- Status:
- Remaining gaps:

## Layer A — D-08 Archive
- Inventory:
- Manifest verification:
- Original-source verification:
- Evidence paths:
- Status:
- Remaining gaps:

## ADR-023
- Sections inspected:
- Sections added:
- Protected-section comparison:
- Diff review:
- Append-only status:
- Unapproved decisions remaining:

## Protected Data
- scientific_record_store.sqlite:
  - Length:
  - LastWriteTimeUtc:
  - SHA-256 before:
  - SHA-256 after:
  - Result:
- capver.sqlite:
  - Length:
  - LastWriteTimeUtc:
  - SHA-256 before:
  - SHA-256 after:
  - Result:

## Files Changed
- [Exact path, previous state, change, evidence, classification]

## Tests and Commands
- [Exact command, exit code, tests, assertions, limitations]

## Remaining Risks
- [Severity, evidence, impact, next safe action]

## Next Action
- Round 2.5 status:
- Round 3 authorization: NOT AUTHORIZED BY THIS PROMPT
- Commit/push: NOT PERFORMED
```

لا تضع قيمًا ثابتة أو حالات نجاح مسبقة في القالب. سجّل `NOT RUN` أو `BLOCKED` عند عدم التنفيذ.

لا تستخدم `Z` في الوقت المحلي إلا إذا كان التوقيت محولًا فعلًا إلى UTC.

---

# 12. قواعد القرار أثناء التنفيذ

1. **عيب مثبت وإصلاح آمن ضمن النطاق:** نفّذ الإصلاح واختبره.
2. **الكود يخالف مثالًا في الماستر:** اعتمد العقد الفعلي، وصحّح طريقة التنفيذ.
3. **اختبار يفشل:** شخّص السبب وأصلحه ثم أعد الاختبار؛ لا تتجاوز الفشل.
4. **اختبار قد يصل إلى قاعدة تشغيلية:** لا تشغّله قبل إثبات العزل.
5. **قرار معماري غير معتمد:** حلل البدائل ووثّق التوصية، ولا تعتمده تلقائيًا.
6. **مصدر أصلي غير متاح:** سجّل `NOT VERIFIED`، ولا تختلق دليلًا.
7. **مشكلة خارج النطاق:** وثّقها وواصل العمل المستقل الآمن.
8. **تغير خط الأساس:** احفظ الأدلة ولا تمسح التغييرات.
9. **بوابة أمن تفشل:** توقف عن الجزء المتأثر.
10. **انتهاء الوقت التقديري:** لا تعلن الإغلاق إذا لم تكتمل الشروط.

---

# 13. معايير الإغلاق

استخدم إحدى الحالات التالية لكل طبقة:

- `CLOSED`
- `PARTIAL`
- `BLOCKED`
- `NOT VERIFIED`
- `NOT STARTED`

لا تعلن `CLOSED` لمجرد نجاح اختبار بنيوي إذا كان التحقق التشغيلي جزءًا من العقد.

لا تعلن `ROUND_2_5_CLOSED` إذا بقيت فجوة أساسية مطلوبة غير محسومة.

لا تعلن `PRE-COMMIT REVIEW READY` إلا إذا أصبحت جميع تغييرات الجولة قابلة للمراجعة، واكتملت شروط القبول، ووُثقت المخاطر المتبقية، ولم تعد هناك مشكلة أمان معروفة غير معالجة ضمن النطاق.

حتى لو اكتملت الجولة الحالية، **لا تبدأ Round 3 ولا CapVer Ship ولا commits أو push**. ارفع التقرير النهائي واترك قرار المرحلة التالية لتفويض مستقل.

---

# 14. التقرير النهائي المطلوب

قدّم تقريرًا عربيًا واحدًا شاملًا يتضمن:

1. ملخصًا تنفيذيًا.
2. خط الأساس وGit قبل التنفيذ وبعده.
3. مصفوفة حالات Layers A وC وE وF وG وH.
4. الملفات التي تغيرت وأسباب التغيير.
5. أوامر الاختبارات المنفذة ورموز الخروج والنتائج الفعلية.
6. أدلة عزل قواعد الاختبار.
7. بصمات قواعد البيانات المحمية كاملة قبل الاختبارات وبعدها.
8. نتيجة مراجعة ADR-023 والأقسام المحمية.
9. حالة D-08 وما إذا كان المصدر الأصلي قد ثبت.
10. العيوب التي تم إصلاحها والاختبارات التي تثبت الإصلاح.
11. المخاطر والفجوات التي بقيت.
12. مسار Checkpoint الجديد.
13. قائمة الملفات المرشحة للمراجعة والمستبعدة.
14. الحالة النهائية: `ROUND_2_5_CLOSED` أو `PARTIAL` أو `BLOCKED`.

لا تنسب نجاحًا إلى اختبار لم يُشغّل. لا تختلق عدد assertions أو نتائج. لا تخلط بين توثيق الفجوة وإغلاقها.

---

# 15. أمر البدء

ابدأ الآن من خط الأساس، ثم اقرأ Checkpoint السابق، وافحص الكود والاختبارات الفعلية، ونفّذ أعمال Round 2.5 بالترتيب المحدد.

لا ترسل خطة نظرية بدل التنفيذ.

لا تعِد الأعمال السابقة التي ثبت نجاحها دون سبب جديد.

لا تتوقف عند أول اختلاف في المسار أو التوقيع؛ افحص المستودع وصحح افتراضات التنفيذ.

استخدم صلاحياتك الهندسية الواسعة لتشخيص العيوب وإصلاحها واختبارها وتوثيقها، مع الحفاظ على البيانات والتغييرات السابقة.

إذا تعذر تنفيذ جزء بأمان، اعزله ووثّق السبب، ثم أكمل بقية الأعمال المستقلة.

**المخرج المطلوب:** عمل هندسي فعلي، واختبارات موثقة، وCheckpoint جديد، وتقرير إغلاق صادق وقابل للمراجعة.

**حدود الجولة غير قابلة للتجاوز:** لا staging، لا commit، لا push، لا Round 3، لا CapVer Ship، ولا تعديل لقواعد البيانات التشغيلية أو اعتماد لقرارات معمارية غير مصرح بها.
