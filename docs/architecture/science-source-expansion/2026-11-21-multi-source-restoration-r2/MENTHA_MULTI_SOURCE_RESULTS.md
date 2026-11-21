# Mentha Multi-Source Results

**Query:** `What is the taxonomic classification of Mentha piperita?`  
**Evidence file:** `E-R2-mentha-live.json`

## Before R2 Universal fix (baseline `E-R2-mentha-baseline.json`)

- Status: `synthesis_completed_partial`
- Attempted: openalex, crossref, gbif
- Citations: **gbif=10 only**

## After R2 (live)

| Field | Value |
|---|---|
| elapsed_s | ~20 |
| status | `synthesis_completed_with_partial_conflicts` |
| answer_status | `SCIENTIFIC_VERIFIED` |
| selected | openalex, crossref, semantic_scholar, agris, dr_duke, gbif, bhl, fao_stat |
| attempted | openalex, crossref, gbif, **bhl** |
| successful | openalex, crossref, gbif, bhl |
| citation_keys | openalex=6, gbif=10 |
| independent_provider_count | **2** |
| validated_evidence_count | 22 |
| composer_eligible_count | 22 |

## Interpretation

- Multi-source citations achieved (OpenAlex + GBIF).
- BHL executed (was missing from earlier Universal-only scholarly subset runs).
- Partial conflicts reported honestly.
- Not COMPLETE exclusive-GBIF recovery; scholarly + structured taxonomy both contribute.
