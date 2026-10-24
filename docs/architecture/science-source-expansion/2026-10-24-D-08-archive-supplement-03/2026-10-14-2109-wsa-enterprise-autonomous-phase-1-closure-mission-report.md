<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15310). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# WSA-Enterprise Phase 1 Closure Mission (9:09 PM): Final Report

## 1. Verdict

**PARTIALLY COMPLETE — BLOCKED.** I could not close Phase 1:

- None of the remaining fixes are authorized; every one falls under Class C.
- No PHP runtime is available, so no tests were run.
- The remaining exit conditions depend on owner decisions or external evidence.

I made no repository edits, staged nothing, and ran no git write commands, Docker, network, database or installs. All proposals below are **NOT APPLIED**.

One correction to my earlier reports affects decision D5: the failing statistical-aligner test and the code it now disagrees with do **not** come from the same commit. The test was last changed in `2cfcd03` (2026-09-17). `0ea1c7e` (2026-09-21) later changed the rule on purpose without updating the test. [EXECUTED] `git log` on both paths, exit 0.

## 2. Baseline

- **Commit and branch:** HEAD `f202531` on `phase-18-m18-ai-marketing-communications`. Remote state is unverified because no network was used.
- **Working tree:** 17 tracked files modified, 1118 untracked, 92 ignored (matching mode), 0 staged, 0 stashes, 8 worktrees.
- **Hash manifest:** `%TEMP%\baseline-2109.txt` holds 40 SHA-256 entries, covering the 17 modified files and the protected files.
- **B-1 files:** all five keep the hashes recorded at 21:09.

## 3. Work performed

- **A and I (baseline and audit):** manifest written and re-checked; no changes.
- **B (B-1 contract):** all ten checks done statically, plus the forbidden-token check.
- **C (investigation):** C1–C7 investigated statically.
- **D (test readiness):** checked; tests NOT RUN; preflight checklist below.
- **E (ADR and owner packet):** §8.33.9 text drafted; 15 decisions prepared.
- **F (deployment):** script, workflow and documentation proposal prepared.
- **G (exit matrix):** completed.
- **H (authorized fixes):** none exist, so nothing was implemented.

## 4. B-1

All ten contract checks pass static inspection [STATIC]:

- **Capability mapping:** `ProjectionFacetConstraintState.php:30–35` maps VERIFIED+CURRENT to CONSTRAINABLE and every other state to UNKNOWN or NOT_CONSTRAINABLE.
- **Re-resolution:** `ProjectionFacetConstraint::fromCapabilityDecision` (lines 59–109) resolves each facet again from the Capability decision.
- **Consistency check:** `ProjectionQueryConstraints::create()` (lines 78–90) rejects any mismatch.
- **Exact keys:** facet keys match exactly. `'7'` and `'07'` stay distinct.

Forbidden-token scan of the four B-1 sources [EXECUTED] `Select-String`, exit 0:

- None of `DB::`, `->save(`, `->persist(`, `->upsert(`, `Http::`, `curl_`, `strtolower(` or `mb_strtolower(` appears.
- No Persistence, Repository, Illuminate or `CapabilityRequirementEvaluator` imports.

Two outcomes:

- No defect was found, so I added no test.
- One observation: the non-strict outcome scan coerces int `7` to `'7'` (lines 68–71). The contract does not forbid this.

Current state of the five files:
- The test file has 17 methods (25 cases). ADR §8.33.8 records 15 methods (23 cases).
- Four hashes have changed since §8.33.8. The edits were made under the 19:54, 20:05 and 20:26 authorizations.

## 5. Regression matrix

| Finding | Status | Evidence |
|---|---|---|
| FAOSTAT production question first requests yield code 2413 | Confirmed [STATIC] | See C1 below |
| How `yield` enters the question text | [INFERRED]; needs a runtime trace | QUS `:242`, `:540–543`, `:1620` |
| Selector test vs `considerFaostatWhenEnabled` | Confirmed contract conflict [STATIC] | `CapabilityDrivenSourceSelector:150–176` vs `RequiredCapabilityAndSourceRoleTest:119/129` |
| CSQ crop-profile variants | Confirmed failure [HISTORICAL]; introducing commit unknown | See C3 |
| Aligner: bare `quantity` | Confirmed, across commits [STATIC] | Test `:33` (`2cfcd03`) vs `FaoStatQclElementSemantics.php:205–206` (`0ea1c7e`) |
| Aligner: Arabic `إنتاجية` ("productivity") | Confirmed code defect [STATIC] | Matches both yield (`:201`) and production (`:216`); `measureFamily:47–49` returns `''` |
| Aligner: year only | Confirmed fixture/contract question [STATIC] | Fixture topic `'production'` (`Test:195`) feeds `detectRequestedMeasures:136–147` |
| MultiAnswer (16 cases) | Confirmed, red since `acb838b` [STATIC] | Serializer `:32–34`, `:148`; fixture `Test:222–231` has no target or process |
| Membership register | Confirmed environmental [EXECUTED] | See C6 |
| Matcher test (4 cases fail only at HEAD) | Fixed by the user's uncommitted test edit (+87/−1) | Preserved, untouched |
| 61 shared Feature cases (52 methods) | Committed failures [HISTORICAL] | Logs `eq-head/eq-wt-Feature.log` |
| 11 Feature methods failing only in the working tree | 10 are tests that do not exist at HEAD; 1 is SemanticScholar, possibly the FAOSTAT flag [INFERRED] | Same logs |

**C1 (FAOSTAT measure chain)** [STATIC]:

1. The catalog's `operatorFirst` and `nounThenOperator` patterns return property surface `yield` (`AgriculturalEntityCatalog:3634`, `:3659`; introduced in `f6085e4`).
2. QUS writes that into `requested_property_surface` (line 501). If the topic is generic, QUS also replaces the topic with it (lines 540–543).
3. `detectRequestedMeasures` (lines 136–142) builds its "user wording" text from the question plus `topic`, `requestedInformation` and `researchIntent`.
4. That text yields two measures: yield and production.
5. Because area, item and year are all present, the resolver splits them into one query per measure (`FaoStatSearchOptionsResolver:100–115`). The first query is 2413 (yield).
6. The multi-query summary leaves the element unset (lines 35–36), which matches the recorded Italy `element => null` failure.

**C3 (crop-profile variants)** [STATIC/HISTORICAL]:

- `EntitySpecificityPreservationContractTest:60–66` (from `1f91107`) expects five variants.
- `PostCsqSemanticPreservationTest:276` (from `f6085e4`) expects no independent question roles.
- Both fail: the recorded output is two "Ipomoea batatas sweet potato farming agronomic requirements" variants.
- `csqHasIndependentQuestionRoles` (`RetrievalSpecification:729–740`) counts any non-empty target, process or property surface. Its docblock says intent must not count.
- `9411f70` only binds the entity (QUS `:790`, `:847`). It does not populate target, process or property.
- Which slot holds the "farming needs" surface needs a runtime trace.

**C6 (membership register)** [EXECUTED] `git config`, `git ls-files --eol`, byte counts; exit 0:

- `core.autocrlf=true`, and no `.gitattributes` rule covers the register.
- The working-tree copy is LF only (859 LF, 0 CR).
- The HEAD snapshot (`%TEMP%\wsa-head-snap`) has 859 CR bytes, so the byte-equality check at line 210 fails.
- In the working-tree run, `docs/` was missing in the container, so the test failed at line 263 ("not found").

## 6. Test execution

**NOT RUN** for all suites: there is no PHP on the host, `backend/vendor` is empty, and container execution is not authorized. Reported failure counts come from earlier logs in `%TEMP%\wsa-tests` [HISTORICAL].

**Preflight checklist for a future authorized container run:**
- Pin the image digest; record the PHP and PHPUnit versions.
- `--network none`, with a read-only repository mount and no writable volumes except a tmpfs for `storage/` and the cache.
- No reachable database; only the forced SQLite `:memory:` from `tests/bootstrap.php`.
- No migrations against any real database.
- `docs/` mounted read-only, which the register and Stage10 tests need.
- `FAOSTAT_ENABLED` set explicitly for both runs.
- Command allowlist: `vendor/bin/phpunit` with `--filter` or `--testsuite` only.
- Record the 40 manifest hashes before and after the run.
- Vendor provenance must be decided first: installing dependencies counts as a separate download authorization.

## 7. Exit matrix

| Exit | Status | Basis |
|---|---|---|
| P1-EXIT-01 | BLOCKED BY OWNER DECISION | (c) NOT CLOSED (§8.33.6, line 5754); (f) still subject to staging |
| P1-EXIT-02 | BLOCKED BY OWNER DECISION | Gate A covers only the IC and B-1 scopes |
| P1-EXIT-03 | BLOCKED BY OWNER DECISION | Gate B covers only the IC and B-1 scopes |
| P1-EXIT-04 | PASS WITH DOCUMENTED LIMITATION | Isolated PostgreSQL: 121 OK on 2026-10-14 [HISTORICAL] (line 5455) |
| P1-EXIT-05 | BLOCKED BY EXTERNAL DEPENDENCY | No staging evidence; no operator record from the §8.25.7 diagnostic |
| P1-EXIT-06 | BLOCKED BY OWNER DECISION | Not authorized |
| P1-EXIT-07 | BLOCKED BY EXTERNAL DEPENDENCY | 109 seats UNCLASSIFIED; 0 identities verified |
| P1-EXIT-08 | BLOCKED BY OWNER DECISION | Not authorized |
| P1-EXIT-09 | NOT RUN | B-1 suite not executed; regression baseline not green |
| P1-EXIT-10 | BLOCKED BY OWNER DECISION | Depends on P1-EXIT-06 to 08 (line 5617) |
| P1-EXIT-11 | NOT RUN | — |

## 8. Gate A/B inventory

Only four gate records exist: GA-IC-01, GB-IC-01, GA-B1-01 and GB-B1-01.

- GB-B1-01 excludes CSQ/QUS, the builder, the selector, the registry, adapters, `AnswerComposer`, Capability writes, environment and FAOSTAT settings, `phpunit.xml` and `composer.lock`.
- So none of the C-package fixes are covered by any gate.

## 9. ADR-023 §8.33.9 proposal (NOT APPLIED)

The text would be appended after line 5853, keeping CRLF:

```markdown
### 8.33.9 - B-1 revisions after §8.33.8 and current verification

**Status:** EXECUTION RECORD. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

| Item | Record |
|---|---|
| Source | [OWNER AUTHORIZATION — conversation record] prompts of 2026-10-14 at 19:54, 20:05, and 20:26 UTC+3 |
| Scope | Step B-1 only, within GB-B1-01 (§8.33.4) |
| Verified | 2026-10-14 21:24 UTC+3, SHA-256 of working-tree files |

| File | SHA-256 |
|---|---|
| `./Projection/ProjectionFacetConstraintState.php` | `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` (unchanged) |
| `./Projection/ProjectionFacetConstraint.php` | `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f` |
| `./Projection/ProjectionQueryConstraints.php` | `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998` |
| `./Projection/ProjectionQueryConstraintsFactory.php` | `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f` |
| `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` | `651d6d5068c2c1ff084381b8a94d364271e9b00034f09c5ea675b69ffa379726` |

These supersede the four changed hashes of §8.33.8. The test file now has 17 test methods (25 cases). Static review of the ten B-1 checks found no defect.

**Test status: NOT RUN.** No PHP on the host, no `backend/vendor`; container execution not authorized.

**Still missing for P1-EXIT-01 (c):** condition 1 accepted and committed for T10 and T8; an executed B-1 test run; an explicit owner closure decision (§8.33.6). The regression baseline is still not green (§8.31.5, §8.32.7).
```

## 10. Deployment proposal (NOT APPLIED)

**Current-state risks** [STATIC]:

1. The workflow defaults `image_tag` to the mutable `main` (`deploy-production.yml:9`).
2. The workflow runs `git reset --hard origin/main` on the production host (lines 34–35). This discards any changes on the host, and the deploy scripts and compose files always come from `main` whatever image is deployed.
3. The script migrates only after the full stack is up (`scripts/deploy-production.sh:44–48`). There is no hold point for the single-ACTIVE diagnostic and no check that the index was created.
4. Rollback leaves migrations in place (`rollback-production.sh:78`). The unique index would therefore stay under the old image, so old code that tried to write a second ACTIVE record would fail rather than corrupt data [INFERRED].

**`scripts/deploy-production.sh`**, replacing lines 41–53:

```bash
backend_cid="$("${COMPOSE[@]}" ps -q backend || true)"
ROLLBACK_IMAGE_TAG=""
if [ -n "${backend_cid}" ]; then
  ROLLBACK_IMAGE_TAG="$(docker inspect --format '{{.Config.Image}}' "${backend_cid}" | sed 's/^.*://')"
fi
if ! [[ "${ROLLBACK_IMAGE_TAG}" =~ ^([0-9a-f]{7,40}|sha-[0-9]+)$ ]]; then
  echo "WARNING: running backend tag '${ROLLBACK_IMAGE_TAG:-none}' is not immutable; no rollback target recorded."
  ROLLBACK_IMAGE_TAG=""
fi

echo "Pulling GHCR images (tag: ${IMAGE_TAG})..."
"${COMPOSE[@]}" pull
"${COMPOSE[@]}" up -d --no-build --wait postgres redis

if [ "${IC_ACTIVE_DIAGNOSTIC_ZERO_ROWS:-}" != "confirmed" ]; then
  echo "HOLD: run the single-ACTIVE diagnostic (ADR-023 §8.25.7) and re-run with IC_ACTIVE_DIAGNOSTIC_ZERO_ROWS=confirmed."
  exit 2
fi

echo "Running database migrations (no seed)..."
"${COMPOSE[@]}" run --rm --no-deps backend php artisan migrate --force

index_present="$("${COMPOSE[@]}" exec -T postgres psql -U "${POSTGRES_USER:-wsa}" -d "${POSTGRES_DB:-wsa_enterprise}" -tAc "SELECT 1 FROM pg_indexes WHERE tablename = 'cghia_integration_classification_records' AND indexname = 'cghia_ic_records_single_active_uq';")"
[ "${index_present}" = "1" ] || { echo "ERROR: single-ACTIVE index missing after migrate"; exit 1; }

"${COMPOSE[@]}" up -d --no-build --wait
"${repo_root}/scripts/smoke-production.sh"

echo "Production deploy complete (tag: ${IMAGE_TAG})."
[ -n "${ROLLBACK_IMAGE_TAG}" ] && echo "Rollback: IMAGE_TAG=${ROLLBACK_IMAGE_TAG} ./scripts/rollback-production.sh"
```

**`.github/workflows/deploy-production.yml`:**
- Remove `default: main` and require an immutable tag.
- Add a required `ic_active_diagnostic_zero_rows` choice input (`not-confirmed`/`confirmed`) and pass it through `envs`.
- In the script block:
  - validate the tag with the regex from `rollback-production.sh:46`;
  - abort if `git status --porcelain --untracked-files=no` is not empty;
  - replace `git reset --hard origin/main` with `git merge --ff-only origin/main`.

**`docs/deploy-production.md`** (lines 66, 100–106, 130): document the immutable tag, the hold point and operator confirmation, migrate-before-start, index verification, and the printed rollback target.

Two parts of this proposal are unverified:
- Whether `run --rm` interacts with `docker-entrypoint.sh` is [INFERRED] and needs a staging validation.
- So is whether `migrate:status --pending` exits non-zero.

## 11. Owner packet (15 decisions)

1. **FAOSTAT measure detection.**
   - **Recommendation:** in `detectRequestedMeasures`, take measures from the question text only first, then from the structured property fields, then from planner-derived fields (topic, requested information, intent) as a last fallback.
   - **Alternatives:** keep as is; fix only through the CSQ change in D2.
   - **Evidence:** lines 117, 145 and 136–142.
   - **Files:** `FaoStatQclElementSemantics.php`; FAOSTAT tests.
   - **Risk:** questions whose measure appears only in the topic now resolve through the fallback.
   - **Unblocks:** the FAOSTAT cases in `ScientificUpstreamRetrievalFixTest`. **Still blocked:** the year-only aligner case (D7).
   - **Proposed tests:** a production question with topic `yield` gives production only; "yield and production" still splits into two queries; the derived topic is used only when the question names no measure.
2. **CSQ "production ≠ yield".**
   - **Recommendation:** make `operatorFirst`/`nounThenOperator` keep `production` as the surface (`AgriculturalEntityCatalog:3615–3664`).
   - **Alternative:** rely on D1 alone.
   - **Risk:** existing yield phrasings.
   - **Unblocks:** a correct `requested_property_surface`. **Still blocked:** needs a CSQ gate.
3. **Selector policy.**
   - **Recommendation:** Option 2, keep source selection capability-driven only, which removes the universal FAOSTAT consideration (`CapabilityDrivenSourceSelector:150–176`).
   - **Alternative:** Option 1, record the universal policy in an ADR and update the test at lines 119/129.
   - **Risk:** with the flag on, FAOSTAT appears less often.
   - **Unblocks:** `RequiredCapabilityAndSourceRoleTest`.
4. **CSQ independent-role check.**
   - **Recommendation:** authorize a runtime trace, then have `hasQuestionSurface` ignore intent surfaces (`AgriculturalEntityCatalog::isIntentRetrievalSurface`).
   - **Before/after:** for "sweet potato farming needs" with option `farming-needs`, two variants become the five-variant snapshot; "sweet potato nitrogen requirements" stays independent in both.
   - **Risk:** the Home path also uses this check, so the Home isolation tests must be run.
   - **Unblocks:** the two crop-variant tests.
5. **Aligner, bare `quantity`.**
   - **Recommendation:** keep the `0ea1c7e` rule and amend `Test:17` (`'quantity'`, `'كمية'`).
   - **Alternative:** restore the `2cfcd03` rule, which would make FAOSTAT pick code 2510 for "quantity".
   - **Note:** this is a contract decision, not a re-baseline.
6. **Aligner, Arabic `إنتاجية`.**
   - **Recommendation:** strip `إنتاجية` before the production check (semantics lines 214–217).
   - **Risk:** minimal.
   - **Unblocks:** test line 85.
7. **Aligner, year only.**
   - **Decision needed:** should a plan topic of `production` count as a measure signal? If not, `detectRequestedMeasures` must stop reading `topic`, which goes further than D1.
8. **Answer identity with absent roles.**
   - **Recommendation:** `roleIdentity` returns `{'mode':'absent'}` for empty roles, keeping version v1 [INFERRED: no existing hash changes, because these inputs previously threw].
   - **Alternative:** add roles to the fixtures. That would leave production code throwing for CSQs with no target.
   - **Unblocks:** 16 cases.
9. **Register line endings and test mount.**
   - **Recommendation:** add `.gitattributes` `docs/architecture/science-source-expansion/*.json text eol=lf`, and mount `docs/` read-only in test containers.
   - **Risk:** none to content.
10. **Container test run.** Authorize the preflight in section 6, including the vendor source.
11. **The 61 committed Feature failures.**
    - **Decision needed:** must they be fixed before exit, or is a documented-limitation disposition acceptable? No re-baselining in either case.
12. **ADR §8.33.9 append.** Authorize the text in section 9.
13. **Deployment patches.** Authorize the script, workflow and documentation changes in section 10.
14. **Staging run and operator diagnostic.**
    - An operator runs the §8.25.7 diagnostic on each environment and records zero rows.
    - Staging validation then follows, using an immutable tag.
    - **Unblocks:** P1-EXIT-05 and 01(f).
15. **P1-EXIT-01(c) closure.**
    - Accept and commit condition 1 for T10 and T8.
    - After D10 produces a green B-1 run, record an explicit closure decision.
    - Optionally approve the §8.28.7 checklist, which is still marked NOT OWNER-APPROVED (line 5094).

## 12. External dependencies

- A PHP/Composer runtime or approved image, with the vendor source decided.
- A staging environment and a production operator.
- Remote and CI state, which is unverified.
- Per-seat evidence for the 109 sources, using public pages only.
- FAOSTAT network access, which remains unauthorized.

## 13. Critical path

1. D10 (authorized container run).
2. B-1 tests run.
3. D15 (close P1-EXIT-01(c)).
4. Gate A/B for the next scopes (P1-EXIT-02/03).
5. P1-EXIT-06 to 08 (registry, seats, adapters).
6. P1-EXIT-10 (matrix).
7. P1-EXIT-11 (Phase 1 verification).

D14 runs in parallel and covers P1-EXIT-05. D1–D9 and D11 cover the regression baseline that P1-EXIT-09 needs.

## 14. Phase 2

**NOT STARTED.** I did no B-2, registry, adapter or activation work.

## 15. Preservation audit

[EXECUTED] at 21:24, `Get-FileHash` and git read commands with `GIT_OPTIONAL_LOCKS=0`, exit 0:

- **Manifest:** all 40 entries unchanged; none missing.
- **B-1 files:** all five hashes unchanged.
- **Git:** HEAD `f202531`; 0 staged, 17 modified, 1118 untracked, 92 ignored, 0 stashes, 8 worktrees — identical to the baseline.
- **ADR-023:** 5853 lines, hash unchanged.
- **User work:** the uncommitted matcher test edit is untouched.

PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED

I'm confirming that §8.33.8's recorded hashes for the four files differ from current because those files were legitimately modified afterward under the proper authorizations, which is exactly what §8.33.9's updated hashes reflect. Now I want to compare this against the earlier 8:48 report's version of §8.33.9 to see how it lines up.

I'm also noting a discrepancy in test counts — the current test file has 17 methods/25 cases versus the ADR's 15/23 — and thinking through the deployment proposal: replacing the destructive `git reset --hard` workflow with a safer detached-HEAD checkout to a pinned immutable tag, with a clean-tree verification check that aborts if anything's uncommitted. I still need to flag the relevant doc lines to update and then move on to writing the final report, keeping tables minimal and the structure organized.

For the owner packet, I'm deciding to use compact per-decision blocks rather than a wide table since there are 15 decisions each needing seven fields. I'm cataloging the Gate A/B inventory items and external dependencies (container runtime, staging environment, IC seat evidence, FAOSTAT credentials), and mapping out the critical path through owner approvals, condition acceptance, and the remaining Phase 1 exit gates — while also catching an earlier misattribution in the report regarding test and code ownership that needs correcting.

Now I'm correcting the commit attribution — the test actually comes from a different commit (2cfcd03, 09-17) than the code change (0ea1c7e, 09-21), which reversed the rule rather than being the same commit as I'd first noted. I'm also confirming the MultiAnswer case count and the shared case-to-method mapping from the logs before moving to write out the patch diffs.

Writing the FAOSTAT patch diff...

I'm checking that normalization doesn't accidentally widen matches, then sketching out test cases: one confirming that a derived topic like 'yield' shouldn't override explicit wording about 'production', and another verifying that wording naming multiple measures (like "yield and production") correctly decomposes into both measures in the right order.

For the Arabic fix, I'm adjusting the production-signal detection so that the word for 'productivity' (إنتاجية) is stripped out first before checking for production-related substrings, to avoid a false match. Now I'm drafting the related diff for the independent question roles check.

I'm walking through concrete before/after examples to verify the fix: sweet-potato farming-needs questions should flip to non-independent, while nitrogen-requirements questions should stay independent since the property surface remains concrete. I'm flagging a risk that the Home page context (no crop binding) might behave differently and needs isolation testing, then moving on to weigh serializer options for how `roleIdentity` should represent empty roles.

For the identity fix, I'm leaning toward option A — treating empty roles as absent without bumping the version, since previously these cases threw errors and never persisted an identity, so there's no risk of hash drift for populated identities. For the capability selector question, I'm weighing whether to codify universal FAOSTAT consideration or restore capability-required-only behavior, and I'm leaning toward the latter since it stays true to the selector's capability-driven architecture, even though it changes runtime behavior.

Now I'm drafting the deployment workflow changes — making the image tag immutable by requiring a full git SHA, adding an operator confirmation step for the diagnostic check, and adding validation logic to the deploy script to enforce the immutable tag format before deployment proceeds.

I'm also working out rollback logic: capturing the currently running backend image tag before deploying, so there's a known target to revert to if the deployment fails, with handling for the case where no backend container exists yet.

After pulling fresh images and bringing up the data services, I'm adding a hold gate that requires confirming the ACTIVE diagnostic check before migrations run, then proceeding to run migrations against the new image.

Once migrations complete, I'm verifying the single-ACTIVE unique index exists in postgres, failing loudly if it's missing, then starting the full stack, running smoke checks, and printing a rollback command using the previously recorded image tag.

I'm also thinking through riskier edge cases: whether the backend entrypoint itself triggers migrations unexpectedly, whether IMAGE_TAG should be forced immutable (rejecting default 'main' unless explicitly overridden), and that rollbacks won't undo the new unique index — meaning any old code path trying to insert a second ACTIVE row would correctly fail closed with a constraint violation. I'm sketching a preflight checklist and an exit code matrix, including a blocked status for decisions the owner hasn't signed off on.

Now I'm assembling the gate inventory and exit status table, then turning to writing up the full report with its required sections and inline evidence labels, followed by a regression matrix covering the various test chains and answer variants.

I'll note the HEAD-only matcher fix clearly as an edit preserved from the user's work, finalize the report in English with the closing line stating Phase 2 hasn't started since Phase 1 exit conditions aren't fully satisfied, and timestamp the verification. Then I'm drafting the ADR section documenting that P1-EXIT-01(c) remains not closed as an execution record.

Still writing ADR content...
