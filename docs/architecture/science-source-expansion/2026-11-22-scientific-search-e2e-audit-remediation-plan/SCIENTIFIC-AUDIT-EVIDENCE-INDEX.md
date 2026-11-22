# Audit Evidence Index

## Git / environment
| ID | Artifact |
|----|----------|
| E-GIT-01 | HEAD `5c3e2a17b6e63f5745ec18223ca9afdc4e6fc4a8` branch `phase-18-m18-ai-marketing-communications` |
| E-GIT-02 | Local=Remote at audit time; working tree dirty WIP left intact |
| E-ENV-01 | Docker compose :8079 healthy; `/api/v1/health` ok |
| E-ENV-02 | Runtime DB: compose `pgsql` / `wsa_enterprise`; project original SQLite invariant noted |

## Code anchors (read-only)
| ID | Path / symbol |
|----|----------------|
| E-CODE-01 | `ScientificSourceCatalog::productionRegistrations()` — ten keys |
| E-CODE-02 | `ScientificSourceCatalogContractTest::PRODUCTION_KEYS` |
| E-CODE-03 | `MultiSourceScientificSearchOrchestrator` — early-stop ADEQUATE=8, protectedSourceKeys, 3rd scholarly |
| E-CODE-04 | `LocalIndexScientificSourceAdapter` — `local_index_cold_or_stale_rebuild_deferred` |
| E-CODE-05 | `ClaimEvidenceMatcher::matchCore` — `sourceKey: $result->sourceKey` |
| E-CODE-06 | `ScientificKnowledgeTypeRelevancePolicy::allowsIdentityOrientedRelevance` |
| E-CODE-07 | `GovernedScientificSourceEligibility` |
| E-CODE-08 | Routes: `POST /api/v1/public/research-agent/query` → AgriculturalResearchAgent |

## Live smoke (this audit)
| ID | File | Result |
|----|------|--------|
| E-AUDIT-01 | `E-AUDIT-01-wheat.json` (this directory) | `scientific_generated`; successful_sources: openalex, crossref, semantic_scholar |
| E-AUDIT-02 | `E-AUDIT-02-mentha.json` (this directory) | `synthesis_completed_with_partial_conflicts`; sources include OA, CR, SS, gbif, bhl |

## Prior missions (referenced, not overwritten)
| ID | Dir |
|----|-----|
| E-PRIOR-01 | `.../relevance-rejection-investigation/` |
| E-PRIOR-02 | `.../2026-11-21-scientific-pipeline-forensic-audit/` |
| E-PRIOR-03 | `.../2026-11-21-scientific-pipeline-repair/` |
| E-PRIOR-04 | `.../2026-11-21-multi-source-restoration-r2/` |
| E-PRIOR-05 | `.../2026-11-21-scientific-search-r3.1/` |

## Tests referenced (existence / purpose; not all re-run this mission)
| ID | Test |
|----|------|
| E-TEST-01 | `Stage4SourceKeyPropagationTest` |
| E-TEST-02 | `TaxonomicClassificationRelevanceRoutingTest` |
| E-TEST-03 | `ScientificSourceCatalogContractTest` |
| E-TEST-04 | Orchestrator early-stop / protected source unit tests (R3.1) |
| E-TEST-05 | LocalIndex fail-fast / deferred rebuild tests |

## NOT_RUN / BLOCKED
| ID | Item | Reason |
|----|------|--------|
| E-NR-01 | Full PHPUnit suite | Scope: audit not CI green mandate; avoid long mutative runs |
| E-NR-02 | AGRIS/Duke/OE live corpus contribution | LocalIndex cold — BLOCKED_LOCAL |
| E-NR-03 | Consensus auto path | By design not auto-selected |
| E-NR-04 | FAOSTAT default-on | Default disabled |
| E-NR-05 | Paid LLM inference cost probes | Cost policy |
| E-NR-06 | Browser E2E Playwright | NOT_RUN this mission |

## Deliverables written (docs only)
| File |
|------|
| `00-BASELINE.txt` |
| `SCIENTIFIC-SEARCH-END-TO-END-ROOT-CAUSE-AUDIT-AND-REMEDIATION-PLAN.md` |
| `SCIENTIFIC-SOURCE-INTEGRATION-MATRIX.md` |
| `SCIENTIFIC-ROOT-CAUSE-REGISTER.md` |
| `SCIENTIFIC-REMEDIATION-ROADMAP.md` |
| `SCIENTIFIC-AUDIT-EVIDENCE-INDEX.md` |