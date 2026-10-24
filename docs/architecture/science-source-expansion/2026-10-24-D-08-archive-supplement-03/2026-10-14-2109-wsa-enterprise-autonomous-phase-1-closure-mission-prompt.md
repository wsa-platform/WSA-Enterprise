<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15251). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 9:09 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15252; it is archived once. -->

---
# WSA-Enterprise — Autonomous Phase 1 Closure Mission
## High-Autonomy Engineering Execution Under Strict Safety Controls
**Repository:** `wsa-platform/WSA-Enterprise`
**Expected branch:** `phase-18-m18-ai-marketing-communications`
**Last reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`
**Primary architecture record:** `docs/architecture/ADR-023-scientific-source-expansion.md`
**Mission type:** Multi-work-package engineering campaign, evidence-driven closure, regression investigation, test readiness, architecture audit, and release-readiness verification.
**Primary objective:** Move Phase 1 as close as legitimately possible to formal closure through autonomous, authorized engineering work, while preserving the repository and all user data, maintaining scientific and architectural contracts, and avoiding repeated conversational approvals for routine investigative work.
**Phase 2 is explicitly prohibited throughout this mission.**
---
# 1. EXECUTION MANDATE
Act as the principal engineer responsible for completing the existing Phase 1 closure campaign.
You are expected to do the engineering work, not merely describe it.
Work autonomously across all independent work packages. Read existing code, tests, ADRs, historical reports, and deployment configuration. Establish verified root causes, perform authorized changes, validate them where possible, and prepare a single consolidated report at the end.
Do not repeatedly ask the user to approve:
- Read-only repository inspection.
- Static analysis.
- Existing test and contract inspection.
- Read-only Git history analysis.
- Failure classification.
- Hash and preservation verification.
- Preparation of proposed patches.
- Documentation of findings in the final report.
- Independent work that is already covered by explicit authorization.
Do not pause the entire mission because one item is blocked. Continue all independent work that can proceed safely.
When a genuine owner decision, privileged action, external dependency, or new scope authorization is required, isolate that blocker and continue elsewhere.
**Do not confuse engineering autonomy with authority to change architecture, access external systems, or perform destructive operations.**
---
# 2. AUTHORITATIVE CURRENT STATE
Use the latest supplied Phase 1 Final Closure Campaign report as the initial evidence source, then verify relevant facts against the actual repository.
Reported current state:
- Branch: `phase-18-m18-ai-marketing-communications`.
- Local HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`.
- Local tracking reference reportedly equals HEAD.
- Remote state is unverified because network access was not used.
- 17 tracked-modified files and 1118 untracked entries were reported.
- Five B-1 files are untracked and must be preserved.
- No repository changes were made during the latest campaign.
- PHP is unavailable on the host and `backend/vendor` is empty.
- PHPUnit suites were not run during the latest campaign.
- B-1 contract checks passed static inspection, but B-1 runtime validation remains outstanding.
- Phase 1 is `PARTIALLY COMPLETE — BLOCKED`.
- Phase 2 is `NOT STARTED`.
- ADR-023's proposed §8.33.9 record remains unapplied.
- No new Gate A/B authorization is implied beyond the existing documented IC and B-1 scopes.
These are reported facts, not permission to assume the live repository still matches them. Recheck before acting.
---
# 3. SAFETY POLICY — NON-NEGOTIABLE
## 3.1 Preserve repository and user state
Never run:
- `git reset`
- `git clean`
- `git checkout` or `git switch`
- `git stash`
- `git rebase`
- `git cherry-pick`
- `git commit`
- `git push`
- `git amend`
- Any command or script that changes branch history, discards work, or cleans untracked files.
Do not stage changes.
Do not delete, rename, relocate, or overwrite:
- Tracked modifications.
- Untracked files.
- Ignored files.
- Temporary reports or evidence.
- Stashes.
- Worktrees.
- User-created artifacts.
- Local configuration.
- Historical evidence.
Do not assume that untracked files are disposable. Do not attempt to clean the working tree.
Never replace a whole file when a minimal patch is sufficient. Before editing any file, inspect its existing contents and the exact local diff. Do not overwrite existing changes with a HEAD version.
At the end, verify the complete repository state against the initial snapshot.
## 3.2 Protected files
Treat the following as protected unless a later explicit authorization names the exact file and operation:
- `.env` and all real environment files.
- `backend/.env.example`.
- `composer.lock` and all dependency lockfiles.
- `phpunit.xml` and test bootstrap configuration.
- `ADR-023` and other ADR files.
- Deployment and rollback scripts.
- GitHub Actions workflows.
- Docker Compose and Docker configuration.
- Database migrations.
- Source registry and source activation configuration.
- Existing user-modified QUS/CSQ code and tests.
- Existing answer-identity implementation and tests.
Read-only inspection is allowed. Proposed diffs are allowed. Changes are not allowed without the required explicit authorization.
## 3.3 Runtime and external operations
Do not perform any of the following unless separately and explicitly authorized:
- Network access.
- Dependency installation or downloads.
- Docker/container execution.
- Database connections or writes.
- Migrations.
- Production or staging deployment.
- Rollback drills.
- Seeding duplicate records.
- External API calls.
- Activation of scientific sources or adapters.
- Changes to CI execution or repository settings.
A previous authorization for one activity does not authorize a different activity.
`--network none` is not, by itself, proof of safe container execution. Before any future authorized isolated run, the execution plan must verify mounts, writable volumes, database connectivity, environment variables, bootstrap behavior, and whether any tests invoke migrations or write data.
If PHP is absent, do not install it. Record the limitation and proceed with static and documentary work.
## 3.4 Architecture and scientific integrity
Do not:
- Start Phase 2.
- Expand the authorized B-1 scope into B-2.
- Activate the Runtime Source Registry.
- Implement or activate the 109 source entries.
- Implement access-family adapters.
- Alter the CSQ identity contract implicitly.
- Change the source-selection policy implicitly.
- Change answer identity, evidence provenance, language behavior, or Home/Crop isolation without explicit authorization.
- Modify deployment behavior without authorization.
- Re-baseline a test simply because it is failing.
- Remove or weaken assertions to obtain green results.
- Invent owner approvals, gate outcomes, test runs, staging evidence, or CI results.
Maintain the existing contracts for:
- CSQ as the semantic authority for the scientific question.
- Source-specific query construction.
- Original source-file preservation.
- Source identity and provenance.
- Evidence sufficiency and answer confidence.
- Separation of Home and Crop search episodes.
- Existing approved Gate A/B scopes.
## 3.5 Evidence classification
Every important conclusion must use one of these labels:
- `[EXECUTED]` — command actually ran; include the exact command and exit code.
- `[STATIC]` — verified through source inspection.
- `[HISTORICAL]` — prior recorded evidence, not rerun.
- `[INFERRED]` — plausible explanation not yet verified.
- `[NOT RUN]` — test or operation did not execute.
- `[OWNER]` — requires an architecture or scope decision.
- `[EXTERNAL]` — requires an operator, runtime, CI, remote, staging, or external dependency.
Never promote an inferred cause to a verified cause without evidence.
---
# 4. AUTONOMY AND DECISION POLICY
Use the following policy to reduce user interactions.
## Class A — Execute autonomously
Proceed without asking when the action is read-only or clearly covered by existing authorization:
- Repository inspection and preservation audits.
- Static analysis and contract tracing.
- Reading tests, reports, ADRs, and Git history.
- Reconstructing failure causes from source and existing evidence.
- Preparing test plans and exact commands.
- Comparing committed code with the working tree.
- Hash verification.
- Preparing proposed patches without applying them.
- Building the closure matrix and dependency graph.
- Running already authorized, locally available, non-destructive checks that do not violate the runtime restrictions.
## Class B — Execute only within existing explicit scope
Proceed only when the current authorization unambiguously covers the precise change.
For any code change:
1. Verify the violated contract.
2. Identify the minimal affected files.
3. Check for pre-existing user changes.
4. Make the smallest safe patch.
5. Add or update a focused test.
6. Execute permitted validation if available.
7. Inspect the diff and protected-file hashes.
8. Report any remaining uncertainty.
If authorization is ambiguous, do not expand it. Prepare the patch proposal and continue independent work.
## Class C — Requires explicit owner authorization
Do not execute until the user explicitly authorizes the exact scope:
- FAOSTAT application-code changes.
- CSQ/QUS/query-builder changes.
- Source-selector policy changes.
- Answer-identity contract changes.
- ADR-023 edits that record owner decisions or change the architecture record.
- Deployment script, deployment documentation, or GitHub workflow changes.
- Docker execution where not already authorized.
- Database, migration, staging, or production operations.
- Source Registry, B-2, adapter, and IC-seat work.
- New Gate A/B scopes.
- Any expansion of the existing scientific search architecture contract.
When blocked, do not ask one question at a time. Accumulate the necessary decisions in a single prioritized authorization packet and continue safe independent work.
---
# 5. WORK PACKAGE A — BASELINE AND PRESERVATION
Perform one complete baseline inspection.
Record:
- Branch and HEAD.
- Tracking-reference relationship.
- Remote-verification status.
- Tracked modifications.
- Untracked files.
- Ignored files.
- Staged files.
- Stashes and worktrees.
- Hashes of all five B-1 files.
- Hashes of protected files.
- Existing QUS/CSQ modifications.
- Existing ADR modifications.
- Relevant test and report files.
Do not repeat this full inventory unnecessarily. Repeat it only at final verification or if a material state change is detected.
Compare the actual state against the latest report. If different, document the discrepancy and preserve it.
**Deliverable:** verified baseline evidence and a concise preservation manifest.
---
# 6. WORK PACKAGE B — B-1 CONTRACT AND VALIDATION READINESS
Inspect the existing five files:
- `ProjectionFacetConstraintState.php`
- `ProjectionFacetConstraint.php`
- `ProjectionQueryConstraints.php`
- `ProjectionQueryConstraintsFactory.php`
- `ProjectionQueryConstraintsTest.php`
Preserve the existing implementation unless a concrete contract defect is demonstrated.
Verify:
1. Constraints are backed by the actual Capability decision.
2. Unsupported claims cannot become constrainable.
3. Freshness mismatches are rejected.
4. Malformed Capability decisions are rejected under the approved contract.
5. Direct creation and factory creation enforce consistent invariants.
6. Exact facet-key matching is preserved.
7. `'7'` and `'07'` remain distinct.
8. Duplicate and unbacked claims are handled consistently.
9. Empty facet lists behave according to the contract.
10. Serialization and existing response shape remain unchanged.
The latest report says all ten checks pass static inspection and that the five files have not changed since the last recorded hashes. Verify only if the current state or evidence warrants it; do not rewrite the files simply to create activity.
No additional test should be added unless a specific uncovered case is demonstrated.
If an approved runtime is genuinely available, execute only the relevant permitted tests. If PHP is unavailable, stop runtime discovery after one reliable check and record `[NOT RUN]`.
**Acceptance:** B-1 is either validated by actual execution or explicitly classified as statically reviewed but runtime-unverified. Do not call it fully tested without execution evidence.
---
# 7. WORK PACKAGE C — SCIENTIFIC REGRESSION INVESTIGATION
This package is investigative first. Do not modify FAOSTAT, CSQ, QUS, the query builder, source selector, or answer identity without the specific authorization described above.
## C1. FAOSTAT measure-resolution chain
Reported root-cause chain:
1. Commit `f6085e4` introduced production-operator patterns in `AgriculturalEntityCatalog.php` that label “X production” and “production of X” as `property_surface => yield`.
2. QUS can select the first property term as the generic topic, yielding `yield`.
3. `FaoStatQclElementSemantics::detectRequestedMeasures()` combines original question text with planner fields such as `topic`, `requestedInformation`, and `researchIntent`.
4. The combined surface can detect `yield` before `production`.
5. Multiple detected measures result in no resolved element.
6. The decomposed request may then use yield code `2413` instead of production code `2510`.
Required actions:
- Trace the exact function, callers, tests, and commit history read-only.
- Verify the report's code references and the actual question inputs.
- Distinguish facts confirmed statically from facts requiring runtime diagnostics.
- Inspect the relevant test cases and expected codes.
- Determine whether the existing function contract supports prioritizing the user's original question over planner-generated wording.
- Prepare the minimum repair proposal and focused tests.
The reported proposal is to detect measures from `originalQuestion` and `normalizedQuestion` first, then use structured surfaces and planner fields only as fallback. Assess this proposal; do not assume it is correct without checking the contract.
An alternative root fix is to prevent CSQ from labelling “production” as “yield.” This is a separate CSQ-scope change and must not be bundled implicitly.
Do not run a historical bisect that changes repository state. Use read-only commit comparisons.
## C2. FAOSTAT source-selector policy conflict
Reported conflict:
- An older test expects `capability_not_required`.
- Later code appears to consider FAOSTAT whenever the feature flag is enabled.
- The reported universal consideration policy does not appear to have an explicit ADR decision.
- Feature-flag state changes the reported skip reason.
Required actions:
- Inspect the selector implementation, relevant tests, and commits.
- Determine whether the current approved policy is explicit or merely implemented.
- Prepare both possible options and their consequences.
- Do not update the test or change selector behavior until the owner confirms the policy.
## C3. CSQ crop-profile variant regression
Reported failure:
- `EntitySpecificityPreservationContractTest`
- `PostCsqSemanticPreservationTest`
Reported cause:
The test asserts that a crop-profile query such as “sweet potato farming needs” does not have independent question roles, while the current CSQ reports roles and triggers role-based variants. The knowledge-option intent may be counted as an independent role even though the retrieval contract says intent alone does not count.
Required actions:
- Inspect `RetrievalSpecification` around the reported lines 725–727.
- Inspect `CropKnowledgeOptionCatalog::isOptionKey`.
- Trace the changes associated with `f6085e4`, `6708856`, and `9411f70` read-only.
- Verify whether the intended crop variant family was unintentionally lost.
- Preserve question identity. Do not add keywords or roles the user did not express.
- Prepare a minimal proposal with explicit before/after semantic examples.
Do not edit CSQ or QUS in this campaign without authorization.
## C4. Statistical claim aligner
Keep the three reported failures separate. Establish the actual contract for:
- `quantity` versus production quantity.
- Arabic yield wording.
- Bare-year alignment.
Do not decide that the test or implementation is wrong solely because they disagree. Record the contract question and smallest possible scope.
## C5. Answer identity
Keep the reported `MultiAnswerIdentitySerializer` and `ScientificMultiAnswerProducer` fixture failures separate. Inspect the required roles and existing fixtures, but do not change the answer-identity contract without authorization.
## C6. Canonical property matcher and membership register
- Inspect the working-tree test modification and preserve it.
- Determine why the canonical matcher test fails at HEAD but is reportedly fixed by the local test edit.
- Do not discard or automatically accept the edit.
- Investigate the membership-register test's line-ending/path issue without rewriting files or creating fake fixtures.
- Determine whether a valid test run is possible only after an authorized runtime and appropriate checkout layout are provided.
## C7. Shared Feature failures
Classify the reported 61 cases and the previously reported 52 methods against actual evidence. Do not merge the counts without explaining the counting difference.
Separate:
- Committed failures.
- Working-tree-only cases.
- New tests absent at HEAD.
- Environmental failures.
- Failures that directly block the required five-source path, CSQ identity, Home/Crop isolation, or answer contract.
- Failures that require separate owner authorization.
Do not attempt broad repairs without a validated cause and authorized scope.
**Deliverable:** one consolidated regression matrix with verified cause, evidence classification, scope, blocking criterion, proposed repair, test plan, and authorization status.
---
# 8. WORK PACKAGE D — TEST EXECUTION READINESS
Inspect the environment once. Do not repeatedly search for PHP or attempt installation.
If PHP 8.2+ and existing PHPUnit dependencies are present, determine which suites can safely run without unauthorized database changes.
The existing focused command list is available in the latest campaign report. Verify paths before execution.
Before running any suite, inspect:
- Test bootstrap.
- `RefreshDatabase` usage.
- Migration invocation.
- External service requirements.
- Environment variable dependencies.
- File writes and side effects.
Do not run database-dependent tests that trigger migrations or writes without explicit authorization. Preserve the existing exclusions for the persistence suites and any additional unsafe tests identified by inspection.
If PHP or vendor dependencies are missing:
- Do not install or download anything.
- Do not modify `composer.lock`, `.env`, `phpunit.xml`, or container configuration.
- Record `[NOT RUN]`.
- Continue static analysis and preparation.
For a future explicitly authorized container run, prepare a preflight checklist requiring:
- Verified image and PHP/PHPUnit versions.
- Network disabled.
- Read-only repository mount.
- No writable project volume unless specifically justified and approved.
- No reachable production/staging database.
- No hidden writable dependency or database volume.
- No migrations or writes.
- Verified `docs/` availability for the membership-register test.
- Explicit `FAOSTAT_ENABLED` state for selector diagnostics.
- Exact command allowlist.
- Before/after preservation hashes.
Do not run Docker during this mission without explicit authorization.
---
# 9. WORK PACKAGE E — ADR-023 AND OWNER DECISIONS
Inspect the current ADR before preparing a proposed change.
The latest report contains a corrected proposed §8.33.9 execution record, but it remains unapplied.
Prepare the precise append-only patch proposal. Do not apply it without authorization.
The proposal must:
- Preserve the existing ADR's CRLF line endings.
- Preserve historical content and section order.
- Identify the record as an execution record, not an architecture approval.
- Use `[OWNER AUTHORIZATION — conversation record]` only where actual evidence supports it.
- Preserve the B-1 hashes and timestamps only after re-verification.
- State that B-1 tests are NOT RUN if still true.
- State that Phase 1 is BLOCKED and Phase 2 NOT STARTED.
- Keep unresolved questions unresolved until approved.
- Record the historical FAOSTAT and CSQ regression evidence with appropriate provenance.
- Keep staging and operator evidence explicitly outstanding.
Do not turn recommendations into decisions. Do not record a decision as approved just because this mission proposes it.
Prepare one compact owner-decision packet covering:
1. T10/T8 definitions and the Option B boundary replacing T2 wording.
2. `projection_query_constraints.v1` as the B-1 constraint metadata.
3. Recording condition (c) in a Gate A record after the required preceding decisions.
4. Applying §8.33.9.
5. The four B-1 questions: `retrieval_method`, `create()` visibility, exact matching, and deferring the AND-set to B-2.
6. The approved test runtime and scope.
7. The committed-HEAD baseline.
8. FAOSTAT repair authorization.
9. The FAOSTAT selector-policy decision.
10. CSQ crop-profile repair authorization.
11. Deployment script/documentation/workflow changes.
12. Staging validation.
13. Future Gate A/B scopes.
14. Ownership and evidence collection for the 109 IC seats.
15. Approval or amendment of the §8.28.7 closure checklist.
For each decision, include:
- Recommendation.
- Alternatives.
- Evidence.
- Exact files/systems affected.
- Risk.
- What becomes unblocked.
- What remains blocked.
Do not ask these questions one at a time. Present them as a single prioritized packet at the end.
---
# 10. WORK PACKAGE F — DEPLOYMENT AND STAGING READINESS
Read-only inspect:
- `scripts/deploy-production.sh`
- `scripts/rollback-production.sh`
- `scripts/smoke-production.sh`
- `scripts/verify-production.sh`
- `docs/deploy-production.md`
- `.github/workflows/deploy-production.yml`
- Compose files.
- Relevant migrations and preflight diagnostics.
The latest report identifies:
- The new application starts before the migration.
- The migration runs inside the new application container.
- There are no required diagnostic/index-verification hold points.
- The deploy uses a mutable `main` tag by default.
- The rollback target is not reliably recorded.
- The documentation describes the old sequence.
- The GitHub workflow has its own mutable-tag and reset behavior and must be considered alongside the shell script.
Verify these findings against current files. Do not assume the previous patch proposal is correct merely because it exists in the report.
Prepare a coordinated patch proposal for all affected paths, including workflow behavior, documentation, immutable image identity, migration sequencing, operator confirmation, index verification, smoke checks, and rollback target recording.
Do not edit or execute the deployment flow in this campaign.
Do not connect to staging or production. Do not seed duplicate records. Do not perform rollback drills.
Record the actual evidence required for staging:
- The §8.25.7 diagnostic returns zero rows.
- Index creation is observed before any new application container starts.
- The duplicate-ACTIVE drill refuses the migration on a suitable staging copy.
- No unintended data changes occur.
- Rollback restores the prior release.
- Smoke checks pass.
- The operator performs the read-only diagnostic on each real environment.
Distinguish a prepared plan from executed staging evidence.
---
# 11. WORK PACKAGE G — EXIT MATRIX AND DEPENDENCY GRAPH
Reconcile P1-EXIT-01 through P1-EXIT-11 against the exact current requirements in ADR-023.
For every exit criterion provide:
- Exact acceptance requirement.
- Current status.
- Evidence and source.
- Whether evidence is historical or newly executed.
- Missing requirement.
- Smallest next action.
- Required authority or external role.
- Dependencies.
- Whether independent work can proceed.
Use only:
- `PASS`
- `PASS WITH DOCUMENTED LIMITATION`
- `FAIL`
- `NOT RUN`
- `BLOCKED BY OWNER DECISION`
- `BLOCKED BY EXTERNAL DEPENDENCY`
Do not invent a PASS state.
Special handling:
**P1-EXIT-01:** Verify the precise conditions and owner decisions against the ADR, not merely the report summary.
**P1-EXIT-02/03:** Recognize only actual Gate A/B records and their exact approved scope. No inherited authorization.
**P1-EXIT-04:** Treat the reported 121 PostgreSQL tests and 840 assertions as historical unless rerun.
**P1-EXIT-05:** Require both approved deployment behavior and actual staging/operator evidence.
**P1-EXIT-06/07/08/10:** Keep Registry, 109-seat identity verification, adapters, and source-matrix closure blocked until the corresponding authority and evidence exist.
**P1-EXIT-09:** Require executed, relevant tests and resolution of required regressions. Static review alone is insufficient.
**P1-EXIT-11:** Formal closure depends on the actual acceptance criteria for P1-EXIT-01 through P1-EXIT-10.
Do not interpret documentary completion as operational closure.
---
# 12. WORK PACKAGE H — IMPLEMENTATION RULES FOR AUTHORIZED FIXES
When a specific change is already authorized, do not stop at proposing it.
Execute the smallest justified patch and its focused tests.
For each authorized fix:
1. Identify the violated contract.
2. Capture the relevant baseline.
3. Reproduce the failure where an authorized runtime is available.
4. Implement the minimal fix.
5. Add or update focused tests without weakening existing expectations.
6. Run the focused suite if permitted.
7. Inspect related regression tests.
8. Inspect the complete diff.
9. Verify protected files and user work remain unchanged.
10. Record the exact test result and residual uncertainty.
If the change is outside existing authorization, prepare the patch proposal and continue independent work.
Do not bundle FAOSTAT, CSQ, selector, answer identity, deployment, or ADR edits into B-1.
Do not change code merely to make the campaign appear productive. A justified no-change outcome is acceptable.
---
# 13. WORK PACKAGE I — FINAL PRESERVATION AUDIT
At the end, repeat the baseline checks and compare them with the initial state.
Verify:
- Branch and HEAD.
- Tracking-reference state.
- Remote verification status.
- Tracked and untracked changes.
- Staged state.
- Ignored files.
- Stashes and worktrees.
- Hashes of the five B-1 files.
- Hashes of protected files.
- All file diffs created during the campaign.
Confirm whether any prohibited action occurred.
If any unexpected state change is found:
- Stop further mutations.
- Preserve the current state.
- Identify the exact files and hashes.
- Do not automatically revert the change.
- Report the discrepancy.
No commit, push, staging, branch change, history rewrite, cleanup, package install, Docker execution, database operation, deployment, or source activation may occur without the applicable explicit authorization.
---
# 14. FINAL DELIVERABLE — ONE REPORT, NOT MULTIPLE REPORTS
Produce one consolidated report with:
1. Executive verdict.
2. Verified baseline and preservation state.
3. Work actually performed.
4. B-1 contract result and validation status.
5. Regression matrix with confirmed versus inferred causes.
6. Test execution results or exact reasons for NOT RUN.
7. P1-EXIT-01 through P1-EXIT-11 matrix.
8. Gate A/B inventory and scope boundaries.
9. ADR-023 proposed amendment and exact diff, clearly marked NOT APPLIED unless authorized.
10. Deployment and staging readiness, including both shell and workflow paths.
11. Owner-decision packet, consolidated and prioritized.
12. External/operator dependencies.
13. Critical path to closure.
14. Explicit Phase 2 status.
15. Final preservation audit.
Every conclusion must cite its actual evidence location: file and line, test and command output, commit, or existing ADR section. Do not fabricate references.
Separate:
- Completed engineering work.
- Static findings.
- Historical test results.
- Tests actually executed.
- Proposed but unapplied changes.
- Owner decisions.
- External dependencies.
End with a clear verdict:
- `CLOSED` only if every authoritative Phase 1 exit condition is actually satisfied.
- Otherwise `PARTIALLY COMPLETE — BLOCKED` or `NOT COMPLETE`, with every blocker enumerated.
Never claim Phase 1 closure because a report is complete.
---
# 15. FINAL EXECUTION DIRECTIVE
Execute all independent, safe, authorized work in one continuous campaign.
Do not return after each work package.
Do not send intermediate reports.
Do not ask routine questions.
Do not repeat already completed analysis without a demonstrated evidence gap.
Do not wait for one blocked item before proceeding with unrelated safe work.
Do not expand authorization to make progress appear faster.
Collect all owner decisions into one final packet. Keep all unapproved changes unapplied.
The desired outcome is the maximum real progress toward Phase 1 closure with the minimum number of conversational turns, zero loss of existing work, strict scope discipline, and a fully auditable evidence trail.
**Phase 1 only. Phase 2 NOT STARTED. No fabricated approvals. No fabricated tests. No unsafe shortcuts.**
