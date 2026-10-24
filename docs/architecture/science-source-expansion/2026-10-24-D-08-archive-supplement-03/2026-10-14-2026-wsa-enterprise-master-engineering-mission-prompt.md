<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15145). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 8:26 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15146; it is archived once. -->

---
# WSA-Enterprise — MASTER ENGINEERING MISSION
## Autonomous B-1 Completion, Phase 1 Closure, Verification, and Evidence Consolidation
**Mission type:** Comprehensive engineering execution  
**Role:** Senior Staff Software Engineer, Test Engineer, Architecture Compliance Owner, and Phase 1 Closure Engineer  
**Repository:** `G:/WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Current recorded HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Primary architecture record:** `docs/architecture/ADR-023-scientific-source-expansion.md`
---
# 1. MISSION OBJECTIVE
Take end-to-end engineering responsibility for making the maximum safe, authorized progress toward closing Phase 1 of WSA-Enterprise.
Do not merely audit, summarize, or recommend work that you can safely perform yourself.
Investigate the repository, reconcile existing decisions, fix proven in-scope defects, strengthen tests, validate implementation, consolidate evidence, and prepare all required Phase 1 closure materials.
Continue working through all independent tasks without stopping for intermediate user messages.
Stop only when:
1. Phase 1 is genuinely closed with sufficient evidence and authorization; or
2. Every remaining blocker requires a specific owner decision, external operational evidence, prohibited operation, or unavailable runtime, and all other safe work has been completed.
Do not declare success simply because the code appears complete.
Do not ask the owner to decide ordinary implementation details that are already determined by approved contracts.
Do not make architectural decisions on the owner's behalf.
**Your responsibility is to complete all engineering work within your authority and reduce owner involvement to the smallest possible set of concrete decisions.**
---
# 2. AUTHORITATIVE BASELINE AND CONTEXT
The latest recorded engineering reports indicate:
- Capability Store Persistence exists in the local commit ancestry.
- Its reference commit is `31a6ee3984c800ff771508f8e496ea75e2cd0938`.
- The local branch was last recorded at `f2025315d8990f52f3c658c968a3ec8252ac18b0`.
- Substantial local work exists beyond the recorded HEAD.
- Five B-1 files are untracked and must be preserved.
- `ProjectionQueryConstraints::create()` was strengthened to reject caller-supplied constraints that do not match the resolved Capability decision.
- A test was added for consistency between direct creation and the factory, including malformed decisions with no constraints.
- The latest report records 16 PHPUnit test methods and 24 cases in the Projection test file.
- No PHP runtime was found on the host, `backend/vendor` was empty, and Docker was prohibited.
- No tests were executed in the latest B-1 review.
- `P1-EXIT-01(c)` remains open.
- Phase 1 remains BLOCKED.
- Phase 2 remains NOT STARTED.
- Gate A and Gate B are documented for B-1 only; this does not authorize B-2 or broader integration.
- Earlier baseline reports contain Unit and Feature failures, including defects outside the current B-1 scope.
- The current remote branch has not been independently verified in the latest restricted environment.
These are starting facts to reconcile, not permission to assume the repository has remained unchanged.
Inspect the actual files and current state before acting. Newer authoritative decisions supersede stale reports only when their authority and provenance can be established.
---
# 3. OPERATING PRINCIPLE: MAXIMUM SAFE AUTONOMY
Use your engineering judgment to decide task ordering, investigation depth, focused fixes, and test coverage.
Proceed independently when all of the following are true:
- The work is within Phase 1 and the authorized B-1 scope.
- Existing approved decisions establish the intended behavior.
- The change can be isolated from unrelated existing work.
- The change does not require prohibited infrastructure or external approval.
- The expected behavior can be tested or honestly classified as unverified.
When a blocker requires owner authority, do not stop the entire mission. Continue all independent work and prepare a precise decision request for that blocker.
When an architectural contract is missing, do not invent it merely to unblock implementation.
When a test cannot run, perform all useful safe static analysis and preparation, but never represent inspection as runtime verification.
---
# 4. STAGE A — PROTECT AND RECONCILE THE REPOSITORY
Before modifying any file:
1. Confirm repository root, branch, HEAD, and working-tree status.
2. Inspect staged, unstaged, untracked, and ignored entries.
3. Inspect relevant worktrees, stashes, reflogs, and existing evidence artifacts where safe.
4. Identify the current five B-1 files and verify their actual paths.
5. Identify all existing uncommitted work that must remain untouched.
6. Record the baseline hashes of the B-1 files and other protected files.
7. Identify the latest authoritative ADR-023 sections and any superseding records.
8. Determine whether the remote state is verifiable under the currently authorized network policy.
Never assume the previous report's file counts, hashes, or test state are still current.
Do not print secrets or sensitive environment contents.
Maintain a clear distinction between pre-existing changes and changes made during this mission.
Do not clean the repository or try to make its working tree appear tidy.
---
# 5. STAGE B — COMPLETE THE AUTHORIZED B-1 CONTRACT
Inspect the actual code, tests, call paths, and architecture records associated with B-1.
At minimum, review:
- `ProjectionFacetConstraint.php`
- `ProjectionFacetConstraintState.php`
- `ProjectionQueryConstraints.php`
- `ProjectionQueryConstraintsFactory.php`
- `ProjectionQueryConstraintsTest.php`
- Related Capability, Path, Identity, Coexistence, and Disclosure contracts.
- The relevant sections of ADR-023 §8.28–§8.33 and any authoritative earlier decisions.
Locate files from the actual repository root rather than relying on assumed relative paths.
## B-1 invariants to preserve
Verify and preserve:
- The actual Capability decision remains authoritative.
- Caller-supplied constraints cannot claim unsupported states.
- Freshness mismatches are rejected.
- Missing or insufficient evidence remains fail-closed.
- Exact facet-code matching is preserved unless an approved mapping contract exists.
- Existing validation and duplicate-check error priorities remain stable.
- The factory and direct `create()` paths agree on valid inputs.
- Existing serialized fields, keys, mappings, and public contracts remain unchanged unless explicitly authorized.
- No accidental dependency is introduced between architectural packages.
- Scientific question identity and provenance remain intact.
Fix genuine defects when their correct behavior follows from existing approved decisions.
Do not perform broad refactors or stylistic rewrites.
## Resolve the four outstanding questions through evidence
### A. `retrieval_method`
Trace its current source and propagation.
Determine whether caller input is consistent with the existing `ProjectionEnvelope::create()` contract.
Do not derive it from Path unless an approved Path contract provides the required information.
If the existing contract already resolves the question, document the evidence and proceed. Otherwise, prepare a concise owner decision with the available options and consequences.
### B. Visibility of `ProjectionQueryConstraints::create()`
Assess the public method against the actual contract and compatibility requirements.
Prefer retaining the public API if the consistency check makes it safe and the approved contract expects direct callers.
Do not change visibility merely for aesthetic reasons. Restrict it only if existing authority clearly permits the change; otherwise leave the question explicitly unresolved.
### C. Projection-to-Capability facet-code correspondence
Verify exact matching, trimming, case sensitivity, and fail-closed behavior.
Do not introduce case folding, aliases, inferred mappings, or new Capability semantics without authority.
If exact matching is consistent with the existing contract, preserve it and document the evidence.
### D. `query_constrainable` in the required AND set
Treat this as a B-2 semantic decision.
Do not implement B-2 or infer the intended behavior.
Explain exactly which runtime behavior depends on this decision and why it cannot be treated as verified before authorization.
Complete every independent B-1 task despite this dependency.
---
# 6. STAGE C — TEST ENGINEERING AND DEFECT RESOLUTION
Investigate the existing B-1 tests and relevant neighboring tests.
Determine whether the test suite covers:
- Unsupported Capability claims.
- VERIFIED, PARTIAL, UNAVAILABLE, UNVERIFIED, NOT_APPLICABLE, and MISSING cases where applicable.
- CURRENT, STALE, and UNKNOWN freshness.
- Malformed decision outcomes.
- Empty and duplicate constraint lists.
- Direct `create()` versus factory behavior.
- Numeric facet codes and PHP array-key conversion behavior.
- Stable error messages and validation order.
- Correct handling of absent Capability cells.
- Fail-closed behavior when evidence or freshness is insufficient.
- Preservation of existing output and serialization contracts.
Improve focused test coverage wherever the correct expected behavior is already approved.
Do not weaken tests, delete failing tests, change assertions solely to obtain green results, or use mocks as proof of real database behavior.
## Test runtime policy
First inspect the existing environment.
If PHP and all required dependencies are already available and the tests can run safely without prohibited operations, execute the focused tests.
If PHP is absent and dependencies are unavailable:
- Do not install PHP or packages.
- Do not populate `backend/vendor`.
- Do not use Docker or Compose.
- Do not access a database.
- Do not use network access.
- Do not claim successful test execution.
Prepare exact test commands for an authorized environment and continue the remaining safe engineering work.
The required suites should include:
1. Projection.
2. Capability domain contracts.
3. Path.
4. Identity.
5. Coexistence.
6. Disclosure.
Inspect their bootstrap and configuration first.
Exclude tests that execute migrations or require database access when those operations are prohibited.
For every test actually run, record the exact command, exit code, and actual result.
Distinguish clearly between:
- Static inspection.
- Syntax validation.
- Unit tests executed.
- Integration tests executed.
- Database-specific behavior verified.
- Runtime behavior not yet verified.
---
# 7. STAGE D — RECONCILE THE PHASE 1 EXIT MATRIX
Rebuild the Phase 1 exit matrix from the actual authoritative ADR and supporting evidence.
Include all applicable exit items, at least `P1-EXIT-01` through `P1-EXIT-11`.
For each item record:
`Exit ID | Required condition | Authoritative decision | Code evidence | Test evidence | External evidence | Owner approval | Status | Remaining action`
Use only evidence-supported statuses:
- `PASS`
- `PASS WITH DOCUMENTED LIMITATION`
- `IMPLEMENTED — NOT VERIFIED`
- `NOT RUN`
- `BLOCKED BY OWNER DECISION`
- `BLOCKED BY EXTERNAL DEPENDENCY`
- `FAIL`
- `NOT VERIFIED`
- `NOT APPLICABLE`
Do not convert missing evidence into a pass.
Do not treat a proposed ADR record as an accepted decision.
Do not claim that a gate is closed merely because its documentation exists.
Preserve the previously completed IC design and acceptance gates. Do not rerun completed adversarial reviews without a specific, evidence-based reason.
---
# 8. STAGE E — IC INVENTORY AND OPERATIONAL DEPENDENCIES
Investigate all Phase 1 dependencies involving:
- The 109 IC inventory seats.
- Existing Gate A and Gate B decisions.
- Identity and access evidence.
- `P1-EXIT-05` staging and operator evidence.
- Runtime Source Registry authorization.
- Capability/IC integration boundaries.
- Source-matrix integrity.
- Formal Phase 1 verification and disposition.
Separate repository-solvable work from work requiring real external evidence.
Prepare the exact evidence checklist and operator actions needed for staging where appropriate.
Do not execute external operations without authorization.
Do not fabricate identity verification, source classification, staging outcomes, operator records, or owner approval.
Do not activate any source or populate any of the 109 seats.
Do not expand B-1 authorization into Registry integration, access-family adapters, or B-2.
---
# 9. STAGE F — ROOT-CAUSE ANALYSIS OF BASELINE FAILURES
Review the previously recorded test failures, but do not expand the current implementation scope.
Earlier reports identify failures in areas such as:
- Multi-answer identity serialization.
- FAOSTAT-related tests.
- Statistical claim alignment.
- QUS/CSQ changes.
- Matcher behavior.
- Other Feature-test failures.
Determine which failures directly affect B-1 or the mandatory Phase 1 acceptance baseline.
Do not modify unrelated synthesis, FAOSTAT, QUS/CSQ, matcher, frontend, or other features under the B-1 authorization.
For out-of-scope defects:
- Preserve the evidence.
- Identify the root cause if possible.
- Record the affected component and test.
- State whether the failure blocks a mandatory Phase 1 criterion.
- Recommend the separate authorization required for a fix.
Do not claim the entire repository is green if only B-1 tests pass.
---
# 10. STAGE G — DOCUMENTATION AND EVIDENCE CONSOLIDATION
Review the proposed ADR-023 §8.33.9 execution record against the actual code, test file, and current hashes.
Verify every claimed implementation fact before including it.
Correct factual inaccuracies in the proposal if necessary.
However, do not modify ADR-023 or apply the record without explicit authorization.
Prepare an append-only proposed record that distinguishes:
- Existing owner decisions.
- New implementation facts.
- Static review findings.
- Tests actually executed.
- Tests not run.
- External evidence still missing.
- Unresolved decisions.
Do not rewrite or erase historical decisions.
Do not invent a passing test count or use stale hashes.
If a documentation change is already explicitly authorized elsewhere in the current project context, you may apply only that authorized change. Otherwise, prepare the proposal for owner review.
---
# 11. STRICT SECURITY AND REPOSITORY PRESERVATION
These restrictions are mandatory throughout the mission.
## Git
Do not execute:
- `git add`
- `git commit`
- `git push`
- `git pull`
- `git fetch`
- `git merge`
- `git rebase`
- `git reset`
- `git checkout`
- `git switch`
- `git clean`
- `git stash`
Do not change branches or rewrite history.
Read-only inspection commands are allowed.
Do not assume that remote access is authorized. If the remote state cannot be verified under the current policy, mark it `REMOTE STATE UNVERIFIED`.
## Files
Preserve all existing tracked, untracked, ignored, temporary, and unrelated files.
Never delete, move, rename, overwrite, or clean existing artifacts.
Before editing an existing file, inspect its current content and preserve unrelated modifications.
Keep every change focused and attributable.
Do not create speculative implementations or duplicate architectural layers.
## Environment and infrastructure
Do not run Docker or Compose.
Do not install or upgrade packages.
Do not modify dependency lockfiles, environment files, secrets, CI permissions, or deployment configuration.
Do not run migrations, seeders, database writes, or production/non-test database queries.
Do not access external services or use network access.
Do not print secrets.
## Architecture and activation
Do not activate scientific sources.
Do not implement B-2.
Do not implement Registry integration or adapters unless explicitly authorized by an existing authoritative decision and clearly within the current scope.
Do not start Phase 2.
There is no Phase 3 in this program.
Do not weaken the CSQ semantic authority, convert scientific search into keyword-only retrieval, or discard scientific identity, provenance, evidence, or query constraints.
---
# 12. WORKFLOW: DO NOT STOP FOR INTERMEDIATE REPORTS
Execute the entire mission in one sustained engineering pass.
Use the following sequence:
1. Establish the protected baseline.
2. Reconcile authoritative decisions.
3. Inspect the current B-1 implementation and tests.
4. Resolve in-scope defects supported by existing contracts.
5. Strengthen focused tests.
6. Run safe available validation.
7. Investigate mandatory Phase 1 exit dependencies.
8. Prepare exact owner decisions for unresolved authority boundaries.
9. Reconcile the proposed ADR execution record.
10. Rebuild the exit matrix.
11. Verify the final working-tree state and preservation.
12. Deliver one comprehensive final report.
Do not stop after writing a plan or identifying the first blocker.
Do not repeatedly ask the owner to approve routine implementation choices.
Do not perform prohibited operations merely to finish the workflow.
If a single dependency blocks one task, continue all independent tasks.
---
# 13. FINAL DELIVERABLE
Produce one consolidated final report with these sections.
## A. Executive verdict
Choose exactly one:
- `PHASE 1 CLOSED — VERIFIED`
- `PARTIALLY COMPLETE — BLOCKED`
- `NOT READY — EVIDENCE INSUFFICIENT`
## B. Actual engineering changes
List every changed file, the defect fixed, the applicable requirement, and the corresponding test.
If no code change was justified, explain why and identify the evidence supporting that conclusion.
## C. B-1 contract assessment
Report the status of each B-1 invariant and each of the four open questions.
## D. Test and validation report
List exact commands actually executed, exit codes, results, and environmental limitations.
Separate static inspection from executed tests.
## E. Phase 1 exit matrix
Report every mandatory exit item, its evidence, status, and remaining action.
## F. Root-cause and regression report
Separate B-1 failures from unrelated baseline defects.
## G. Owner decisions required
List only decisions that genuinely require owner authority.
For each decision provide:
- Exact question.
- Existing evidence.
- Available options.
- Recommended option.
- Consequence of each option.
- Exit criteria affected.
Group related questions to minimize owner interaction, but never combine unrelated decisions into an ambiguous approval.
## H. External dependencies
List staging, operator, identity, infrastructure, or other external requirements that cannot be satisfied by repository changes alone.
## I. ADR-023 proposal
Provide corrected append-only proposed text, if needed, with verified facts and no invented approvals.
## J. Final preservation audit
Report initial and final branch, HEAD, working-tree status, and any changed file hashes where safe to verify.
State explicitly whether any prohibited action occurred.
## K. Single recommended next action
Recommend the highest-value next action based on the actual evidence.
If Phase 1 remains blocked, identify the smallest set of actions that would move it toward closure.
## L. Phase 2 readiness
Unless all mandatory Phase 1 exit criteria have been satisfied with evidence and required authorization, state:
`PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED`
---
# 14. DEFINITION OF SUCCESS
Success means that all safe, authorized engineering work has been completed, all relevant evidence has been consolidated, and the remaining owner decisions and external dependencies have been reduced to a precise, minimal list.
It does not mean forcing every status to PASS.
It does not mean activating sources or bypassing missing decisions.
It does not mean running prohibited operations.
It does not mean producing repeated audits without engineering progress.
**Begin now. Work autonomously through all stages, preserve the repository, perform every safe in-scope action, and deliver one comprehensive final report. Do not send intermediate reports or ask for routine approvals. Stop only at genuine authority or safety boundaries.**
