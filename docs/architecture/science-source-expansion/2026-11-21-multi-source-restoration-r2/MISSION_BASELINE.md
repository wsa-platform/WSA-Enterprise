# Mission Baseline — WSA-MS-RESTORATION-2026-11-21-R2

## Gate 0 status: PASS

| Field | Value |
|---|---|
| Mission | Multi-source scientific retrieval restoration R2 |
| Branch | `phase-18-m18-ai-marketing-communications` |
| Starting HEAD | `052305b02efebee5ef27b35d8d89a0301e11a735` |
| Remote tracking | `origin/phase-18-m18-ai-marketing-communications` @ same SHA (Local=Remote at start) |
| Historical repair commits | `d95a0a7` (Stage-4 source identity + taxonomy), `052305b` (git delivery doc) |

## Runtime

| Item | Observed |
|---|---|
| Compose stack | backend, frontend, nginx `:8079`, postgres, redis — healthy |
| Docker DB | PostgreSQL (`wsa-enterprise-postgres-1`) |
| Original project DB | SQLite (preserved; not converted) |
| App default connection (compose) | `pgsql` (from catalog dump) |

## WIP protection

Unrelated staged/unstaged/untracked WIP left untouched (CapVer, membership, frontend e2e-tmp, remediation docs, etc.). Only in-scope science multi-source files and this mission directory are committed.

## Prior repair verification

| Repair | Status |
|---|---|
| P0 Stage-4 `source_key` propagation | PASS — `Stage4SourceKeyPropagationTest` |
| Taxonomy answerability (no bare “species”) | PASS — `TaxonomicClassificationRelevanceRoutingTest` |
| Taxonomy target topics | PASS — Mentha QU topics include taxonomy/phylogeny |
| GBIF hierarchy enrichment | PASS — hierarchy summary still taxonomic direct evidence |
| Mentha not `no_validated_evidence` | PASS — live `SCIENTIFIC_VERIFIED` |
| Wheat regression | PASS — live `SCIENTIFIC_VERIFIED` |

## R2 defect found at baseline

UniversalAnswerOrchestrator passed capability-provider IDs as Stage-3 `sourceKeys`, which **omitted GOVERNED_EXPANSION** (GBIF, BHL, AGRIS, …). Mentha taxonomy therefore became scholarly-only on the Universal path even though `ScientificSourceSelector` correctly selected GBIF+BHL.
