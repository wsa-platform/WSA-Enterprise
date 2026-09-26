# CSQ v1 Architecture Decision — Formal Closure

**Status:** CLOSED
**Date:** 2026-09-27
**Branch:** `phase-18-m18-ai-marketing-communications`
**Official sequence:** P0 / P1 / P2 only. This document does not create Phase 2, 2.1, or 2.2.

---

## Original decision

Canonical Scientific Question v1 is the frozen, language-independent, provider-independent meaning of a scientific agricultural question.

**Freeze point:** end of `QueryUnderstandingService::understand()`, before `ResearchPlanner::planKnowledgeQuery`.

**Implemented flow:**

```
User Question
  → QueryUnderstandingService
  → CanonicalScientificQuestion (semantic authority)
  → AgriculturalKnowledgeQuery (internal attach; public toArray omits CSQ)
  → ResearchPlanner (reads CSQ; does not rewrite roles)
  → RetrievalSpecification = f(CSQ)
  → RetrievalSemanticContract::apply (transport-only on CSQ path)
  → ScientificSearchQueryBuilder (compiles variants from Spec)
```

Meaning is created once. Downstream consumes the frozen graph. Builder does not re-parse the raw question when CSQ is present.

---

## Scope of the decision

P1 (implemented and accepted):

1. CSQ v1 object exists.
2. Populate E/T/P/M/R from existing extractors.
3. Do not collapse TARGET into PROPERTY.
4. Attach CSQ to the query.
5. Planner reads CSQ.
6. Planner does not rewrite CSQ roles.
7. Builder compiles from CSQ / RetrievalSpecification.
8. Late semantic rematerialization is stopped. `apply()` remains on the path as transport; the plan clone is intentional ownership isolation and preserves the same CSQ reference.

P2 from the original §23 table remains **deferred** and is not part of this closure: VariantBudget merge, single stored Gate/Directness label, drop duplicate `understand()` on `/plan`, rename-only cleanup of `requested_property` vs `requested_property_key`.

---

## Implemented semantic model

`CanonicalScientificQuestion` is `final readonly` with:

| Role | Meaning |
|------|---------|
| ENTITY | Verified taxonomy crop or catalog livestock ID, or unresolved surface |
| TARGET | First-class; no invented product ID (Decision 1 Option B) |
| PROCESS | Separate from entity; generic process tokens are not frozen as entity (R13) |
| PROPERTY | Family key + surface + `of_role`; R7 blocks silent yield/production rewrite |
| RELATION | causal / comparative / associative / descriptive / temporal / spatial / none |
| Context, conditions, time, geography, evidence, resolution, crop binding | Present on CSQ |

Comparative operands are first-class on `relation.operands` and are compiled.

---

## Decision Lock reconciliation

### Decision 1 — TARGET identity

TARGET may exist without a canonical ID. Surface + kind + resolution + nullable ID. Milk/cattle: entity `cattle` (`catalog.livestock`, resolved); target unresolved; no invented milk ID; no FAOSTAT item written onto CSQ.

### Decision 2 — DIRECT

Hybrid D. `AnswerComposer::requiresFactualDirectEvidence` remains the DIRECT owner. CSQ `evidenceRequirement.requiresFactualDirect` is `null`. No Composer cache.

### Decision 3 — RSC split

Canonicalization lives at freeze. Scholarly bags and factor interleave live on `RetrievalSpecification`. R7 compile filter strips `yield`/`production` for protected families.

- **#6:** factor interleave/order is derived from CSQ/Spec.
- **#7:** scholarly bags match the approved vocabulary for water (including evapotranspiration), temperature, classification, and quantity.

`apply()` is transport-only on the CSQ path. The clone is retained for plan isolation (`NotSame` plan, `Same` CSQ).

---

## Multilingual verification

Approved causal sentences in Arabic, English, French, and Turkish share relation type, entity identity (`cattle`), process/target presence, and property family `quantity`. Surface strings may differ.

---

## Home / Crop boundary

Home and Crop remain separate `researchContext` values. Crop binding does not overwrite question ENTITY/TARGET.

---

## Protected contracts (unchanged)

| Contract | State |
|----------|--------|
| `FINAL_ANSWER_CONFIDENCE_THRESHOLD` | 0.50 |
| Composer DIRECT ownership | unchanged |
| CSQ DIRECT cache | null |
| Public `AgriculturalKnowledgeQuery::toArray()` | omits CSQ |
| Home/Crop isolation | unchanged |
| FAOSTAT incomplete-filter guard | unchanged; no QCL codes on CSQ |
| Presentation / adapter HTTP shapes | not modified by this decision |

---

## Architectural acceptance result

Dedicated CSQ acceptance suite (established paths under `backend/tests/Unit/Agriculture/Research/` plus `GenericScientificResearchArchitectureTest`):

| Metric | Value |
|--------|--------|
| Tests | 169 / 169 |
| Assertions | 1291 |
| Failures | 0 |
| Errors | 0 |

This is **not** a claim that the full backend suite, FAOSTAT WIP, or Feature WIP is green.

### Not directly exercised by the dedicated suite

- Presentation `primary_answer` path beyond the 0.50 constant
- Live FAOSTAT adapter HTTP shapes
- Catalog/FR/TR extraction beyond the four locked causal sentences
- Composer DIRECT rule-body bit-identity versus HEAD (method ownership and CSQ null **were** tested)

These are not failures of this architecture decision.

---

## Formal statement

The original Canonical Scientific Question v1 architecture decision is **formally CLOSED**.
P2 items listed above remain explicitly deferred and are not opened by this closure.
