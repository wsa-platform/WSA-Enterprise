# Scientific Source Integration Matrix (Updated)

## Purpose
Highest proven L0–L7 levels for the ten production sources after this mission wave.

HEAD baseline for this wave: `cbc6a4d` → post-commit SHA recorded in final report.

| key | role | Highest proven | Evidence | Limitation |
|-----|------|----------------|----------|------------|
| openalex | INTERNET_FIRST | L7 | Live prior + ledger API + Browser E2E mock path | — |
| crossref | INTERNET_FIRST | L7 | paired scholarly | — |
| semantic_scholar | INTERNET_FIRST | L4–L6+ | live successful_sources | rate limits |
| consensus | OPTIONAL_EXPLICIT | L1 catalog; policy VERIFIED | `FaoStatAndConsensusPolicyContractTest` | Explicit live NOT_RUN (key/cost) |
| fao_stat | STATISTICAL | L1; default-disabled VERIFIED | policy test; enabled toggle isolated | Enabled live NOT_RUN |
| agris | GOVERNED | L0–L3 READY index; L4 PARTIAL | store=FTS=2088; FTS rows sampled | Full agent L4–L7 timeout risk on large DB |
| dr_duke | GOVERNED | L0–L3 READY | store=FTS=19 | Live L4–L7 PARTIAL |
| gbif | GOVERNED | L4+ live prior; structured contract | prior Mentha + unit | L7 PARTIAL |
| organic_eprints | GOVERNED | L0–L3 READY | store=FTS=5 | Live L4–L7 PARTIAL |
| bhl | GOVERNED | L4+ live prior | Mentha smoke | L7 PARTIAL |

## Notes
- READY LocalIndex ≠ L7.
- Policy-disabled / optional sources are NOT_APPLICABLE for default auto-run, not integration failures.
