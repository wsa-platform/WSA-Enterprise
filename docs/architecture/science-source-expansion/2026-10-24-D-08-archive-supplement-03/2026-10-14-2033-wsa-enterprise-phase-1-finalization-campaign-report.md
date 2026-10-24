<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15200). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# Phase 1 Finalization Campaign: final report

**Verdict: PARTIALLY COMPLETE — BLOCKED.** No repository file changed this campaign. B-1 has no remaining defect that I can fix under existing authority, and its tests are still NOT RUN because this machine has no PHP. Phase 1 cannot close: every remaining exit item needs an owner decision, a test runtime, staging or operator evidence, or a fix outside B-1.

## A. Changes made

- **Repository: none.** A static re-review of the four B-1 source files and the test file found no new defect, so no edit was justified.
- **Outside the repository:** two `%TEMP%` working files: `prev-826-report.txt` and `prompt-833.txt`.

## B. B-1 status

**Implementation.** Complete. The five files are untracked, use CRLF line endings, have no BOM, and their hashes are unchanged since the 8:26 PM report:

| File | SHA-256 |
|---|---|
| `ProjectionFacetConstraintState.php` | `f60536e5…` |
| `ProjectionFacetConstraint.php` | `c4fbe165…` |
| `ProjectionQueryConstraints.php` | `8810decd…` |
| `ProjectionQueryConstraintsFactory.php` | `515e432e…` |
| `ProjectionQueryConstraintsTest.php` | `651d6d50…` (17 test methods, 25 cases) |

**Tests: NOT RUN.** I checked once: PHP is not on PATH and `backend/vendor` has 0 entries. Nothing was installed. The eight prepared commands are unchanged from the earlier report.

**The four open questions stay unresolved.** Evidence and recommendation for each:

1. **`retrieval_method` source.** The approved `ProjectionEnvelope::create()` (commit `1a892c4`) already takes it as optional caller input, defaulting to UNKNOWN. `PathDecisionIdentity` has no such field. Recommendation: confirm caller input.
2. **`create()` visibility.** ADR §8.33.8 item 5 documents direct calls, and the consistency check means a direct call cannot produce unbacked constraints. Recommendation: keep it public.
3. **Facet-code mapping beyond exact match.** Owner decision R-2 fixes exact matching. Capability codes are only trimmed, and persistence stores them as given. Recommendation: exact match stays the rule; any mapping contract belongs to a later scope.
4. **`query_constrainable` in the required (AND) set.** This is a B-2 decision. The only runtime caller requires just `access_method:api`, so wiring the contract today would make every facet UNKNOWN. Adding facet dimensions to the AND set would change Path eligibility.

## C. Exit matrix

| Exit | Status | Evidence / remaining action |
|---|---|---|
| 01 | BLOCKED BY OWNER DECISION | (a), (b), (d), (e) decided; (f) decided subject to staging. (c) still needs §8.32.3 conditions 1, 3 and 5 (decisions 1–3 in section G). |
| 02 / 03 | BLOCKED BY OWNER DECISION | Gate A and Gate B exist only for IC and B-1 (GA/GB-IC-01, GA/GB-B1-01). |
| 04 | PASS WITH DOCUMENTED LIMITATION | Recorded: 121 OK on PostgreSQL (§8.31.5), and the CI `backend-pgsql` job succeeded. Not re-run. |
| 05 | BLOCKED BY EXTERNAL DEPENDENCY plus a script defect | The script still uses the old order (section E). |
| 06 / 07 / 08 / 10 | BLOCKED BY OWNER DECISION | Registry not authorized; 109 seats UNCLASSIFIED with 0 verified identities; 0 adapters. |
| 09 | NOT RUN, with known committed failures that block it | See section D. |
| 11 | BLOCKED | Depends on 01–10. |

## D. Regression root causes and minimum repair scopes (P1-EXIT-09)

**Baseline:** P1-EXIT-09 covers the five-source path, CSQ identity, and Home/Crop isolation. The FAOSTAT, selector, sweet-potato snapshot and post-CSQ preservation failures below were recorded at both HEAD and the working tree, so they come from committed code. The working-tree QUS/CSQ edits did not cause them.

**1. FAOSTAT measure resolution: 7 Unit failures. Blocks P1-EXIT-09: yes.**
- **Symptoms.**
  - Production questions resolve to no element instead of the production code 2510.
  - One case reports AMBIGUOUS_MEASURES where INCOMPLETE_FILTERS was expected.
  - For "What was wheat production in Italy in 2022?" the request actually sent used the yield code 2413 (`FaoStatPhase3FixTest:59`), and the follow-on test gets zero results.
- **Mechanism (verified in code).** `FaoStatQclElementSemantics::detectRequestedMeasures` (lines 136–148) detects measures from one combined text. It includes the user's question but also the planner's `topic`, `requestedInformation` and `researchIntent`. If it finds more than one measure, the resolver returns no element. The comment on line 145, "User wording wins", is not what the code does.
- **Inferred cause, not yet run.** The planner surfaces now carry a second measure word (yield). The FAOSTAT code and its tests last changed together in `0ea1c7e` (09-21). The planner, QUS and CSQ were then changed by six commits: `14c380f`, `5bf2499`, `c940e2c`, `f6085e4`, `6708856`, `9411f70` (09-24 to 09-28).
- **Smallest repair.**
  - First, one isolated diagnostic run that prints those planner fields for the four failing questions, plus a bisect across the six commits.
  - Then change the one function so measures come from the question text first and planner surfaces only as a fallback. That stays inside FAOSTAT.
  - The alternative is to stop the planner emitting measure words the user never wrote, which touches CSQ.
- **Tests:** `tests/Unit/Agriculture/Intelligence/FaoStat`, then the Research Unit suite.
- **Authorization:** not authorized; needs an owner decision.

**2. Selector test versus the FAOSTAT consideration policy (`RequiredCapabilityAndSourceRoleTest:119` and the SemanticScholar Feature regression). Blocks P1-EXIT-09: yes, as a red test; it is not a runtime defect.**
- The test (`b0ad3b6`, 09-10) expects FAOSTAT to be skipped with the reason `capability_not_required`.
- The later policy (`0ed5d64` and `870cb75`, 09-15/16) makes FAOSTAT consideration universal whenever its feature flag is on. With the flag off, the reason reported is `disabled` instead. So the test cannot pass in either flag state: it predates the policy.
- The difference between the HEAD and working-tree runs is environmental: the HEAD snapshot had no `.env` file, so the flag was off.
- ADR §8.32.7 attributes this regression to the QUS/CSQ edits. That attribution is contested; a rerun with `FAOSTAT_ENABLED` set explicitly would settle it.
- **Smallest repair:** one test method's expectation, if the owner confirms the universal policy is authoritative.
- **Authorization:** needs an owner decision.

**3. Sweet-potato variant snapshot (`EntitySpecificityPreservationContractTest:60`) and post-CSQ preservation (`PostCsqSemanticPreservationTest:276`). Blocks P1-EXIT-09: yes (CSQ identity and Home/Crop isolation).**
- **Snapshot drift.** The snapshot (09-17) expects five cultivation/production search variants. The code now produces two: "Ipomoea batatas sweet potato farming agronomic requirements" and "Ipomoea batatas sweet potato farming". The phrase "agronomic requirements" was introduced by `14c380f` and `f6085e4`.
- **Self-contradiction.** `PostCsqSemanticPreservationTest` was added in `f6085e4` specifically to assert that the existing variant family is preserved, and it now fails. That makes this a regression from `f6085e4`, `6708856` or `9411f70`, not an intended re-baseline.
- **Smallest repair:** bisect those three commits, then restore the variant family for crop-profile queries without independent roles in the query-builder path.
- **Tests:** both test files, plus the Research Unit suite.
- **Authorization:** needs an owner decision; this is CSQ territory outside B-1.

**Out of the mandated areas, not modified:**
- **Statistical claim aligner (3 failures, synthesis):** `quantity` no longer normalizes to production quantity, the Arabic word for yield gets no family, and a bare year creates an alignment. It affects FAOSTAT answer validation only indirectly.
- **`MultiAnswerIdentitySerializer` (16 errors):** a fixture/contract mismatch from `acb838b`.
- **`CanonicalPropertyIdentityAtMatcherContractTest` (4 failures):** HEAD only; the test is modified in the working tree.
- **52 shared Feature failures:** need a run to triage.

**Cross-cutting decision:** which tree is the P1-EXIT-09 baseline, HEAD `f2025315` or HEAD plus the 17 uncommitted tracked changes (including the French/Arabic QUS/CSQ edits).

## E. Deployment and staging readiness (P1-EXIT-05)

**Script defects (repository changes; they need authorization because they change deployment settings):**
1. **The application starts before the migration.** `deploy-production.sh` starts the new backend, queue and scheduler (line 45) before migrating (line 48). If the migration fails, the new release is already serving, which breaks §8.26.5 item 3.
2. **The migration needs the new application running.** It runs via `exec` into the new container rather than `run --rm --no-deps backend`.
3. **No checks around the migration.** There is no hold point for the §8.25.7 diagnostic and no index check afterwards.
4. **The rollback target is not recorded.** The deploy defaults to the mutable tag `main` and never records the previously running immutable tag, yet `rollback-production.sh` refuses `main`.
5. **Documentation:** `docs/deploy-production.md` describes the same old order (§8.25.6).

**Verified non-issues:**
- The backend entrypoint (`docker-entrypoint.sh`) never migrates.
- Rollback runs no migrations.
- The index is additive, and `down()` only drops it.
- The migration preflight refuses on duplicates and changes no data.
- Inferred: Laravel's transactional migrations on PostgreSQL make the preflight and index creation atomic, so a concurrent duplicate would fail the index creation.

**Checklist in the decided order. None of this has been executed.**
1. Record the current immutable tag as the rollback target, and deploy by commit SHA, not `main`.
2. Pull images (`"${COMPOSE[@]}" pull`). This does not stop the running release.
3. Start the database only: `up -d --no-build --wait postgres redis`. The backend depends on both being healthy.
4. The operator runs the §8.25.7 `SELECT` read-only and requires zero rows. Otherwise stop: no migration, and the duplicates go to the architecture owner.
5. Migrate: `run --rm --no-deps backend php artisan migrate --force`. A failure stops the deploy, and the previous release keeps serving.
6. Verify the index: `SELECT indexname FROM pg_indexes WHERE tablename='cghia_integration_classification_records' AND indexname='cghia_ic_records_single_active_uq';` must return one row, and `migrate:status` must show the migration as Ran.
7. Start the application only now: `up -d --no-build --wait`.
8. Run `smoke-production.sh` (`health/live`, `health/ready`, `health`, `/up`).
9. Rollback check: `IMAGE_TAG=<recorded tag> rollback-production.sh`, which runs `verify-production.sh`.

**Staging-only evidence (§8.26.5 items 1–5). None of it has occurred.**
- The diagnostic returns zero rows.
- Container event timestamps show the index existing before any new application container starts.
- The duplicate-ACTIVE drill. It must run on a staging copy where this migration has not yet run, because the index itself blocks seeding a duplicate. Seeding needs authorization.
- Rollback restores the previous release.
- Smoke checks pass.

**Operator-only:** the §8.25.7 diagnostic on each real environment (§8.25.10 item 4).

## F. Corrected proposed §8.33.9 (not applied)

The proposed record in the 8:26 PM report, section I, is still factually correct. All five hashes and the 17 methods / 25 cases are re-verified. Three corrections:
- Relabel the **Source** tag from `[OWNER DECISION]` to `[OWNER AUTHORIZATION — conversation record]`. The prompts authorize work; they do not decide architecture.
- Add after the files table: "Hashes re-verified at the close of the 20:33 campaign; no B-1 file changed after 20:26."
- Replace **Still missing** with: "executed B-1 and adjacent unit suites; a P1-EXIT-09 baseline (committed FAOSTAT and CSQ-variant failures recorded at HEAD, §D of the 20:33 report); staging and operator evidence (P1-EXIT-05)."

The rest stays unchanged: status NOT CLOSED / BLOCKED / NOT STARTED, the finding, changes 1–3, the static review, test status **NOT RUN**, and the four [UNRESOLVED] items.

## G. Owner decisions (each one separate)

| # | Exact question | Recommended | Alternatives | Consequence | Exits | Proceeds independently |
|---|---|---|---|---|---|---|
| 1 | Accept the §8.32.3 transcription as the tracked T10/T8 definition, and replace T2's "Projection owns query" with the Option B wording? (condition 1) | Accept, amending if the wording differs from intent | Amend; supply another definition | Only this closes condition 1 | 01, 11 | All else |
| 2 | Accept `projection_query_constraints.v1` (B-1) as the condition-3 constraint metadata? | Accept | Define different metadata | Closes condition 3; B-1 becomes the record | 01 | Test run |
| 3 | Record (c) in a Gate A record for this scope? (condition 5) | Yes, after decisions 1 and 2 | Defer | Closes (c) | 01, 02 | — |
| 4 | Apply §8.33.9 (section F) to ADR-023? | Yes | Keep the ADR stale | The ADR's file hashes and test counts stay wrong otherwise | 01, 09 | Everything |
| 5a–d | Confirm `retrieval_method` as caller input / keep `create()` public / exact facet match / move the AND-set question to B-2 | Yes ×4 | New Path contract / factory-only / mapping contract / decide AND set now | Clears the [UNRESOLVED] tags | 01 | Test run |
| 6 | Authorize one isolated test run (`--network none`, read-only mounts, existing vendor volume, §8.31.5 precedent), including the FAOSTAT diagnostic and bisect? | Yes | PHP on the host (package installs); keep NOT RUN | The only route to executed evidence | 09, 01 | — |
| 7 | Which tree is the P1-EXIT-09 baseline: HEAD, or HEAD plus the 17 uncommitted tracked changes? | HEAD, with uncommitted work reviewed separately | Working tree | Determines which failures count | 09 | 6 |
| 8 | FAOSTAT repair: measures from the question first, planner surfaces as fallback, in one function? | Yes, after the diagnostic | Planner/CSQ change | Fixes 7 failures | 09 | 9, 10 |
| 9 | Selector test: is universal FAOSTAT consideration (09-15/16) authoritative? | Yes; update one test expectation | Capability-gated selection, which changes selector behaviour | Clears 1 Unit failure and probably the SemanticScholar Feature one | 09 | 8, 10 |
| 10 | CSQ variant family: restore the pre-CSQ family for crop-profile queries without independent roles? | Restore, as the CSQ closure's own test asserts | Re-baseline both tests via a recorded CSQ decision | Clears 2 failures | 09 | 8, 9 |
| 11 | Authorize the deploy-script change to the decided order (section E, including recording the rollback tag), then staging? | Yes | Keep the script; staging cannot validate the decided order | Unblocks staging | 05 | Everything |
| 12 | Authorize staging, including seeding a duplicate ACTIVE row on a staging copy that has not run this migration? | Yes, after 11 | — | Produces §8.26.5 evidence | 05 | — |
| 13 | Define the remaining Gate A/B scopes: B-2, Registry, adapters, per-seat IC? | Define B-2 first | — | — | 02, 03, 06, 08, 10 | — |
| 14 | Per-seat identity evidence for the 109 IC seats? | Owner review, seat by seat | — | 0 verified today | 07, 10 | — |
| 15 | Confirm the §8.28.7 checklist, which is still marked "PROPOSED — NOT CONFIRMED"? | Confirm or amend | — | The closure criteria themselves are unconfirmed | 11 | — |

## H. External dependencies

- A PHP 8.2+ runtime with PHPUnit 11.5.
- Staging evidence for §8.26.5 items 1–5.
- The operator's §8.25.7 check on each real environment.
- Identity and access evidence for the 109 seats.
- Remote and CI state: REMOTE STATE UNVERIFIED (no network). The local tracking ref equals HEAD.

## I. Git and preservation audit (start and end identical)

- **Branch and HEAD:** `phase-18-m18-ai-marketing-communications`, HEAD equal to its upstream at `f2025315d8990f52f3c658c968a3ec8252ac18b0`.
- **Status:** 1135 entries (1118 untracked, 17 tracked-changed, 0 staged); 92 ignored (matching mode); 0 stashes; 8 worktrees.
- **Unchanged files:** `composer.lock`, `.env`, `phpunit.xml`, ADR-023, `deploy-production.sh` and the five B-1 files all match their start-of-mission hashes. `rollback-production.sh`, `smoke-production.sh` and the migration had no start-of-mission hashes recorded, but `git diff` on `scripts/` and the migrations directory is clean.
- **Prohibited actions:** none (no git writes, Docker, network, database access, installs or activation).

## J. Prioritized action plan

1. Decision 6, the isolated test run with the FAOSTAT diagnostic and bisect, together with decision 7, the baseline tree.
2. Decisions 2, 4 and 5a–d: the B-1 records.
3. Decisions 1 and 3: close P1-EXIT-01(c).
4. Decisions 8, 9 and 10 (the regression repairs), then re-run the suites.
5. Decisions 11 and 12, then staging and the operator check.
6. Decisions 13, 14 and 15.

## K. Verdict

`PARTIALLY COMPLETE — BLOCKED`

`PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED`

I see that item (c) still needs three separate decisions resolved: Condition 1 (the transcription plus replacement work), Condition 3 (defining the constraint metadata, possibly via the B-1 contract), and Condition 5 (the Gate A record). Now I'm moving on to drafting the final report, aiming to keep it comprehensive yet readable, using tables for the exit matrix and owner decisions.

Previous reports were in English so I'll stick with English here despite one earlier heading referencing an "Arabic" title. Looking at the FAOSTAT root cause evidence: all 7 failures are identical between HEAD and the working tree, meaning they're committed. The symptoms trace back to a measure-detection mechanism that builds a blob from several question fields and returns a null element whenever more than one measure term is detected — and I'm seeing the plan surfaces now carry a second measure term (yield alongside production), which explains why element 2413 was sent instead of the expected production element. I'm tracing through five Planner/QUS/CSQ commits made after FAOSTAT's last commit to pin down exactly when this extra measure term was introduced.

The smallest fix scope would be a one-function change inside FAOSTAT's detectRequestedMeasures to prioritize the original/normalized question over plan-derived surfaces, restoring the documented "user wording wins" rule that the current blob-mixing logic actually contradicts — a real inconsistency between the code and its own comment. This blocks the P1-EXIT-09 five-source path, so I'm planning a diagnostic probe across the four failing questions plus a bisect across the six commits before settling on which remediation test suite to target.

For the selector/SemanticScholar issue, the real root cause is that considerFaostatWhenEnabled always adds fao_stat when the flag is on, per a policy change in two later commits — directly contradicting an earlier test that expects a 'capability_not_required' skip. So the test can't pass under either flag state, meaning this is a stale test versus a newer policy rather than a live bug; I suspect the working-tree vs HEAD discrepancy is just because the HEAD snapshot was built via git archive without an .env file, defaulting the flag to false. I want to settle the disputed attribution by rerunning with the flag explicitly pinned in both states.

This is really a contract conflict blocking the "regression green" bar rather than a runtime defect, and it needs an owner decision: either the universal policy is authoritative (update the test) or the capability-gated selection is authoritative (change the selector's behavior) — I lean toward updating the test since the policy was the more recent, deliberate change.

Now I'm looking at the EntitySpecificity sweet potato snapshot test, which fails at HEAD because the snapshot expects five cultivation/production variants but actual output only returns two, tied to an "agronomic requirements" phrase introduced by later commits. A companion PostCsq test added in the same commit series also fails, suggesting a subsequent commit regressed the crop-knowledge variant family that the test was meant to protect.

I'm concluding this blocks the mandated P1-EXIT-09 criterion since it touches CSQ identity and Home/Crop isolation, and the smallest-scope fix is to bisect the three suspect commits — since the PostCsq test explicitly asserts preservation of the variant family, the safer default is treating this as a real regression to restore in code rather than re-baselining the tests, though it still needs owner sign-off since it's outside the B-1 area.

I'm working through the remaining findings: the ClaimAligner failures touch synthesis/validation, which is out of strict scope but may indirectly affect the FAOSTAT answer path, so I'll flag it as an owner decision rather than classify it definitively. The MultiAnswer and CanonicalPropertyIdentity test issues aren't blocking the mandated areas, and the 52 shared Feature failures can't be triaged without an actual run.

The key cross-cutting question I need to resolve is which tree counts as the P1-EXIT-09 baseline — HEAD or the working tree with its 17 uncommitted tracked changes — since that determines how the exit matrix gets filled in, with section 09 marked NOT RUN due to known committed failures blocking it.

I'm reviewing the §8.33.9 corrected text against current hashes to confirm it's still accurate, noting a few wording fixes needed: adding the 20:33 prompt reference, relabeling prompts as "[OWNER AUTHORIZATION — conversation record]" instead of "[OWNER DECISION]", and noting the hashes were re-verified unchanged at the end of this campaign.

I'm finalizing the remaining sections — listing external dependencies like runtime, staging, and CI as unverified, prioritizing the action plan, confirming the ignored test count stays at 92, and noting the two temp report files created outside the repo — before writing the full final report as concisely as the complexity allows.
