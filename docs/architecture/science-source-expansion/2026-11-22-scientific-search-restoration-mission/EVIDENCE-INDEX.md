# Evidence Index — Scientific Search Restoration Mission

## Purpose
Stable references for verification claims in this mission.

| ID | Artifact |
|----|----------|
| E-GIT-START | HEAD `5c3e2a17b6e63f5745ec18223ca9afdc4e6fc4a8` |
| E-TEST-B | `CapabilityAwareEarlyStopTest` + `PrioritySourceEarlyStopProtectionTest` OK |
| E-TEST-G | `ClaimAttributionLedgerTest` OK |
| E-TEST-C | `GbifStructuredEvidenceContractTest` + RestJson family OK (40+1) |
| E-LIVE-01 | `E-LIVE-01-smoke.json` — wheat `scientific_generated` ledger=2; Mentha partial + gbif/bhl ledger=1 |
| E-LI-01 | `php artisan scientific:diagnose-persistence` — LocalIndex disk I/O error / unreachable |
| E-CODE-B | `ScientificKnowledgeTypeRelevancePolicy::mayEarlyStopSource` |
| E-CODE-G | `ClaimAttributionLedger` + API `claim_attribution_ledger` |
| E-CODE-C | GBIF `withRelevanceMetadata` structured_taxonomic |
| E-CODE-A | LocalIndex `local_index_readiness` observability |

## NOT_RUN / BLOCKED
| ID | Item | Reason |
|----|------|--------|
| E-NR-BROWSER | Browser E2E | NOT_RUN this session |
| E-NR-FULL | Full PHPUnit | Scoped unit/contract only |
| E-BLOCK-LI | AGRIS/Duke/OE live L4+ | LocalIndex sqlite disk I/O |
| E-NR-FAO | FAOSTAT enabled | Cost/policy; default false preserved |
| E-NR-CONS | Consensus explicit | NOT_RUN |
