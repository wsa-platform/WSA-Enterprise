# Ten-Source Integration & Answer Contribution Matrix

**Catalog authority:** `ScientificSourceCatalog::productionRegistrations()`  
**Contract keys:** `ScientificSourceCatalogContractTest::PRODUCTION_KEYS`  
**HEAD:** `5c3e2a1`

## Integration matrix

| # | key | role | adapter | activation | auto-selected? | eligibility | protected early-stop when | live retrieval (this env) | Stage-3 survivors path | Answer contribution proven |
|---|-----|------|---------|------------|----------------|-------------|---------------------------|---------------------------|------------------------|----------------------------|
| 1 | openalex | INTERNET_FIRST_DEFAULT | OpenAlexScientificSourceAdapter | OPENALEX_ENABLED | yes | n/a | never (scholarly default) | YES (E-AUDIT-01/02) | YES | YES (wheat scientific_generated; Mentha synthesis) |
| 2 | crossref | INTERNET_FIRST_DEFAULT | CrossRefScientificSourceAdapter | CROSSREF_ENABLED | yes | n/a | never | YES | YES | YES (paired with OA) |
| 3 | semantic_scholar | INTERNET_FIRST_DEFAULT | SemanticScholarScientificSourceAdapter | SEMANTIC_SCHOLAR_ENABLED | yes | n/a | 3rd scholarly allowed (R3.1) | YES (E-AUDIT-01/02, R3.1) | YES | PARTIAL — in successful_sources on generated answers; claim-level attribution NOT fully ledgered this audit |
| 4 | consensus | OPTIONAL_EXPLICIT_ONLY | ConsensusScientificSourceAdapter | always-enabled flag class | **no** (explicit only) | n/a | n/a | NOT_RUN (not auto) | N/A unless forced | NOT_VERIFIED |
| 5 | fao_stat | STATISTICAL_CONSIDERATION | FaoStatDeveloperPortalAdapter | FAOSTAT_ENABLED **default false** | only if enabled + statistical need | runtime policy | STATISTICAL_OBSERVATION | often absent | NOT_RUN default-off | NOT_VERIFIED in default compose |
| 6 | agris | GOVERNED_EXPANSION | LocalIndexScientificSourceAdapter | AGRIS_ENABLED; seat **G1-01 ACTIVE** | if agronomy-eligible | GovernedScientificSourceEligibility | AGRICULTURAL_PRACTICE | BLOCKED — cold/stale index → `local_index_cold_or_stale_rebuild_deferred` / prior 504 | unit: not early-stopped when practice required | NOT_VERIFIED live corpus |
| 7 | dr_duke | GOVERNED_EXPANSION | LocalIndexScientificSourceAdapter | DR_DUKE_ENABLED; **G1-17 ACTIVE** | phytochem/ethno only | eligibility | CHEMICAL / TRADITIONAL_USE | BLOCKED — LocalIndex cold | unit: protected | NOT_VERIFIED live; isolated harness exists (fixture) |
| 8 | gbif | GOVERNED_EXPANSION | RestJsonScientificSourceAdapter | GBIF_ENABLED; **G5-04** | taxonomy/occurrence | eligibility | TAXONOMIC / BIODIVERSITY | YES (E-AUDIT-02 Mentha) | YES | PARTIAL — present on Mentha synthesis path; hierarchy-as-abstract still structural risk |
| 9 | organic_eprints | GOVERNED_EXPANSION | LocalIndexScientificSourceAdapter | ORGANIC_EPRINTS_ENABLED; **G1-02** NearReady | organic/agroecology cues | eligibility | AGRICULTURAL_PRACTICE | NOT_RUN / likely cold LocalIndex | same LI path as AGRIS | NOT_VERIFIED |
| 10 | bhl | GOVERNED_EXPANSION | RestJsonScientificSourceAdapter | BHL_ENABLED; **G6-20** | heritage/taxonomic lit | eligibility | HERITAGE + TAXONOMIC | YES (E-AUDIT-02) | YES | PARTIAL — on Mentha successful_sources |

## Actual answer contribution levels (definitions)

| Level | Meaning |
|-------|---------|
| L0 Registered | In production catalog |
| L1 Implemented | Adapter exists |
| L2 Selected | Appears in selector for suitable plan |
| L3 Executed | Adapter attempted (not early-stopped) |
| L4 Retrieved | Non-empty results |
| L5 Validated usable | EVIDENCE_USABLE / synthesizable |
| L6 Composer input | Included in usable set |
| L7 Answer attribution | Claim/citation in final answer |

Highest proven this audit:
- OA/CR: **L7** (wheat)  
- SS: **L4–L6** typical; L7 PARTIAL  
- GBIF/BHL: **L4+** on Mentha; L7 PARTIAL  
- AGRIS/Duke/OE: **L0–L2** proven; L3+ blocked by index  
- consensus/fao_stat: **L0–L1**; selection often N/A

## Query-to-source suitability (planning matrix — not production special-cases)

| Capability family | Prefer | Must not force |
|-------------------|--------|----------------|
| Scholarly effect / physiology | OA, CR, SS | consensus |
| Taxonomy / species identity | GBIF, BHL (+ scholarly) | Duke chemistry |
| Agronomy / irrigation practice | AGRIS, OE (+ scholarly) | Duke |
| Phytochemistry / ethnobotany | Dr. Duke (+ scholarly) | GBIF-only |
| Quantitative statistics | fao_stat when enabled | treating OA abstracts as stats |
| Explicit consensus-only | consensus when requested | auto-include |

## Next-Gate seats note

17 ADR seat IDs exist; only ACTIVE registrations enter runtime catalog merge. Deferred seats ≠ production failures.