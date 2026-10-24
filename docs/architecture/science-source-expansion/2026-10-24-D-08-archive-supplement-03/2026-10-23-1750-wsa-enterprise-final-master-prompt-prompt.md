<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18208). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 5:50 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18209, 18212; it is archived once. -->

---
# WSA-Enterprise — FINAL MASTER PROMPT
## Add G2-26, Align All Current Source Matrices to 110, Verify Integrity, Run Safe Tests, and STOP Before Commit
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local path:** `G:\WSA-Enterprise`  
**Primary task:** Scientific Source Expansion — reconcile the approved 110-source membership with the current source matrices.  
**Execution mode:** Inspect → Verify → Implement → Test → Audit → Report.  
**Final gate:** STOP BEFORE `git add`, `git commit`, and `git push`.
---
# 1. ROLE AND EXECUTION STANDARD
Act as a senior software engineer and scientific-data architecture auditor working directly in the existing WSA-Enterprise repository.
Perform actual repository inspection, authorized file edits, and permitted tests. Do not merely propose a plan when the necessary evidence and safe execution path are available.
Follow these principles:
1. Preserve existing work, historical records, architectural decisions, and source provenance.
2. Base conclusions on files and command results actually inspected during this run.
3. Do not invent file contents, test results, hashes, approvals, or implementation status.
4. Do not repeat unrelated forensic audits unless a new finding makes them necessary.
5. Do not broaden the task into unrelated architecture, source expansion, refactoring, or cleanup.
6. Minimize unnecessary questions. Resolve matters from repository evidence whenever safe and possible.
7. Distinguish verified facts, historical context, inference, and unresolved issues.
8. Report actual results in one comprehensive Arabic engineering report.
9. When a safety or architectural gate prevents execution, explain the precise blocker rather than bypassing it.
10. The task is not complete merely because the source appears in a document. Membership, matrices, manifests, references, and applicable validations must be reconciled.
This prompt is the current execution instruction for this narrowly defined task. The historical instruction files listed below are not available as attachments and must not be assumed to have been read.
---
# 2. UNAVAILABLE REFERENCE FILES — EXPLICIT HANDLING
The following files and report are currently unavailable as supplied attachments:
- `MASTER-ENGINEERING-COMMON.md`
- `MASTER-PROMPT-ROUND1.md`
- The latest baseline report as a separate file.
**Do not block the task solely because these items are unavailable.**
Do not claim to have read them. Do not fabricate their contents, reconstruct them from their filenames, or ask the user to supply them before beginning repository inspection.
Instead:
1. Treat this prompt as the active execution instruction for this task.
2. Inspect the current repository and its actual architectural records.
3. Read the relevant existing ADRs, source-membership records, matrices, manifests, canonical records, and validation definitions from disk.
4. Use the historical baseline information in this prompt only as a lead for verification. It is not proof of the current Git state.
5. If an equivalent reference file actually exists in the repository, inspect it and record its real path. Do not assume it exists.
6. If a repository decision conflicts with this prompt, do not silently override that decision. Record the conflict and stop the conflicting operation if it cannot be resolved safely.
7. Do not interpret missing reference files as permission to alter unrelated files or architectural contracts.
Proceed with the authorized task if repository evidence establishes the target files, the approved membership, and a safe implementation path.
---
# 3. TASK OBJECTIVE — RECONCILE THE APPROVED 110-SOURCE MEMBERSHIP
The historical baseline reported the following discrepancy:
- Approved source-membership record: **110 unique source entries**.
- Three current source matrices: **109 seats each**.
- Missing entry identified by the historical report:
`G2-26 — Aquaculture, Fish and Fisheries`
The requested outcome is to add this source to the appropriate current source matrices and associated authoritative records so that the intended final membership is **110 unique source entries**, without losing or duplicating any of the existing 109 entries.
These values are initial hypotheses to verify against the current repository. Recalculate them before making changes.
## Required outcome
Subject to verification of the authoritative records:
1. Preserve all 109 previously included source entries.
2. Add `G2-26 — Aquaculture, Fish and Fisheries` to every applicable current matrix where the entry is missing.
3. Reconcile the current source matrices to 110 unique entries each, where those matrices are intended to represent the complete current membership.
4. Reconcile any applicable current canonical membership records, registries, manifests, or machine-readable inventories.
5. Update current aggregate counts and validation metadata that depend on the changed membership.
6. Preserve historical snapshots that intentionally document an earlier 109-entry state.
7. Validate uniqueness, identity, consistency, provenance, and relevant file-integrity records.
8. Run the safe, relevant tests supported by the actual environment.
9. Produce a complete report and STOP before any staging or commit operation.
Do not add unrelated sources. Do not rename existing source identifiers unless an authoritative record explicitly requires it and the change is within scope.
---
# 4. CURRENT BASELINE MUST BE RE-ESTABLISHED
Before modifying anything, inspect the actual state of the repository.
Record at minimum:
- Current working directory.
- Current branch.
- Current `HEAD` commit.
- Current upstream, if configured.
- Whether the working tree has tracked modifications.
- Whether untracked files or directories exist.
- Whether the Git index contains staged changes.
- Whether the current branch and upstream differ, where this can be checked safely.
- Whether the target files already contain local edits.
- Whether any relevant files are ignored by Git.
- Whether there are merge, rebase, cherry-pick, or other repository operations in progress.
Use read-only commands first, such as:
- `git status --short --branch`
- `git branch --show-current`
- `git rev-parse HEAD`
- `git diff --stat`
- `git diff --name-status`
- `git diff --cached --stat`
- `git ls-files`
- `git check-ignore -v` for relevant paths when necessary.
Use additional commands only when relevant and safe.
Do not assume that the historical `HEAD` or counts below still apply.
Historical information to verify, not blindly trust:
- Previously reported `HEAD`: `fb60b60746ed487e2bfa04e3d335827d91cda446`.
- Previously reported upstream: `origin/phase-18-m18-ai-marketing-communications`.
- Previously reported branch: `phase-18-m18-ai-marketing-communications`.
- Previously reported working-tree inventory: 51 tracked modified files, 117 untracked entries in porcelain output, and 1,444 files after expanding directories.
- Previously reported zero deletions and zero staged changes.
These numbers are historical only. Record the current values obtained from actual commands.
## Protection rule
The existing working tree contains unrelated local work and unresolved files according to the historical report.
Do not reset, clean, discard, overwrite, or broadly reformat existing work.
Do not use `git add .` or `git add -A`.
Do not include unrelated files in this task.
If a target file contains pre-existing local modifications, inspect its diff and preserve all unrelated content. Do not overwrite the whole file from an outdated template.
If a safe, minimal edit cannot be separated from unrelated local work, stop editing that file and report the blocker.
---
# 5. AUTHORITATIVE RECORDS AND TARGET DISCOVERY
Locate the actual files that define the source membership and current source matrices.
Search for records relevant to:
- The 110-entry source-membership inventory.
- The three current source matrices reported as having 109 seats.
- The canonical source inventory or canonical membership definition.
- Source Registry and Capability Contract records, if applicable to membership validation.
- Inventory or entry-count reports such as IC-ER.
- Relevant manifests and SHA-256 records.
- Existing tests and validators that enforce source membership or matrix integrity.
- The authoritative definition and metadata of `G2-26`.
The historical report described a difference between membership inventory 109 and membership inventory 110, where the 110-entry record included `G2-26 — Aquaculture, Fish and Fisheries`.
It also reported that the three matrices, including a version dated 2026-10-12, contained 109 seats and omitted `G2-26`, and that IC-ER reported 109 records.
Verify these claims against the actual files.
## Authority hierarchy
Determine which files are authoritative from their contents, architecture documentation, and existing project conventions.
Do not assume that the newest-looking filename is authoritative.
Do not use an old report as a substitute for the current source-of-truth record.
Do not modify historical reports simply to make their counts agree with the current inventory.
A historical snapshot that correctly records 109 entries at an earlier point in time must remain historically accurate.
Only modify a historical document if the architecture explicitly treats it as a living, current-state record and the edit is justified by that contract.
## Required target inventory
Before editing, prepare an internal target inventory identifying:
- Exact file path.
- Record type.
- Whether it is authoritative, current, derived, or historical.
- Current source count.
- Whether `G2-26` is present.
- Whether the file needs modification.
- Why the proposed change is safe.
Do not create a new source matrix or registry merely to avoid updating the existing authoritative one.
Do not modify files solely because their names contain `109`, `110`, `matrix`, `manifest`, or `source`.
---
# 6. VERIFY THE SOURCE IDENTITY AND METADATA
Before inserting `G2-26`, inspect its authoritative record.
Confirm, where applicable:
- Exact identifier: `G2-26`.
- Exact source name: `Aquaculture, Fish and Fisheries`.
- Its source group and intended position.
- Whether it represents one source entry or multiple underlying providers.
- Required metadata fields and schema.
- Consistency with existing source-entry conventions.
- Any source-specific capability or availability fields required by the current architecture.
- Whether the entry is already present under an alternative identifier or duplicate name.
Do not invent provider URLs, APIs, authentication details, licenses, availability claims, capabilities, or access methods.
This task is about reconciling membership and its current matrices. It is not authorization to implement a new API adapter or claim that an external source is operational.
If metadata required for a valid membership entry is missing, reuse only verified metadata from the authoritative existing record. If a critical required field cannot be established, stop that specific change and report the missing evidence.
---
# 7. IMPLEMENTATION RULES
Once the authoritative target files and the intended membership are verified, make the smallest safe changes necessary.
## 7.1 Preserve all existing entries
Compare the pre-edit membership with the post-edit membership.
The 109 existing entries must remain present with their identifiers and required metadata intact.
Do not reconstruct an entire matrix from a partial report or manually retype all entries.
Prefer a minimal, localized insertion that follows the file's existing ordering and formatting conventions.
## 7.2 Add the missing source exactly once
Add `G2-26 — Aquaculture, Fish and Fisheries` to each applicable current matrix where it is missing.
Ensure:
- The identifier appears exactly once in each complete current matrix.
- The new entry is in the appropriate group and location.
- No existing entry is overwritten.
- No duplicate source is introduced under a slightly different spelling or identifier.
- Any required matrix totals or group counts are updated consistently.
If the source is already present in a target, do not add it again. Verify whether its current representation is valid and make only necessary corrections.
## 7.3 Reconcile current canonical records and counts
Inspect all current authoritative and derived records that depend on the membership.
Update only those that are supposed to represent the current complete membership, including applicable:
- Canonical membership inventory.
- Current source matrices.
- Current inventory totals.
- Current validation metadata.
- Machine-readable manifests.
- Membership-derived counts such as IC-ER, if its contract defines it as a current-state aggregate.
Do not mechanically change every occurrence of `109` to `110`.
A historical report can legitimately retain 109.
A test fixture can legitimately use 109 if it intentionally represents a historical or partial dataset.
Determine the semantics of each occurrence before editing.
## 7.4 Source Registry and Capability Contract
If a Source Registry or Capability Contract exists and membership changes are required to be represented there, inspect its existing contract.
Do not expand this task into a general capability-system redesign.
Do not claim that `G2-26` is executable, searchable, or API-integrated merely because it is listed in a membership matrix.
If registry membership is required by an existing architectural rule, update it only when the correct representation is supported by existing conventions and verified metadata.
If the contract is ambiguous or the necessary source metadata is unavailable, report the blocker rather than inventing a capability.
## 7.5 Formatting and provenance
Preserve each target file's existing:
- Encoding.
- Line endings, including CRLF where present.
- JSON/YAML/schema structure.
- Ordering conventions.
- Comments and provenance notes.
- Historical content and surrounding records.
Avoid broad formatters or whole-file rewrites.
For JSON or other machine-readable files, validate syntax after editing.
For files with checksums or manifests, update them only when the manifest contract requires it, and only after all underlying file changes are finalized.
Never copy a stale checksum into a newly updated manifest.
---
# 8. MANIFESTS AND SHA-256 INTEGRITY
The historical baseline said that several archived manifests and membership files had SHA-256 values that had not been recalculated during the earlier inspection.
Treat those hashes as unverified until actually recalculated.
For every manifest or checksum file that directly covers a changed current-state file:
1. Determine what the manifest covers.
2. Confirm whether its hash values are expected to match current file contents.
3. Recalculate hashes using the project's existing conventions or a verified SHA-256 implementation.
4. Update the manifest only if the architecture requires it and the change is within this task.
5. Recalculate or independently verify the final values.
6. Record which paths were hashed and which values were verified.
Do not modify D-08 historical archive manifests merely because they contain a historical inventory or a filename also used elsewhere.
The historical baseline described:
- A D-08 archive of 436 files: 218 prompts, 217 reports, and one manifest.
- An additional 01 archive of 95 files.
- An additional 02 archive of 12 files.
- `MANIFEST.md` as the only repeated filename between those archives.
These counts must not be assumed to be current or complete. Recheck only if archive integrity is directly relevant to the affected membership records.
Do not rewrite archived evidence, regenerate unrelated archives, or change historical checksums without an explicit architectural reason.
If a hash cannot be verified, mark it `NOT VERIFIED` rather than claiming success.
---
# 9. SAFE TESTING AND VALIDATION
After implementation, perform all relevant safe validations that are supported by the environment and authorized by the repository's actual instructions.
Do not claim a test passed unless it was actually executed and its result inspected.
## 9.1 Mandatory membership checks
Verify the post-edit state programmatically where practical.
For each authoritative current membership record and each complete current matrix in scope, verify:
1. The total number of entries is 110.
2. The number of unique identifiers is 110.
3. No identifier is duplicated.
4. All 109 previously approved entries remain present.
5. `G2-26` appears exactly once.
6. The source name matches the authoritative record.
7. Required fields are present.
8. Group totals add up to the complete membership.
9. Cross-file identifiers and names are consistent.
10. No unrelated source has been added, removed, renamed, or silently modified.
Where appropriate, calculate the set difference before and after the change:
- Previously present entries missing afterward: must be zero.
- Newly introduced membership entries: must be limited to the authorized `G2-26` entry, except for explicitly justified metadata changes.
- Unexpected differences: must be investigated and reported.
Do not rely on visual inspection alone when a script or existing validator can verify uniqueness and counts safely.
## 9.2 Existing project tests
Inspect the available test commands and relevant tests before running them.
Run the smallest relevant tests first, followed by broader relevant tests only when they are safe and useful.
Possible checks, depending on the actual repository, include:
- Existing source-inventory validators.
- Matrix consistency checks.
- JSON/YAML/schema validation.
- Tests that enforce source membership and unique identifiers.
- Existing unit tests for the affected registry or inventory code.
- Existing targeted test scripts.
Use only commands and test paths that actually exist.
Do not invent test names or assert that a command is supported without checking.
## 9.3 Environment limitations
The historical report noted that PHP and PostgreSQL might not be available locally and that Docker was prohibited in the previous execution context.
Verify the actual current environment before testing. Do not assume those limitations have disappeared or remain in force without checking.
Do not install dependencies, start Docker, launch services, or alter environment configuration just to make tests run unless the existing project instructions explicitly authorize the action.
If a test requires unavailable PHP, PostgreSQL, Docker, credentials, network access, or another dependency, record it as `BLOCKED` or `NOT RUN`, with the reason.
A static check or custom read-only validation does not substitute for an unavailable integration test. Clearly distinguish the two.
## 9.4 Database protection
Do not write to operational or protected databases.
Do not run migrations against a real project database.
Do not modify SQLite operational data or use a database file simply because it is present.
Before running a test that might write to a database, establish that it uses an isolated test database and that its write effects are safe.
If isolation cannot be verified, do not run that test.
---
# 10. FILES AND DIRECTORIES THAT MUST NOT BE CLEANED UP OR INCLUDED AUTOMATICALLY
The historical report identified the following areas as unrelated, sensitive, or unresolved. Recheck the current state if necessary, but do not modify or delete them as part of this task:
- `backend/storage/framework/recovery/`
- `backend/e2e-tmp/`
- `admin-mobile/e2e-tmp/`
- `e2e-tmp/`
- `tools/`
- `backend/composer.lock`
- Operational or ignored SQLite databases.
- Unrelated CapVer files, services, migrations, tests, and recovery records.
- Unrelated search, viewer, adapter, and scientific-source integration work.
Specific historical findings included:
- `backend/storage/framework/recovery/capver-historical-recovery.json` and its `.sha256` file.
- A large set of files in `backend/e2e-tmp/` and `admin-mobile/e2e-tmp/`.
- `e2e-tmp/capver_v22.json`.
- Three Python scripts under `tools/science-source-expansion/`.
- An unresolved `_extract_api_seats.py` whose full body had not been reviewed.
- Untracked `backend/composer.lock`.
These are historical observations, not proof of current contents. Do not delete, move, execute, or modify these items simply because they are listed here.
If an item becomes directly relevant, inspect it safely and explain why it is needed before acting.
Do not add unrelated reports, generated files, temporary files, databases, caches, archives, or test outputs to Git.
---
# 11. ADR-023 AND MIXED LOCAL CHANGES
The historical baseline reported local changes to:
`docs/architecture/ADR-023-scientific-source-expansion.md`
The reported diff was approximately `+231/-0`, with sections §§8.66–8.71 related to CapVer.
Those changes were reported as pre-existing local work and not part of the source-matrix task.
**Do not assume this file is safe to rewrite or commit.**
Before editing ADR-023:
1. Inspect its current Git diff.
2. Determine whether its existing local changes are unrelated to `G2-26`.
3. Identify the relevant architectural section and its historical/current status.
4. Preserve all existing content.
5. Do not delete, rewrite, reorder, or normalize unrelated CapVer records.
6. Do not update ADR-023 merely to make the report look complete.
If a specific ADR update is necessary to document the current membership and can be isolated safely, make only the minimal change supported by the existing ADR structure.
If it cannot be separated safely from pre-existing changes, leave the file untouched and report that the ADR update remains pending.
Do not treat an unresolved ADR edit as permission to abandon safe updates to other authoritative membership files.
---
# 12. SCOPE CONTROL
This task is limited to:
- Verifying the approved 110-source membership.
- Adding `G2-26` where missing.
- Reconciling the applicable current matrices and authoritative membership records.
- Updating necessary current counts and integrity metadata.
- Running safe, relevant validations and tests.
- Reporting evidence and stopping before commit.
Do not:
- Start a new source-expansion phase.
- Implement new external API adapters.
- Redesign the scientific search pipeline.
- Change `ScientificSearchQueryBuilder`, `AgriculturalEntityCatalog`, or other unrelated search components.
- Perform general CapVer cleanup or remediation.
- Modify unrelated migrations, services, tests, or application behavior.
- Reconcile every historical occurrence of 109 across the repository without determining its meaning.
- Delete historical reports or evidence.
- Commit or push.
- Hide a blocker by changing the acceptance criteria.
If a dependency outside this scope prevents the requested change, identify the dependency and stop only the blocked portion when the remaining work can safely proceed.
---
# 13. FINAL GIT AUDIT — REQUIRED
After the edits and tests, inspect the repository again.
Run appropriate read-only checks such as:
- `git status --short --branch`
- `git diff --stat`
- `git diff --name-status`
- `git diff --check`
- `git diff -- <each intended target file>`
- `git diff --cached --stat`
Inspect every changed file's diff and confirm:
1. Only intended task-related edits were made.
2. Existing unrelated changes remain intact.
3. No accidental file deletion occurred.
4. No generated test artifacts or operational data were introduced.
5. No file was staged.
6. No changes were committed.
7. No push was performed.
If a new file is necessary, leave it untracked and identify it explicitly in the report.
Do not stage it.
Do not alter Git configuration or rewrite branch history.
## Absolute prohibition
**Do not execute any of the following during this task:**
- `git add`
- `git commit`
- `git push`
- `git push --force`
- `git reset --hard`
- `git clean -fd`
Do not use an equivalent command or scripted action to achieve the same prohibited result.
Even if all validations pass, stop before staging and wait for the user's review and explicit authorization.
---
# 14. ACCEPTANCE CRITERIA
The implementation can be reported as fully verified only if the applicable conditions below are demonstrated with actual evidence.
## A. Membership
- The authoritative current source membership contains 110 unique entries.
- Each complete current source matrix in scope contains 110 unique entries.
- `G2-26 — Aquaculture, Fish and Fisheries` appears exactly once in each applicable target.
- All 109 previously approved entries remain present.
- No unintended duplicates or omissions exist.
## B. Cross-file consistency
- Current membership records, matrices, canonical records, and required aggregate counts agree.
- Group totals are internally consistent.
- Required source metadata is valid and consistent with the authoritative record.
- Historical snapshots remain historically accurate.
- Source Registry or Capability Contract changes, if required, follow the actual architecture and verified metadata.
## C. Integrity
- Relevant manifests and checksums have been updated only where required.
- Each reported verified hash has actually been calculated and checked.
- Machine-readable target files pass their applicable syntax or schema validation.
- No unrelated historical archives or records have been altered.
## D. Tests
- Applicable safe tests have actually been run.
- Each test's real result is documented.
- Unavailable or unsafe tests are explicitly identified as `NOT RUN` or `BLOCKED`.
- No claim of full integration validation is made if the environment prevented it.
## E. Repository safety
- Pre-existing unrelated changes are preserved.
- No unrelated files were modified.
- Nothing was staged.
- No commit or push was performed.
If some criteria cannot be verified, do not mark the task fully complete.
Use one of these overall statuses:
- `VERIFIED` — all applicable acceptance criteria have been demonstrated.
- `PARTIAL` — the implementation is made, but one or more criteria remain unverified or blocked.
- `BLOCKED` — safe implementation could not proceed because of a material blocker.
A `VERIFIED` result does not authorize a commit.
---
# 15. REQUIRED FINAL REPORT — ARABIC ONLY
Produce one comprehensive Arabic engineering report, with no duplicate analyses or separate partial reports.
Use this structure:
## 1. الملخص التنفيذي
- الحالة النهائية: `VERIFIED` أو `PARTIAL` أو `BLOCKED`.
- هل أضيف `G2-26` بنجاح؟
- هل أصبحت العضوية الحالية 110 مصادر فريدة؟
- هل أصبحت المصفوفات المستهدفة 110 مقاعد فريدة؟
- هل اكتمل التحقق والاختبار؟
- هل توقفت قبل `commit` و`push`؟
## 2. خط الأساس الفعلي
- المسار.
- الفرع.
- `HEAD`.
- حالة upstream، إن أمكن.
- عدد الملفات المتتبعة المعدلة.
- عدد الملفات غير المتتبعة، مع توضيح طريقة العد.
- عدد الملفات في Staging.
- أي عمليات Git جارية.
- توضيح الاختلافات عن خط الأساس التاريخي.
## 3. سجل العضوية والمصفوفات
جدول يتضمن لكل سجل:
- المسار.
- الدور المعماري.
- العدد قبل التعديل.
- العدد بعد التعديل.
- عدد المعرفات الفريدة.
- وجود `G2-26` قبل وبعد.
- نتيجة التحقق.
## 4. الملفات التي تغيرت
جدول بالمسارات الدقيقة:
- سبب التعديل.
- التغيير الفعلي.
- علاقته المباشرة بالمهمة.
- أي تغييرات سابقة محلية تم الحفاظ عليها.
اذكر صراحة الملفات التي لم تعدلها بسبب اختلاط تغييراتها السابقة أو تعذر فصلها بأمان.
## 5. سلامة العضوية
- هل بقيت جميع المصادر الـ109 السابقة؟
- هل أضيف `G2-26` مرة واحدة؟
- هل توجد معرفات مكررة أو مصادر مفقودة؟
- نتيجة مقارنة المجموعات قبل/بعد.
- أي اختلافات غير محلولة.
## 6. manifests وSHA-256
- الملفات التي تم التحقق منها.
- الملفات التي أعيد حساب بصماتها.
- النتائج الفعلية.
- ما بقي `NOT VERIFIED` وسبب ذلك.
## 7. الاختبارات
جدول يذكر:
- اسم الاختبار أو الأمر الحقيقي.
- سبب تشغيله.
- هل نُفذ فعلًا؟
- النتيجة الفعلية.
- أي قيود بيئية أو حماية من الكتابة.
افصل بين الاختبارات الناجحة، والفاشلة، والمحجوبة، وغير المنفذة.
## 8. مراجعة Git النهائية
- ملخص `git diff`.
- نتيجة `git diff --check`.
- تأكيد عدم وجود staging.
- تأكيد عدم تنفيذ `commit`.
- تأكيد عدم تنفيذ `push`.
- أي ملفات غير متتبعة بقيت خارج النطاق.
## 9. المشكلات المتبقية والمخاطر
لكل نقطة:
- وصف واضح.
- دليل.
- الأثر.
- ما الذي يلزم لحلها.
## 10. قرار التوقف
اكتب بوضوح أن التنفيذ توقف قبل `git add` و`git commit` و`git push`، وأنه ينتظر مراجعة المستخدم قبل أي التزام أو رفع.
لا تستخدم `VERIFIED` إذا كانت الأدلة لا تدعمه.
---
# 16. FINAL EXECUTION INSTRUCTION
ابدأ الآن بفحص الحالة الفعلية للمستودع، ثم حدد الملفات المستهدفة من الأدلة المعمارية الحقيقية، ونفّذ أقل تعديل آمن لتحقيق العضوية الحالية المستهدفة ذات 110 مصادر، ثم نفّذ الاختبارات المسموح بها وراجع الفروقات.
لا تنتظر الملفات المرجعية غير المتاحة لمجرد أنها غير متاحة.
لا تتجاوز أي قرار معماري معتمد.
لا تختلق نتائج.
لا تجرِ أي تعديل خارج النطاق.
**توقف إلزاميًا قبل `git add` و`git commit` و`git push`.**
بعد ذلك، أرسل تقريرًا عربيًا شاملًا واحدًا، واستعد لانتظار مراجعة المستخدم. لا تتابع تلقائيًا إلى الالتزام أو الرفع مهما كانت نتائج الاختبارات.
