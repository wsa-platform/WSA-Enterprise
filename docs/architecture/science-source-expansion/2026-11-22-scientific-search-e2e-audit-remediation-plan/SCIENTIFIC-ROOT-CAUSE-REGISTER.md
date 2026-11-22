# Root-Cause & Defect Register

**Audit HEAD:** `5c3e2a1` | **Mode:** READ-ONLY

## Confirmed issues

### ISS-RC-01 — Early-stop vs distinctive source effectiveness
- **Class:** CONFIRMED_ROOT_CAUSE / ORCHESTRATION_DEFECT  
- **Where:** `MultiSourceScientificSearchOrchestrator::collectAdaptersSequentially` (`ADEQUATE_RESULT_COUNT=8`)  
- **Behavior:** After ≥2 scholarly providers fill ≥8 hits, non-protected adapters skipped (`adequate_results_early_stop`).  
- **Mitigation already in tree (R3.1):** 3rd scholarly allowed; AGRIS/OE protected when `AGRICULTURAL_PRACTICE` required; Duke/GBIF/BHL/FAOSTAT conditionally protected.  
- **Remaining gap:** Sources selected but not knowledge-protected still skipped (e.g. AGRIS on physiology Q); SS rate-limits can still fail.  
- **Impact:** Multi-source effectiveness incomplete for some suitable queries.  
- **Priority:** P1 (partially remediated)

### ISS-RC-02 — LocalIndex cold/stale blocks AGRIS / Dr. Duke / Organic Eprints live path
- **Class:** CONFIRMED_ROOT_CAUSE / INDEXING_DEFECT + ENVIRONMENTAL_LIMITATION  
- **Where:** `LocalIndexScientificSourceAdapter::search` → `local_index_cold_or_stale_rebuild_deferred` (R3.1 fail-fast; previously sync rebuild → 504)  
- **Behavior:** Search refuses sync rebuild; offline ingest required. Without warm index → UNAVAILABLE.  
- **Impact:** Priority governed corpora cannot contribute to live answers despite ACTIVE seats.  
- **Priority:** P0 for data ops / P1 for product effectiveness  
- **Remediation type:** DATA_OR_INDEX + OPERATIONAL (not Mentha-specific)

### ISS-RC-03 — Stage-4 / Composer source identity (HISTORICAL — FIXED)
- **Class:** CONFIRMED_DEFECT (fixed)  
- **Was:** ClaimEvidenceMatcher / Directness without `source_key` → false relevance rejects → `no_validated_evidence` after Stage-3 survivors.  
- **Now:** `ClaimEvidenceMatcher::matchCore` passes `$result->sourceKey` (lines ~178–185); Composer paths carry sourceKey; `Stage4SourceKeyPropagationTest` exists.  
- **Do not re-open** unless regression proven.

### ISS-RC-04 — Taxonomy QU misrouting (HISTORICAL — FIXED)
- **Class:** CONFIRMED_DEFECT (fixed)  
- **Was:** Taxonomic binomial → land/variety inventory → Stage-3 wipe.  
- **Now:** species routing + binomial recovery; `TaxonomicClassificationRelevanceRoutingTest`.  
- Mentha live this audit: `synthesis_completed_with_partial_conflicts` with GBIF+BHL — path restored beyond prior `no_validated_evidence`.

### ISS-RC-05 — Identity-oriented scholarly over-acceptance for taxonomy
- **Class:** CONTRIBUTING_FACTOR / RELEVANCE_DEFECT  
- **Where:** `ScientificKnowledgeTypeRelevancePolicy::allowsIdentityOrientedRelevance` for scholarly + TAXONOMIC_IDENTITY  
- **Behavior:** Entity-matched oil/agronomy Mentha papers can pass Stage-3 without taxonomy answerability cues.  
- **Impact:** Noise into Stage-4; conflicts; weaker Directness.  
- **Priority:** P1  
- **Remediation:** Contract-level taxonomy answerability for scholarly identity path (general rule, not plant-name list).

### ISS-RC-06 — GBIF paper-shaped evidence contract
- **Class:** CONFIRMED_DEFECT / CONTRACT_DEFECT  
- **Where:** GBIF profile maps rank→abstract; metadata validators expect journal/author fields.  
- **Impact:** Structured taxonomic hierarchy underused for DIRECT taxonomy answers.  
- **Priority:** P1

### ISS-RC-07 — FAOSTAT default-disabled + Consensus never auto-selected
- **Class:** SOURCE_CONFIGURATION_DEFECT / by-design limitation  
- **Where:** `FaoStatRuntimeSourceEnablement` default false; `OPTIONAL_EXPLICIT_ONLY` for consensus  
- **Impact:** “Ten sources registered” ≠ ten sources in default live path.  
- **Priority:** P2 (document + optional enablement policy; do not force on all queries)

### ISS-RC-08 — Observability gaps on public API slim responses
- **Class:** OBSERVABILITY_DEFECT  
- **Evidence:** Public slim responses often omit item_dispositions (prior audits).  
- **Impact:** Hard to prove per-candidate Stage-4 dispositions from frontend alone.  
- **Priority:** P2

### ISS-RC-09 — Actual answer contribution not systematically asserted
- **Class:** TEST_COVERAGE_GAP  
- **Gap:** Few automated tests prove evidence-id → claim → citation for each of 10 sources on live path.  
- **Priority:** P1 for verification program

### ISS-RC-10 — Independent evidence counting may conflate providers/records
- **Class:** HYPOTHESIS / VALIDATION_DEFECT risk  
- **Need:** Audit `independent_provider_count` semantics vs duplicate OA+CR same DOI.  
- **Priority:** P2 investigation then fix if confirmed

### ISS-RC-11 — Browser E2E not part of continuous proof
- **Class:** TEST_COVERAGE_GAP / NOT_RUN this mission  
- **Priority:** P2

### ISS-RC-12 — Next-Gate seats inventory vs runtime activation confusion
- **Class:** DOCUMENTATION_GAP  
- **Fact:** 17 seat IDs; subset ACTIVE. Deferred ≠ broken.  
- **Priority:** P3 docs/ops clarity

## External / environmental

| ID | Class | Note |
|----|-------|------|
| EXT-LI | EXTERNAL_BLOCKER / ENVIRONMENTAL_LIMITATION | AGRIS/Duke/OE LocalIndex not warm in compose |
| EXT-SS-RL | ENVIRONMENTAL_LIMITATION | Semantic Scholar intermittent rate_limit without/with key |
| EXT-COST | EXTERNAL_BLOCKER | Paid model inference NOT_RUN (policy) |

## Severity summary

| Priority | Count (open) |
|----------|----------------|
| P0 | 1 (LocalIndex operational readiness for ACTIVE seats) |
| P1 | 4 (early-stop remainder, taxonomy scholarly guard, GBIF contract, contribution tests) |
| P2 | 4 |
| P3 | 1 |
| Fixed (do not re-break) | 2 (Stage-4 source_key; taxonomy QU) |