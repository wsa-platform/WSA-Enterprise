# Registered Source Inventory — Gate 1

**Authoritative runtime registry:** `ScientificSourceCatalog::productionRegistrations()`  
**Evidence dump:** `E-R2-catalog-dump.json`  
**Runtime count:** **10** keys

Membership (ADR-023 CURRENT 110) ≠ runtime catalog. Next-gate seats: **17**. Near-ready seats: **2** (`organic_eprints`, `bhl` already activated into runtime).

## Runtime catalog (authoritative for Stage-3)

| source_key | Adapter | Lifecycle | Selection role | Execution group | Access | Enabled | ADR |
|---|---|---|---|---|---|---|---|
| openalex | OpenAlexScientificSourceAdapter | legacy_stage3 | internet_first_default | independent_scholarly | rest_api | yes | — |
| crossref | CrossRefScientificSourceAdapter | legacy_stage3 | internet_first_default | independent_scholarly | rest_api | yes | — |
| semantic_scholar | SemanticScholarScientificSourceAdapter | legacy_stage3 | internet_first_default | independent_scholarly | rest_api | yes | — |
| consensus | ConsensusScientificSourceAdapter | legacy_stage3 | optional_explicit_only | sequential | rest_api | yes | — |
| fao_stat | FaoStatDeveloperPortalAdapter | legacy_stage3 | statistical_consideration | statistical | rest_api | yes* | — |
| agris | LocalIndexScientificSourceAdapter | governed_onboarded | governed_expansion | independent_scholarly | bulk_dataset | yes | G1-01 |
| dr_duke | LocalIndexScientificSourceAdapter | governed_onboarded | governed_expansion | independent_scholarly | static_dataset | yes | G1-17 |
| gbif | RestJsonScientificSourceAdapter | governed_onboarded | governed_expansion | independent_scholarly | rest_api | yes | G5-04 |
| organic_eprints | LocalIndexScientificSourceAdapter | governed_onboarded | governed_expansion | independent_scholarly | oai_pmh | yes | G1-02 |
| bhl | RestJsonScientificSourceAdapter | governed_onboarded | governed_expansion | independent_scholarly | rest_api | yes | G6-20 |

\*FAOSTAT enablement is runtime-flagged (`FaoStatRuntimeSourceEnablement`).

## State distinctions

| State | Notes |
|---|---|
| Registered (membership 110) | Broader than runtime; many seats not activated |
| Runtime registered | The 10 keys above |
| Implemented | All 10 have adapter classes |
| Configured / enabled | All 10 `enabled=true` in dump (FAOSTAT still policy-gated) |
| Eligible | Query-specific via `GovernedScientificSourceEligibility` |
| Activated (onboarding) | G1-01, G1-17, G5-04, G1-02, G6-20 ACTIVE; remaining next-gate seats scaffolded with blockers |
| Selected / Executed | Query-specific; see Mentha/Wheat reports |

## Next-gate 17 (not all runtime)

Scaffolded seats remain inventoried in `NextGateEligibleSeatCatalog` (G1-13, G1-15, G1-19, G2-15, G2-23, G3-05, G4-01, G4-02, G6-03, G6-04, G6-06, G6-08, G6-12, G6-16, …). Non-ACTIVE seats are **not** silently removed; they are documented as activation-withheld.

## Gate 1 acceptance: PASS

Every runtime-registered source is listed. Membership / next-gate seats are distinguished from runtime activation.
