<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15159). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
**Verdict: PARTIALLY COMPLETE — BLOCKED.** B-1 is fully implemented and statically reviewed, and I added one more test. No test could run: there is no PHP anywhere on this machine and `backend/vendor` is empty. Phase 1 cannot close: every remaining exit item needs an owner decision, staging or operator evidence, or a test runtime.

## A. Executive verdict

`PARTIALLY COMPLETE — BLOCKED`

## B. Engineering changes

One file changed this mission: `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` (untracked; CRLF, no BOM; now 17 test methods, 25 cases).

I added `test_numeric_codes_keep_distinct_identities_and_empty_lists_are_valid`. It closes two coverage gaps:
- **Numeric facet codes.** PHP turns the array key `'7'` into the integer 7 but leaves `'07'` as a string. The test proves the two stay separate facets (owner decision R-2: no silent merge of identities) and that a Capability cell for `'7'` does not leak to `'07'`. It also checks that a numeric duplicate is rejected on both routes, and that a numeric unbacked claim is rejected.
- **Empty facet lists.** An empty list gives the same result from the factory and from a direct `create()`.

No source change was justified. Re-review of the four source files found no defect, and their hashes are unchanged since my previous change.

## C. B-1 contract assessment

All invariants below are verified by inspection only.

| Invariant | Status |
|---|---|
| The Capability decision stays authoritative; unsupported states and freshness are rejected | Verified (`create()` compares every field, freshness included) |
| Missing or insufficient evidence is fail-closed | Verified (MISSING, or VERIFIED without CURRENT, gives UNKNOWN plus a warning) |
| Exact facet-code matching | Verified (Capability trims only; persistence stores `dimension_code` as given) |
| Error priority stable | Verified (instance check, then duplicate check, then consistency check) |
| Factory and `create()` agree on valid input | Verified for 5-facet, numeric, empty and single-facet cases |
| Serialized fields, keys, mappings and public contracts unchanged | Verified; no tracked Capability, Path, Identity, Coexistence, Disclosure or Projection file is modified |
| No accidental package dependency | Verified: imports run only Projection→Capability/Path/Identity, the approved direction; nothing imports B-1 |
| CSQ identity and provenance intact | Verified: the contract only carries `canonical_query_id`; it is not wired into runtime |

**The four open questions:**
- **`retrieval_method`.** The evidence supports caller input. The approved, tracked `ProjectionEnvelope::create()` (commit `1a892c4`) already takes it as optional caller input with default UNKNOWN. `PathDecisionIdentity` has no such field, so the alternative in the resolution package (Path as the source) would need a new Path contract. B-1 already behaves this way; only the ADR's [UNRESOLVED] tag needs your confirmation.
- **Visibility of `create()`.** Keep it public. ADR §8.33.8 item 5 documents direct calls. "Single producer" appears only in the package proposal. Since the consistency check, a direct call cannot produce unbacked content. I have no authority to restrict it.
- **Facet-code correspondence.** Owner decision R-2 already fixes exact matching for B-1. A mapping contract would be new work for a later scope.
- **`query_constrainable` in the required (AND) set.** This is a B-2 decision. The evaluator resolves only required dimensions, and its only runtime caller requires only `access_method:api`. So if the contract were wired today, every facet would be MISSING and then UNKNOWN. Adding facet dimensions to the AND set would change Path eligibility: a missing Capability fact would defer the whole path. That behaviour cannot be verified before you decide it in B-2.

## D. Test and validation report

**Executed:** only read-only PowerShell and `git` inspection commands (`rev-parse`, `status`, `worktree list`, `stash list`, `reflog`, `merge-base --is-ancestor`, `log`), all exit 0.
- No PHP on PATH or in common install locations.
- WSL has only `docker-desktop`.
- `vendor` has 0 entries, so `tests/bootstrap.php` (which requires `vendor/autoload.php`) cannot load. The project requires PHPUnit `^11.5.50`.

**Not run:** syntax validation, unit, integration and database tests.

**Static checks done:** encoding (CRLF, no BOM), bracket balance, and API signatures traced by hand.

**Prepared commands,** from `backend/`:

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

Excluded because they use `RefreshDatabase`, which runs migrations: `CapabilityStorePersistenceTest`, `IdentityBindingPersistenceTest`, `Stage3CghiaCoexistenceBoundaryTest`. No B-1 integration tests exist; the contract is not wired by design.

## E. Phase 1 exit matrix

| Exit ID | Required condition | Authoritative decision | Code evidence | Test evidence | External evidence | Owner approval | Status | Remaining action |
|---|---|---|---|---|---|---|---|---|
| 01 | Decisions (a)–(f) | (a), (b), (d), (e) decided; (f) decided subject to staging; (c) Option B selected | B-1 constraint metadata (untracked) | NOT RUN | — | (c) not closed | BLOCKED BY OWNER DECISION | Accept or amend T10/T8 (§8.32.3); confirm (c) conditions 3 and 5 |
| 02 | Gate A for each scope | GA-IC-01, GA-B1-01 only | — | — | — | Set of scopes and §8.24 acceptance missing | BLOCKED BY OWNER DECISION | Define scopes; record Gate A for each |
| 03 | Gate B for each scope | GB-IC-01, GB-B1-01 only | — | — | — | Missing for other scopes | BLOCKED BY OWNER DECISION | Gate B for each further scope |
| 04 | IC persistence evidence (PostgreSQL) | §8.22, §8.25 | Pgsql tests at `f2025315` | Recorded: 121 OK (§8.31.5); CI `backend-pgsql` run succeeded | — | — | PASS WITH DOCUMENTED LIMITATION | None; not re-run today |
| 05 | Order validated in staging; operator check | (f) subject to staging | `deploy-production.sh` still starts the app (line 45) before migrating (line 48) | — | None | Script change not authorized | BLOCKED BY EXTERNAL DEPENDENCY | Section H, item 1 |
| 06 | Registry and Capability integration | Not authorized | None | — | — | No | BLOCKED BY OWNER DECISION | Gate A/B for that scope |
| 07 | Per-seat IC and identity, 109 seats | GA/GB-IC-01 | 109 documentary records, all UNCLASSIFIED | — | 0 identities verified | Verification pending | BLOCKED BY OWNER DECISION | Per-seat owner review plus evidence |
| 08 | Access-family adapters | Not authorized | 0 adapters | — | No verified family | No | BLOCKED BY OWNER DECISION | Depends on 07 |
| 09 | Focused and regression tests | — | B-1 implemented | B-1 NOT RUN; last recorded baseline fails in the mandated regression areas | — | — | NOT RUN | Test runtime, then fixes (Section F) |
| 10 | Matrix integrity | — | 109 rows in register order | — | — | — | BLOCKED BY OWNER DECISION | Depends on 06–08 |
| 11 | Formal Phase 1 disposition | — | — | — | — | — | BLOCKED BY OWNER DECISION | Only after 01–10 |

## F. Root-cause and regression report

- **B-1 failures:** none recorded. The B-1 tests were never executed, and no logged Unit or Feature failure is in Projection, Capability or Path.
- **Out-of-scope baseline defects:** these come from the recorded Docker runs of 2026-10-14. P1-EXIT-09 requires regression of "the existing five-source path, CSQ identity, and Home/Crop isolation", so several of them block a mandatory exit criterion.

| Defect | Root cause | Blocks P1-EXIT-09? | Separate authorization needed |
|---|---|---|---|
| `MultiAnswerIdentitySerializerTest` and `ScientificMultiAnswerProducerTest` (16 errors) | Committed together in `acb838b`: the fixtures omit the `target` and `process` roles, which `roleIdentity()` requires; a helper lacks a `conditions:` parameter | Not directly | Decide the answer-identity contract for absent roles |
| FAOSTAT Phase 3 (7 failures) | Five-source path | Yes | FAOSTAT fix scope |
| `RequiredCapabilityAndSourceRoleTest`, SemanticScholar selector (regressions) | Consistent with the uncommitted QUS/CSQ edits | Yes (five-source path, CSQ) | QUS/CSQ change review |
| `EntitySpecificityPreservationContractTest`, `PostCsqSemanticPreservationTest` | CSQ and entity area | Yes (CSQ identity) | Same |
| `ScientificStatisticalClaimAlignerTest` (3) | Synthesis | No | Separate scope |
| `CanonicalPropertyIdentityAtMatcherContractTest` (4, HEAD only) | Test modified in the working tree | Unclear | Matcher scope |
| 52 shared scientific-pipeline Feature failures | ADR §8.32.7 | Likely | Separate scope |

## G. Owner decisions required

1. **Test runtime.** This is the only route to executed evidence.
   - Options:
     - (a) One isolated run following the §8.31.5 precedent: a container with `--network none`, read-only mounts and the existing vendor volume, running only the Section D commands.
     - (b) Provide PHP 8.2+ with dependencies on the host. That means installing packages.
     - (c) Keep NOT RUN.
   - Recommended: (a).
   - Consequence: without a run, B-1 stays unverified and P1-EXIT-09 stays NOT RUN.
2. **B-1 records and confirmations.** These are separate yes/no answers, not one bundled approval.
   1. Apply the §8.33.9 record (Section I). Without it, the ADR's file hashes and test counts stay stale.
   2. Confirm caller input as the `retrieval_method` source, matching the approved envelope. The alternative is a new Path contract in a later scope.
   3. Keep `create()` public. The alternative is restricting it to the factory, which changes the documented direct-call behaviour.
   4. Confirm exact facet-code matching as the standing rule. The alternative is a mapping contract in a later scope.
   - Recommended: yes to all four.
   - Affects: B-1 closure and P1-EXIT-01(c) condition 3.
3. **Closing P1-EXIT-01(c).**
   - Options: accept, amend or reject the T10/T8 transcriptions (§8.32.3), and confirm or reject the executor assessments for conditions 3 and 5.
   - Consequence: only an explicit decision can close (c). Until then P1-EXIT-01 stays blocked.
4. **Deployment order implementation.**
   - Question: authorize changing `scripts/deploy-production.sh` to the order you decided (database, then diagnostic, then one-off migrate, then application, then smoke checks).
   - Recommended: authorize it, with staging validation first.
   - Consequence: staging cannot validate an order the script does not implement.
   - Affects: P1-EXIT-05.
5. **Baseline defect fix scopes** (Section F).
   - Recommended: start with the FAOSTAT and QUS/CSQ regressions; they block P1-EXIT-09.
6. **Further Gate A/B scopes:** B-2 (which includes the AND-set question), Registry, adapters, and per-seat IC.
   - Affects: P1-EXIT-02, -03, -06, -07, -08 and -10.

## H. External dependencies

1. **Staging (ADR §8.26.5):**
   - the §8.25.7 diagnostic returns zero rows on a staging copy;
   - `migrate --force` finishes and the index `cghia_ic_records_single_active_uq` exists in `pg_indexes` before any new application container starts;
   - a drill with a seeded duplicate ACTIVE row shows the migration refuses and the previous release keeps serving;
   - `rollback-production.sh` restores the previous release;
   - smoke checks pass.
2. **Operator:** run the §8.25.7 read-only `SELECT` on each real environment before deployment, and record zero rows.
3. **Per-seat evidence:** identity and access evidence for the 109 seats.
4. **Remote and CI:** REMOTE STATE UNVERIFIED (no network). The local tracking ref equals HEAD `f2025315`.
5. **Infrastructure:** a PHP 8.2+ runtime with PHPUnit 11.5.

## I. ADR-023 proposal (not applied)

```markdown
### 8.33.9 — B-1 hardening after 18:49 (execution record, 2026-10-14)

**Status:** EXECUTION RECORD. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source** [OWNER DECISION]: the prompts of 19:54, 20:05, and 20:26 UTC+3 authorized focused source and test changes within B-1 whose behaviour follows from existing decisions. They are conversation records, not archived [M]. No decision of §8.33.1–§8.33.8 is changed.

**Finding** [IMPLEMENTATION FACT]: `ProjectionQueryConstraints::create()` accepted caller-built facet constraints, so a constraint not backed by the Capability decision (for example `VERIFIED` + `CURRENT` without any cell) could become `CONSTRAINABLE`, contrary to M-1 and R-1.

**Changes** [IMPLEMENTATION FACT]:
1. The cell-reading rules and their rejection messages moved from the factory to `ProjectionFacetConstraint::fromCapabilityDecision()`, which also normalizes the codes it receives (idempotent) and builds the constraint list.
2. `create()` rejects any facet constraint that differs from the one the decision resolves, freshness included ("Facet constraint [x] does not match the capability decision."). The check runs after the instance and duplicate checks. A direct `create()` now rejects a malformed decision even with no constraints, as the factory does.
3. No field, key, mapping, serialization, or visibility changed.

**Static review** [IMPLEMENTATION FACT, inspection only]: exact code matching (Capability codes trimmed only; persistence stores `dimension_code` as given); numeric codes kept distinct (`'7'` ≠ `'07'`); no caller outside the B-1 files; imports only Projection → Capability/Path/Identity.

**Files** (CRLF, no BOM, untracked):

| File | SHA-256 |
|---|---|
| `./Projection/ProjectionFacetConstraintState.php` | `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` (unchanged since §8.33.8) |
| `./Projection/ProjectionFacetConstraint.php` | `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f` |
| `./Projection/ProjectionQueryConstraints.php` | `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998` |
| `./Projection/ProjectionQueryConstraintsFactory.php` | `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f` |
| `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` | `651d6d5068c2c1ff084381b8a94d364271e9b00034f09c5ea675b69ffa379726` |

The file hashes and test counts of §8.33.8 are superseded by this table.

**Tests:** 17 test methods, 25 cases. New: `test_direct_create_accepts_only_constraints_the_capability_decision_resolves`, `test_numeric_codes_keep_distinct_identities_and_empty_lists_are_valid`. **Executed: none. Test status: NOT RUN** (no PHP on the host, `backend/vendor` empty, Docker prohibited). No syntax check was possible. **No test is claimed to pass.**

**Still missing:** executed B-1 and adjacent unit suites; a regression baseline for P1-EXIT-09; staging and operator evidence (P1-EXIT-05).

**Unresolved** [UNRESOLVED]: `retrieval_method` source (evidence supports caller input, as in `ProjectionEnvelope::create()`); visibility of `create()`; Projection-to-Capability facet-code correspondence beyond exact match; `query_constrainable` in the required set (B-2).
```

## J. Final preservation audit

- **Git (identical at start and end):** branch `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0` (the tracking ref matches). 1135 entries: 1118 untracked, 17 tracked-changed (all pre-existing), 0 staged; 92 ignored, 0 stashes, 8 worktrees.
- **`31a6ee3`** is an ancestor of HEAD (`merge-base --is-ancestor` exit 0).
- **Protected files unchanged:** `composer.lock`, `.env`, `phpunit.xml`, ADR-023 (`D3771AA0…`) and the resolution package.
- **Read only, not compared:** `composer.json`, `tests/bootstrap.php` and `scripts/deploy-production.sh`. I only read them and had no baseline hashes for them from before this mission.
- **Changed this mission:** only the B-1 test file, from `192580632a5c…` to `651d6d5068c2…`. There is no pre-edit backup of this round's version; the `%TEMP%` backup holds the version from before the 19:54 changes.
- **Prohibited actions:** none occurred (no git writes, Docker, network, database, package installs or source activation).

## K. Recommended next action

Authorize the single isolated, network-less run of the Section D commands. It is the one step that can turn B-1 into executed evidence. In the same run, re-running the Unit and Feature baseline would confirm which Section F defects block P1-EXIT-09.

The smallest set of actions that moves Phase 1 toward closure:
1. that test run;
2. the four B-1 confirmations plus the §8.33.9 record;
3. the P1-EXIT-01(c) decision;
4. fix scopes for the FAOSTAT and QUS/CSQ regressions;
5. the deploy-script change followed by staging validation.

## L. Phase 2 readiness

`PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED`

I'm structuring the final report into organized sections with an exit matrix tracking each requirement's status, evidence, and ownership — starting by marking exit 01 as blocked pending an owner decision.

Working through exits 02-08, most come back blocked by either an outstanding owner decision or an external dependency (like the undeployed script change needing authorization). For exit 09, I'm wrestling with ambiguity in the baseline regression criteria — the recorded data shows failures in the mandated regression areas, but it's unclear whether that counts against the current implementation or reflects pre-existing issues, so I need to settle on a single defensible status to report.

I'm leaning toward NOT RUN as the status for 09 since there's no current evidence for the implemented scope, while noting that the last recorded baseline showed failures in the regression areas. Exits 10 and 11 remain blocked pending owner decisions and earlier items, and I'm now organizing the outstanding owner decisions into groups — covering test environment setup, B-1 record and contract confirmations, and closure items from exit 01(c).

I'm also outlining remaining groups: deployment script authorization, out-of-scope defect fix scopes needed for a green regression, and scope expansion decisions for B-2/Registry/adapters. Beyond these, external dependencies include staging environments, operator sign-offs, per-seat access evidence, GitHub verification, and PHP runtime, and I'm noting the corrected ADR proposal details before settling on a verdict of partially complete but blocked, with the next step being to authorize focused isolated work.
