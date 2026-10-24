<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15160). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 8:33 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15161; it is archived once. -->

---
# WSA-Enterprise — Phase 1 Finalization Campaign
## Execute All Authorized Work, Resolve Independent Blockers, and Consolidate Owner Decisions
You are continuing from the latest comprehensive report dated 2026-10-14.
Your mission is to complete every remaining safe, authorized engineering task that can move Phase 1 toward closure, and consolidate all unavoidable owner decisions and external dependencies into one final package.
Do not restart completed investigations. Use the actual repository state, not stale hashes or assumptions.
## 1. Current baseline
- Repository: `G:/WSA-Enterprise`
- Expected branch: `phase-18-m18-ai-marketing-communications`
- Recorded HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`
- Primary ADR: `docs/architecture/ADR-023-scientific-source-expansion.md`
- B-1 implementation and its five files remain local and untracked.
- The Projection test file has 17 test methods and 25 cases according to the last report.
- No PHP runtime is available on the host, `backend/vendor` is empty, and Docker is currently prohibited.
- B-1 tests have not been executed.
- `P1-EXIT-01(c)` remains open.
- Phase 1 remains blocked.
- Phase 2 has not started.
First verify the current state. Do not assume these facts are unchanged.
## 2. B-1: finish all repository-solvable work
Inspect the actual five B-1 files, their callers, tests, and relevant ADR decisions.
Resolve every genuine defect that can be fixed using already-approved contracts.
Preserve:
- Capability decision authority.
- Fail-closed behavior.
- Exact facet-code matching.
- Freshness consistency.
- Stable error priorities.
- Existing serialization and public contracts.
- The existing Projection → Capability/Path/Identity dependency direction.
- Scientific question identity and provenance.
Do not make speculative refactors.
Keep the four open questions unresolved unless an authoritative decision already answers them:
- `retrieval_method` source.
- Public visibility of `ProjectionQueryConstraints::create()`.
- Facet-code mapping beyond exact matching.
- `query_constrainable` in the required AND set.
For each question, provide the evidence and recommendation, but do not invent an owner decision.
## 3. B-1 testing and runtime
Check once more whether a suitable existing PHP/PHPUnit runtime has become available.
If it is available and tests can run without Docker, package installation, network access, database changes, or prohibited side effects, run the focused suites and report actual results.
If it is unavailable, do not repeat futile runtime searches or install anything. Mark the suites `NOT RUN`, preserve the prepared commands, and continue the rest of the campaign.
Do not claim successful syntax validation or test execution without executing it.
## 4. P1-EXIT-09: identify the minimum regression repair scope
The latest report identifies prior failures affecting the five-source path and CSQ identity, including FAOSTAT and QUS/CSQ regressions.
Inspect the recorded evidence and existing code to determine which defects demonstrably block the mandatory Phase 1 regression criteria.
Do not modify unrelated synthesis, MultiAnswerIdentitySerializer, matcher, frontend, or other components under B-1 authorization.
For every defect, provide:
- Root cause and exact evidence.
- Whether it blocks a mandatory Phase 1 exit.
- The smallest repair scope.
- Required tests.
- Whether the fix is already authorized or needs a separate owner decision.
If a defect is outside the authorized scope, prepare a concrete, minimal follow-up task. Continue other independent work instead of stopping.
## 5. P1-EXIT-05: deployment-order and staging readiness
Inspect `scripts/deploy-production.sh`, `scripts/rollback-production.sh`, the existing migration, the diagnostic query, and the authoritative deployment decisions read-only.
Do not edit deployment scripts or execute deployment operations unless explicit authorization already exists.
Prepare a verified deployment-order checklist that addresses:
1. The required pre-deployment diagnostic.
2. Migration completion and index verification.
3. Application startup only after migration success.
4. Duplicate-ACTIVE failure drill and previous-release availability.
5. Rollback verification.
6. Post-deployment smoke checks.
Distinguish script defects from staging-only evidence. Never claim a staging result that has not occurred.
## 6. Phase 1 owner decision package
Consolidate all remaining owner decisions into one table.
Include:
- `P1-EXIT-01(c)` and the exact unresolved T10/T8 and executor-assessment conditions.
- Authorization to record the accurate append-only §8.33.9 execution record.
- The four B-1 contract confirmations and their consequences.
- Required test-runtime authorization if still unavailable.
- The minimum repair scope for FAOSTAT/QUS-CSQ regressions affecting `P1-EXIT-09`.
- Any decision needed for the deployment script and staging plan.
- Gate A/Gate B scope boundaries.
- Remaining identity evidence for the 109 IC seats.
- Any other mandatory Phase 1 condition that still lacks authoritative evidence.
Do not bundle unrelated approvals into one ambiguous yes/no question.
For every decision, state:
- Exact question.
- Recommended option.
- Alternatives.
- Consequences.
- Affected exit criteria.
- What can proceed independently.
Do not request approval for routine work already authorized.
## 7. ADR-023 and evidence accuracy
Reconcile the proposed §8.33.9 text against the actual current B-1 files and tests.
Correct stale hashes, counts, timestamps, or factual wording in the proposal.
Do not edit ADR-023 or record owner approval without explicit authorization.
Preserve append-only history and clearly distinguish implementation facts, static inspection, executed tests, external evidence, and owner decisions.
## 8. Strict security controls
- Do not run `git add`, `commit`, `push`, `pull`, `fetch`, `merge`, `rebase`, `reset`, `checkout`, `switch`, `clean`, or `stash`.
- Do not change branches or rewrite history.
- Do not delete, move, rename, overwrite, or clean existing files.
- Preserve all tracked, untracked, ignored, temporary, and unrelated work.
- Do not run Docker or Compose.
- Do not install PHP, Composer dependencies, or other packages.
- Do not access databases, run migrations, or seed data.
- Do not use network access or external services.
- Do not modify secrets, environment files, lockfiles, CI permissions, or deployment settings.
- Do not activate any source or populate any of the 109 IC seats.
- Do not implement B-2 or Registry integration.
- Do not start Phase 2 or create Phase 3.
If an operation is not explicitly authorized, prepare the necessary evidence and recommendation without executing it.
## 9. Work continuously
Do not stop for intermediate reports.
Complete all independent, safe work in one sustained pass. Make focused changes only where existing authority establishes the expected behavior. Keep unrelated existing work intact.
If one task is blocked, continue every other independent task.
Do not repeatedly rerun completed reviews unless new evidence requires it.
## 10. Final deliverables
Return one final report containing:
A. Actual changes made and their rationale.
B. B-1 implementation and test status.
C. The mandatory Phase 1 exit matrix with exact evidence.
D. Root causes and minimum repair scope for each regression blocker.
E. Deployment and staging readiness checklist.
F. Corrected proposed §8.33.9 text, if necessary.
G. One consolidated table of remaining owner decisions.
H. External dependencies and exact evidence still required.
I. Initial versus final Git state and preservation audit.
J. One prioritized action plan for closing Phase 1.
K. Final verdict:
- `PHASE 1 CLOSED — VERIFIED`
- `PARTIALLY COMPLETE — BLOCKED`
- `NOT READY — EVIDENCE INSUFFICIENT`
Do not choose `PHASE 1 CLOSED — VERIFIED` unless every mandatory exit condition has sufficient evidence and required authorization.
Unless Phase 1 is genuinely closed, state:
`PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED`
Begin now and complete all safe, authorized work before returning the consolidated report.
