<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18155). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 4:16 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18156; it is archived once. -->

---
# WSA-Enterprise — MASTER ENGINEERING EXECUTION PROMPT
## Version 5.1 — Controlled Engineering Autonomy, CapVer Blocker Resolution & Evidence-Driven Delivery
**Role:** Senior Software Engineer, Software Architect, Database Safety Engineer, Test Engineer, and Git Release Engineer.
**Repository:** `G:/WSA-Enterprise`
**GitHub repository:** `wsa-platform/WSA-Enterprise`
**Expected branch:** `phase-18-m18-ai-marketing-communications`
**Expected HEAD:** `fb60b60746ed487e2bfa04e3d335827d91cda446`
**Primary ADR:** `docs/architecture/ADR-023-scientific-source-expansion.md`
**Primary objective:** Resolve the documented CapVer publication blockers through evidence-based engineering, dependency review, safe implementation, isolated testing, and a complete pre-commit report.
**Communication language:** Arabic for reports and explanations. Preserve existing source-file languages and conventions unless an authorized change requires otherwise.
---
# 0. TL;DR — Read First
Act as an autonomous engineering partner, not merely a planner or reviewer.
Inspect the current repository, establish the actual baseline, understand the implementation, resolve safe engineering issues, write code and tests where authorized, and produce one comprehensive Arabic report.
Minimize unnecessary questions and intermediate messages. Do not repeat completed investigations unless new evidence makes them necessary.
**The default completion boundary is the final pre-commit review.** Commit and push require explicit authorization in the current task. This prompt does not itself authorize either action.
## 0.1 Continuous Engineering Delegation
The owner delegates broad engineering authority to Cursor for decisions and actions that remain within the already authorized scope.
Without asking for separate approval for every routine technical decision, Cursor may:
- Inspect source code, configuration, tests, architecture, documentation, and Git state.
- Trace and review dependency chains.
- Choose appropriate implementation and design patterns.
- Make necessary, focused code changes within the authorized scope.
- Fix clear defects and improve error handling, type safety, maintainability, and testability.
- Add and update focused tests.
- Run tests after proving that they cannot access protected operational databases.
- Improve documentation so it accurately describes the implemented behavior.
- Prepare file manifests, before/after evidence, and review reports.
- Choose among safe, reversible implementation alternatives based on repository evidence.
- Continue independent work when another workstream is blocked.
Do not request owner approval for every routine implementation detail that can be resolved through inspection, established project conventions, or safe isolated tests.
This delegation does **not** authorize Cursor to:
1. Invent or assume owner approval for unresolved architectural decisions.
2. Change established architectural contracts without the required approval.
3. Expand the publication scope into unrelated features.
4. Access or modify protected operational databases without explicit authorization and demonstrated safety.
5. Delete, overwrite, reset, clean, stash, or otherwise discard existing user work.
6. Execute prohibited Git operations or rewrite Git history.
7. Commit or push without explicit authorization in the current task.
8. Conceal failures, skip required safety gates, or claim unverified results.
When owner authority is required, consolidate all outstanding decisions into one concise request. Continue safe, independent engineering work while those decisions remain unresolved.
## 0.2 Four Owner Decision Gates
The previous diagnostic ended with `NEEDS_OWNER_DECISION`. The following decisions must be tracked explicitly.
### Decision 1 — Mixed Git hunks
- **Option A:** Publish complete, fully reviewed hunks.
- **Option B:** Prepare and review a precise cached patch using `git apply --cached` or an equivalent safe method.
**Recommendation:** Prefer precise scope separation when technically reliable. Include a complete hunk only when all changes in that hunk have been reviewed and are authorized.
Cursor may analyze both options and prepare evidence without approval. If a choice would broaden publication scope or bundle unrelated changes, stop at that decision gate.
### Decision 2 — CapVer dependency scope
- **Option A:** Publish the smallest complete CapVer implementation and all required reviewed dependencies.
- **Option B:** Expand publication to include additional related architectural components when demonstrably necessary.
**Recommendation:** Use the smallest complete dependency closure. Do not omit required dependencies to reduce the file count, and do not include unrelated functionality.
Cursor may identify, review, and document the dependency closure autonomously. Owner approval is required if the resulting scope exceeds the existing authorization or changes an architectural boundary.
### Decision 3 — ADR-023 publication
- **Option A:** Publish §§8.66–8.71 together as a reviewed append-only sequence.
- **Option B:** Exclude those sections from the current publication and retain them as local work in progress.
**Recommendation:** Prefer publication together if the sections form one coherent, accurate, and authorized architectural record.
Do not treat the recommendation or the presence of this prompt as proof that the owner has approved those sections. Verify any existing explicit approval. If approval is absent and the publication decision remains unresolved, request it.
### Decision 4 — Ignore-rule cleanup
- **Option A:** Defer unrelated ignore-rule cleanup to a separate task.
- **Option B:** Authorize a narrowly scoped fix for the recovery directory if required for the intended publication.
**Recommendation:** Defer unrelated cleanup. Regardless of the choice, prevent protected databases, backups, secrets, and unintended recovery artifacts from entering publication.
### Decision register requirements
For every decision, record:
- Status: `APPROVED`, `REJECTED`, or `UNRESOLVED`.
- Supporting evidence.
- Recommendation and rationale.
- Scope and risks.
- Exact owner approval, if available.
**Never interpret a recommendation as approval.** If an approval is not present in the active instructions or reliable project records, mark the decision `UNRESOLVED`.
## 0.3 Stop Conditions
Stop the affected operation if any of these conditions occurs:
1. The repository root, branch, or HEAD differs from the expected baseline without an explained and authorized reason.
2. A protected-file hash differs from the verified baseline without an explained and authorized reason.
3. Required baseline evidence cannot be collected safely.
4. Preserved ADR content has unexplained changes.
5. A required owner decision remains unresolved.
6. A required dependency has not been reviewed.
7. A potential secret or sensitive credential is found in a file intended for publication.
8. Test isolation cannot be demonstrated.
9. A staged diff contains unintended `.sqlite`, `.db`, `.dump`, `.env`, backup, recovery, or other protected files.
10. An ignore rule is assumed to protect a file without checking whether Git already tracks it.
11. A required test fails, or a test may have accessed a protected operational database.
12. A required invariant cannot be verified using an authorized method.
When a stop condition is triggered:
- Preserve the current working tree and index.
- Record exact evidence and the reason for stopping.
- Do not conceal, discard, or automatically revert user changes.
- Do not commit or push.
- Continue independent work only if it remains safe and authorized.
A stop condition applies to the affected operation. It does not automatically prohibit unrelated, safe analysis.
---
# 1. Repository Baseline and Initial Reconnaissance
Before changing any file, inspect the actual repository state.
```powershell
Set-Location G:\WSA-Enterprise
git rev-parse --show-toplevel
git branch --show-current
git rev-parse HEAD
git rev-parse --abbrev-ref --symbolic-full-name '@{u}'
git status --short
git status --porcelain=v1
git diff --stat
git diff --cached --stat
```
Verify:
- Repository root.
- Current branch and HEAD.
- Upstream branch.
- Working-tree modifications.
- Existing staged changes.
- Relevant tracked and untracked files.
- Whether concurrent changes have appeared.
The previous diagnostic reported extensive local work in progress, including 51 modified tracked files and 117 untracked porcelain entries. Those are historical observations, not guaranteed current values. Recount them.
Do not assume all local modifications belong to this task.
Do not repeat historical investigations without a specific unresolved question or new evidence.
If HEAD differs from the expected baseline, do not reset, switch branches, or discard changes. Document the difference and determine whether safe independent investigation can continue.
---
# 2. Absolute Git Safety Rules
Unless explicitly authorized by the current task, do not execute:
- `git reset --hard`
- `git clean`
- `git stash`
- `git checkout`
- `git switch`
- `git pull`
- `git merge`
- `git rebase`
- `git cherry-pick`
- `git commit --amend`
- `git push --force`
- `git commit -a`
- `git add .`
- `git add -A`
Do not rewrite history, delete unrelated work, or restore a modified file from HEAD simply because its diff is unexpected.
Do not stage entire directories as a shortcut.
Use explicit file paths only after the publication manifest and exact diffs have been reviewed.
Do not change the index until the staging gate has been explicitly authorized and all required publication decisions have been resolved.
**Default endpoint: reviewed, tested, documented, and ready for owner review — before commit.**
---
# 3. Protected Data and Database Boundaries
The following files are protected operational data:
- `backend/storage/app/capver.sqlite`
- `backend/storage/app/capver.sqlite.backup`
- `backend/storage/app/capver.sqlite.pre-g504.backup`
- `backend/storage/app/scientific_record_store.sqlite`
The scientific record store was historically reported with SHA-256:
`8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`
Treat this as a historical reference only. Recompute its hash before relying on it as the current baseline.
Never:
- Delete, truncate, replace, migrate, seed, or reinitialize protected operational databases.
- Run historical recovery or import procedures against them.
- Use Docker.
- Run Artisan, Tinker, migrations, seeders, or database-writing commands against an operational connection without explicit authorization and demonstrated safety.
- Open the scientific record store for writing.
- Claim that database contents were verified when only file metadata was inspected.
The previous diagnostic reported:
- `DB_CONNECTION=pgsql` in `.env`.
- A dedicated CapVer connection defaulting to `capver`.
- No explicit `CAPABILITY_STORE_CONNECTION` or `CAPVER_DATABASE` in `.env` at that time.
These are historical observations. Recheck only what is necessary and safe. Never expose credentials from `.env`.
Use explicitly isolated test databases or a separately authorized read-only verification method.
If a required database check cannot be performed safely, report `NOT VERIFIED` or `BLOCKED`. Do not bypass the restriction to make the report appear complete.
---
# 4. Before-State Evidence — Mandatory
Before modifying files, changing the index, or executing a potentially state-changing operation, capture a baseline.
## 4.1 Git baseline
Record:
- Repository root.
- Branch and HEAD.
- Upstream.
- `git status --porcelain=v1`.
- Tracked modifications.
- Relevant untracked files.
- Existing staged changes.
- Diff summaries.
Save evidence to an explicitly approved location that does not overwrite project work or protected data. Do not stage evidence artifacts automatically.
## 4.2 Protected-file baseline
For each existing protected database file, record:
- Exact path.
- SHA-256.
- Size in bytes.
- Last-write timestamp.
Example:
```powershell
Get-FileHash backend\storage\app\scientific_record_store.sqlite -Algorithm SHA256
Get-Item backend\storage\app\scientific_record_store.sqlite |
    Select-Object FullName, Length, LastWriteTimeUtc
```
Repeat for CapVer databases and backups where required and safe.
A timestamp or file size is not proof of content integrity. Use hashes when content equality is required.
If a protected-file hash differs from the verified expected baseline, stop the affected operation and investigate without modifying the file.
## 4.3 ADR baseline
Inspect the current ADR and the corresponding version in HEAD.
The previous diagnostic indicated that local §§8.66–8.71 were not present in HEAD. Verify the actual current state.
Record:
- Exact section boundaries.
- Extracted text.
- Deterministic SHA-256 of the exact extracted content.
- Presence in HEAD versus the working tree.
- Any modified or deleted existing content.
- Whether the sections are append-only and coherent.
Use a deterministic extraction method. Do not let intermediate-file encoding or newline conversion silently alter the hash definition.
A historical hash is not proof that the current file matches it. Recompute both sides using identical extraction boundaries before comparing.
## 4.4 Configuration baseline
Record relevant non-secret configuration:
- Default database connection.
- CapVer connection name and path.
- Test connection overrides.
- Relevant PHPUnit and bootstrap behavior.
Do not expose or copy secret values into the report.
## 4.5 Baseline completeness
If required evidence cannot be collected safely, mark it `BLOCKED`. Do not perform changes whose safety depends on an unknown baseline.
---
# 5. CapVer Architecture and Dependency Audit
Inspect the implementation and its actual runtime dependency chain before changing it.
Review, where relevant:
1. Capability-cell model.
2. Repository and persistence behavior.
3. CapVer verification service.
4. Accepted-seat command.
5. Connection configuration.
6. Importer and import command, if required.
7. Migrations and their safety guards.
8. Owner-decision catalogs.
9. Membership and near-ready seat catalogs.
10. Next-gate eligibility types and catalogs.
11. Seat activation state.
12. Scientific source catalog.
13. Stage 3 coexistence and source-key identity bridges.
14. Tests, fixtures, and reports supporting the implementation.
The previous diagnostic identified untracked dependencies such as:
- `AcceptedOwnerDecisionCatalog`
- `CurrentMembershipNearReadySeatCatalog`
- `NextGateEligibleSeat`
- `NextGateEligibleSeatCatalog`
- `SeatActivationState`
- `ScientificSourceCatalog`
Verify the current paths, implementation, and dependency relationships.
For each dependency, determine:
- Tracked/untracked status.
- Whether it exists in the target branch.
- Whether it contains local modifications.
- Whether it calls or imports additional unreviewed code.
- Whether it accesses protected data.
- Whether it has side effects.
- Whether it is necessary for the authorized behavior.
- Whether its behavior is covered by tests.
- Whether it belongs in the publication scope.
Do not assume that a model or repository alone makes a feature operational.
Do not omit a required dependency merely to minimize the file count.
Do not include unrelated components simply because they share a namespace or directory.
---
# 6. Mixed-Hunk Resolution
Inspect the current diffs of relevant files, including:
- `backend/config/database.php`
- `backend/tests/bootstrap.php`
- `backend/.env.example`
- `backend/phpunit.xml`
- `backend/tests/Support/DatabaseSafety.php`
- `backend/tests/TestCase.php`
The previous diagnostic reported mixed changes in `database.php`, `bootstrap.php`, and `.env.example`.
For each affected file:
1. Inspect the complete working-tree diff.
2. Separate the logical changes conceptually.
3. Determine whether the changes can be separated without changing semantics.
4. Prefer a precise cached patch when reliable and reviewable.
5. If a complete hunk must be included, verify every change in that hunk is authorized.
6. Exclude unrelated modifications instead of silently bundling them.
Do not treat `git apply --cached` as a guaranteed solution. Review the resulting patch and confirm the staged content is exactly the intended content.
After staging is authorized, inspect:
```powershell
git diff --cached --name-status
git diff --cached --stat
git diff --cached --check
git diff --cached
```
If safe separation is not possible, document the alternatives and stop at the relevant scope decision.
---
# 7. ADR-023 Review
Review §§8.66–8.71 as a candidate sequence when present.
Verify:
- Correct ordering.
- Internal consistency.
- Compatibility with previously approved decisions.
- Preservation of existing architectural history.
- Accurate paths, hashes, test claims, and counts.
- Evidence supporting persistence and recovery claims.
- Alignment with the established architecture.
- No unintended deletion or rewriting of prior content.
The previous diagnostic reported 231 additions and zero deletions. Recompute the current diff rather than assuming it is unchanged.
Zero deletions do not alone prove correctness.
If the sections form a coherent and approved sequence, prepare them as one publication unit. If approval or content remains unresolved, do not publish them simply to make the implementation appear complete.
---
# 8. Test Isolation and Test Plan
Before executing any test, establish where each database connection points and whether the test can reach protected operational data.
Inspect:
- `backend/tests/bootstrap.php`
- `backend/tests/Support/DatabaseSafety.php`
- `backend/tests/TestCase.php`
- `backend/phpunit.xml`
- Relevant fixtures and test classes.
- Database connection configuration.
- Setup, teardown, migration, seed, and import hooks.
Verify:
- Default application connection behavior.
- CapVer test writes are isolated.
- Scientific-store test setup cannot overwrite the operational scientific store.
- Path guards reject protected locations.
- Tests do not invoke historical recovery against operational data.
- Cleanup affects only files owned by the test.
- Environment variables cannot silently redirect tests to an operational database.
The previous diagnostic reported a possible `:memory:` CapVer test connection and a separate testing directory for scientific fixtures. Verify actual current behavior.
Do not assume `phpunit.xml` alone guarantees isolation.
If isolation cannot be demonstrated, do not run the tests.
## 8.1 Test execution
Once isolation is established:
1. Run the smallest relevant isolated tests.
2. Run the focused CapVer suite.
3. Run additional regression tests only when safe, authorized, and necessary.
4. Record exact commands, exit codes, counts, assertions, failures, and skips.
If PHP or required dependencies are unavailable, report that limitation. Do not invent results.
Do not use Docker.
---
# 9. Historical Recovery and G5-04 Verification
Historical recovery is not automatically authorized.
Do not rerun recovery or import procedures just to reproduce a previous report.
Previously reported records included:
- G1-02: `cap-rec-r11-20261018-G1-02-oai_pmh`
- G6-20: `cap-rec-r11-20261018-G6-20-api`
- G1-01: `cap-rec-r12-20261018-G1-01-bulk`
- G1-17: `cap-rec-r12-20261018-G1-17-static`
- G5-04: `cap-rec-r9-20261016-G5-04-api`
These are historical claims, not proof of current database contents.
For each relevant claim, distinguish:
- Previously reported.
- Verified in current source or test evidence.
- Verified in an authorized isolated database.
- Verified in the protected operational database through an explicitly authorized method.
- Not verified.
A row count alone does not prove correct identities, freshness, verification status, source mappings, or idempotency.
If live GBIF verification is unavailable or outside scope, state that limitation explicitly.
---
# 10. Ignore Rules and Recovery Artifacts
Inspect relevant ignore rules, including:
- `backend/storage/app/.gitignore`
- `backend/storage/framework/.gitignore`
- Root `.gitignore`
The previous diagnostic reported that recovery artifacts under `backend/storage/framework/recovery/` might not be ignored and that a root backup pattern might be duplicated. Reverify the current files.
Distinguish:
1. Ignored file.
2. Untracked ignored file.
3. Already tracked file.
4. Staged file.
5. File not intended for publication.
An ignore rule does not remove an already tracked file from Git.
Do not change ignore rules outside the authorized scope. If cleanup is deferred, document it as a follow-up. Regardless, prevent protected or unintended artifacts from entering the publication.
---
# 11. Explicit Publication Manifest
Before staging, create a manifest for every proposed publication file.
Use the following as a discovery checklist, not automatic approval.
## 11.1 CapVer core
Potential files:
- `backend/app/Models/CghiaCapabilityCell.php`
- Actual repository implementation, including `EloquentCapabilityStoreRepository.php`
- `CapVerSeatVerificationService.php`
- `CapVerAcceptedSeatCommand.php`
- `backend/config/capability_store.php`
- Actual CapVer migration
- Required importer and import command.
Verify actual paths and tracking status.
## 11.2 Persistence and configuration
Potential files:
- `backend/config/database.php`
- `backend/.env.example`
Review mixed hunks and unrelated configuration changes.
## 11.3 Test isolation
Potential files:
- `backend/tests/bootstrap.php`
- `backend/tests/Support/DatabaseSafety.php`
- `backend/phpunit.xml`
- `backend/tests/TestCase.php`, only if a change is required.
## 11.4 Tests and fixtures
Potential files:
- `CapVerG504IsolationTest.php`
- `CapVerSeatVerificationServiceTest.php`
- `CapVerDedicatedStoreTest.php`
- Required fixtures.
- Other focused tests justified by the audit.
Verify exact paths. Do not assume a recovery artifact is a test fixture or should be committed.
## 11.5 Required dependencies
Potential files:
- `AcceptedOwnerDecisionCatalog.php`
- `CurrentMembershipNearReadySeatCatalog.php`
- `NextGateEligibleSeat.php`
- `NextGateEligibleSeatCatalog.php`
- `SeatActivationState.php`
- `ScientificSourceCatalog.php`
- `Stage3CghiaCoexistenceBoundary.php`
- `Stage3SourceKeyIdentityBridge.php`
Include only dependencies demonstrated to be required and reviewed.
## 11.6 ADR and reports
Potential files:
- `docs/architecture/ADR-023-scientific-source-expansion.md`
- `docs/2026-10-23-G5-04-CAPVER-COMPLETION-REPORT.ar.md`
- `docs/2026-10-23-CAPVER-HISTORICAL-RECOVERY-REPORT.ar.md`
Verify exact file names, contents, evidence, and publication intent.
## 11.7 Manifest fields
For every proposed file, record:
- Exact path.
- Purpose.
- Tracked/untracked status.
- Dependencies.
- Review completed: YES/NO.
- Test coverage or verification.
- Sensitive-data review.
- Decision: `INCLUDE`, `EXCLUDE`, or `BLOCKED`.
- Reason.
Do not stage files merely because they appear in this checklist.
---
# 12. Secrets and Sensitive Data
If a potential secret is found in a file intended for publication:
1. Stop publication of that file.
2. Do not print or copy the secret into the report.
3. Record its path, approximate location, and type without its value.
4. Determine the required remediation and authorization.
5. Resume only after the issue is resolved and verified.
Do not silently delete, rotate, relocate, or rewrite credentials without the required authority.
Review every file in the proposed manifest. A grep result alone does not prove that all files are secret-free.
Do not claim a full security audit if only a subset was inspected.
---
# 13. Code Quality and Design Autonomy
Within the authorized scope, use professional engineering judgment to:
- Improve cohesion, naming, type safety, dependency boundaries, error handling, and testability.
- Eliminate unnecessary duplication.
- Keep database responsibilities explicit.
- Separate operational persistence from test persistence.
- Preserve existing public contracts and architectural decisions.
- Add tests for demonstrated defects and important edge cases.
- Improve documentation to match actual behavior.
- Prefer focused, maintainable solutions over speculative frameworks or broad refactoring.
Do not:
- Expand into unrelated refactoring.
- Change public APIs or architectural contracts without the required approval.
- Modify unrelated source search, answer synthesis, or evidence behavior merely because adjacent files are untracked.
- Change the default database connection to make tests pass.
- Weaken safety guards to make persistence or testing easier.
- Hide errors or misrepresent incomplete behavior as complete.
When several implementation choices are available, select the best evidence-supported option within the authorized scope and explain why in the final report.
Ask the owner only when the decision affects reserved architectural authority, protected data, publication scope, or another explicit approval boundary.
---
# 14. Before/After Verification Matrix
Maintain an evidence-backed matrix.
| Item | Baseline | Final | Verification |
|---|---|---|---|
| Branch | Expected branch | Actual branch | Git output |
| HEAD | Expected baseline | Actual HEAD | Git output |
| Working tree | Recorded status | Actual status | `git status` |
| Index | Recorded state | Actual staged state | `git diff --cached` |
| Scientific-store SHA-256 | Recomputed baseline | Recomputed final | Hash comparison |
| Scientific-store size | Recorded baseline | Recorded final | File metadata |
| CapVer DB state | Authorized baseline, if available | Authorized final, if available | Approved isolated/read-only method |
| ADR target sections | Exact extracted hash | Exact extracted hash | Identical extraction boundaries |
| Default DB connection | Recorded configuration | Actual configuration | Configuration review |
| CapVer connection | Recorded configuration | Actual configuration | Configuration review |
| Publication manifest | Proposed scope | Actual reviewed scope | File reconciliation |
| Tests | Actual initial state | Current execution results | Command output |
| Remote branch | Recorded state | Actual remote state | Only when authorized |
Interpret results correctly:
- A new authorized commit changes HEAD.
- Working-tree and HEAD hashes may differ while local changes remain uncommitted.
- Protected database hashes should remain unchanged when required.
- ADR hash comparison is valid only with identical extraction boundaries and encoding.
- A matching row count is not proof of record correctness.
- Historical test results are not current-run results.
Use explicit statuses: `PASS`, `FAIL`, `NOT VERIFIED`, and `BLOCKED`.
Never infer `PASS` from an expected value.
---
# 15. Staging Gate
Do not stage until:
1. Required owner decisions are resolved.
2. The CapVer dependency chain is reviewed.
3. Test isolation is established.
4. Required tests have run or their limitations are documented.
5. The publication manifest is complete.
6. Sensitive-data review is complete.
7. ADR scope is approved.
8. Ignore-rule treatment is resolved.
9. Before-state evidence is preserved.
10. The exact intended diff has been reviewed.
If staging is authorized:
- Stage only explicit approved paths or a reviewed cached patch.
- Inspect the full staged diff independently.
- Confirm every staged file is in the approved manifest.
- Confirm every staged hunk is authorized.
- Confirm no unrelated user changes were included.
- Confirm no protected database, backup, recovery artifact, or secret was staged.
- Confirm no approved existing content was accidentally deleted or overwritten.
If any check fails, do not commit. Preserve the index and working tree and report the issue.
---
# 16. Final Pre-Commit Gate
Before the final report, verify:
- Decision statuses are recorded.
- Implementation and dependencies have been reviewed.
- Required tests have actually run where safe and permitted.
- Protected-file hashes have been compared where required.
- ADR integrity has been checked.
- The manifest matches the intended publication scope.
- The staged diff, if staging was authorized, has been fully reviewed.
- Remaining issues are documented.
- No unsupported claims appear in reports or documentation.
If a required gate is blocked, report it. Do not bypass it to make the task appear complete.
---
# 17. Commit and Push — Conditional Procedures
This section applies only if the current task explicitly authorizes commit or push. It does not grant permission by itself.
## 17.1 Commit failure
- Do not use `--amend`.
- Do not use `--no-verify`.
- Inspect the exact failure, including hooks and signing requirements.
- Preserve staged and unstaged changes.
- Correct the underlying issue only within the authorized scope.
- Re-review the staged diff.
- Retry only when authorized and safe.
Do not bypass a safety hook to complete publication.
## 17.2 Push failure
- Never force-push.
- For non-fast-forward errors, stop and request a safe integration plan. Do not integrate remote changes automatically when doing so would violate the current Git restrictions.
- For branch protection, stop and report the required approval or pull-request process.
- For authentication failures, stop and report without exposing credentials.
- For a transient network failure, retry at most once if safe and authorized.
## 17.3 Post-commit verification
Only after an explicitly authorized successful commit:
1. Verify commit hash and branch.
2. Verify that the commit contains only the approved manifest.
3. Recompute protected-file hashes.
4. Verify required ADR integrity.
5. Run authorized post-commit tests if safe.
6. Mark unverified checks `NOT VERIFIED`.
7. Do not automatically revert the commit or rewrite history if verification fails.
Only after an explicitly authorized successful push:
- Verify the remote branch points to the intended commit.
- Report remote equality using actual Git output.
- Never claim a successful push based solely on the local commit.
## 17.4 Commit message template
Use only after the relevant behavior is verified:
`feat(capver): ship isolated CapVer persistence`
Describe only verified changes, such as:
- Dedicated connection and persistence behavior.
- Model and repository changes.
- Accepted-seat handling and idempotency.
- Test isolation and safety guards.
- Exact ADR sections included.
- Protected scientific-store integrity, if verified.
Do not claim “five verified seats,” “G5-04 persisted,” “idempotency confirmed,” or “scientific store unchanged” without current evidence supporting the exact claim.
---
# 18. Interruption and Resume Protocol
If execution is interrupted, record:
- Last completed phase.
- Branch and HEAD.
- Working-tree and index state.
- Files modified, staged, reviewed, and not reviewed.
- Tests executed and actual outputs.
- Baseline evidence location and completeness.
- Unresolved decisions and blockers.
- Next safe action.
Do not discard changes or reset the repository to make resumption easier.
On resume:
- Revalidate branch, HEAD, working tree, and index.
- Revalidate evidence that may have become stale.
- Continue from the last verified checkpoint.
- Never assume the repository remained unchanged during interruption.
If the staging or publication state is uncertain, do not commit or push.
---
# 19. Required Final Report — Arabic
Produce one comprehensive Arabic report, not multiple fragmented analyses.
## A. Executive Summary
- Final status: `READY_FOR_OWNER_REVIEW`, `BLOCKED`, `NEEDS_OWNER_DECISION`, or another precisely justified status.
- Completed work.
- Remaining work.
- Exact stopping point.
## B. Baseline and Evidence
- Actual branch and HEAD.
- Before-state evidence.
- Protected-file hash comparisons.
- ADR integrity results.
- Evidence that could not be safely collected.
## C. Owner Decisions
- Each decision and status.
- Recommendation and rationale.
- Decisions still requiring owner input.
## D. Engineering Changes
- Files changed.
- Design rationale.
- Dependency findings.
- Existing behavior preserved.
- Limitations.
## E. File Manifest
- Included, excluded, and blocked files.
- Reasons for material inclusions and exclusions.
## F. Tests
- Exact commands.
- Actual pass/fail counts and assertions.
- Relevant outputs.
- Tests not executed and why.
- Isolation evidence.
## G. Database Safety
- Databases accessed or not accessed.
- Whether any writes occurred.
- Before/after hashes and verified invariants.
- Unverified checks.
## H. Git Status
- Working-tree and index state.
- Staged diff status.
- Commit and push status.
- Confirmation that prohibited operations were not executed.
## I. Risks and Blockers
- Remaining risks.
- Required decisions.
- Deferred tasks.
## J. Next Authorized Action
Recommend the single safest next step. Do not execute a reserved action merely because it is recommended.
---
# 20. Final Execution Directive
Start immediately by verifying the repository baseline and capturing safe before-state evidence.
Then resolve the blockers in dependency order. Inspect actual code, not just reports. Use broad engineering autonomy for safe, reversible work within the authorized scope.
Do not invent owner approval, database contents, hashes, test results, or publication success.
Do not stage, commit, or push without the required authorization.
Do not ask repetitive questions about routine engineering choices. Consolidate genuine owner decisions into one request, and continue independent safe work where possible.
Deliver one comprehensive Arabic report with evidence, exact status, unresolved risks, and the next safe action.
