# Root-Cause Resolution Matrix

## Purpose
Record each material issue, correction size, implementation, and verification for the 2026-11-22 restoration mission.

| Issue | Observed | Root cause | Size | Correction | Tests | Status | Residual |
|-------|----------|------------|------|------------|-------|--------|----------|
| ISS-RC-01 | Eligible distinctive sources skipped after OA/CR fill | Early-stop used raw count without capability gate | Minimal→Comprehensive within existing policy | `mayEarlyStopSource` + AGRIS/OE knowledge type = practice; orchestrator uses capability gate | `CapabilityAwareEarlyStopTest`, `PrioritySourceEarlyStopProtectionTest` | VERIFIED (unit) | Latency if many protected sources |
| ISS-RC-09 | L7 not ledgered | Claims lacked source_keys/attributions in API | Comprehensive (Composer+Claim DTO+API) | `ClaimAttributionLedger`; claim fields; API `claim_attribution_ledger` | `ClaimAttributionLedgerTest` | VERIFIED (unit); live PARTIAL pending smoke | Slim UI may still hide ledger |
| ISS-RC-02 | AGRIS/Duke/OE unavailable | Transient Docker SQLite I/O; file intact | Minimal (diagnose + readiness) | No destructive recovery; store=FTS gens match READY | diagnose + fts_meta_select | VERIFIED readiness; live agent L4 PARTIAL | Large-DB search latency |
| ISS-RC-06 | GBIF paper-shaped | Missing structured taxonomy metadata stamp | Minimal | `withRelevanceMetadata` structured_taxonomic fields on GBIF map | `GbifStructuredEvidenceContractTest` | VERIFIED (unit) | Live L7 for GBIF PARTIAL |
| ISS-RC-05 | Scholarly taxonomy noise | Entity-only identity path | Already gated in RelevanceGate | Preserved `hasTaxonomicAnswerabilityCues` for scholarly | existing policy tests | VERIFIED (existing) | — |
| ISS-RC-07 | FAOSTAT/Consensus | Policy by design | None (preserve) | Policy contract tests | `FaoStatAndConsensusPolicyContractTest` | VERIFIED policy | Explicit/enabled live NOT_RUN |
| ISS-RC-10 | Provider count ≠ document independence | `independent_provider_count` counted discovery keys | Minimal | `EvidenceIndependenceAssessor` + document count | `EvidenceIndependenceAssessorTest` | VERIFIED | — |
| ISS-RC-11 | Browser E2E gap | Ledger not asserted in UI | Minimal | Playwright claim-ledger + frontend rebuild | 2 passed | VERIFIED (mocked API browser) | Live browser scientific path separate |
| ISS-RC-03/04 | Stage-4 source_key / QU | Fixed previously | Regression only | No reopen | prior tests | VERIFIED prior | — |
