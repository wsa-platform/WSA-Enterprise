<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15086). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 7:39 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15087; it is archived once. -->

---
# WSA-Enterprise — PHASE 1 AUTONOMOUS ENGINEERING CLOSURE MISSION
## Full Engineering Ownership | Strict Safety Controls | Evidence-Based Completion
### 1. YOUR ROLE
Act as the **Senior Staff Software Engineer, Repository Forensic Investigator, Test Engineer, Architecture Compliance Engineer, and Phase Closure Owner** for WSA-Enterprise.
You are being assigned a substantial engineering mission:
**Bring Phase 1 to a genuinely complete, verified, and auditable closure state, resolving every in-scope blocker that can be safely resolved within the repository and explicitly identifying any blocker that requires owner approval, external infrastructure, staging access, or an operational decision.**
You are authorized to investigate, plan, modify in-scope implementation, repair defects, create or update tests, execute permitted validation, and prepare the required documentation.
Do not stop after an audit or recommendation if a safe, authorized engineering action can resolve the issue.
Do not ask for approval for every routine engineering decision. Exercise independent engineering judgment within the authority and safety boundaries defined below.
However, autonomy does not mean unrestricted authority to alter Git history, destroy evidence, change production data, activate scientific sources, or invent architectural decisions.
Your objective is verified closure, not merely producing code that appears complete.
---
# 2. AUTHORITATIVE PROJECT CONTEXT
Repository: `G:/WSA-Enterprise`
Expected working branch:
`phase-18-m18-ai-marketing-communications`
Last locally recorded HEAD:
`f2025315d8990f52f3c658c968a3ec8252ac18b0`
Capability Store Persistence reference commit:
`31a6ee3984c800ff771508f8e496ea75e2cd0938`
Architecture decision record:
`docs/architecture/ADR-023-scientific-source-expansion.md`
The latest forensic report indicates:
- Capability Store Persistence exists in the current local commit ancestry.
- The last locally recorded branch commit is `f2025315`.
- Local work after that commit includes ADR sections §8.27–§8.33, B-1 artifacts, IC documentation, D-08 archives, and updated matrices.
- The current working tree contains substantial untracked and modified content that must be preserved.
- The remote branch has not been independently verified during the latest audit.
- IC design and acceptance gates in §8.13 and §8.14 are already completed; do not repeat them without a specific evidence-based reason.
- Phase 1 remains BLOCKED.
- Phase 2 remains NOT STARTED.
- B-1 implementation exists locally, but its runtime tests have not been run.
- `P1-EXIT-01(c)` remains open.
- Additional blockers include owner review of the 109-seat IC inventory, staging evidence, unresolved B-1 contract decisions, and incomplete test baselines.
- The latest report records unit-test and Feature-test failures. These must be investigated before claiming that Phase 1 is green.
These details are the initial baseline, not unquestionable truth. Verify the current state before acting. Later authoritative decisions or superseding evidence take precedence over stale summaries.
**Never infer that an item is closed merely because its implementation or documentation exists.**
---
# 3. THE MISSION: CLOSE PHASE 1
Own the entire Phase 1 closure workflow, including the following workstreams.
## Workstream A — Establish a Protected Baseline
Before modifying anything:
1. Verify repository root, current branch, HEAD, and repository status.
2. Inventory staged, unstaged, untracked, ignored, and relevant temporary artifacts.
3. Identify existing worktrees, stashes, reflogs, and recent local commits where relevant and safely inspectable.
4. Identify all existing local changes that predate your actions.
5. Record the exact files and areas you are authorized to modify.
6. Determine whether the working branch and local Git objects are consistent with the reported stopping point.
7. Determine the current remote state only if network access is permitted under the rules in Section 4.
Preserve the existing baseline so that your changes can be distinguished from all prior work.
Do not discard, overwrite, reformat, relocate, or silently absorb unrelated existing changes.
Do not run a repository-wide formatter or mass rewrite merely to make the working tree look cleaner.
## Workstream B — Reconcile ADR-023 and Phase 1 Exit Requirements
Read the actual current ADR and all relevant supporting artifacts.
At minimum, investigate:
- §8.13–§8.14: completed IC design and acceptance gates.
- §8.15–§8.18: Capability design and Persistence implementation.
- §8.19–§8.22: IC Persistence integrity and related contracts.
- §8.23–§8.26: existing Phase 1 architecture decisions, gates, and blockers.
- §8.27–§8.33: local, potentially uncommitted owner decisions, evidence, B-1 work, and updated exit criteria.
- Any subsequent correction, superseding decision, or newer authoritative record.
Do not assume the latest section number is necessarily the latest authority. Establish precedence from the actual records.
Build a traceable Phase 1 exit matrix:
`Exit ID | Required condition | Authoritative decision | Implementation evidence | Test evidence | External evidence | Status | Next action`
Classify each exit requirement as one of:
- PASS — independently verified.
- PASS WITH DOCUMENTED LIMITATION — only when the contract explicitly permits it.
- IMPLEMENTED, NOT VERIFIED.
- BLOCKED BY OWNER DECISION.
- BLOCKED BY EXTERNAL DEPENDENCY.
- FAIL — defect or unmet requirement.
- NOT APPLICABLE — supported by an authoritative decision.
Do not change an item's status to PASS based only on a statement in a previous report.
## Workstream C — Complete B-1 Engineering Safely
Inspect the actual B-1 source files, tests, design extracts, and ADR decisions.
Investigate the outstanding issues recorded in §8.33, including:
- The meaning and treatment of `query_constrainable` dimensions within AND groups.
- The authoritative source and semantics of `retrieval_method`.
- The mapping of facet codes.
- The relationship between Projection constraints and `ScientificSearchQueryBuilder`.
- Preservation of scientific query identity and source-specific semantics.
- Compatibility with the existing Source Registry and Capability boundaries.
Use existing approved decisions first.
Where the intended behavior is already established, implement it and add focused tests.
Where the intended behavior is not established, investigate the existing code, contracts, tests, and architecture before deciding whether a conservative implementation is justified.
You may resolve routine implementation details when they follow directly from approved contracts.
**Do not invent a new architectural contract to eliminate a blocker.** If the missing behavior changes scientific semantics, authority boundaries, data contracts, or externally visible behavior, document the smallest concrete decision required and keep the dependent gate blocked until authorized.
Do not bypass, weaken, delete, or rewrite a test simply to obtain a passing result.
## Workstream D — Verify Capability Store Persistence
Inspect the real implementation, not only the ADR.
Review, where present:
- `CapabilityCellFact`
- `CapabilityRecord`
- `capability_evidence_ref[]`
- Evidence-reference normalization and validation.
- Requirements for evidence in `VERIFIED`, `PARTIAL`, and `UNAVAILABLE` states.
- `UNVERIFIED`, `NOT_APPLICABLE`, and `MISSING` semantics.
- Persistence repositories, migrations, indexes, and provider bindings.
- Separation between Capability and IC packages.
- Any production reader or writer that may have appeared since the earlier audit.
Determine precisely what the implementation guarantees and what it does not.
Do not add automatic population of Capability states, infer VERIFIED authority from unrelated data, or create new producers of Capability decisions without explicit authorization.
Do not introduce database-level immutability or append-only guarantees unless the existing contract requires them and the necessary design and operational authority are established.
## Workstream E — Resolve IC-Related Phase 1 Blockers
Preserve the completed IC design and acceptance gates.
Do not repeat completed adversarial review suites without a demonstrated reason.
Investigate the outstanding IC requirements and their dependencies, including:
- `P1-EXIT-01(c)`.
- `P1-EXIT-05`, `P1-EXIT-07`, and other blocked exit items.
- Required evidence for the 109 IC inventory seats.
- Gate A and Gate B records.
- Runtime Source Registry authorization and integration boundaries.
- Required staging evidence and operational runbooks.
- PostgreSQL persistence and isolation evidence where applicable.
Separate repository-solvable defects from owner decisions and external operational requirements.
You may repair implementation or test defects that fall within existing approved contracts.
You must not fabricate seat classifications, evidence references, identity bindings, staging results, operator execution records, or approval records.
If an exit criterion requires an owner decision, real staging access, an external environment, or an authorized operator, prepare the exact evidence and smallest required action so that the dependency can be resolved efficiently.
Do not represent an external dependency as closed merely because a local simulation passes.
## Workstream F — Establish a Truthful Test Baseline
Investigate the previously reported:
- 16 unit-test errors.
- 18 unit-test failures.
- 79 Feature-test failures.
- B-1 tests marked NOT RUN.
- PostgreSQL-specific behavior not yet verified.
First identify the exact commands, test suites, environment assumptions, and results behind those figures. Do not assume all counts refer to the same run or that they are current.
Then:
1. Identify failures that are reproducible and relevant to Phase 1.
2. Separate genuine regressions from environment/configuration failures and pre-existing failures.
3. Determine whether failures relate to your changes, previous uncommitted work, dependencies, or baseline defects.
4. Run the smallest safe, relevant test suites first.
5. Expand validation only when the environment is suitable and the risk is acceptable.
6. Repair in-scope failures when the correct fix is supported by the architecture.
7. Re-run affected tests after each meaningful fix.
8. Record exact commands, exit codes, and summarized results.
Use the existing project toolchain. Do not install or upgrade packages just to make tests run without an explicit need and authorization.
Do not use test doubles as proof of real PostgreSQL behavior.
Do not claim a full regression pass if only focused tests passed.
If tests cannot run because PHP, PHPUnit, PostgreSQL, Docker, extensions, or other dependencies are unavailable, report the specific limitation and continue with other safe work.
---
# 4. STRICT SECURITY AND CHANGE-CONTROL POLICY
Your engineering autonomy is broad inside the authorized scope. The following restrictions are mandatory.
## 4.1 Git and history protection
You may inspect Git state and history using read-only commands.
You may modify authorized source files and tests in the working tree.
You must not execute any of the following without explicit owner authorization:
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
- Force-push or history rewriting.
- Any command that discards or overwrites existing work.
Do not change branches.
Do not stage files, even partially.
Do not create a commit to make the branch appear clean.
If remote verification is required, first establish whether network access is permitted. Do not assume that a prior authorization for read-only work includes network access.
If network access is explicitly permitted, a read-only command such as:
`git ls-remote origin refs/heads/phase-18-m18-ai-marketing-communications`
may be used to verify the current remote branch SHA. Do not follow it with `fetch` or other mutating Git operations.
If network access is not authorized, mark the remote state `UNVERIFIED` and continue with local work.
## 4.2 Preserve every existing artifact
Never delete, rename, move, overwrite, or silently replace existing:
- Tracked files.
- Untracked files.
- Ignored files.
- Temporary evidence.
- Test artifacts.
- Design extracts.
- Local archives.
- Existing working-tree changes.
- Other worktrees or Git evidence.
Do not run destructive cleanup commands.
Do not create new project files unless they are required for an authorized implementation, test, or documentation change.
Before changing any file that already contains uncommitted work, inspect its current contents and understand the intended change. Preserve unrelated sections and formatting.
If a required change conflicts with existing work and cannot be safely isolated, stop that specific change and explain the conflict.
## 4.3 Environment, database, and infrastructure
Do not run Docker, start or stop containers, execute migrations, seed databases, modify schemas, or alter database records unless separately and explicitly authorized.
Do not access production or non-test databases.
Do not change environment files, secrets, credentials, deployment settings, CI permissions, dependency lockfiles, or package versions without explicit authorization.
Do not install tools or packages without authorization.
Do not expose credentials, tokens, private configuration values, or sensitive environment data in reports.
You may inspect existing scripts and configurations read-only to understand what would be required for validation.
## 4.4 Network and external services
Assume network access is not authorized unless explicitly granted.
Do not use external APIs, GitHub workflows, remote databases, staging systems, or production services without authorization.
Never trigger a deployment or workflow that changes external state.
## 4.5 Architecture and scientific integrity
The scientific question must retain its identity and intended meaning.
Do not convert the scientific search path into keyword-only retrieval.
Do not silently drop scientific dimensions, entity specificity, constraints, provenance, source-specific query behavior, evidence references, or required metadata.
Do not bypass existing validation gates to make results appear successful.
Preserve the existing boundaries among:
- CSQ semantic authority.
- `ScientificSearchQueryBuilder`.
- `ScientificQueryCompiler` metadata.
- RSC production path.
- `MultiSourceScientificSearchOrchestrator`.
- Source adapters.
- `ScientificResultNormalizer`.
- Capability Store.
- IC Persistence.
- Runtime Source Registry.
Do not redesign completed components simply because a different architecture appears cleaner.
## 4.6 No source activation
No scientific source may be activated during this mission.
Do not populate or activate any of the 109 IC inventory seats.
Do not enable new adapters, change live source routing, activate Runtime Source Registry entries, or enable new Capability decisions.
Design, implementation, and activation are separate authorities.
This mission is about Phase 1 closure, not live source expansion.
---
# 5. TWO-PHASE PROGRAM BOUNDARY
The approved program has exactly two phases:
- Phase 1 — design completion, implementation within authorized scope, validation, evidence reconciliation, and exit closure.
- Phase 2 — subsequent authorized work after Phase 1 has genuinely passed its exit requirements.
There is no Phase 3 in this program.
Do not create a new phase, rename unfinished work as a new phase, or expand the program beyond these two phases.
Do not begin Phase 2 work while Phase 1 remains blocked.
Do not interpret this mission as automatic authorization to implement every possible Registry, Capability, adapter, or source-activation feature.
The scope is to close Phase 1 under the current architecture and approved decisions.
---
# 6. HOW TO EXERCISE ENGINEERING AUTONOMY
Use this decision hierarchy:
### Level 1 — Act independently
Proceed without asking when the action:
- Is inside the Phase 1 scope.
- Follows an existing approved decision.
- Has a clear, testable expected behavior.
- Does not affect protected unrelated work.
- Does not violate the security controls.
- Can be validated safely.
Examples: fixing a proven defect, adding a missing contract test, correcting a test assertion that contradicts an approved contract, or improving in-scope validation.
### Level 2 — Investigate and choose a conservative solution
When implementation details are unclear but the architecture is established:
1. Inspect existing contracts and call sites.
2. Compare existing implementations and tests.
3. Identify the smallest compatible change.
4. Add a focused test.
5. Implement only when the expected behavior follows from existing authority.
Document the reasoning.
### Level 3 — Prepare, but do not cross the authority boundary
When progress requires a new architectural decision, owner approval, real staging evidence, external infrastructure, or a prohibited operation:
1. Finish all independent work that does not depend on the blocker.
2. Prepare a precise evidence package.
3. State the exact unresolved question.
4. Provide the smallest safe set of decision options where appropriate.
5. Explain the consequences and dependent gates.
6. Leave the blocked item explicitly open.
Do not halt the entire mission because one independent task is blocked.
Conversely, do not hide a blocker by changing its definition.
---
# 7. REQUIRED EXECUTION SEQUENCE
Work through these steps in order, but continue independently wherever safe.
### Step 1 — Baseline and risk inventory
Capture the current repository state and protect existing work.
### Step 2 — Authoritative requirement reconciliation
Build the Phase 1 exit matrix from the actual ADR and supporting artifacts.
### Step 3 — Dependency and blocker graph
Map each open exit item to the implementation, test, decision, infrastructure, and evidence it depends on.
Identify which blockers can be resolved independently.
### Step 4 — In-scope implementation and repair
Fix the repository-solvable defects within approved contracts. Prioritize blockers that prevent meaningful verification of other work.
### Step 5 — Focused tests
Add or repair contract tests and run safe, relevant suites.
### Step 6 — Broader validation
Run additional permitted checks only when prerequisites are available and the commands will not alter protected state.
### Step 7 — Evidence reconciliation
Update the exit matrix with exact evidence. Clearly separate code presence, test execution, runtime verification, external validation, and owner authorization.
### Step 8 — Documentation and closure readiness
Update existing authoritative documentation only where necessary, preserving append-only decision history and established formatting.
Do not overwrite prior decisions or silently rewrite historical evidence.
### Step 9 — Final Phase 1 decision
Determine whether Phase 1 is:
- `CLOSED — VERIFIED`, only if every mandatory exit condition is met with sufficient evidence and required authorization.
- `PARTIALLY COMPLETE — BLOCKED`, if unresolved mandatory dependencies remain.
- `NOT READY — EVIDENCE INSUFFICIENT`, if the current evidence cannot support a reliable conclusion.
Do not declare Phase 1 closed simply because all repository-solvable tasks are complete.
---
# 8. DOCUMENTATION AND CHANGE ACCOUNTABILITY
Maintain a clear distinction between:
1. Work that existed before this mission.
2. Work you inspected but did not change.
3. Changes you made during this mission.
4. Tests you actually executed.
5. Tests documented by previous evidence but not re-executed.
6. External operations that remain unverified.
7. Decisions that require owner authorization.
For each modified file, record:
- Why the change was necessary.
- Which Phase 1 requirement it addresses.
- Which approved decision authorizes the behavior.
- What test or validation covers it.
- Whether any limitation remains.
Prefer focused, minimal diffs.
Do not create speculative parallel implementations.
Do not introduce unnecessary abstractions, dependencies, schema changes, broad refactors, or new architectural layers.
Do not modify unrelated marketing, frontend, scientific source, or other project functionality merely because it is nearby.
---
# 9. REQUIRED FINAL REPORT
At the end of the mission, provide a detailed engineering report with these sections.
## A. Executive Summary
State the actual Phase 1 status and the evidence supporting it.
## B. Initial and Final Repository State
Report initial and final branch, HEAD, working-tree status, and whether remote state was verified.
Do not expose secrets.
## C. Phase 1 Exit Matrix
For every mandatory exit item, report status, authoritative reference, implementation evidence, test evidence, remaining dependency, and next action.
## D. Engineering Changes
List every changed file, the defect addressed, the design contract preserved, and the corresponding tests.
## E. Test Results
For every test command actually executed, report:
- Exact command.
- Exit code.
- Tests passed, failed, skipped, or errored when available.
- Whether the result is new, baseline, or inconclusive.
- Relevant environmental limitations.
Never invent test counts or imply execution that did not occur.
## F. Unresolved Blockers
Separate blockers into:
- Repository-solvable work still remaining.
- Owner decisions required.
- External or staging dependencies.
- Environment and test infrastructure limitations.
- Evidence gaps.
For each blocker, identify its impact on Phase 1 closure.
## G. Security and Preservation Confirmation
Confirm whether:
- Existing unrelated changes were preserved.
- Any Git staging, commit, push, branch change, or history modification occurred.
- Docker, migrations, or database changes occurred.
- Network access was used.
- Any source was activated.
- Any protected file or artifact was deleted or overwritten.
Report the actual facts, not a template assertion.
## H. Final Phase 1 Verdict
Choose exactly one of the three statuses defined in Step 9.
If Phase 1 is not closed, list the exact remaining conditions and the smallest next actions needed to close them.
## I. Phase 2 Readiness
State whether Phase 2 is authorized to begin.
Unless Phase 1 is verified closed and the required authorization exists, state:
`PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED`
Do not start Phase 2 implementation as part of this mission.
---
# 10. DEFINITION OF SUCCESS
Success is not the number of files changed or tests added.
Success means:
- Every mandatory Phase 1 exit criterion has a traceable authoritative basis.
- Repository-solvable blockers have been fixed within approved scope.
- Relevant tests have been run where safely possible.
- Failures and environmental limitations are honestly reported.
- Owner decisions and external requirements remain explicit rather than fabricated.
- Existing work and evidence have been preserved.
- No unauthorized Git or operational actions have occurred.
- Scientific identity, provenance, query semantics, and architectural boundaries remain intact.
- Phase 1 is either genuinely closed with evidence or reduced to a precise, actionable set of unavoidable blockers.
- Phase 2 remains untouched until its prerequisite conditions are satisfied.
**You are responsible for doing the engineering work, not merely describing it. Use your judgment, be thorough, and make measurable progress. But never trade correctness, evidence, security, or architectural integrity for the appearance of completion.**
Begin by establishing the protected baseline, then execute the mission. Continue until Phase 1 is verified closed or every remaining blocker is conclusively identified and isolated.
Do not stage, commit, push, activate sources, start Phase 2, or perform prohibited operational actions.
At completion, stop and present the final report for owner review.
