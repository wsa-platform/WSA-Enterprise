<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15250). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
# Phase 1 Final Closure Campaign: final report

## 1. Executive verdict

**PARTIALLY COMPLETE — BLOCKED.** I made no repository changes. Under the current authorization no code fix remains: every remaining defect is in FAOSTAT, CSQ, answer identity or deployment, and each needs its own scope approval.

The campaign did close evidence gaps:
- The FAOSTAT regression now has a traced root cause and an introducing commit.
- The two CSQ failures were narrowed to one shared cause.
- A previously unlisted failure (the 109-seat register test) turned out to be environmental in both recorded runs.
- Deployment review found a second deploy path: the GitHub workflow.

**PHP and tests:** PHP is absent and `backend/vendor` is empty, so every PHPUnit suite is NOT RUN.

Evidence labels used below:

| Tag | Meaning |
|---|---|
| [EXECUTED] | Read-only git or PowerShell command run this campaign |
| [STATIC] | Verified by static inspection |
| [HISTORICAL] | Documented historical evidence, not re-run |
| [INFERRED] | Inferred, not yet verified |
| [NOT RUN] | Not run |
| [OWNER] | Blocked by owner decision |
| [EXTERNAL] | Blocked by external dependency |

## 2. Actual baseline [EXECUTED]

**It matches the reported baseline exactly.**
- **Branch and HEAD:** `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`, equal to `origin/phase-18-m18-ai-marketing-communications` (0 ahead, 0 behind).
- **Remote:** not verifiable without network (REMOTE STATE UNVERIFIED).
- **Worktree:** 1135 entries (1118 untracked, 17 tracked-modified, 0 staged), 92 ignored (`--ignored=matching`), 0 stashes, 8 worktrees.
- **The 17 tracked modifications, all pre-existing:**
  - Backend code: `QueryUnderstandingService.php` (+105 lines) and `CanonicalScientificQuestion.php` (7 lines).
  - Backend tests: 8 Feature test files and 2 Unit test files.
  - Docs: `ADR-021`, ADR-023 and `docs/adr/README.md`.
  - Other: `backend/.env.example` and 2 frontend files.
  - No FAOSTAT or answer-identity application code is modified.
- **B-1 files:** untracked, CRLF only, no BOM.

| File | SHA-256 | Last modified |
|---|---|---|
| `ProjectionFacetConstraintState.php` | `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` | 18:56:55 |
| `ProjectionFacetConstraint.php` | `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f` | 19:59:30 |
| `ProjectionQueryConstraints.php` | `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998` | 20:00:12 |
| `ProjectionQueryConstraintsFactory.php` | `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f` | 19:59:54 |
| `ProjectionQueryConstraintsTest.php` | `651d6d5068c2c1ff084381b8a94d364271e9b00034f09c5ea675b69ffa379726` | 20:28:27 |

- **Protected files (prefix of SHA-256), unchanged from start to end:**

| File | Hash prefix |
|---|---|
| `composer.lock` | `d6ee17a7…` |
| `.env` | `16b96f1e…` |
| `phpunit.xml` | `1ec076c5…` |
| ADR-023 | `d3771aa0…` |
| `deploy-production.sh` | `820191ad…` |
| `rollback-production.sh` | `00440452…` |
| `smoke-production.sh` | `d162c706…` |
| `docs/deploy-production.md` | `c773904e…` |
| `.github/workflows/deploy-production.yml` | `4a9da461…` |
| Single-ACTIVE migration | `b9e4fddf…` |

## 3. Work performed

**No repository changes.** The only files written were two read-only extracts in `%TEMP%`, outside the repository.

**Read-only investigations completed this campaign:**
- Rebuilt the HEAD versus working-tree failure classification from the recorded logs.
- Traced the FAOSTAT root cause through the code and its commit history.
- Localized the CSQ failure to one assertion.
- Re-examined the aligner, membership-register and selector failures.
- Re-read all four B-1 sources against the ten contract checks.
- Reviewed the deploy, rollback, smoke and verify scripts, the deploy documentation, the deploy workflow, the compose files and the backend entrypoint.

## 4. B-1 result

**Contract checks [STATIC]: all ten hold.**
1. `create()` re-resolves every constraint from the Capability decision and rejects any mismatch, freshness included (`ProjectionQueryConstraints.php` lines 78–90). Unsupported claims and asserted CURRENT freshness are rejected.
2. The mapping follows the approved contract (`ProjectionFacetConstraintState.php` lines 30–35):
   - VERIFIED becomes CONSTRAINABLE only with CURRENT freshness;
   - STALE or MISSING gives UNKNOWN;
   - UNAVAILABLE and NOT_APPLICABLE give NOT_CONSTRAINABLE.
3. A malformed decision is rejected even with an empty facet list. The decision's outcomes are scanned before any facet filter (`ProjectionFacetConstraint.php` lines 68–71).
4. Facets match only the exact Capability dimension key. There is no mapping layer.
5. Numeric codes keep their identities: `'7'` and `'07'` stay distinct keys, and `'9'` versus `' 9 '` is a rejected duplicate.
6. Error order is the same on both routes: subject alignment, then instance, then duplicate, then consistency.
7. Empty facet lists are valid.
8. Serialization keeps 10 top-level keys and 4 per facet.
9. Nothing outside the B-1 files references the contract (case-sensitive search).

**Observation, not a defect:** `fromCapabilityDecision()` runs without strict types, so a direct caller passing the integer 7 is treated as the string '7'. This creates no identity confusion, and the factory itself rejects non-string codes.

**No new test is justified.**

**Tests: NOT RUN.** Missing prerequisites: PHP 8.2+ and the `vendor` directory with PHPUnit 11.5.50.

**Historical evidence [HISTORICAL]:** the working-tree Unit run at 15:29 had zero failures in Projection, Capability, Path, Identity, Coexistence and Disclosure. That run predates the B-1 files (earliest 18:56), so it covers the adjacent suites only.

**Prepared commands (unchanged).** The exclusions for `CapabilityStorePersistenceTest`, `IdentityBindingPersistenceTest` and `Stage3CghiaCoexistenceBoundaryTest` remain, because `RefreshDatabase` runs migrations.

**Owner questions (C1–C4).** Evidence and recommendation for each:
- **C1. `retrieval_method` source.**
  - Evidence [STATIC]: `ProjectionEnvelope::create()` takes `?ProjectionRetrievalMethod $retrievalMethod = null` (line 64), defaults to UNKNOWN (line 129), and comes from unmodified commit `1a892c4`. `PathDecisionIdentity` has no retrieval field.
  - Recommendation: keep caller input.
  - If approved: only the ADR tag changes.
  - Alternative: a new Path field and contract (Path package, B-2 or later).
- **C2. Visibility of `create()`.**
  - Evidence: ADR §8.33.8 item 5 documents direct calls, and the consistency check makes them safe.
  - Recommendation: keep it public. If approved, no file changes.
- **C3. Facet-code mapping.**
  - Evidence: R-2 (§8.33.8) fixes exact matching.
  - Recommendation: exact match, with any mapping contract in a later scope. If approved, no file changes.
- **C4. `query_constrainable` in the AND set.** DEFERRED TO B-2. No change.

None of these four blocks running the tests.

## 5. Regression matrix (P1-EXIT-09)

Baseline: committed HEAD, with the working tree assessed separately. "Committed" means the failure appears at both HEAD and the working tree.

| Group | Expected → actual | Baseline | Verified cause | Confidence | Blocks | Authorization |
|---|---|---|---|---|---|---|
| **FAOSTAT measure resolution** (7 cases in `FaoStatPhase3AGeneralRemediationTest` and `FaoStatPhase3FixTest`) | Production questions should give element 2510, and the "Atlantis" question INCOMPLETE_FILTERS → null, AMBIGUOUS_MEASURES, the request sent with yield code 2413, empty results | Committed | See below | High [STATIC]; execution [NOT RUN] | 09 (five-source path) | FAOSTAT scope, or CSQ scope |
| **Selector test** (`RequiredCapabilityAndSourceRoleTest:119/129`) | FAOSTAT skipped with `capability_not_required` → FAOSTAT selected (flag on) or reason `disabled` (flag off) | Committed contract conflict; depends on the flag | The test (`b0ad3b6`, 09-10) predates `considerFaostatWhenEnabled` (`CapabilityDrivenSourceSelector` lines 150–176; commits `0ed5d64`/`870cb75`, 09-15/16). The skip-reason order (lines 133–140) gives `disabled` first. The universal policy appears only in code and archived reports, never as an ADR decision | High [STATIC] | 09 (red test, not a runtime defect) | Owner decision on the policy |
| **CSQ crop-profile variants** (`EntitySpecificityPreservationContractTest:60`, `PostCsqSemanticPreservationTest:276`) | Five cultivation/production variants → two "Ipomoea batatas sweet potato farming [agronomic requirements]" variants | Committed | See below | Medium [STATIC]; introducing commit unknown | 09 (CSQ identity, Crop isolation) | CSQ scope |
| **Statistical claim aligner** (3) | `quantity` should map to production quantity → `quantity`; Arabic yield surface → `''`; a bare year creates an alignment | Committed | `measureFamily()` delegates to `detectMeasuresInSurface`, whose documented rule refuses bare "quantity" as production (lines 116 and 205–206). The test and code come from the same commit `0ea1c7e`, so this is a contract conflict within one commit. The other two cases are [INFERRED] | Medium | Likely 09 (FAOSTAT answer validation); owner to confirm scope | Owner contract decision |
| **`MultiAnswerIdentitySerializer` and `ScientificMultiAnswerProducer`** (16) | Errors | Committed | Fixtures from `acb838b` omit the roles `roleIdentity()` requires | [HISTORICAL] static, earlier session | Not a mandated area | Answer-identity contract decision |
| **`CanonicalPropertyIdentityAtMatcherContractTest`** (4) | Fail at HEAD only | Committed failure, fixed by the uncommitted test edit (+88 lines) | Test/contract mismatch at HEAD | High | Not a mandated area | Decide the fate of the working-tree edit |
| **`Adr023109MembershipRegisterContractTest`** (1, new to the list) | Fails in both runs | Environmental | HEAD snapshot had CRLF while the committed file is LF ("Strings contain different line endings"). Working-tree run: "register not found" because `docs/` was not at either candidate path. **No valid run exists** | High | P1-EXIT-10 evidence | Test runtime only |
| **Feature, shared** (61 cases; §8.32.7 counts 52 methods) | — | Committed | Retrieval-fix (13 cases), language (7), Phase 5 accuracy (8), Home multilingual (8, [INFERRED] relevant to Home isolation), accuracy (6), and others; 2 environmental (`Stage10`) | [HISTORICAL] | 09 | Separate scopes after a run |
| **Feature, HEAD only** (18) | — | Fixed by working-tree test edits; `PublicExpensiveComputeProtectionTest` (6, test unmodified) [INFERRED] environmental | — | Medium | — | — |
| **Feature, working tree only** (18 cases, 11 methods) | — | 10 methods are tests that do not exist at HEAD (unfinished work). SemanticScholar (1): the FAOSTAT flag versus the QUS edits; the §8.32.7 [I] attribution is contested | — | Medium | — | A rerun with `FAOSTAT_ENABLED` set explicitly |

**FAOSTAT cause (traced statically):**
1. Commit `f6085e4` (09-27) added production-operator patterns that label "X production" and "production of X" as `property_surface => 'yield'` (`AgriculturalEntityCatalog.php` lines 3634 and 3659).
2. QUS replaces a generic topic with the first property term, `yield` (lines 540–543; logic from `5678f59`).
3. `detectRequestedMeasures` puts `topic`, `requestedInformation` and `researchIntent` into the "user wording wins" text (lines 136–148).
4. `detectMeasuresInSurface` records yield before bare "production" (lines 198–227), giving the order yield, then production. Two measures produce no element, and the decomposed request goes out with 2413 first.

The same chain explains every recorded symptom. Separately, labelling "production" as "yield" is itself a CSQ fidelity problem.

**CSQ cause:** line 276 asserts that `hasIndependentQuestionRoles` is false for the crop-profile query "sweet potato farming needs". The CSQ now reports independent roles for that query, so the builder switches to role-based variants. The PostCsq test was added in `f6085e4`, and later commits `6708856` and `9411f70` changed role extraction.

**Working-tree-only Unit failures:** zero. Every Unit failure exists at HEAD.

**Minimum repair proposals (not implemented):**
- **FAOSTAT.** In `FaoStatQclElementSemantics::detectRequestedMeasures`, detect measures from `originalQuestion` and `normalizedQuestion` only. Fall back to the structured surfaces and then to `topic`, `requestedInformation` and `researchIntent`. This is one function in one file, it matches the function's own comment and docblock, and it does not touch CSQ.
  - Tests: `tests/Unit/Agriculture/Intelligence/FaoStat`, `ScientificStatisticalClaimAlignerTest` (shared caller at line 196), then the Research Unit suite.
  - The root fix would be to stop labelling production as yield (CSQ scope).
- **CSQ.** First a diagnostic that prints the CSQ target, process and property surfaces for this query. Then make the intent of the crop knowledge option not count as an independent role. `RetrievalSpecification` lines 725–727 already say intent does not count, and `CropKnowledgeOptionCatalog::isOptionKey` (added in `6708856`) exists for this purpose. No new keywords or roles would be added.
- **Selector.** If the owner confirms the universal policy, change one test method's expected skip reason. Otherwise, change the selector's behaviour.

## 6. Exit matrix (requirements from ADR-023 §8.28.7, lines 5102–5112)

| Exit | Status | Evidence and provenance | Remaining action and role |
|---|---|---|---|
| 01: decisions (a)–(f) | BLOCKED BY OWNER DECISION | [STATIC]: §8.32.8 line 5453 and §8.33.7 record (a), (b), (d), (e) decided and (f) decided subject to staging. (c): condition 2 is met (owner decision); conditions 1, 3 and 5 are executor assessments (§8.33.6 and §8.33.8 line 5821) | Owner: decisions 1–3 in section 10 |
| 02: Gate A per scope | BLOCKED BY OWNER DECISION | Only GA-IC-01 (§8.30.3) and GA-B1-01 (§8.33.3) | Owner: define scopes (decision 13) |
| 03: Gate B per scope | BLOCKED BY OWNER DECISION | Only GB-IC-01 (§8.30.4) and GB-B1-01 (§8.33.4) | Owner |
| 04: IC on PostgreSQL | PASS WITH DOCUMENTED LIMITATION | [HISTORICAL]: `pgsql.log` "OK (121 tests, 840 assertions)" at 15:40; CI run `38315050442` as recorded (remote unverified) | None; it is not a fresh pass |
| 05: deployment order plus operator check | BLOCKED BY EXTERNAL DEPENDENCY, plus script defects | Section 8 [STATIC] | Owner: decisions 11–12. Operator: §8.25.7 checks |
| 06: Registry and Capability | BLOCKED BY OWNER DECISION | Not authorized | Owner: Gate A/B |
| 07: 109 seats | BLOCKED BY OWNER DECISION | 109 records, all UNCLASSIFIED; 0 identities verified | Owner and evidence owners (decision 14) |
| 08: adapters | BLOCKED BY OWNER DECISION | 0 adapters | Depends on 07 |
| 09: tests and regression | B-1 focused tests: NOT RUN. Regression baseline: FAIL on recorded evidence [HISTORICAL] | Section 5 | Runtime (decision 6), then repairs (decisions 8–10) |
| 10: matrix integrity | BLOCKED BY OWNER DECISION; the register contract test has no valid run | 109 rows in register order [HISTORICAL]; register test environmental | Rerun the test; depends on 06–08 |
| 11: formal disposition | NOT RUN | Depends on 01–10; the checklist itself is "PROPOSED … NOT OWNER-APPROVED" (line 5094) | Owner (decision 15) |

## 7. Gate A/B inventory [STATIC]

| Scope | Gate A | Gate B |
|---|---|---|
| IC inventory | GA-IC-01, §8.30.3 (line 5291) | GB-IC-01, §8.30.4 (line 5312) |
| B-1 constraint contract | GA-B1-01, §8.33.3 (line 5673) | GB-B1-01, §8.33.4 (line 5692); five files plus §8.33; its acceptance requires the eight test groups to pass, which has not happened |
| B-2, Runtime Source Registry, access-family adapters, per-seat IC and identity, deploy-script change, FAOSTAT/CSQ repairs | **None** | **None** |

No approval is inherited. GB-B1-01 explicitly prohibits CSQ, QUS, the query builder, the source selector, FAOSTAT settings and the deployment scripts.

## 8. Deployment and staging readiness

**Verified defects [STATIC]:**
1. **Application starts before the migration.** `deploy-production.sh` line 45 starts the whole new stack (backend, queue, scheduler); the migration follows at line 48. If the migration fails, the new release is already serving.
2. **Migration runs inside the new application.** It uses `exec -T backend` into the new container, not a one-off container.
3. **No hold points.** There is none for the §8.25.7 diagnostic and none for index verification.
4. **Mutable tag.** `IMAGE_TAG` defaults to `main` (line 27), and no rollback target is recorded. `rollback-production.sh` refuses `main` (lines 40–49).
5. **Old order documented.** `docs/deploy-production.md` lines 100–106 describe it, and line 130 says "restore from backup before retry".
6. **New: the GitHub workflow.** `.github/workflows/deploy-production.yml` defaults `image_tag` to `main` (line 9) and runs `git reset --hard origin/main` before the script (lines 35–37). A script fix therefore only reaches production through `main`, and the workflow needs the same changes.

**Verified safe:**
- The backend entrypoint never migrates.
- Rollback runs no migrations.
- `down()` only drops the index.
- The preflight refuses on duplicates and changes no data.
- The backend depends on postgres and redis being healthy, so `run --no-deps` needs both started first.

**Proposed patch for `scripts/deploy-production.sh` (NOT APPLIED; it replaces lines 41–51):**

```bash
current_image="$("${COMPOSE[@]}" ps -q backend | xargs -r docker inspect --format '{{.Config.Image}}' 2>/dev/null || true)"
ROLLBACK_IMAGE_TAG="${ROLLBACK_IMAGE_TAG:-${current_image##*:}}"
if ! [[ "${ROLLBACK_IMAGE_TAG}" =~ ^([0-9a-f]{7,40}|sha-[0-9]+)$ ]]; then
  echo "ERROR: no immutable rollback target (got '${ROLLBACK_IMAGE_TAG}'). Set ROLLBACK_IMAGE_TAG."
  exit 1
fi
echo "Rollback target: ${ROLLBACK_IMAGE_TAG}"

echo "Pulling GHCR images (tag: ${IMAGE_TAG}); the running release is not stopped..."
"${COMPOSE[@]}" pull

echo "Starting database and cache only..."
"${COMPOSE[@]}" up -d --no-build --wait postgres redis

if [ "${IC_ACTIVE_DIAGNOSTIC_ZERO_ROWS:-}" != "confirmed" ]; then
  echo "ERROR: run the ADR-023 §8.25.7 read-only diagnostic first; set IC_ACTIVE_DIAGNOSTIC_ZERO_ROWS=confirmed only if it returned zero rows."
  exit 1
fi

echo "Running database migrations in a one-off container (no seed)..."
"${COMPOSE[@]}" run --rm --no-deps backend php artisan migrate --force

index_present="$("${COMPOSE[@]}" exec -T postgres psql -U "${POSTGRES_USER:-wsa}" -d "${POSTGRES_DB:-wsa_enterprise}" -tAc \
  "SELECT 1 FROM pg_indexes WHERE tablename = 'cghia_integration_classification_records' AND indexname = 'cghia_ic_records_single_active_uq';")"
if [ "${index_present}" != "1" ]; then
  echo "ERROR: cghia_ic_records_single_active_uq is missing; the application was not started."
  exit 1
fi
"${COMPOSE[@]}" run --rm --no-deps backend php artisan migrate:status

echo "Starting production stack..."
"${COMPOSE[@]}" up -d --no-build --wait

echo "Running smoke checks..."
"${repo_root}/scripts/smoke-production.sh"

echo "Production deploy complete (tag: ${IMAGE_TAG}). Rollback: IMAGE_TAG=${ROLLBACK_IMAGE_TAG} ./scripts/rollback-production.sh"
```

Notes on the patch:
- **Pending-migration check [INFERRED]:** whether `migrate:status` should take `--pending` and fail on a non-zero exit depends on the installed Laravel version. Verify before applying.
- **Accompanying changes, also not applied:**
  - `docs/deploy-production.md`: rewrite lines 100–106 in the new order, and make line 130 say a failed preflight changes no data.
  - `deploy-production.yml`: require an immutable `image_tag` and pass `IC_ACTIVE_DIAGNOSTIC_ZERO_ROWS`.
- **Rollback verification (step 9)** is a staging drill, not part of every deploy.

**Evidence still required [EXTERNAL]. None of it exists:**
- On staging (§8.26.5):
  - the §8.25.7 diagnostic returns zero rows;
  - container event timestamps show the index existing before any new application container starts;
  - a duplicate-ACTIVE refusal on a staging copy where this migration has not yet run (once the index exists, a duplicate cannot be seeded);
  - no data changes;
  - rollback restores the previous release;
  - smoke checks pass.
- From the operator: the §8.25.7 check on each real environment.

## 9. ADR-023 proposal (NOT APPLIED)

The record would be appended after line 5853 (end of file). The file ends with CRLF and uses CRLF throughout.

```diff
@@ -5853,0 +5854,25 @@
+
+### 8.33.9 — B-1 hardening after 18:49 (execution record, 2026-10-14)
+
+**Status:** EXECUTION RECORD. B-1: IMPLEMENTED, uncommitted, NOT CLOSED. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.
+
+**Source** [OWNER AUTHORIZATION — conversation record]: the prompts of 19:54, 20:05 and 20:26 UTC+3 authorized focused changes to the five B-1 files whose behaviour follows from existing decisions (M-1, R-1, R-2). The prompts of 20:33 and 20:48 authorized no file change. This tag records authorization of work, not an architecture decision (nearest §8.28 label [P]); the prompts are not archived [M]. No decision of §8.33.1–§8.33.8 is changed.
+
+**Finding** [IMPLEMENTATION FACT]: `ProjectionQueryConstraints::create()` accepted caller-built facet constraints, so a constraint not backed by the Capability decision (for example `VERIFIED` + `CURRENT` without any cell) could become `CONSTRAINABLE`, contrary to M-1 and R-1.
+
+**Changes** [IMPLEMENTATION FACT]: (1) The cell-reading rules and their rejection messages moved from the factory to `ProjectionFacetConstraint::fromCapabilityDecision()`, which normalizes the codes it receives and builds the constraint list. (2) `create()` rejects any facet constraint that differs from the one the decision resolves, freshness included ("Facet constraint [x] does not match the capability decision."), after the instance and duplicate checks; a direct `create()` rejects a malformed decision even with no constraints, as the factory does. (3) No field, key, mapping, serialization or visibility changed.
+
+**Static review** [IMPLEMENTATION FACT, inspection only]: exact code matching; numeric codes kept distinct (`'7'` ≠ `'07'`); no caller outside the B-1 files; imports only Projection → Capability/Path/Identity.
+
+**Files** (CRLF, no BOM, untracked): `ProjectionFacetConstraintState.php` `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` (unchanged since §8.33.8); `ProjectionFacetConstraint.php` `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f`; `ProjectionQueryConstraints.php` `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998`; `ProjectionQueryConstraintsFactory.php` `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f`; `ProjectionQueryConstraintsTest.php` `651d6d5068c2c1ff084381b8a94d364271e9b00034f09c5ea675b69ffa379726`. These supersede the hashes of §8.33.8. Re-verified on 2026-10-14 at 21:02:42 UTC+3; the newest B-1 modification time is 20:28:27 (the test file), and no B-1 file changed after it.
+
+**Tests:** 17 test methods, 25 cases. New: `test_direct_create_accepts_only_constraints_the_capability_decision_resolves`, `test_numeric_codes_keep_distinct_identities_and_empty_lists_are_valid`. **Test status: NOT RUN** (no PHP on the host, `backend/vendor` empty, Docker not authorized). No syntax check was possible. **No test is claimed to pass.**
+
+**Still missing:** execution of B-1 and the adjacent Capability, Path, Identity, Coexistence and Disclosure suites; a green P1-EXIT-09 baseline (the recorded runs of §8.31.5 show committed failures in FAOSTAT measure resolution and CSQ crop-profile variants, analysed in the campaign report of 2026-10-14 20:48 [M]; confirmation by execution NOT RUN); staging and operator evidence for P1-EXIT-05.
+
+**Unresolved** [UNRESOLVED]: `retrieval_method` source; visibility of `create()`; facet-code correspondence beyond exact match; `query_constrainable` in the required set (B-2).
```

## 10. Owner-decision packet (prioritized)

| # | Exact question | Recommended (alternatives) | Evidence | Files or systems; risk | Unblocks / still blocked |
|---|---|---|---|---|---|
| 6 | Authorize one isolated run (`--network none`, read-only mounts, existing vendor volume, LF checkout, `docs/` mounted, `FAOSTAT_ENABLED` set explicitly) of the B-1 commands, the FAOSTAT and CSQ diagnostics, and the register test? | Yes (host PHP, which means installs; keep NOT RUN) | §8.31.5 precedent | Docker locally, no database writes; low risk | Unblocks: B-1 evidence and root-cause confirmation. Still blocked: repairs |
| 7 | Is committed HEAD the P1-EXIT-09 baseline, with the 17 modifications assessed separately? | Yes (the working tree) | Section 5: zero working-tree-only Unit failures | None | Unblocks: classification. Still blocked: — |
| 4 | Apply §8.33.9 (section 9) to ADR-023? | Yes (keep the ADR stale) | Section 2 hashes | ADR-023, append-only | Unblocks: accurate record. Still blocked: closure |
| 5 | Confirm caller input for `retrieval_method`, keep `create()` public, exact facet match, and the AND set deferred to B-2? | Yes ×4 (section 4) | Section 4 | ADR tags only | Unblocks: removes 3 of the 4 [UNRESOLVED] tags in §8.33.8 (the AND set stays deferred to B-2). Still blocked: B-2 |
| 2 | Accept `projection_query_constraints.v1` (§8.33.2) as the condition-3 constraint metadata? | Yes (define different metadata) | §8.33.6: the assessment is the executor's | ADR | Unblocks: condition 3. Still blocked: (c) |
| 1 | Accept the §8.32.3 definitions of T10 ("Cap-gated multi-layer primary path selection among P01–P17") and T8 ("L1→L2 EXTERNAL→L3 P18 fallback layers; non-upgrade"), and confirm that §8.33.1's boundary replaces T2's "Projection owns query"? | Accept (amend; supply another definition) | §8.32.3; §8.33.8 line 5811 | ADR | Unblocks: condition 1. Still blocked: — |
| 3 | After 1 and 2, record (c) as decided within a Gate A record? | Yes | §8.28.6 condition 5 | ADR | Unblocks: P1-EXIT-01. Still blocked: 02 and 03 for other scopes |
| 8 | Authorize the FAOSTAT repair (question wording first) after the diagnostic? | Yes (CSQ "production ≠ yield" fix instead or as well) | Section 5 cause chain | `FaoStatQclElementSemantics.php` plus FAOSTAT tests; low risk | Unblocks: 7 failures, maybe the aligner. Still blocked: — |
| 9 | Is universal FAOSTAT consideration authoritative? | Yes; update one test expectation (alternative: capability-gated selection) | Selector lines 133–176; commits | One test, or the selector | Unblocks: 1 Unit failure and probably 1 Feature failure. Still blocked: — |
| 10 | Authorize restoring the crop-profile variant family by not counting knowledge-option intent as a role? | Yes, after the diagnostic (re-baseline via a CSQ decision) | `RetrievalSpecification` lines 725–727; `isOptionKey` | QUS or `RetrievalSpecification`; CSQ risk | Unblocks: 2 failures. Still blocked: — |
| 11 | Authorize the section 8 changes to the script, docs and workflow? | Yes (keep the old order) | Section 8 | Deploy settings and CI; reaches production only via `main` | Unblocks: staging. Still blocked: evidence |
| 12 | Authorize staging, including the duplicate drill on a copy where the migration has not run? | Yes, after 11 | §8.26.5 | Staging data; medium risk | Unblocks: P1-EXIT-05. Still blocked: operator checks |
| 13 | Define Gate A/B for B-2, Registry, adapters and per-seat IC? | B-2 first | Section 7 | ADR | Unblocks: 02, 03, 06, 08. Still blocked: evidence |
| 14 | Assign an owner for identity evidence on each of the 109 seats? | Yes | 0 verified | Documents | Unblocks: 07 and 10. Still blocked: — |
| 15 | Confirm or amend the §8.28.7 checklist ("PROPOSED … NOT OWNER-APPROVED")? | Confirm | Line 5094 | ADR | Unblocks: 11. Still blocked: — |

**Can be answered together in one reply:** decisions 6, 7, 4, 5, 2 and 15. They are independent of each other.

**Need the diagnostic first:** decisions 8, 9 and 10.

## 11. Preservation audit (21:02:42) [EXECUTED]

- **Git state identical to the start:**
  - branch and HEAD unchanged, equal to upstream;
  - 1135 entries (1118 untracked, 17 modified, 0 staged);
  - 92 ignored, 0 stashes, 8 worktrees.
- **Hashes:** all 17 tracked-modified hashes, all five B-1 hashes and all protected-file hashes are unchanged.
- **Prohibited actions, none performed:**
  - no git writes, branch changes or cleanup;
  - no network, installs, Docker or database access;
  - no source activation, deployment or test execution;
  - no ADR edit.
- **Records:** every gate record cited above exists at the line given.
- **Phase 2:** not started.

## 12. Critical path to closure

1. **Decisions 6 and 7.** Then the isolated run gives B-1 executed evidence, confirms the FAOSTAT and CSQ causes, and produces a valid register-test result.
2. **Decisions 4, 5 and 2, then 1, then 3.** This closes P1-EXIT-01(c).
3. **Decisions 8, 9 and 10, implemented, then a full Unit and Feature rerun.** P1-EXIT-09 can pass only if the remaining 61 shared Feature cases are triaged and repaired under their own scopes.
4. **Decision 11, then 12, then the operator checks.** This closes P1-EXIT-05.
5. **Decisions 13 and 14, then P1-EXIT-06, 07, 08 and 10.** This is the longest pole: seat-by-seat evidence.
6. **Decision 15, then the formal P1-EXIT-11 report and decision.**

Steps 2, 4 and 5 can proceed in parallel with step 1.

## 13. Phase 2

`PHASE 2 NOT STARTED — PHASE 1 EXIT CONDITIONS NOT FULLY SATISFIED`

The defaults for the env vars look fine. I'm checking how to capture the currently running image tag—if it's a mutable tag like "main", I need to resolve it to the actual digest since the rollback script requires a SHA reference rather than a floating tag.

Now I'm thinking through the rollback logic: refuse to deploy unless the currently serving tag is immutable, or else record the digest and require the operator to supply a commit SHA. I'm proposing that deploys require an immutable IMAGE_TAG going forward (rejecting "main" via the same regex used in rollback), and drafting a script that extracts the current backend image tag, falls back to an operator-provided ROLLBACK_IMAGE_TAG, and validates it against a pattern matching either a commit hash or a "sha-" prefixed identifier before failing with an error if it doesn't match.

After validating the rollback target, the script pulls images without touching running containers, brings up only postgres and redis, then adds an operator hold point requiring confirmation that the §8.25.7 diagnostic returned zero rows before proceeding. It then runs a one-off migration, verifies the unique index exists on the classification records table, and begins another one-off backend command to continue verification.

Step 9's rollback verification is really a staging drill, not something done on every deploy, so I should just print the rollback command. I also need to handle the first-deploy case where the table doesn't exist yet, update the docs for the deploy steps and troubleshooting sections, and note that the GitHub Actions workflow resets hard to origin/main, meaning the patch only applies there and needs separate CI authorization. Then I'm drafting the ADR text to insert into the file.

I'm working through the §8.33.9 content carefully — tracing which owner prompts (19:54, 20:05, 20:26 UTC+3) authorized the B-1 file changes versus the later 20:33/20:48 prompts that authorized nothing, since this record needs to be append-only and accurately reflect what was actually approved.

Now I'm compiling the status section: hashes re-verified at 21:02:42, confirming no B-1 file changed after the test file's 20:28:27 timestamp, with tests still showing NOT RUN and the earlier regression failures in FAOSTAT measure resolution and CSQ crop-profile variants still unconfirmed by execution. I'm settling on the final status line — B-1 IMPLEMENTED but uncommitted and NOT CLOSED, Phase 1 BLOCKED, Phase 2 NOT STARTED — and aligning my tagging scheme with the ADR's existing conventions before moving into the owner decision packet with its fifteen open questions and recommendations.

For the decision table, I'm weighing an 8-column format against readability — too many columns per decision makes it unreadable, so I'm compressing to #, Question, Recommended option with alternatives, Evidence, Files/systems and risk, and Unblocks/still-blocked status. I want to flag which decisions can be approved together in one reply since they're independent and low-risk (items like 2, 4, 5a-d, 6, 7, 15), versus ones that need to wait on diagnostics or depend on earlier choices, while being careful not to bundle unrelated approvals into one ambiguous yes/no as instructed earlier. I'm also noting that decision 2 overlaps with a boundary/mapping choice already settled in an earlier section, so I need to scope it down to just the remaining field-level details.

I'm refining the exact wording for decision 1 to properly reference the tracked definitions from the two transcribed terms and confirm how the Builder/Projection boundary supersedes the older query-construction assumption. For the test-failure item tied to Home multilingual semantics, I'll mark it as inferred rather than confirmed since I can't verify the exact isolation criterion. I'm also setting up a labeling legend — EXECUTED, STATIC, HISTORICAL, INFERRED, NOT RUN, OWNER, EXTERNAL — to tag each claim by its evidence source, with EXECUTED reserved strictly for read-only commands I actually ran, before writing out the full report.
