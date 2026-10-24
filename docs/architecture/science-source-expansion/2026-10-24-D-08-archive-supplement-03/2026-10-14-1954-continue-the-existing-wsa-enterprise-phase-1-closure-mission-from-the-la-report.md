<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15131). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
I fixed one real B-1 defect, added a test for it, and changed nothing else. **No test was executed.** PHP is absent, `backend/vendor` is empty and Docker is prohibited, so everything below is verified by inspection only. P1-EXIT-01(c) remains NOT CLOSED, and Phase 1 remains BLOCKED.

## The change

**Problem.** The public `ProjectionQueryConstraints::create()` accepted any facet constraint the caller built. A caller could pass `ProjectionFacetConstraint::of('species', VERIFIED, CURRENT)` with a Capability decision holding no such cell and get `CONSTRAINABLE`. That breaks two owner decisions:
- **M-1:** freshness that cannot be established from the actual contract counts as not established.
- **R-1:** constraints must use the resolved Capability decision results.

**Fix.** Four of the five B-1 files changed (CRLF, no BOM, still untracked). The fifth, `ProjectionFacetConstraintState.php`, is unchanged.

| File | Change | Requirement it serves |
|---|---|---|
| `ProjectionFacetConstraint.php` | Added `fromCapabilityDecision()`. It holds the decision-cell reading moved verbatim from the factory, with the same rules and error messages | R-1, R-2 (exact codes, no case folding) |
| `ProjectionQueryConstraintsFactory.php` | Code checks unchanged; it now delegates to `fromCapabilityDecision()`. Its docblock no longer says "sole producer", which was inaccurate while `create()` is public | R-1 |
| `ProjectionQueryConstraints.php` | `create()` rejects any facet constraint that differs from what the decision resolves, freshness included. Message: "Facet constraint [x] does not match the capability decision." The check runs after the existing instance and duplicate checks, so those messages are unchanged | M-1, R-1 |
| `ProjectionQueryConstraintsTest.php` | New test `test_direct_create_accepts_only_constraints_the_capability_decision_resolves` (now 16 test methods) | M-1, R-1 |

The new test covers four rejections and one acceptance:
- VERIFIED+CURRENT claimed with no Capability cell is rejected.
- CURRENT claimed over a STALE cell is rejected.
- A freshness-only mismatch with identical serialized output is rejected.
- MISSING claimed over a VERIFIED+CURRENT cell is rejected.
- A constraint the decision does back is accepted, and its output equals the factory's.

No field, key, serialization or mapping changed, and no Capability contract changed. The M-1 Option A mapping is unchanged.

## Evidence, by type

- **Verified by inspection:**
  - Every API the B-1 code and test call exists with the expected signature and default. This covers the per-cell outcome shape from `CapabilityRequirementEvaluator`, `CapabilityRecord::create()` (freshness defaults to UNKNOWN), `bindFromCapabilityDecision`, the value-object messages, and the envelope parameters.
  - I traced every existing test that calls `create()` directly: each still holds under the new check.
  - Mechanical checks: CRLF preserved, brackets balanced, no runtime class references the contract.
- **Executed tests:** none. The required run is `phpunit tests/Unit/Agriculture/Research/Projection`, plus the Capability, Path, Identity, Coexistence and Disclosure unit suites.

## Reported failures

None can be resolved within B-1. No logged Unit or Feature failure is in Projection, Capability or Path. The one Capability-named test, `RequiredCapabilityAndSourceRoleTest`, is the `Intelligence` provider selector on the FAOSTAT path. The 16 serializer errors (synthesis) and the FAOSTAT, QUS/CSQ and matcher failures are outside this assignment and were not touched.

## Open questions checked, not resolved

No authoritative decision answers these, so they stay open:
- **`retrieval_method`:** the resolution package (a proposal) names Path as the source, but `PathDecisionIdentity` has no such field. Caller input is the same pattern the approved `ProjectionEnvelope::create()` uses.
- **Projection vs Capability facet-code correspondence:** Capability codes are opaque and only trimmed, so only exact matches count. Behaviour stays fail-closed.
- **Whether `query_constrainable` belongs in the required (AND) set,** and how facet codes are extracted from CSQ: both belong to B-2.
- **Whether to restrict the visibility of `create()`:** I did not change visibility. It is now harmless, because content must match the decision.

## Remaining blockers

- **Owner decisions:**
  - closing P1-EXIT-01(c), which needs condition 1 (tracked T10/T8 definitions) and confirmation of the executor assessments for conditions 3 and 5;
  - the four questions above;
  - authorizing the ADR record below.
- **Environment:** a permitted PHP/PHPUnit runtime to execute the B-1 suite.
- **Evidence gap:** the file-hash table in ADR §8.33.8 is now stale for the four changed files.
- **Not started:** B-2, Registry integration, adapters and source activation. Gate A and Gate B remain B-1-only.

## Proposed ADR record (not applied)

```markdown
### 8.33.9 — Decision-consistency check of `create()` (executor correction, 2026-10-14 19:54)

**Status:** EXECUTION RECORD. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Finding** [IMPLEMENTATION FACT]: `ProjectionQueryConstraints::create()` accepted caller-built facet constraints, so a constraint not backed by the Capability decision (for example `VERIFIED` + `CURRENT` without any cell) could become `CONSTRAINABLE`, contrary to M-1 and R-1 (§8.33.8) [OWNER DECISION].

**Change** [IMPLEMENTATION FACT]: The cell reading moved unchanged from the factory to `ProjectionFacetConstraint::fromCapabilityDecision()`. `create()` rejects any facet constraint that differs from the one the decision resolves, freshness included ("Facet constraint [x] does not match the capability decision."). No field, key, mapping, or serialization changed. The visibility of `create()` is unchanged; restricting it remains [UNRESOLVED].

**Files** (CRLF, no BOM, untracked) [IMPLEMENTATION FACT]:

| File | SHA-256 |
|---|---|
| `./Projection/ProjectionFacetConstraintState.php` | `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` (unchanged) |
| `./Projection/ProjectionFacetConstraint.php` | `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f` |
| `./Projection/ProjectionQueryConstraints.php` | `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998` |
| `./Projection/ProjectionQueryConstraintsFactory.php` | `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f` |
| `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` | `5e8e9f75c1ed904de1a857fac1c12406343776971d86e4b1ac95e6f4f395fc6a` |

**Tests:** 16 test methods; new: `test_direct_create_accepts_only_constraints_the_capability_decision_resolves`. **Test status: NOT RUN** (no PHP, empty `backend/vendor`, Docker prohibited). **No test is claimed to pass.**
```

## Preservation

- **Git:** branch and HEAD `f2025315` unchanged; 1135 entries, 0 staged, 0 stashes. No git write command was run.
- **Protected files:** `composer.lock`, `.env`, `phpunit.xml` and ADR-023 hashes unchanged.
- **Backups:** the pre-edit copies of the four files are in `%TEMP%\wsa-b1-backup-20261014-1954`.
- **Prohibited actions:** no network access, Docker, database access or package installs.

I've stopped here for your review. Next steps for you to decide: authorize the ADR §8.33.9 record, permit a PHP test run for the B-1 suite, and rule on the open B-1 questions.

Now I'm drafting the report itself, including the proposed ADR §8.33.9 record text formatted in the ADR's markdown style, noting that it's proposed but not yet applied. I need to flag that the §8.33.8 "Files after correction" hash table is now stale for four files since this new record supersedes it, and I'll list the remaining blockers.
