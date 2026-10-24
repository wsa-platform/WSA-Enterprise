<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15132). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 8:05 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15133; it is archived once. -->

---
# WSA-Enterprise — NEXT ENGINEERING MISSION
## B-1 Contract Hardening, Evidence Completion, and Phase 1 Closure Progress
### Mission
Continue directly from the current B-1 implementation in ADR-023 §8.33.
Act as the responsible senior software engineer. Your task is to make measurable engineering progress toward closing B-1 and Phase 1, not merely produce another audit report.
Use the actual repository state and existing authoritative decisions. Preserve all existing work and follow the strict safety constraints below.
## 1. Review and validate the current B-1 change
Inspect the actual current contents of:
- `Projection/ProjectionFacetConstraint.php`
- `Projection/ProjectionQueryConstraints.php`
- `Projection/ProjectionQueryConstraintsFactory.php`
- `Projection/ProjectionFacetConstraintState.php`
- `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php`
Locate the actual paths before assuming these relative paths are correct.
Independently verify the new `fromCapabilityDecision()` method and the `create()` consistency check.
Confirm that:
- Constraints cannot claim a Capability state or freshness that the actual decision does not support.
- Existing instance validation and duplicate-check error priorities remain unchanged.
- Exact facet-code matching and fail-closed behavior remain intact.
- No Capability contract, serialized field, key, or mapping has changed.
- The factory and direct `create()` paths produce consistent results for valid inputs.
- Existing callers remain compatible.
- The new tests test actual behavior rather than merely implementation details.
Do not rewrite correct code merely to make stylistic changes.
## 2. Repair any proven B-1 defects
If the review identifies a genuine defect within the existing approved B-1 contract, fix it with the smallest safe change and add or update a focused test.
You are authorized to make focused source and test changes within B-1 when their behavior is established by existing decisions.
Do not modify unrelated synthesis, FAOSTAT, QUS/CSQ, matcher, frontend, Registry integration, or source-adapter behavior.
If a proposed fix requires a new architectural decision, do not implement it. Record the precise unresolved question and continue with independent work.
## 3. Prepare executable validation
Inspect the existing project test configuration and identify the exact commands for:
1. Projection unit tests.
2. Capability unit tests.
3. Path unit tests.
4. Identity unit tests.
5. Coexistence unit tests.
6. Disclosure unit tests.
7. Relevant B-1 integration tests, if any can be run without prohibited infrastructure.
Check whether PHP and the project's existing test dependencies are available.
If a suitable PHP/PHPUnit environment already exists and the tests can run without Docker, database access, package installation, network access, or protected-state changes, execute the focused B-1 tests.
If they cannot run, do not install anything or bypass the restriction. Record the exact environmental blocker and leave the test status `NOT RUN`.
Do not claim that a test passed unless it was actually executed and returned a successful result.
Do not run the full test suite indiscriminately while the environment is unsuitable.
## 4. Complete B-1 evidence without inventing decisions
Reconcile the implementation and tests with the existing B-1 records in ADR-023 §8.33.
Preserve the currently unresolved questions:
- Whether `query_constrainable` belongs in the required AND set.
- The authoritative source of `retrieval_method`.
- Projection-to-Capability facet-code correspondence beyond established exact-match behavior.
- Whether the public visibility of `create()` should be restricted.
Do not resolve these questions through inference alone.
Review the proposed §8.33.9 execution record for factual accuracy. Prepare a corrected proposal only if needed, but do not edit ADR-023 or record owner approval.
If the evidence is sufficient, prepare a precise B-1 completion checklist that distinguishes:
- Implementation complete.
- Static review complete.
- Unit tests executed and passed.
- Runtime behavior verified.
- Owner decisions complete.
- Formal gate acceptance recorded.
Do not label B-1 fully closed unless all required conditions have evidence and authorization.
## 5. Preserve the Phase 1 boundary
Continue working toward Phase 1 closure, but do not expand this mission into unrelated work.
Do not implement B-2.
Do not implement Runtime Source Registry integration.
Do not add or activate source adapters.
Do not populate or activate any of the 109 source seats.
Do not start Phase 2.
Do not create Phase 3.
Gate A and Gate B remain limited to the explicitly authorized B-1 scope. Do not treat this mission as authorization for other scopes.
Keep `P1-EXIT-01(c)` open unless its actual authoritative closure conditions are satisfied.
## 6. Strict operational safety
- Do not run `git add`, `commit`, `push`, `pull`, `fetch`, `merge`, `rebase`, `reset`, `checkout`, `switch`, `clean`, or `stash`.
- Do not change branches or rewrite history.
- Do not delete, move, rename, overwrite, or clean existing files or artifacts.
- Preserve all tracked, untracked, ignored, temporary, and unrelated existing work.
- Do not use Docker or Compose.
- Do not run migrations, seeders, or database operations.
- Do not access production or non-test databases.
- Do not install or upgrade packages.
- Do not modify secrets, `.env`, lockfiles, CI permissions, or deployment settings.
- Do not use network access or external services.
- Do not activate sources or change runtime source routing.
- Do not manufacture approval records, test results, hashes, or staging evidence.
Before modifying an existing file, inspect its contents and preserve unrelated work. Keep changes minimal and attributable to B-1.
If an operation would violate a restriction, skip that operation and continue with safe independent work.
## 7. Required final output
Return:
### A. Engineering work completed
List every actual source or test change and why it was needed.
### B. Contract review
State which B-1 invariants were verified and which remain uncertain.
### C. Validation results
Provide the exact commands executed, exit codes, and actual results. Distinguish execution from static inspection.
### D. B-1 closure checklist
For each mandatory condition, report `PASS`, `NOT RUN`, `BLOCKED`, or `NOT VERIFIED`, with evidence.
### E. Remaining owner decisions
List only the decisions that genuinely require owner authority, with concise options and consequences.
### F. Phase 1 status
State the actual remaining blockers and whether Phase 1 is still blocked.
### G. Preservation confirmation
Report the actual Git state and whether any protected or unrelated artifact changed. Never claim a preservation check that was not performed.
### H. One recommended next action
Identify the single most useful next step based on the evidence.
### Final instruction
Proceed with the engineering work now. Do not stop after identifying a correct implementation or writing a plan. Make every safe, authorized improvement that can be completed in this environment, then stop for review before any owner-only decision or prohibited operation.
Do not stage, commit, push, start Phase 2, or declare Phase 1 closed without sufficient evidence.
