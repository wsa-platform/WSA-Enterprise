<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 17531). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
لا توجد مجموعة جاهزة للالتزام الآن إلا أرشيف المهمة. إصلاح الواجهة لم يعد ضمن التعديلات المحلية، وبقية العمل ما زال مترابطًا أو يحتاج إصلاحًا أو تحققًا.

## 1. حالة Git الحالية

الفرع `phase-18-m18-ai-marketing-communications`.

| الطرف | القيمة |
|---|---|
| HEAD المحلي | `3eeba4012d40a3bddf9318470406b8c8000694b7` |
| عنوانه | `fix(frontend): accept crop profiles that have no presentation message` |
| HEAD البعيد بعد `git fetch` | `8dd6827f818aa8ec2e8d7e315dd26cdcfec34c10` |
| العلاقة | متقدم 1 ومتأخر 4 |

الالتزام المحلي `3eeba40` والأصل `8dd6827` يحملان رقعة الواجهة نفسها على تاريخين مختلفين. الأصل يحتوي أيضًا ثلاثة التزامات توثيق قبل تلك الرقعة. منطقة التجهيز فارغة. `git status --short` يضم 149 مسارًا: 43 معدّلًا و106 غير متعقب. `git diff --stat` للمتعقب: `+5931 / -231`. `git diff --check` خرج بالرمز 2 بسبب مسافات بيضاء في ADR-021 السطر 357 وADR-023 الأسطر 7948 و7992–7997، وسطر فارغ في نهاية ADR-023.

## 2 و3. المجموعات

**أرشيف المهمة — جاهزة للـ Commit**

غرضها مخزن ملفات هندسية منفصل عن LocalIndex وCapVer، مع ترشيح أسماء الأسرار وسلامة SHA-256.

- `backend/app/Services/Architecture/MissionArtifactArchive/` والملفات السبعة داخله
- `backend/app/Console/Commands/MissionArtifactArchiveCommand.php`
- `backend/config/mission_artifact_archive.php`
- `backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php`

لا تعتمد على الملفات المتعقبة المعدّلة. الاختبارات تكتب فقط تحت `storage/framework/testing/mission_artifact_archive_*`.

**البحث العلمي وLocalIndex والعقود والقدرات — تحتاج اختبارات أو تحققًا**

تشمل التعديلات المتعقبة في `backend/app/Services/Agriculture/` و`AgriculturalIntelligenceServiceProvider.php` و`ScientificSourceAdapterInterface.php` و`backend/config/database.php` و`agricultural_intelligence.php` والاختبارات المتعقبة المرافقة، مع المسارات غير المتعقبة `Search/LocalIndex/` و`Search/Contract/` و`Search/Family/` و`Search/Registry/` و`Search/Provenance/` و`Capability/CapVer/` و`Onboarding/` و`Membership/` و`Evaluation/` و`Projection/` وأوامر `scientific:*` و`tests/Unit` و`tests/Feature` و`tests/Fixtures` المرتبطة. الغرض طبقة مصادر علمية ومخزن SQLite تشغيلي. لا تُفصل عن بعضها لأن الملفات المعدّلة تستدعي الأصناف الجديدة. لم تُشغَّل اختباراتها في هذه الجولة لأن `LocalIndexSearchIntegrationTest` يلمس SQLite التشغيلية عند التشغيل داخل الحاوية.

**`backend/tests/Pgsql/` — تحتاج اختبارات أو تحققًا**

خمسة ملفات لاختبار مخزن PostgreSQL عبر `phpunit.pgsql.xml`. لم تُشغَّل حتى لا تتصل بـ `wsa_enterprise`.

**التوثيق المعماري — تحتاج إصلاحًا**

- `docs/architecture/ADR-023-scientific-source-expansion.md`: `+3602 / -1`، ومسافات بيضاء، وأقسام محلية بأرقام 8.27 وما بعدها تختلف عن أقسام 8.27–8.29 الموجودة أصلًا على الفرع البعيد.
- `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md`: إغلاق مرحلة 3 مع مسافة بيضاء، والاختبارات المذكورة فيه لم تُعد هنا.
- `docs/adr/README.md`: يربط سجلات JSON غير متعقبة.
- `docs/architecture/science-source-expansion/` و`BASELINE-RECONCILIATION.md` و`REMEDIATION-DEPENDENCY-MAP.md`: أرشيف تقارير كبير، وبعض JSON أكبر من 1 ميغابايت.

**الإعدادات — تحتاج إصلاحًا**

- `.gitignore`: يضيف `/backups/` مرتين.
- `backend/.env.example`: يخلط مفاتيح المخزن العلمي ومفاتيح الأمراض وFacebook، وتعليق SQLite يتعارض مع خيار التشغيل الحالي.

**`backend/composer.lock` — مستبعدة من Commit**

غير متعقب، ولا يظهر له التزام سابق، و`composer.json` غير معدّل.

**بيانات مؤقتة — مستبعدة من Commit**

- `backend/e2e-tmp/` و`e2e-tmp/capver_v22.json`: مخرجات تشغيل.
- `admin-mobile/e2e-tmp/`: فيها `login-body.json` و`login-out.json` وحقول كلمات سر أو رموز. القيم لم تُطبع.
- `tools/science-source-expansion/`: مولّدات تقارير، ليست مسار التشغيل.
- `backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php`: يبقى خارج الالتزام بقرار مستقل.

## 4. الأدلة للمجموعة الجاهزة

`phpunit --configuration phpunit.xml tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php` داخل `wsa-enterprise-backend-1`: 9 اختبارات، 40 تأكيدًا، OK، خروج 0. لم يُنفَّذ أمر الأرشيف على قاعدة التشغيل، لأن الاختبارات تغطي الأصناف عبر `MissionArtifactArchiveFactory::forTesting()`.

## 5. ما يمنع بقية المجموعات

البحث العلمي حزمة واحدة مع ترحيل ممنوع واختبار منسق غير معزول. توثيق ADR-023 يصطدم ترقيمه مع الأصل وفيه مسافات بيضاء. `.gitignore` و`.env.example` يحتوي كل منهما أكثر من تغيير واحد غير متجانس. `composer.lock` بلا تغيير مقابل في `composer.json`. ملفات E2E وتسجيل الدخول بيانات تشغيل أو أسرار.

## 6. المجموعة التالية الوحيدة

عنوان مقترح:

`feat(archive): add a mission artifact store separate from the scientific index`

لا عنوان آخر جاهز للتنفيذ الآن.

## 7. ما لم يتغير

لم يُنفَّذ `git add` ولا Commit ولا Push، ولم يُستخدم stash أو reset أو rebase أو merge. لم يُعدَّل ADR-023 ولا أي ملف مصدر.
