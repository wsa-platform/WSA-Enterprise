<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18350). Message timestamp as recorded in the conversation: Saturday, Oct 24, 2026, 11:18 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18351; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION PROMPT v10.4
## Autonomous Engineering · Round 3 · CapVer · Scientific Source Expansion · ADR · Isolated Tests · Git Safety · Commits · Push · Final Closure
**Project:** WSA-Enterprise  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Target branch:** `phase-18-m18-ai-marketing-communications`  
**Expected local path:** `G:\WSA-Enterprise`  
**Primary ADR:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Execution mode:** Autonomous engineering under explicit owner authorization and non-negotiable safety boundaries.
---
# 1. Executive Mandate and Owner Authorization
You are the senior software engineer, software architect, test engineer, and security-conscious implementation owner for this task.
I am the project owner. I authorize you to execute the engineering work required to advance Round 3, including CapVer readiness, scientific source expansion integration, architectural documentation, testing, verification, commit creation, ordinary push, and final reporting.
Do not merely propose plans. Perform the work that is actually possible in the available environment.
You are authorized to:
- Inspect repository state, code, tests, architecture decisions, and relevant historical reports.
- Make routine engineering decisions within the approved architecture and task scope.
- Implement and modify code.
- Fix verified defects.
- Add and improve unit and integration tests.
- Improve validation, error handling, isolation, security, and maintainability.
- Update relevant architecture documentation.
- Execute safe tests and record actual results.
- Create logically separated commits after the required checks.
- Perform ordinary pushes after the required repository and safety checks.
**Do not ask the owner to approve every routine engineering decision.** Choose the best technically justified solution, implement it, verify it, and document the rationale.
This authorization does not permit destruction of existing work, unsafe access to operational databases, bypassing security controls, rewriting Git history, or inventing successful outcomes.
If one operation is unsafe or blocked, isolate it and continue independent work that can safely proceed.
# 2. Starting Point and Historical Context
Start from the previously recorded stopping point, but establish the actual current repository state before proceeding.
Historical context available for orientation:
- Last recorded Round 2.5 HEAD: `1e4d7a62a1054b82e4eec655bd6dcd630e837cf6`.
- Target branch: `phase-18-m18-ai-marketing-communications`.
- Round 2.5 had no commit or push during that round.
- A previous `git diff --check` reported a whitespace issue in `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md:357`.
- Layer A: `PARTIAL — INVENTORY_ONLY`.
- Layer C: closed for append-integrity checks only; this did not constitute approval of every new architectural decision.
- Layer E: closed within the isolated contract and test scope previously verified.
- Layer F: `PARTIAL — STRUCTURE TESTED, VERIFICATION PENDING`.
- Layer G: closed for cases actually tested; the symlink branch remained untested.
- Layer H: not started.
- Overall historical status: `PARTIAL`.
These are historical facts, not proof of the current state.
Do not assume HEAD, working-tree contents, test results, database state, or remote branch state remain unchanged.
Do not repeat completed investigations without a new reason, changed state, or contradictory evidence.
# 3. Authority Hierarchy
When evaluating conflicting information, apply this order:
1. Data safety and protection of existing work.
2. Explicitly approved architectural decisions.
3. The owner's explicit authorization in this prompt.
4. Actual code contracts and reliable test evidence.
5. Current task requirements.
6. Engineering best practices within the authorized scope.
Do not treat the presence of text in an ADR, report, local file, or prior prompt as sufficient proof that a decision has been approved.
Do not replace an approved architectural contract merely because the existing implementation is inconvenient.
# 4. Explicit Owner Pre-Approval for ADR-023
The owner explicitly authorizes approval of §§8.66–8.72 in:
`docs/architecture/ADR-023-scientific-source-expansion.md`
The authorization covers:
- §8.66
- §8.67
- §8.68
- §8.69
- §8.70
- §8.71
- §8.72
This is explicit owner authorization within this prompt. Do not request another approval merely because these sections contain architectural decisions within the stated scope.
## 4.1 Validate the actual authorized text
Read the actual sections and compare their contents against the available Round 2.5 report, the reviewed v10.0 material, and relevant historical decision records available in the repository or project files.
Confirm that the text to be approved matches the subject matter covered by this authorization.
Do not silently extend approval to:
- Materially different text added after the reviewed material.
- New architectural decisions outside §§8.66–8.72.
- Changes to established contracts not covered by the authorization.
- Changes that materially alter product requirements, security boundaries, or ownership decisions.
If the actual text differs materially, or if a section conflicts with an earlier approved decision, isolate the conflicting section, document the reason, and continue independent safe work.
**There is no default approval by silence.** This does not cancel the explicit authorization already given here; it prevents extending that authorization to unrelated or materially different decisions.
## 4.2 ADR integrity requirements
- Preserve historical decisions and records.
- Do not delete or replace prior sections.
- Do not duplicate section headings.
- Verify each required heading exists exactly as expected.
- Make missing expected headings cause verification failure rather than silent success.
- Review the complete diff, including surrounding context.
- Preserve the file's existing line-ending conventions.
- Do not infer correctness from the existence of a section.
- Do not infer approval from the presence of text alone.
- Do not use a fixed number of commits or `HEAD~N` as proof of ADR integrity.
After the actual content passes these checks, apply the owner's explicit authorization to matching sections and include the approved documentation changes in the reviewed commit plan.
# 5. Round 3 Scope
Perform the following work according to the actual repository state.
## 5.1 CapVer readiness
- Inspect relevant contracts, services, repositories, configuration, and tests.
- Verify integration with the existing architecture.
- Identify actual defects and gaps.
- Fix in-scope defects.
- Validate error handling, data boundaries, and dependencies.
- Add contract and behavioral tests where needed.
- Verify that operational databases are not accessed unintentionally.
- Document environmental limitations that prevent required verification.
Do not assume that the expected CapVer cell count is five merely because a historical report mentions it. Verify that the approved contract requires five, then prove the count through a safe test.
If the approved contract explicitly establishes five cells as an acceptance criterion, verification is mandatory for closure.
## 5.2 Scientific Source Expansion
Preserve the approved scientific search architecture, including:
- CSQ semantic authority.
- Scientific question identity rather than keyword-only substitution.
- Scientific evidence provenance.
- `ScientificSearchQueryBuilder`.
- `ScientificQueryCompiler`.
- `MultiSourceScientificSearchOrchestrator`.
- Source Adapter contracts.
- `ScientificResultNormalizer`.
Do not create a parallel search path that bypasses established contracts.
Distinguish among:
- A source being listed in a registry.
- Its capabilities being declared.
- Its adapter being implemented.
- Its query being compiled correctly.
- Its results being normalized correctly.
- Its integration being verified.
- Its live external service being reachable.
A registry entry is not proof of a functioning integration.
Implement in-scope corrections without introducing unrelated source expansion or unapproved contracts.
## 5.3 Layer E — Scientific Knowledge Persistence
Review existing Layer E implementation and test evidence.
Verify relevant behaviors using safely isolated tests, including applicable cases for:
- Persisted record correctness.
- Original byte preservation.
- Invalid or unsupported source rejection.
- Failure and timeout handling.
- Size limits.
- Duplicate handling.
- Organization isolation.
- Tenant mismatch.
- Early rejection conditions.
Do not rerun previously successful tests without a reason. Re-run when required by changed code, changed environment, or a specific verification gap.
Do not claim that a case passed unless the test actually ran and its result supports that claim.
## 5.4 Layer G — Mission Artifact Archive
Review the actual implementation and previous evidence for:
- Rejection of dangerous names.
- Path traversal prevention.
- Blob reads confined to the intended root.
- Safe handling of directory creation failures.
- Preservation of storage consistency when failures occur.
- Symlink-related behavior where relevant.
The symlink branch was historically untested. Determine whether it is required for current acceptance.
If required, add and execute a safe test. If the environment prevents reliable verification, document the limitation and do not claim that the branch is covered.
## 5.5 Layer F — Integration Classification
Layer F must be explicitly addressed in the Round 3 plan and final report.
Its historical status is:
`PARTIAL — STRUCTURE TESTED, VERIFICATION PENDING`
Distinguish structural tests, unit tests, integration tests, actual-data verification, and live external-service verification.
If the repository does not contain the responsibility or service needed for live verification, do not pretend structural tests provide that evidence.
**Keep Layer F `PARTIAL` at the end of Round 3 unless new evidence satisfies its actual acceptance criteria.**
Document:
- What structural behavior was tested.
- What verification remains.
- Why remaining verification is outside the repository's current capability, if applicable.
- Whether a separate future verification-service workstream may be needed.
Do not automatically create a new verification service unless it is established as part of the authorized scope.
## 5.6 Layer H
Do not automatically start Layer H merely because it was historically marked `NOT STARTED`.
Start it only if its requirements are actually included in the approved Round 3 scope and can be implemented safely within the available contracts.
Otherwise, document its status and continue independent authorized work.
# 6. Repository Baseline and WIP Protection
Before modifying files:
1. Confirm the repository root.
2. Confirm the current branch and HEAD.
3. Capture the full current Git status.
4. Inspect the index separately.
5. Record tracked and untracked changes.
6. Identify existing WIP and files relevant to the task.
7. Create a new, reviewable baseline checkpoint.
8. Preserve the distinction between pre-existing changes and changes introduced during execution.
Do not assume an untracked file is disposable or unrelated.
Do not use a fixed file-count threshold as proof that work has been preserved.
Compare actual paths and contents when determining whether existing work remains intact.
## 6.1 Prohibited Git operations
Never use:
- `git reset --hard`
- `git clean`
- `git stash`
- `git add .`
- `git add -A`
- `git commit -a`
- `git commit --amend`
- `git push --force`
- History rewriting.
- Broad cleanup scripts that cannot distinguish user work from task files.
- `git restore` or `git checkout` in a way that overwrites existing changes.
Use selective staging only.
If an unexpected change appears, do not remove or overwrite it. Investigate it, preserve it, and continue independent safe work.
# 7. Protected Databases and Operational Data
The following database files are protected:
`backend/storage/app/scientific_record_store.sqlite`
`backend/storage/app/capver.sqlite`
Do not open, query, modify, migrate, seed, or run tests against these operational databases.
Do not use:
- Tinker to access the operational CapVer database.
- Direct SQLite queries against either protected database.
- Artisan commands whose safety and isolation have not been established.
- Migrations or seeders against protected databases.
- Tests that implicitly use production or operational database configuration.
The presence of `:memory:`, `RefreshDatabase`, or `Storage::fake()` alone is not sufficient proof of isolation.
Before running a test, establish from its actual configuration and execution path that it cannot read, write, or otherwise alter protected operational data.
Where needed, verify database-file integrity through metadata and SHA-256 checks without opening database content.
Compare file state with a baseline established at the beginning of execution. Historical hashes are reference information only; re-establish the current baseline before relying on them.
Use an independent test database only after verifying that the test configuration actually points to it and that the execution path cannot fall back to a protected database.
If isolation cannot be proven, do not run the test. Record the limitation and continue safe independent work.
# 8. Test Execution and Verification
Run tests relevant to the changes made, not all tests indiscriminately.
Before execution:
- Inspect the real test configuration.
- Determine which database and filesystem paths are used.
- Identify external services or resources that may be accessed.
- Prove that protected databases cannot be accessed or changed.
- Check that commands will not run migrations against protected environments.
- Ensure test results are attributable to the code under review.
For every test group, record:
- The exact command executed.
- The relevant code or contract covered.
- Actual test counts, if reported.
- Actual passed, failed, and skipped counts, if reported.
- Actual assertion counts, if reported.
- Actual exit code.
- Warnings and limitations.
- Tests not executed and the reason.
Do not infer success from the absence of an obvious error in the editor interface.
Do not use a broad PHPUnit filter as proof that every required test ran.
If a test fails:
1. Inspect the failure.
2. Determine whether the cause is code, test logic, or environment.
3. Fix the root cause if in scope.
4. Re-run the relevant safe test.
5. Record meaningful initial failure, correction, and final result.
Do not conceal earlier failures when reporting the final state.
# 9. Mandatory CapVer Cell-Count Verification
If the approved contract requires exactly five CapVer cells, the count is a mandatory acceptance criterion.
Verify it through an isolated contract or integration test using an independent test database.
The test must establish that:
- The expected count comes from the actual approved contract.
- The test database is distinct from the protected operational database.
- The test cannot fall back to the operational connection.
- The test verifies the expected records or contract behavior.
- The observed result is recorded from actual execution.
Do not use `php artisan tinker` or direct queries against `capver.sqlite`.
Do not make this criterion optional merely because the operational database may be unavailable or locked.
If a safe independent test environment cannot be established, record:
`VERIFICATION PENDING — CAPVER CELL COUNT`
Do not declare CapVer readiness fully verified while this required criterion remains unverified.
Continue independent work that does not depend on this blocked verification.
## 9.1 Establishing and proving an independent test database
Before creating or running a CapVer cell-count test, inspect the actual test and database configuration, including relevant files such as:
- `backend/phpunit.xml`
- `backend/tests/bootstrap.php`, if present
- The application's database configuration.
- The CapVer connection definition and environment overrides.
- The test setup and teardown paths.
- Relevant migrations and schema definitions.
Use read-only inspection first. For example:
```powershell
Get-Content backend/phpunit.xml
if (Test-Path backend/tests/bootstrap.php) {
    Get-Content backend/tests/bootstrap.php
}
Get-Content backend/config/database.php |
    Select-String -Pattern "capver|sqlite|database|DB_"
```
Adapt paths to the actual repository structure. Do not assume these are the only files that control the connection.
### Required isolation properties
Establish and document all of the following:
1. The primary test connection is explicitly configured for an independent test database.
2. The CapVer connection is independently configured and cannot silently fall back to `backend/storage/app/capver.sqlite`.
3. Every relevant connection used by the test is identified.
4. The effective runtime configuration is checked before database operations.
5. The database is created and initialized only in the approved test environment.
6. Test setup does not run migrations, seeders, or cleanup against a protected operational database.
7. The execution path cannot reconnect to an operational database through an alternate connection or configuration fallback.
8. The test environment is disposable and does not contain operational data.
An in-memory SQLite database may be appropriate if the actual application and test configuration support it. Do not assume that setting `DB_CONNECTION=sqlite` or `DB_DATABASE=:memory:` correctly configures the separate CapVer connection.
### Runtime safeguards
Before any database operation, assert that the effective CapVer database configuration is the expected test configuration and is not the protected operational path.
A configuration assertion may compare the resolved database path with the known protected path. Adapt the comparison to the actual driver and configuration, accounting for path normalization where applicable.
A configuration assertion alone is insufficient. Also inspect the connection lifecycle and prove that no later code path can replace the isolated connection with the operational one.
Where practical, add a fail-closed guard to the isolated test setup that aborts before database access if the resolved connection points to a protected database.
Do not use the operational database to validate this guard.
### Restrictions
- Do not run Tinker against the operational CapVer connection.
- Do not query the protected SQLite database to obtain the expected count.
- Do not assume `RefreshDatabase`, `:memory:`, or `Storage::fake()` proves complete isolation.
- Do not modify shared bootstrap or database configuration until its impact on other tests is understood and the change is necessary.
- Do not introduce a fallback to the operational database when test configuration is missing.
- Do not run the test if isolation cannot be established.
If isolation cannot be proven, record:
`VERIFICATION PENDING — CAPVER TEST ISOLATION`
Continue independent safe work.
# 10. Security Review and Secret Detection
Review changed files for:
- API keys, passwords, and credentials.
- Database connection secrets.
- Sensitive operational or personal data.
- Secrets embedded in tests or logs.
- Unsafe filesystem paths and permissions.
- Path traversal.
- Symlink escapes.
- Unbounded reads or writes.
- Unintended production configuration changes.
Do not rely on a limited regular expression as proof that secrets are absent.
Inspect actual changed files, including new files, and review available scanner results.
If a secret may have been exposed, do not print it in the report. Identify the affected file and location with the minimum necessary detail, and block publication of the affected change until it is addressed.
A file being ignored by `.gitignore` does not prove that it is untracked or safe.
## 10.1 Explicit `.gitignore` and sensitive-file audit
Audit the repository's ignore rules and Git tracking state for protected databases, recovery material, checkpoints, dumps, and other sensitive artifacts.
Inspect actual `.gitignore` files and relevant nested ignore rules. Check the following paths when present:
- `backend/storage/app/capver.sqlite`
- `backend/storage/app/scientific_record_store.sqlite`
- `backend/storage/framework/recovery/`
- `checkpoints/`
- Other recovery, backup, dump, or generated-artifact locations discovered in the repository.
Example read-only checks:
```powershell
$sensitive = @(
    "backend/storage/app/capver.sqlite",
    "backend/storage/app/scientific_record_store.sqlite",
    "backend/storage/framework/recovery/",
    "checkpoints/"
)
foreach ($path in $sensitive) {
    git check-ignore -v -- $path
    if ($LASTEXITCODE -ne 0) {
        Write-Host "NOT CONFIRMED IGNORED: $path"
    }
}
git ls-files |
    Select-String -Pattern '(^|/)[^/]*\.sqlite$|\.dump$|(^|/)recovery/|(^|/)checkpoints/'
```
Adapt patterns to the actual repository paths and inspect all relevant results.
### Interpretation rules
- A path not being ignored is a finding to investigate, not automatic proof of a vulnerability.
- An ignored path may still be tracked.
- `git check-ignore` does not prove a tracked file is safe or untracked.
- `git ls-files` reports tracked paths; it does not by itself prove a file was published remotely.
- An empty tracked-file result is useful evidence but does not replace reviewing the index and the commits being published.
- Ignore-rule changes must not be used to conceal existing tracked sensitive files.
If a protected database or sensitive artifact is tracked, staged, or included in a proposed commit, stop that specific publication path, inspect its status without opening protected database content, and prevent accidental publication.
Do not automatically untrack, delete, rewrite, or clean up files. Preserve existing work and investigate the safe remediation path.
Before each commit and push, inspect the staged diff and actual commit contents for sensitive files and secrets.
# 11. Verify File Existence Before Staging
Before every `git add` operation:
1. Confirm each expected path exists.
2. Confirm the file is the intended file, not an alternative copy.
3. Inspect its content and actual diff.
4. Determine whether it is tracked or untracked.
5. Verify that it belongs to the authorized task.
6. Review it for secrets and protected data.
7. Stage only individually reviewed paths.
8. Inspect `git diff --cached` after staging.
9. Confirm that no unrelated file has entered the index.
If a file is missing or its path differs from the expected path, do not automatically create a replacement or ignore the discrepancy.
Investigate the cause and continue independent safe work.
Example PowerShell existence check:
```powershell
$files = @(
    "backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceStorageTest.php",
    "backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceServiceTest.php",
    "backend/app/Services/Agriculture/Research/Persistence/ScientificKnowledgePersistenceService.php",
    "backend/app/Services/Architecture/MissionArtifactArchive/FilesystemMissionArtifactArchive.php",
    "backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php",
    "docs/architecture/ADR-023-scientific-source-expansion.md"
)
foreach ($f in $files) {
    if (-not (Test-Path -LiteralPath $f -PathType Leaf)) {
        Write-Host "MISSING: $f"
    }
}
```
This is an example verification technique, not a declaration that all listed files must exist or must be committed. Derive the actual commit scope from the current repository state.
# 12. ADR and Architecture Review
After making changes:
- Review every changed ADR diff.
- Verify required headings and their uniqueness.
- Check surrounding context and historical sections.
- Confirm that earlier decisions and records remain intact.
- Distinguish proposals from approved decisions.
- Ensure that documented test results actually occurred.
- Verify that no new architectural contract has been introduced outside the authorized scope.
- Preserve existing line-ending conventions.
Do not use `HEAD~4`, a fixed commit count, or line counts as proof of ADR integrity.
Review the actual base content, working-tree diff, staged diff, and final committed content.
# 13. Pre-Commit Review
Create a factual pre-commit report before creating commits.
Include:
1. Current branch and HEAD.
2. Baseline and changes since the beginning of the task.
3. Relevant modified and new files.
4. Purpose of each change group.
5. ADR-023 approval and integrity results.
6. Actual test results.
7. Security and isolation results.
8. Protected database integrity results.
9. `git diff --check` result.
10. Candidate paths for staging.
11. Rationale for including each path.
12. Unrelated changes to exclude.
13. Remaining risks and limitations.
14. Proposed commit groups and dependencies.
15. Remote branch state, if safely verifiable.
If a historical whitespace issue remains in ADR-021, distinguish it from any issue introduced by the current work. Do not silently modify unrelated historical content.
Do not prepopulate checkpoints with future or assumed statuses.
# 14. Logical Commit Boundaries
Commit boundaries must reflect engineering responsibilities and dependencies.
Where the actual changes support separation, prefer independent commits for:
- Layer E persistence changes and tests.
- Layer G archive security changes and tests.
- ADR-023 documentation changes.
Do not assume that the historical list of six files still represents the current changes. Inspect actual diffs before deciding which files belong in each commit.
Do not force separation that leaves an inconsistent or broken intermediate state.
Do not force a fixed number of commits. Choose the number that best preserves reviewability, testability, and logical independence.
## 14.1 Layer E commit
If current diffs confirm that these files belong to Layer E, stage them selectively:
```powershell
git add -- backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceStorageTest.php
git add -- backend/tests/Unit/Agriculture/Research/Persistence/ScientificKnowledgePersistenceServiceTest.php
git add -- backend/app/Services/Agriculture/Research/Persistence/ScientificKnowledgePersistenceService.php
```
Example message:
`test(knowledge): add storage and byte preservation tests`
Adjust the message if the actual change is primarily a fix or contract change rather than tests.
## 14.2 Layer G commit
If current diffs confirm that these files belong to Layer G, stage them selectively:
```powershell
git add -- backend/app/Services/Architecture/MissionArtifactArchive/FilesystemMissionArtifactArchive.php
git add -- backend/tests/Unit/Architecture/MissionArtifactArchive/MissionArtifactArchiveTest.php
```
Example message:
`fix(archive): prevent path traversal in ingest and export`
Use this message only if it accurately describes the actual diff.
## 14.3 ADR commit
Only stage ADR-023 if its actual contents have passed the authorization and integrity checks:
```powershell
git add -- docs/architecture/ADR-023-scientific-source-expansion.md
```
Example message:
`docs(adr): complete CapVer history and Round 2.5 limitations`
Change the message if it does not accurately describe the final content.
## 14.4 Commit acceptance checks
Before each commit:
- Inspect the staged diff.
- Confirm that all included paths have been reviewed.
- Confirm that no unrelated WIP is staged.
- Confirm that no secret or protected operational data is included.
- Confirm that relevant tests have passed, or explicitly document any justified verification limitation.
- Confirm that the intermediate state is coherent.
After each commit, verify:
- Actual exit code.
- Commit hash and subject.
- Files included.
- Index state.
- Working-tree state.
- Preservation of unrelated WIP.
Do not claim that a commit succeeded without Git evidence.
# 15. Commit Failure Handling
If a commit fails:
1. Inspect the exact failure.
2. Inspect the index and working tree.
3. Determine whether the failure relates to hooks, signing, configuration, validation, or staged content.
4. Fix the underlying cause if safe and in scope.
5. Revalidate the staged diff.
6. Retry without bypassing safeguards.
Do not use `--no-verify`, `--amend`, or history rewriting to force success.
If a later commit fails after earlier commits succeeded, preserve those successful local commits. Do not automatically reset, revert, or delete them.
Determine whether the remaining commits can be completed safely without compromising consistency. If not, stop the affected sequence and report the exact state.
# 16. Owner Authorization for Ordinary Push
The owner authorizes ordinary push to the target branch after local commits and required checks are complete.
Before pushing:
1. Confirm the current branch.
2. Inspect the remote branch state using available safe Git operations.
3. Confirm that commits to be published belong to this task.
4. Check for unexpected remote changes.
5. Confirm that publication does not require force push or history rewriting.
6. Confirm that no secrets or protected database files are included.
7. Review relevant tests and outstanding verification limitations.
Do not wait for a separate approval merely for an ordinary push that satisfies these conditions.
This authorization does not permit force push, history rewriting, destructive conflict resolution, or overwriting work from other contributors.
# 17. Push Failure Handling
## 17.1 Non-fast-forward
If push is rejected because the remote branch has advanced:
- Stop the push sequence.
- Do not use force push.
- Do not automatically merge or rebase.
- Use safe read-only Git commands to inspect the divergence.
- Preserve all local commits and working-tree changes.
- Record the remote state and the required next decision.
Resume only through a safe, justified path that preserves others' work and does not rewrite history.
## 17.2 Authentication failure
Stop the push attempt.
Record the error without printing tokens, passwords, or credentials.
Do not repeatedly retry an authentication failure without a justified change in authentication state.
## 17.3 Network failure
A single retry is permitted only after checking whether the previous push actually reached the remote.
If the outcome is ambiguous, inspect the remote branch first.
If the retry fails, stop and report the state.
## 17.4 Push completed but verification failed
Do not assume that a reported command success proves the final remote state.
Verify the remote branch's commit through available Git evidence.
If verification is unavailable, record:
`REMOTE VERIFICATION PENDING`
Do not falsely report the push as fully verified.
# 18. Post-Commit and Post-Push Verification
After each commit and after push, verify the actual state.
Check:
- Commit hash and subject.
- Included file paths.
- Index and working-tree state.
- Preservation of WIP.
- Relevant test evidence.
- Integrity of protected database files.
- Remote branch state after publication.
- Any unexpected changes introduced during execution.
If a check fails, investigate and correct the affected part when safe. Do not use destructive cleanup or history rewriting as a shortcut.
# 19. Status Classification
Use the following statuses accurately.
**CLOSED:** All applicable acceptance criteria are met and supported by actual evidence.
**PARTIAL:** Some work is complete, but requirements, verification, or publication remain incomplete.
**BLOCKED:** A critical dependency or safety constraint prevents the core remaining work from proceeding, and no safe independent work remains that can materially advance the objective.
**NOT STARTED:** The work has not actually begun.
**VERIFICATION PENDING:** Implementation or structural testing exists, but required verification remains incomplete. This status may be used for an individual workstream; the overall Round 3 status must still be selected according to §19.1.
An individual workstream can be closed independently if its own criteria are met.
Do not claim that Round 3 as a whole is closed while required acceptance criteria remain outstanding.
## 19.1 Final Round 3 status decision
Determine the overall Round 3 status from actual workstream results, mandatory acceptance criteria, and available evidence.
| Overall status | Decision rule |
|---|---|
| `CLOSED` | All mandatory Round 3 acceptance criteria are satisfied, required workstreams are closed, required verification has passed, and the final repository state is verified. |
| `PARTIAL` | Meaningful work is complete, but at least one required workstream, verification, or publication step remains incomplete. |
| `BLOCKED` | A critical blocker prevents further meaningful safe progress. |
| `NOT STARTED` | No Round 3 implementation work has actually begun. |
### Mandatory closure rules
1. Do not declare Round 3 `CLOSED` while a mandatory acceptance criterion remains unverified.
2. If the approved contract requires exactly five CapVer cells, do not close Round 3 until the count is verified through a safe, isolated test.
3. ADR-023 sections covered by the owner's explicit authorization must pass content and integrity checks before being treated as approved and committed.
4. Under this Round 3 scope, Layer F remains `PARTIAL` unless its required verification criteria are satisfied. If Layer F remains an outstanding required workstream, the overall Round 3 status cannot be `CLOSED`.
5. A failed or unverified push does not automatically mean `BLOCKED`. If local work is complete but publication remains outstanding, use `PARTIAL` and state the exact publication status.
6. Use `BLOCKED` only when a critical blocker prevents further meaningful safe progress.
7. Do not equate a successful local commit with successful remote publication.
8. Never infer closure from the absence of errors, the existence of files, or an expected test result.
9. State the overall Round 3 status separately from the status of each workstream.
# 20. Checkpoint and Audit Trail
Create a new checkpoint without overwriting historical reports.
Include:
- Actual date and time.
- Repository path and branch.
- Actual HEAD.
- Baseline and changes observed.
- Files modified.
- Architectural decisions applied.
- Tests executed and actual results.
- Tests not executed and reasons.
- Protected database integrity state.
- Commits actually created.
- Push state and verification evidence.
- Remaining work and risks.
- Next stopping point.
Do not prepopulate outcomes such as `COMPLETED`, `PUSHED`, or `PRE-COMMIT REVIEW READY`.
Do not include secrets or protected database contents.
Use a new path for the checkpoint so previous evidence remains intact.
# 21. Engineering Autonomy and Decision-Making
Work as the engineer responsible for the outcome, not merely as an operator executing commands.
- Do not ask for approval on routine implementation details.
- Do not repeat successful investigations without a reason.
- Do not introduce superficial tests that fail to prove required behavior.
- Do not broaden scope unnecessarily.
- Do not fix unrelated issues merely because they exist.
- Do not confuse registry presence with working source integration.
- Do not confuse local tests with live-service verification.
- Do not conceal environmental limitations.
- Do not report expected results as actual results.
- Prefer root-cause fixes over symptom suppression.
- Keep changes reviewable and traceable.
- Continue independent safe work when another part is blocked.
When several solutions are valid, choose the one most consistent with approved contracts, security, testability, maintainability, and minimal unintended impact.
# 22. Final Execution Order
Execute the work in this order, adapting only when evidence shows a dependency requires it:
1. Establish repository baseline and protect existing work.
2. Review relevant historical evidence and actual current state.
3. Validate ADR-023 sections against the explicit owner authorization.
4. Inspect CapVer contracts and safely verify the expected cell-count requirement.
5. Complete authorized Layer E and Layer G work that remains necessary.
6. Address scientific source expansion integration gaps within scope.
7. Explicitly assess Layer F and Layer H status.
8. Run only tests whose isolation has been established.
9. Perform security, ADR, diff, ignore-rule, and database-integrity checks.
10. Prepare and review the factual pre-commit report.
11. Stage reviewed files selectively.
12. Create logical commits and verify each one.
13. Perform an ordinary push if all required checks pass.
14. Verify the remote state.
15. Create the final checkpoint and report.
Do not force this sequence when an independent task can safely proceed while another is blocked. Preserve dependencies and report any deviations.
# 23. Final Report Requirements
Produce one comprehensive final engineering report in Arabic.
It must include:
1. Executive summary.
2. Actual status of each relevant layer and workstream.
3. Files changed and why.
4. ADR decisions applied and supporting evidence.
5. Actual tests executed and results.
6. Security and isolation checks.
7. Protected database integrity results.
8. `.gitignore`, tracked-file, index, and publication-scope audit results.
9. Diff and staging review results.
10. Actual commits, hashes, messages, and included files.
11. Push status and remote verification evidence.
12. Incomplete work and reasons.
13. Remaining risks.
14. Checkpoint location.
15. Exact next stopping point.
Distinguish completed work from pending verification.
Do not claim successful execution, tests, commits, or push without actual evidence.
# 24. Final Command
Begin from the recorded stopping point, verify the current repository state, and execute Round 3 within this authorization.
Use the owner's explicit pre-approval for matching ADR-023 §§8.66–8.72 text. Make routine engineering decisions independently.
Protect all existing work and operational databases. Do not use destructive Git operations, do not bypass test isolation, and do not rewrite history.
Perform required checks, create logically separated commits, and push normally when all safety and verification conditions are met.
If one operation is blocked, isolate it and continue independent work safely.
Deliver one factual Arabic engineering report with evidence, remaining risks, and the next stopping point.
**Objective:** Deliver verifiable engineering progress in WSA-Enterprise with broad implementation autonomy, architectural integrity, protected data, and a complete audit trail.
