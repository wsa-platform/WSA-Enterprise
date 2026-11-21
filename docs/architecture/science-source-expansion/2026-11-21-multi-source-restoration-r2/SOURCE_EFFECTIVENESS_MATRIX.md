# Source Effectiveness Matrix — Gate 2

Verification levels: contract/unit where feasible; live where environment allows. External limitations are not coded as defects.

| source_key | Reg | Adapter | Contract | Caps | Eligibility | Activation | Query | Exec | Parse | Identity | Provenance | Evidence | Unit/contract | Integration/live | Overall |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| openalex | PASS | PASS | PASS | PASS | N/A (legacy default) | PASS | PASS | PASS | PASS | PASS | PASS | PASS | existing adapter tests | LIVE Mentha/Wheat success | **VERIFIED** |
| crossref | PASS | PASS | PASS | PASS | N/A | PASS | PASS | PASS | PASS | PASS | PASS | PASS | existing | LIVE Mentha success (attempted) | **VERIFIED** |
| semantic_scholar | PASS | PASS | PASS | PASS | N/A | PASS | PASS | PASS | PASS | PASS | PASS | PASS | existing | SELECTED Mentha/Wheat; often early-stopped | **PARTIALLY_VERIFIED** |
| consensus | PASS | PASS | PASS | PASS | optional_explicit_only | PASS | N/A auto | N/A | N/A | N/A | N/A | N/A | conformance | NOT auto-selected by design | **NOT_APPLICABLE** (optional) |
| fao_stat | PASS | PASS | PASS | PASS | statistical | PASS* | PASS | PASS | PASS | PASS | PASS | gated | FaoStat tests | SELECTED; consideration≠acceptance | **PARTIALLY_VERIFIED** |
| agris | PASS | PASS | PASS | PASS | governed | ACTIVE | PASS | PASS | PASS | PASS | PASS | local index | G1-01 harness | SELECTED Mentha; early-stop skip | **PARTIALLY_VERIFIED** |
| dr_duke | PASS | PASS | PASS | PASS | chemistry/ethnobot | ACTIVE | PASS | PASS | PASS | PASS | PASS | local index | G1-17 harness | SELECTED Mentha; early-stop skip | **PARTIALLY_VERIFIED** |
| gbif | PASS | PASS | PASS | PASS | taxonomy/occurrence | ACTIVE | PASS taxonomy variants | PASS | PASS hierarchy | PASS | PASS | PASS Stage-4 | G5-04 + taxonomy tests | LIVE Mentha 10 citations | **VERIFIED** |
| organic_eprints | PASS | PASS | PASS | PASS | organic/agroecology only | ACTIVE | PASS | PASS | PASS | PASS | PASS | local index | G1-02 harness | Excluded Mentha (correct) | **PARTIALLY_VERIFIED** |
| bhl | PASS | PASS | PASS | PASS | heritage + taxonomy | ACTIVE | PASS | PASS | PASS | PASS | PASS | heritage/taxonomy | G6-20 harness | LIVE Mentha attempted+success | **VERIFIED** (live retrieval; citations may defer to stronger taxonomy records) |

\*FAOSTAT runtime policy may disable portal calls independently of catalog enablement.

## Layer notes

- **Layer H live:** OpenAlex, Crossref, GBIF, BHL confirmed on Mentha R2 live run (`E-R2-mentha-live.json`).
- Semantic Scholar / AGRIS / DrDuke: selected but early-stopped after scholarly adequacy + protected GBIF/BHL already ran — not a registration defect.
- Consensus: correctly never auto-selected.

## Gate 2 acceptance: PASS (with explicit partial live coverage)
