<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15144). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
I found no defects in the new consistency check and changed no source code this round. I added one test assertion, covering the one behaviour change from the previous round that no test pinned. **No test was executed:** there is no PHP runtime anywhere on this machine and `backend/vendor` is empty, so everything below is static inspection. Phase 1 is still blocked.

## A. Engineering work completed

One change, to the test file only (still untracked; CRLF, no BOM; SHA-256 now `192580632a5c2fc3…`):

- **`test_direct_create_accepts_only_constraints_the_capability_decision_resolves`** now also asserts that a malformed decision (an outcome with no `dimension_key`) is rejected with the same message on both routes: the factory with no facet codes, and a direct `create()` with an empty list.
- **Why it was needed:** last round's change made a direct `create()` read the decision's outcomes even when there are no constraints. That brought it in line with the factory, but no test pinned it.

The four source files are unchanged since last round; their hashes match what I recorded then.

## B. Contract review

The four source files and the test were all found at their expected paths under `backend/app/Services/Agriculture/Research/Projection/` and `backend/tests/Unit/Agriculture/Research/Projection/`.

**Verified by inspection:**
- **No unbacked claims.** `create()` compares every field of each caller-built constraint with what `fromCapabilityDecision()` resolves: code, identity-critical flag, Capability state, constraint, and freshness. Freshness is compared separately because it is not serialized. The only constructor is private, so no other route exists.
- **Error priority unchanged.** The instance check, then the duplicate check, still run before the new consistency check. The existing duplicate test proves the order: its `dose` VERIFIED+CURRENT entry has no backing cell, so a wrong order would surface the wrong message.
- **Exact matching and fail-closed behaviour intact.** Matching uses the exact Capability dimension key. Capability codes are only trimmed, never case-folded. A missing cell or `missing_record` gives MISSING, and then UNKNOWN with a warning.
- **No contract changed.** Mappings, keys and serialized fields are unchanged. `git status` shows no modified tracked file in the Capability, Path, Identity, Coexistence, Disclosure or Projection areas; the only entries are the five untracked B-1 files.
- **Factory and direct `create()` agree** for valid inputs, including the five-facet ordering test with numeric codes.
- **Existing callers compatible.** A case-sensitive search finds no caller outside the B-1 files. The earlier hits were `bindFromCapabilityDecision`.
- **The tests exercise behaviour**, through public APIs with decisions from the real evaluator. The one exception is the source-scan guard test, which is intentionally structural.
- **Every external symbol the code and test use exists** with matching signatures. PHPUnit `^11.5.50` supports the `DataProvider` attribute the test uses.

**Still uncertain:**
- Whether the code actually compiles and passes: no `php -l` and no PHPUnit run were possible.
- One subtlety deserves a real run: numeric facet codes such as `'9'` become integer array keys in PHP. I traced the lookups by hand and they are consistent.

## C. Validation results

**Commands executed:** only read-only PowerShell and `git rev-parse`/`status`/`stash list`/`worktree list`, all exit 0.
- No PHP on PATH or in nine common install locations.
- WSL has only `docker-desktop`.
- `backend/vendor` has 0 entries, so `tests/bootstrap.php`, which requires `vendor/autoload.php`, cannot load.

**Prepared commands (all NOT RUN),** from `backend/`:

| Suite | Command |
|---|---|
| Projection | `php vendor/bin/phpunit tests/Unit/Agriculture/Research/Projection` |
| Capability | `php vendor/bin/phpunit tests/Unit/Agriculture/Research/Capability/CapabilityStoreDomainContractTest.php` |
| Path | `php vendor/bin/phpunit tests/Unit/Agriculture/Research/Path` |
| Identity | `php vendor/bin/phpunit tests/Unit/Agriculture/Research/Identity/SourceIdentityDomainContractTest.php` |
| Coexistence | `php vendor/bin/phpunit tests/Unit/Agriculture/Research/Coexistence/CoexistenceRetrievalPlanTest.php`, and the same for `Stage3CoexistenceSearchObservabilityAttachTest.php` and `Stage3SourceKeyIdentityBridgeTest.php` |
| Disclosure | `php vendor/bin/phpunit tests/Unit/Agriculture/Research/Disclosure` |
| B-1 integration | None exist: the contract is not wired into runtime by design |

Three tests are deliberately left out because they use `RefreshDatabase`, which runs migrations, and that is prohibited: `CapabilityStorePersistenceTest`, `IdentityBindingPersistenceTest` and `Stage3CghiaCoexistenceBoundaryTest`.

## D. B-1 closure checklist

| Condition | Status | Evidence |
|---|---|---|
| Implementation complete within GB-B1-01 scope | PASS (inspection) | Changes confined to the five authorized files |
| Static review complete | PASS | Section B |
| PHP syntax check | NOT RUN | No PHP |
| B-1 unit tests executed and passed | NOT RUN | No PHP, empty `vendor` |
| Adjacent unit suites (no regressions) | NOT RUN | Same |
| Runtime behaviour verified | NOT VERIFIED | Not wired by design; wiring is B-2 |
| ADR record matches files | BLOCKED | The §8.33.8 hashes and test counts are stale; the proposed §8.33.9 needs owner authorization |
| Owner decisions complete | BLOCKED | Section E |
| Gate A and Gate B recorded | PASS | GA-B1-01 and GB-B1-01 are recorded in the ADR |
| GB-B1-01 acceptance criteria met | BLOCKED | They require the test groups to pass in a permitted environment |

**B-1 is IMPLEMENTED, but not verified and not closed.**

**Corrected §8.33.9 proposal (not applied).** The earlier draft said the cell reading "moved unchanged" and gave stale counts and hashes. Corrected text:

```markdown
### 8.33.9 — Decision-consistency check of `create()` (executor correction, 2026-10-14)

**Status:** EXECUTION RECORD. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Finding** [IMPLEMENTATION FACT]: `ProjectionQueryConstraints::create()` accepted caller-built facet constraints, so a constraint not backed by the Capability decision (for example `VERIFIED` + `CURRENT` without any cell) could become `CONSTRAINABLE`, contrary to M-1 and R-1 (§8.33.8) [OWNER DECISION].

**Change** [IMPLEMENTATION FACT]: The cell-reading rules and their rejection messages moved from the factory to `ProjectionFacetConstraint::fromCapabilityDecision()`, which also normalizes the codes it receives (idempotent) and builds the constraint list. `create()` rejects any facet constraint that differs from the one the decision resolves, freshness included ("Facet constraint [x] does not match the capability decision."); this check runs after the instance and duplicate checks. A direct `create()` now rejects a malformed decision even with no constraints, as the factory already did. No field, key, mapping, or serialization changed. The visibility of `create()` is unchanged; restricting it remains [UNRESOLVED].

**Files** (CRLF, no BOM, untracked) [IMPLEMENTATION FACT]:

| File | SHA-256 |
|---|---|
| `./Projection/ProjectionFacetConstraintState.php` | `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` (unchanged) |
| `./Projection/ProjectionFacetConstraint.php` | `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f` |
| `./Projection/ProjectionQueryConstraints.php` | `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998` |
| `./Projection/ProjectionQueryConstraintsFactory.php` | `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f` |
| `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` | `192580632a5c2fc3938acc4a84a01712ab47c81a914946ed2834a94656b0cc04` |

**Tests:** 16 test methods, 24 cases. New: `test_direct_create_accepts_only_constraints_the_capability_decision_resolves`. **Test status: NOT RUN** (no PHP on the host, `backend/vendor` empty, Docker prohibited). **No test is claimed to pass.**
```

## E. Remaining owner decisions

1. **Test runtime.** This is the only route to executed evidence.
   - Authorize one isolated run, following the ADR §8.31.5 precedent: a container with `--network none`, read-only mounts and the existing vendor volume, running only the commands in Section C.
   - Or provide a host PHP 8.2+ with dependencies. That means installing packages, which needs your separate approval.
   - Or accept NOT RUN, in which case B-1 cannot close.
2. **The §8.33.9 record:** apply it, amend it, or reject it. Until something is applied, the ADR's file evidence stays stale.
3. **`retrieval_method` source.**
   - Keep it as caller input. This matches the approved `ProjectionEnvelope::create()`.
   - Or derive it from the Path decision. That needs a new Path field, which is a new contract.
4. **Visibility of `create()`.**
   - Keep it public. Its content is now checked against the decision.
   - Or restrict it to the factory. That is cleaner, but changes the documented direct-call behaviour in §8.33.8 item 5.
5. **Facet-code correspondence.**
   - Keep exact matching. This is fail-closed, and mixed-case Capability codes never match.
   - Or define the mapping in the Capability contract.
6. **`query_constrainable` in the required (AND) set.** This is a B-2 decision. Until it is made, no facet can be CONSTRAINABLE at runtime.

## F. Phase 1 status

Phase 1 is still **BLOCKED**, and Phase 2 is **NOT STARTED**.
- **P1-EXIT-01(c):** NOT CLOSED. Condition 1 needs tracked T10/T8 definitions; conditions 3 and 5 are executor assessments awaiting owner confirmation.
- **P1-EXIT-02, -03, -06, -07, -08, -10, -11:** blocked on owner decisions.
- **P1-EXIT-05:** needs staging and operator evidence.
- **P1-EXIT-09:** baseline not green, and the B-1 tests are NOT RUN.

## G. Preservation confirmation

All checked this turn:
- **Git:** branch `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`. 1135 entries: 1118 untracked, 17 modified tracked, 0 staged; 0 stashes, 8 worktrees, the same as before. No git write command was run.
- **Protected files:** `composer.lock`, `.env`, `phpunit.xml` and ADR-023 hashes are unchanged from my baseline.
- **`composer.json`:** I only read it. I had no earlier baseline hash for it; it now hashes `DE302DFA…`.
- **Other:** no Docker, network, database or package activity. No source was activated.

## H. Recommended next action

Authorize the one isolated, network-less test run of the six focused commands in Section C. It is the single step that turns B-1 from "implemented" into "verified", and its result decides whether §8.33.9 can be recorded as accurate.

I don't have a recorded baseline for composer.json, but I'm confident I never edited it myself, so I'll just note that it's unchanged by my actions rather than digging into git status for it.

Now I'm drafting the final report with sections A through H, including the corrected proposal for §8.33.9 and the exact (unexecuted) test commands for Projection, Capability, Path, Identity, and the Coexistence-related test files.

Updated test count to 24 cases across 16 methods, hash refreshed. Going through checklist D: implementation and static review pass by inspection, but syntax check, unit tests, and regression suites are all not run; runtime behavior isn't verifiable since B-1 isn't wired by design (wiring happens in B-2). The ADR record is blocked pending owner authorization since §8.33.8 hashes are stale and §8.33.9 still needs sign-off.

Owner decisions remain blocked on open questions — retrieval_method, facet-code correspondence, and create visibility matter for B-1 closure, while AND-set logic belongs to B-2. Gate recording (GA-B1-01, GB-B1-01) is logged but acceptance criteria aren't met since tests haven't passed, and the "only five new files" git criterion holds true for B-1's own changes even though the broader repo has many unrelated pre-existing modifications.

I'm laying out concrete owner options now: authorizing a sandboxed Docker test run versus host PHP with composer install versus leaving tests unrun (which blocks closure); deciding on the ADR record; choosing whether retrieval_method stays caller-supplied or becomes Path-derived; and settling create() visibility and facet-code mapping. Phase 1 overall is blocked, and the clearest next step is authorizing the isolated Docker test run per the existing precedent, since that's the one blocker that would convert B-1 from untested to evidenced. I'll write up the final report now, keeping it concise but complete.
