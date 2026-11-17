# Rev 11.0.10 — Governed Source Relevance Routing (Substantial Design)

## Problem
`ScientificSourceSelector` previously selected every enabled `GOVERNED_EXPANSION` seat for every internet-first plan. That violated Rev 11.0.10 §16 (relevance-based routing) and polluted retrieval with scientifically inappropriate sources.

## Alternatives considered
1. **Keyword-only hard filters in the orchestrator** — rejected: bypasses selector contract and duplicates policy.
2. **Capability-declaration domain arrays in the membership register** — rejected for this mission: would risk membership-authority churn; out of scope.
3. **Plan-cue eligibility service wired into the selector (chosen)** — preserves Stage-3 defaults, keeps capability authorization separate, fail-open for unknown governed keys.

## Selected architecture
- New `GovernedScientificSourceEligibility` evaluates AGRIS / Organic Eprints / Dr. Duke / GBIF / BHL against `KnowledgeQueryPlan` cues (question, domain, intent, scientific name, topics).
- `ScientificSourceSelector` applies the gate only to `GOVERNED_EXPANSION` after capability authorization.
- `selectionTrace` exposes `relevance_excluded` with reason codes.
- Forced orchestrator source lists (tests / explicit callers) remain unchanged.

## Compatibility
- Stage-3 internet-first defaults and FAOSTAT consideration unchanged.
- Membership register and 110-source matrix untouched.
- No alternative production path.

## Tests
- `GovernedScientificSourceEligibilityTest`
- Updated `GovernedSourceRuntimeActivationContractTest`
- Updated `ScientificSourceCatalogContractTest::test_selector_follows_catalog_roles_and_flags`

## EXIT mapping
- Source routing → EXIT-08 (PARTIAL; relevance now evidenced, formal closure not claimed)
