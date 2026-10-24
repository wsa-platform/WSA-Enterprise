<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15201). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 8:48 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15202; it is archived once. -->

---
# WSA-Enterprise — Phase 1 Final Closure Campaign
## Comprehensive Execution Mandate — Evidence-Driven, Scope-Controlled, No Phase 2
**Role:** Act as the principal software engineer, architecture auditor, regression investigator, test engineer, and release-readiness verifier responsible for completing the existing Phase 1 closure work in the WSA-Enterprise repository.
**Mission:** Bring Phase 1 to the strongest legitimately achievable closure state, resolve every blocker that can be resolved under existing authorization, execute available validation where explicitly permitted, prepare precise evidence for owner-only decisions and external dependencies, and produce a final auditable exit determination.
This is an execution mission, not a documentation-only exercise. Perform authorized work rather than merely proposing it. Do not repeat completed investigations without a specific evidence gap. Do not claim completion when the required evidence does not exist.
---
# 1. AUTHORITATIVE BASELINE AND PROJECT CONTEXT
Repository: `wsa-platform/WSA-Enterprise`
Expected branch:
`phase-18-m18-ai-marketing-communications`
Last reported local HEAD:
`f2025315d8990f52f3c658c968a3ec8252ac18b0`
Primary architecture record:
`docs/architecture/ADR-023-scientific-source-expansion.md`
Relevant existing work and reports:
- Phase 1 baseline and forensic audit.
- ADR-023 sections covering Phase 1 exit criteria, IC persistence, B-1 projection constraints, deployment safety, and the proposed §8.33.9 record.
- Latest Phase 1 Finalization Campaign report dated 2026-10-14, containing the exit matrix, regression analysis, deployment findings, unresolved decisions, and preservation audit.
- Existing IC and B-1 Gate A/Gate B records.
- Existing B-1 implementation and tests under `backend/app/Services/Agriculture/Research/Projection/` and `backend/tests/Unit/Agriculture/Research/Projection/`.
Before acting, inspect the actual current repository state. Treat the values above as a reported baseline, not as permission to assume the current state is identical.
## Baseline verification requirements
Record and verify:
1. Current branch and HEAD.
2. Relationship between HEAD and the local upstream tracking reference.
3. Whether the remote state is verifiable; do not claim remote synchronization without evidence.
4. Tracked modified files, untracked files, ignored files, staged changes, stashes, and worktrees.
5. Existing B-1 files, their contents, line endings, BOM status, and SHA-256 hashes.
6. Protected configuration, lockfiles, ADR-023, deployment scripts, migration files, and other files whose preservation is required.
7. Existing changes in the QUS/CSQ, FAOSTAT, scientific research, and answer-identity areas.
Do not overwrite or clean the user's work to obtain a clean Git status. Do not use broad reset, clean, checkout, restore, stash, rebase, cherry-pick, or equivalent destructive Git operations.
If the actual baseline differs from the reported baseline, stop any action that depends on the discrepancy, preserve the state, and explain the exact difference. Continue independent read-only work where safe.
---
# 2. NON-NEGOTIABLE SAFETY AND AUTHORIZATION RULES
These rules apply throughout the entire mission.
## 2.1 Git and repository preservation
- Do not stage, commit, push, amend, reset, clean, switch branches, rebase, or modify Git history.
- Do not delete, relocate, rename, or overwrite existing user files, temporary evidence, ignored files, untracked files, stashes, worktrees, or historical artifacts.
- Do not use cleanup commands to simplify the repository.
- Do not overwrite a protected file merely because its current contents appear outdated.
- Do not assume untracked means disposable.
- Every authorized modification must have an exact, documented file scope and a before/after diff.
- If a change might overlap existing user work, preserve the existing content and stop that specific edit until the conflict is resolved.
## 2.2 Runtime, packages, and external systems
- No network access, package installation, dependency download, external API call, database connection, migration, deployment, production operation, or source activation unless explicitly authorized.
- No Docker/container execution unless the user explicitly authorizes the exact isolated test method and the relevant environment satisfies the approved safety constraints.
- Do not create a new runtime or modify lockfiles/configuration to work around missing PHP.
- Do not execute tests that trigger migrations or database writes unless specifically authorized.
- Do not seed duplicate records or conduct rollback/deployment drills without explicit staging authorization.
- Read-only local inspection is permitted when it does not alter repository or external state.
If PHP or PHPUnit is unavailable, do not install them or search repeatedly for alternative runtimes. Record the exact limitation and continue all useful static and documentary work.
## 2.3 Architectural boundaries
- Phase 1 only. Phase 2 must remain NOT STARTED.
- Do not activate any of the 109 scientific source entries.
- Do not activate the Runtime Source Registry, adapters, B-2, or any other unapproved scope.
- Do not silently change the CSQ scientific question identity, query semantics, evidence provenance, language rules, source selection, answer synthesis, or Home/Crop isolation.
- Do not weaken tests, delete failing tests, alter snapshots simply to obtain green results, suppress errors, lower thresholds, or re-baseline expected behavior without an explicit architecture decision.
- Do not infer owner approval from an earlier engineering prompt.
- Do not write an architectural decision into ADR-023 as approved unless the owner has explicitly approved that decision or the existing authorization unambiguously covers the exact change.
- A proposed record must remain clearly marked PROPOSED until authorized.
- Do not modify deployment scripts until the deployment change is explicitly authorized.
- Do not claim Phase 1 is closed unless every required exit criterion is satisfied with evidence.
## 2.4 Evidence integrity
Every important claim must be labeled as one of:
- VERIFIED BY EXECUTION
- VERIFIED BY STATIC INSPECTION
- DOCUMENTED HISTORICAL EVIDENCE — NOT RE-RUN
- INFERRED — NOT YET VERIFIED
- NOT RUN
- BLOCKED BY OWNER DECISION
- BLOCKED BY EXTERNAL DEPENDENCY
Never present static inspection as a successful runtime test. Never describe an unexecuted test as passing. Distinguish committed failures from working-tree-only failures and from environment-dependent failures.
---
# 3. CAMPAIGN METHOD
Execute this campaign in ordered work packages. Avoid repeatedly auditing the same material.
For each work package:
1. Establish the exact starting state relevant to that package.
2. Inspect the smallest authoritative code/test/document set that answers the question.
3. Perform all authorized work that is safe and supported by evidence.
4. Run only authorized tests that are actually available.
5. Record the exact commands, exit codes, relevant output, and file changes.
6. Update the working exit matrix and dependency graph.
7. Continue to the next independent work package without waiting for another user turn, unless an explicit owner decision or authorization is required.
Do not stop the entire campaign because one work package is blocked. Continue independent work safely.
Do not edit the same file repeatedly for cosmetic improvements. Prefer one coherent, minimal change per authorized scope.
---
# 4. WORK PACKAGE A — PRESERVATION AND BASELINE
Perform the baseline verification described in Section 1.
Produce an immutable-in-practice evidence record in the campaign report. Do not create a repository file unless its creation is independently authorized.
Identify:
- Which failures exist at the committed HEAD.
- Which failures exist only with the 17 reported tracked modifications.
- Which failures depend on `.env` or feature flags.
- Which tests have historical successful evidence but have not been rerun.
- Which local B-1 files are untracked and must remain preserved.
- Which findings cannot be established without network, runtime, CI, staging, or operator access.
Use explicit file lists and exact hashes wherever available. Do not rely solely on a summary or previous report.
**Acceptance:** Baseline is reproducible from the collected local evidence, and no protected or pre-existing user state has been changed.
---
# 5. WORK PACKAGE B — B-1 PROJECTION CONSTRAINTS
Scope:
- `ProjectionFacetConstraintState.php`
- `ProjectionFacetConstraint.php`
- `ProjectionQueryConstraints.php`
- `ProjectionQueryConstraintsFactory.php`
- `ProjectionQueryConstraintsTest.php`
Use the existing implementation and latest report as the starting point. Do not rewrite the implementation from scratch.
## Required contract checks
Verify that:
1. Constraint creation is backed by the actual Capability decision.
2. Unsupported claims cannot create constraints.
3. STALE, MISSING, VERIFIED, and CURRENT freshness/status cases behave according to the approved contract.
4. Malformed decisions are rejected, including when the requested constraint list is empty, wherever the current contract requires validation.
5. Direct `create()` calls and factory-created constraints enforce consistent invariants.
6. Exact facet-code matching is preserved; do not invent a mapping layer.
7. Numeric-looking identifiers such as `'7'` and `'07'` retain the identity semantics specified by the current contract.
8. Duplicate and unbacked numeric claims are handled consistently.
9. Empty constraint lists remain valid when the approved contract permits them.
10. Serialization, existing field names, public response shapes, identity mappings, visibility rules, and unrelated research behavior remain unchanged.
Inspect the existing tests and add a test only if a specific missing contract case is demonstrated. Preserve the current test style, line endings, and file encoding.
Do not change the contract merely to make the tests pass.
## Test execution
If the approved runtime is available:
- Execute the Projection tests.
- Execute the smallest relevant Capability, Path, and identity/coexistence suites that can validate the boundary without unauthorized database writes.
- Expand to regression tests only when the preceding results and dependencies justify it.
If the runtime is unavailable, mark these tests NOT RUN and state the exact missing prerequisite.
**Acceptance:** B-1 is either verified by execution or clearly classified as implementation-complete but runtime-unverified. It must not be reported as fully validated when tests were not run.
---
# 6. WORK PACKAGE C — OWNER-DECISION RECORD FOR B-1
Prepare a compact, evidence-backed decision packet for the following questions. Do not silently decide them on the owner's behalf.
### C1. `retrieval_method`
Reported evidence: `ProjectionEnvelope::create()` already accepts optional caller input and defaults it to UNKNOWN; `PathDecisionIdentity` does not contain this field.
Recommended decision: retain caller input under the existing contract.
Verify the exact signature and relevant commit before relying on this recommendation.
### C2. `ProjectionQueryConstraints::create()` visibility
Reported evidence: ADR §8.33.8 item 5 documents direct calls; the implementation validates direct calls against the Capability decision.
Recommended decision: keep the method public, unless code evidence reveals a concrete contract violation.
### C3. Projection facet-code mapping
Reported architecture rule: R-2 uses exact matching.
Recommended decision: preserve exact matching and defer any mapping contract to a separately authorized future scope.
### C4. `query_constrainable` in the required AND-set
This is a B-2 decision and is not authorized by this campaign.
Do not add facet dimensions to the required AND-set, alter Path eligibility, or wire a new capability contract as part of B-1.
Record the question as DEFERRED TO B-2.
For each item, provide:
- Exact code/ADR evidence.
- The recommended decision.
- Consequences and risks.
- Exact files that would change if approved.
- Whether the decision can be deferred without blocking independent tests.
Prepare one consolidated decision packet. Do not create a separate conversational approval request for every question.
---
# 7. WORK PACKAGE D — SCIENTIFIC REGRESSION BASELINE (P1-EXIT-09)
This is a critical closure blocker. Investigate it carefully without mixing unrelated fixes.
The latest report identifies the following groups.
## D1. FAOSTAT measure resolution — 7 Unit failures
Reported mechanism:
`FaoStatQclElementSemantics::detectRequestedMeasures()` combines user wording with planner fields including `topic`, `requestedInformation`, and `researchIntent`. When multiple measures are detected, the resolver returns no element, although the current comment says user wording wins.
Required actions:
1. Re-inspect the function and its callers.
2. Inspect the four failing questions and the exact planner fields used for each.
3. Establish the expected measure codes from existing tests/contracts; do not guess.
4. Determine whether the current evidence confirms the reported mechanism.
5. If tests can run under the authorized runtime, execute the focused tests and capture the exact failure output.
6. If a historical bisect is necessary, perform a read-only comparison of the relevant commits and file versions. Do not execute destructive `git bisect` operations or change repository state.
7. Prepare the minimum proposed repair: prioritize the user's explicit question wording and use planner surfaces only as fallback, but only if that behavior is supported by the existing contract and confirmed by diagnostics.
Do not implement the repair until its scope is explicitly authorized.
## D2. FAOSTAT selector policy test
Reported conflict:
- Older test expects FAOSTAT to be skipped with `capability_not_required`.
- Later policy commits appear to make FAOSTAT consideration universal when its feature flag is enabled.
- The reported test cannot pass under either feature-flag state because the expected reason differs.
Required actions:
1. Inspect the actual selector code, test history, and ADR-023 §8.32.7.
2. Determine whether the test or runtime policy is inconsistent with the current approved contract.
3. Do not alter the test expectation merely to obtain a green suite.
4. Prepare a recommendation and exact change proposal requiring owner approval.
## D3. CSQ variant preservation
Reported failures:
- `EntitySpecificityPreservationContractTest`
- `PostCsqSemanticPreservationTest`
Required actions:
1. Inspect the current code and both tests.
2. Compare the expected variant family against the current output.
3. Check the relevant committed changes read-only.
4. Determine whether crop-profile query variants were unintentionally lost.
5. Preserve the scientific identity of the user's question. Do not add independent roles or keywords that the user did not express merely to satisfy a snapshot.
6. Prepare the smallest repair proposal, but do not change CSQ without explicit scope authorization.
## D4. Other recorded failures
Keep separate:
- Three `ScientificStatisticalClaimAligner` failures.
- Sixteen `MultiAnswerIdentitySerializer` errors.
- Four `CanonicalPropertyIdentityAtMatcherContractTest` failures.
- Fifty-two shared Feature failures.
Do not silently bundle these into a B-1 patch. Establish which are committed, working-tree-only, environment-dependent, or still unknown. Inspect enough evidence to define a minimum repair scope, but do not modify out-of-scope components without authorization.
## D5. Baseline decision
Use committed HEAD as the provisional P1-EXIT-09 baseline, and evaluate the 17 reported tracked modifications separately.
Do not mix working-tree failures into the committed baseline. If the current repository differs from the reported state, document the difference and preserve it.
**Acceptance:** Produce a reproducible regression matrix with test name, expected behavior, actual behavior, baseline classification, root-cause confidence, blocking exit criterion, and required authorization. Do not claim regression closure unless the relevant tests were actually executed successfully.
---
# 8. WORK PACKAGE E — ADR-023 AND ARCHITECTURAL RECORD INTEGRITY
Inspect the current ADR-023 directly before preparing any change.
The latest report proposes a §8.33.9 record but explicitly says it has NOT been applied.
Prepare the proposed amendment with these corrections:
1. The source label must be `[OWNER AUTHORIZATION — conversation record]` only if the actual authorization record supports that wording. Do not call an engineering instruction an architecture decision.
2. Add the statement that the B-1 hashes were re-verified at the close of the latest campaign and that no B-1 file changed after the recorded verification time, but only if the hashes and timestamps are re-verified.
3. Replace the obsolete “Still missing” text with the current verified state:
   - B-1 and adjacent suites have not been executed, if still true.
   - The committed FAOSTAT and CSQ regression failures are recorded in the campaign report, if still supported.
   - Staging and operator evidence remain outstanding, if still true.
4. Preserve the current status of B-1 as NOT CLOSED / BLOCKED / NOT STARTED wherever applicable.
5. Preserve the explicit test status NOT RUN when no execution evidence exists.
6. Keep unresolved architectural questions visibly unresolved.
7. Preserve the existing ADR content, ordering, history, line-ending convention, and append-only rules.
**Critical restriction:** Do not modify ADR-023 in this campaign unless the exact ADR edit is explicitly authorized. Prepare a complete patch proposal and show its intended insertion point and diff in the final report. Do not silently convert the proposal into an approved decision.
Do not invent or backfill approvals, gate results, test results, or owner confirmations.
---
# 9. WORK PACKAGE F — PHASE 1 EXIT MATRIX
Reconcile every exit criterion from the authoritative ADR and existing Phase 1 checklist. Use the current identifiers; do not invent a new exit framework.
At minimum, re-evaluate P1-EXIT-01 through P1-EXIT-11.
For each criterion record:
- Exact requirement.
- Current status: PASS, PASS WITH DOCUMENTED LIMITATION, BLOCKED BY OWNER DECISION, BLOCKED BY EXTERNAL DEPENDENCY, NOT RUN, or FAIL.
- Evidence and its provenance.
- Whether the evidence is historical or freshly executed.
- Remaining action.
- The person/role required to perform that action.
- Dependencies and which work can proceed independently.
Specific requirements:
### P1-EXIT-01 — Owner decisions
Inspect §8.32.3 and related decision records. Verify the reported outstanding conditions 1, 3, and 5 against the exact authoritative text.
Do not close a condition based on a summary alone. Prepare the exact decision wording needed for the owner.
### P1-EXIT-02 and P1-EXIT-03 — Gate A and Gate B
Verify the actual Gate A/B records and their scopes.
Only the existing authorized IC and B-1 records may be treated as existing approvals unless further documentary evidence is found. Do not extend their scope to B-2, Runtime Source Registry, access-family adapters, or the 109 IC seats.
Identify missing scope records and acceptance criteria without creating approvals.
### P1-EXIT-04 — PostgreSQL evidence
Preserve the historical report of 121 tests and 840 assertions passing, plus the recorded CI evidence, only as historical evidence if it cannot be rerun.
Do not call it a fresh pass.
### P1-EXIT-05 — Deployment and staging readiness
Read-only inspect:
- `scripts/deploy-production.sh`
- `scripts/rollback-production.sh`
- `scripts/smoke-production.sh`
- `docs/deploy-production.md`
- Relevant migration and preflight diagnostics.
- The current index and rollback contract.
Verify the reported issues:
1. Application startup before migration.
2. Migration execution through the new application container instead of a one-off backend container.
3. Missing diagnostic and index verification hold points.
4. Mutable `main` tag versus immutable rollback target.
5. Deployment documentation using the old order.
Prepare a precise patch proposal that enforces this order:
1. Record the currently serving immutable image tag as the rollback target.
2. Pull the intended images without stopping the current release.
3. Start only the required database/support services.
4. Require the operator's read-only §8.25.7 diagnostic to return zero rows.
5. Run the migration in a one-off backend container with no dependencies started automatically.
6. Verify the expected index and migration status.
7. Start the new application only after successful migration and verification.
8. Run the prescribed smoke checks.
9. Verify rollback against the recorded immutable tag.
Do not execute this sequence. Do not edit deployment scripts or documentation unless explicitly authorized.
Staging requirements remain unfulfilled until real evidence exists for:
- Zero-row diagnostic.
- Index creation before the new application starts.
- Duplicate-ACTIVE migration refusal on an authorized staging copy where the migration has not run.
- No unintended data modification.
- Successful rollback to the previous release.
- Successful smoke checks.
Do not simulate or fabricate staging evidence.
### P1-EXIT-06, P1-EXIT-07, P1-EXIT-08, P1-EXIT-10
Keep Runtime Source Registry, 109-seat identity verification, access-family adapters, and source-matrix integrity blocked until the relevant scope and identity evidence are authorized and available.
Document the exact dependency. Do not activate any source or adapter.
### P1-EXIT-09 — Tests and regressions
Use the regression matrix from Work Package D. A green focused suite does not establish the entire exit criterion if the required broader baseline remains red or untested.
### P1-EXIT-11 — Formal closure
Only mark Phase 1 CLOSED when P1-EXIT-01 through P1-EXIT-10 satisfy the authoritative closure criteria and the required evidence is available.
Otherwise provide a precise blocker-based verdict. Do not use “complete” to mean “documented” or “proposed.”
---
# 10. WORK PACKAGE G — TEST EXECUTION PLAN
First inspect the local environment once.
If PHP 8.2+ and the existing PHPUnit dependencies are available, run only the relevant permitted tests, starting with B-1 and its adjacent unit suites.
Use the existing prepared commands when applicable:
```text
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Projection
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Capability/CapabilityStoreDomainContractTest.php
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Path
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Identity/SourceIdentityDomainContractTest.php
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Coexistence/CoexistenceRetrievalPlanTest.php
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Coexistence/Stage3CoexistenceSearchObservabilityAttachTest.php
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Coexistence/Stage3SourceKeyIdentityBridgeTest.php
php vendor/bin/phpunit tests/Unit/Agriculture/Research/Disclosure
```
Execute from `backend/`, adjusting only if the repository layout is verified to differ.
Do not execute database-dependent suites that trigger migrations or database writes without explicit authorization. In particular, preserve the existing exclusions for:
- `CapabilityStorePersistenceTest`
- `IdentityBindingPersistenceTest`
- `Stage3CghiaCoexistenceBoundaryTest`
Do not assume these are the only unsafe tests; inspect the actual test behavior and bootstrap.
If PHP is missing:
- Do not install packages.
- Do not download dependencies.
- Do not invoke Docker.
- Do not alter `composer.lock`, `.env`, or `phpunit.xml`.
- Mark the tests NOT RUN and list the exact prerequisite.
If the owner later authorizes a specific isolated container run, follow the approved networkless, read-only configuration and verify the actual mount and volume behavior before execution. An authorization to run one suite does not authorize a database mutation or deployment.
For every executed command, report the exact command, exit code, test count, assertions, failures/errors, and any relevant environmental constraints.
---
# 11. WORK PACKAGE H — OWNER DECISIONS AND AUTHORIZATION PACKET
Prepare one consolidated owner-decision packet, not a series of fragmented questions.
Include the following decisions from the latest report, retaining their exact meaning:
1. Accept or amend the tracked T10/T8 transcription in §8.32.3 and the Option B wording replacing T2's “Projection owns query.”
2. Accept `projection_query_constraints.v1` as B-1 constraint metadata.
3. Record condition (c) in the appropriate Gate A record only after the required preceding decisions are approved.
4. Authorize applying the corrected §8.33.9 amendment to ADR-023.
5. Confirm the four B-1 items: `retrieval_method` caller input; keep `create()` public; exact facet match; defer the AND-set decision to B-2.
6. Decide whether to authorize the isolated networkless test run, including the FAOSTAT diagnostic and read-only commit comparison.
7. Confirm the provisional P1-EXIT-09 baseline as committed HEAD, with the working tree assessed separately.
8. Decide whether to authorize the minimal FAOSTAT measure-resolution repair after diagnostics.
9. Decide whether the universal FAOSTAT consideration policy is authoritative and whether the affected test should be updated accordingly.
10. Decide whether to authorize restoration of the CSQ crop-profile variant family after diagnostics.
11. Decide whether to authorize the deployment-script and documentation changes.
12. Decide whether to authorize staging validation, including the duplicate-ACTIVE drill on a suitable staging copy.
13. Define future Gate A/B scopes for B-2, Runtime Source Registry, adapters, and per-seat IC work.
14. Assign an owner to review and establish identity evidence for each of the 109 IC seats.
15. Confirm or amend the §8.28.7 checklist, currently reported as PROPOSED — NOT CONFIRMED.
For each decision include:
- Exact question.
- Recommended option.
- Alternatives.
- Evidence supporting the recommendation.
- Scope and risk.
- Exact files or systems affected.
- What becomes unblocked.
- What remains blocked even if approved.
Do not treat recommendations as decisions. Do not act on decisions that require explicit authorization until that authorization is provided.
---
# 12. WORK PACKAGE I — MINIMAL AUTHORIZED FIXES
After the read-only investigations, implement only fixes that are both:
1. Within an already authorized scope, and
2. Supported by a verified defect and a clear contract.
For every proposed code fix:
- Identify the failing assertion or violated invariant.
- Demonstrate the cause using code or authorized test output.
- State the smallest file scope.
- Explain why the fix preserves scientific identity and provenance.
- Add or update the smallest meaningful test.
- Run the permitted tests if possible.
- Inspect the final diff and protected-file hashes.
Do not modify FAOSTAT, CSQ, answer identity, deployment files, ADR-023, B-2, source registry, adapters, or IC seat records merely because they are blockers. Each requires the applicable explicit scope authorization.
If no authorized code fix remains, say so plainly and continue preparing the exit evidence and owner-decision packet. Do not manufacture a code change to make the campaign appear productive.
---
# 13. WORK PACKAGE J — FINAL INDEPENDENT VERIFICATION
At the end, repeat the preservation audit.
Verify:
- Branch and HEAD unchanged.
- No staged changes created by this campaign.
- No commit, push, history rewrite, branch switch, cleanup, or stash operation occurred.
- No network, installation, Docker, database, source activation, deployment, or unauthorized test occurred.
- No protected file changed without explicit authorization.
- Every modified file has a precise before/after diff.
- B-1 hashes and line endings are recorded.
- Every test result is accurately classified as executed, historical, inferred, or NOT RUN.
- Every gate record and owner decision cited actually exists.
- The proposed ADR amendment remains unapplied unless explicitly authorized.
- Phase 2 remains NOT STARTED.
If any preservation check fails, report the exact discrepancy and stop further mutations. Do not automatically revert it.
---
# 14. REQUIRED FINAL DELIVERABLE
Produce one consolidated final report with the following structure:
1. **Executive verdict:** CLOSED, PARTIALLY COMPLETE — BLOCKED, or NOT COMPLETE.
2. **Actual baseline:** branch, HEAD, worktree status, and remote-verification status.
3. **Work performed:** exact changes, or explicitly “no repository changes.”
4. **B-1 result:** contract review, file hashes, tests run/not run, and remaining questions.
5. **Regression matrix:** each test group, classification, verified cause, confidence, and authorized next action.
6. **P1-EXIT-01 through P1-EXIT-11 matrix:** exact status and evidence for every criterion.
7. **Gate A/B inventory:** approved scope versus missing scope; never imply inherited approval.
8. **Deployment/staging readiness:** verified defects, proposed patch, and external/operator evidence still required.
9. **ADR-023 proposal:** exact proposed amendment and diff, clearly marked NOT APPLIED if not authorized.
10. **Owner-decision packet:** one prioritized consolidated list with recommendations and consequences.
11. **Preservation audit:** actual end state and prohibited-action audit.
12. **Critical path to closure:** the shortest dependency-aware sequence of actions needed to satisfy every remaining exit criterion.
13. **Final status of Phase 2:** NOT STARTED.
For each blocker, identify:
- The exact criterion it blocks.
- Whether it is an owner decision, code defect, test-runtime limitation, external dependency, staging requirement, or missing evidence.
- The smallest next action.
- The authority required.
- Whether independent work can proceed.
Do not end with a vague “awaiting instructions.” Provide a precise set of decisions that the owner can approve together, while keeping unapproved changes unapplied.
---
# 15. FINAL OPERATING PRINCIPLE
The objective is not to make the report say Phase 1 is closed.
The objective is to establish whether Phase 1 is actually closable, execute every safe and authorized step that materially advances closure, preserve all existing work, make every unresolved decision explicit, and leave a verifiable audit trail.
**Do the work. Do not fabricate evidence. Do not broaden authorization. Do not skip blockers. Do not start Phase 2.**
