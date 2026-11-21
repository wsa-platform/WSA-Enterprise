# Source Selection and Execution Report

## Mentha — `What is the taxonomic classification of Mentha piperita?`

| Phase | Sources |
|---|---|
| Eligible (selector) | fao_stat, openalex, crossref, semantic_scholar, agris, dr_duke, gbif, bhl |
| Excluded | organic_eprints — `organic_eprints_requires_organic_or_agroecology_signal` |
| Selected (live) | openalex, crossref, semantic_scholar, agris, dr_duke, gbif, bhl, fao_stat |
| Attempted | openalex, crossref, gbif, bhl |
| Successful | openalex, crossref, gbif, bhl |
| Skipped (early-stop) | semantic_scholar, agris, dr_duke (adequate scholarly hits; not protected) |
| Citations | openalex=6, gbif=10 (`independent_provider_count=2`) |

Query variants (taxonomy-preferring):  
`Mentha piperita taxonomy`, `… taxonomic classification`, `… phylogeny`, `taxonomy of Mentha piperita`, `… scientific name classification`.

## Wheat — `What is the effect of temperature on wheat growth?`

| Phase | Sources |
|---|---|
| Selected | openalex, crossref, semantic_scholar, agris, gbif, fao_stat |
| Attempted | openalex, crossref |
| Successful | openalex, crossref |
| Citations | openalex=5 |
| Answer status | `SCIENTIFIC_VERIFIED` |

GBIF may be eligible via species cues but is **not** early-stop-protected for plant-growth questions; scholarly early-stop is appropriate. Taxonomy logic did not force BHL.
