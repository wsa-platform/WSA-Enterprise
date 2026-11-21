# Multi-Source Retrieval Audit — Gate 3

## Principle

No source is universal. Complete inventory ≠ every-query execution. Query-specific selection uses eligibility + capability + usefulness.

## Defect (root cause)

`UniversalAnswerOrchestrator` collected scientific provider IDs from `CapabilityDrivenSourceSelector` (legacy scholarly + FAOSTAT only) and passed them as Stage-3 `sourceKeys`.  
`MultiSourceScientificSearchOrchestrator` then **bypassed** `ScientificSourceSelector` governed expansion → GBIF/BHL omitted on Universal path.

## Fix

`mergeStage3SourceKeys()` merges capability IDs with `ScientificSourceSelector::selectSources($plan)` so governed seats remain available.

## Early stop

- ADEQUATE_RESULT_COUNT = 8 after ≥2 scholarly attempts.
- Knowledge-type **protected** keys never early-stopped: GBIF (+ occurrence), BHL (heritage **or** TAXONOMIC_IDENTITY), DrDuke, FAOSTAT as applicable.
- Mentha live: openalex+crossref ran; GBIF+BHL still attempted; semantic_scholar/agris/dr_duke skipped with `adequate_results_early_stop` — justified and observable.

## Acceptance checks

| Check | Result |
|---|---|
| Multiple eligible sources selected | PASS (Mentha: 8 sources) |
| Multiple qualified execute | PASS (openalex, crossref, gbif, bhl) |
| Multiple contribute evidence | PASS (citations openalex+gbif) |
| GBIF not exclusive | PASS |
| One success does not suppress protected sources | PASS |
| Failure isolation | PASS (no failed sources on Mentha) |
| Irrelevant sources not forced | PASS (organic_eprints excluded) |
| No fixed source count | PASS |
| Resource limits respected | PASS (early-stop + timeouts) |
