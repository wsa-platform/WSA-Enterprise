# Integrated Remediation Roadmap

**Principle:** Fix root causes inside existing architecture. No Mentha/Wheat/Tomato special-cases. No new Registry/Orchestrator unless proven necessary. Preserve Stage-4 source_key and taxonomic QU fixes.

## Architectural invariants to preserve

1. Production ten-source catalog keys & roles (INTERNET_FIRST / GOVERNED / OPTIONAL_EXPLICIT / STATISTICAL).  
2. CSQ → RetrievalSpecification → MultiSource orchestrator → Stage-3 → Stage-4 → Composer pipeline.  
3. Eligibility via `GovernedScientificSourceEligibility` (capability matching, not plant-name lists).  
4. LocalIndex offline ingest / no sync rebuild on request path.  
5. Consensus remains OPTIONAL_EXPLICIT_ONLY unless product decides otherwise with ADR.  
6. Scientific validation thresholds not relaxed to inflate answer rates.  
7. `source_key` mandatory through Stage-3/4/Composer.

---

## Phase A — Architectural & Contract Integrity  
**Effort:** 3–5 eng-days | **Confidence:** medium-high

| ID | Issues | Action |
|----|--------|--------|
| REM-A1 | ISS-RC-06 | Introduce structured evidence profile contract for GBIF (hierarchy/taxon fields as first-class; stop forcing journal-shaped metadata as sole validity path) inside existing adapter/normalizer contracts |
| REM-A2 | ISS-RC-05 | Tighten `allowsIdentityOrientedRelevance` for TAXONOMIC_IDENTITY: require taxonomy answerability cues OR GBIF/BHL structured hits; keep scholarly only when abstract supports classification claims |
| REM-A3 | ISS-RC-10 | Investigate + document independent evidence counting; fix if OA+CR same DOI inflate independence |

**Acceptance:** Contract tests for GBIF structured path; taxonomy scholarly false-accept regression suite (generic binomials, not Mentha-only).

---

## Phase B — Query Identity & Construction  
**Effort:** 2–3 eng-days | **Depends:** none blocking | **Confidence:** high

| ID | Issues | Action |
|----|--------|--------|
| REM-B1 | regression guard | Expand QU/CSQ identity preservation tests (language, entity, knowledge targets) |
| REM-B2 | — | Verify ScientificQueryCompiler / Source-specific builders do not drop taxonomy vs practice intents |

**Do not:** re-open fixed Mentha EntityCatalog as special-case; keep general catalog/QU rules.

---

## Phase C — Source Integration & Orchestration  
**Effort:** 4–6 eng-days | **Depends:** Phase A contracts helpful | **Confidence:** medium

| ID | Issues | Action |
|----|--------|--------|
| REM-C1 | ISS-RC-01 | Generalize early-stop protection: if plan requires knowledge type that only a selected source can satisfy, never early-stop that source (capability-driven, not plant lists) |
| REM-C2 | ISS-RC-07 | Product policy ADR: when/how to enable FAOSTAT; keep consensus explicit unless ADR changes |
| REM-C3 | ISS-RC-12 | Ops doc: ACTIVE vs deferred seats; runtime catalog dump in health/debug (no secrets) |

**Acceptance:** Orchestration tests: practice-required → AGRIS/OE not skipped; taxonomy-required → GBIF/BHL not skipped; scholarly-only physiology may still early-stop AGRIS.

---

## Phase D — Retrieval & Index Data Integrity  
**Effort:** 5–10 eng-days (ops heavy) | **Depends:** offline data availability | **Confidence:** medium (external data)

| ID | Issues | Action |
|----|--------|--------|
| REM-D1 | ISS-RC-02 / EXT-LI | Operational warm path: documented offline ingest for AGRIS, Dr. Duke, Organic Eprints; readiness probe; reject sync rebuild forever |
| REM-D2 | EXT-LI | CI/staging gate: LocalIndex freshness check for ACTIVE LI sources before “multi-source green” claims |
| REM-D3 | — | Adapter error mapping: UNAVAILABLE vs EMPTY vs ERROR distinct to frontend |

**Acceptance:** Live AGRIS/Duke/OE return RETRIEVED (or clean EMPTY) without 504; contribution tests on warm env.

---

## Phase E — Relevance & Validation  
**Effort:** 3–5 eng-days | **Depends:** A | **Confidence:** medium-high

| ID | Issues | Action |
|----|--------|--------|
| REM-E1 | ISS-RC-05 | Taxonomy Directness: structured GBIF/BHL can be DIRECT; scholarly identity path gated |
| REM-E2 | — | Preserve Stage-4 source_key (regression only) |
| REM-E3 | ISS-RC-10 | Validation independence after investigation |

---

## Phase F — Sufficiency & Answer Composition  
**Effort:** 3–4 eng-days | **Depends:** E | **Confidence:** medium

| ID | Issues | Action |
|----|--------|--------|
| REM-F1 | ISS-RC-09 | Composer ledger: per-claim `source_keys` + evidence ids in full API (and optional slim field) |
| REM-F2 | — | Conflict path (`synthesis_completed_with_partial_conflicts`) must surface which sources disagreed |

---

## Phase G — API & Frontend  
**Effort:** 2–4 eng-days | **Depends:** F | **Confidence:** high

| ID | Issues | Action |
|----|--------|--------|
| REM-G1 | ISS-RC-08 | Ensure public response carries enough provenance for citations + failure reasons (no empty success) |
| REM-G2 | ISS-RC-11 | Browser E2E: question → status → answer → citations for multi-source case |

---

## Phase H — Performance, Reliability, Observability  
**Effort:** 3–5 eng-days | **Confidence:** medium

| ID | Issues | Action |
|----|--------|--------|
| REM-H1 | EXT-SS-RL | SS timeout/rate isolation; continue non-blocking other sources |
| REM-H2 | ISS-RC-08 | Structured traces: selected / executed / skipped_reason / stage3 / stage4 / composer contribution |
| REM-H3 | — | Keep request-path latency budget; never sync LocalIndex rebuild |

---

## Phase I — Full Regression & E2E Verification  
**Effort:** 4–6 eng-days | **Depends:** A–H as applicable | **Confidence:** medium

| ID | Issues | Action |
|----|--------|--------|
| REM-I1 | ISS-RC-09 | Answer Contribution Matrix automation for all 10 sources (warm env + fixtures) |
| REM-I2 | — | Query-to-Source matrix CI (eligible / ineligible / empty / contribution) |
| REM-I3 | — | No scientific threshold relaxation in gate |

**Execution order:** D (index ops) can parallel A/B; C after A; E after A; F after E; G after F; H parallel mid-stream; I last.

**Total effort band:** ~29–48 eng-days (wide due to LocalIndex data ops).  
**Assumptions:** One senior PHP + one data/ops; no paid API spend; SQLite/pgsql env parity verified separately.