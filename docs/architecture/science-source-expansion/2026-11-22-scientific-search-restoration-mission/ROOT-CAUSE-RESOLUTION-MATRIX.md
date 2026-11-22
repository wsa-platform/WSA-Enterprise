# Root-Cause Resolution Matrix

## Purpose
Record each material issue, correction size, implementation, and verification for the 2026-11-22 restoration mission.

| Issue | Observed | Root cause | Size | Correction | Tests | Status | Residual |
|-------|----------|------------|------|------------|-------|--------|----------|
| ISS-RC-01 | Eligible distinctive sources skipped after OA/CR fill | Early-stop used raw count without capability gate | Minimal→Comprehensive within existing policy | `mayEarlyStopSource` + AGRIS/OE knowledge type = practice; orchestrator uses capability gate | `CapabilityAwareEarlyStopTest`, `PrioritySourceEarlyStopProtectionTest` | VERIFIED (unit) | Latency if many protected sources |
| ISS-RC-09 | L7 not ledgered | Claims lacked source_keys/attributions in API | Comprehensive (Composer+Claim DTO+API) | `ClaimAttributionLedger`; claim fields; API `claim_attribution_ledger` | `ClaimAttributionLedgerTest` | VERIFIED (unit); live PARTIAL pending smoke | Slim UI may still hide ledger |
| ISS-RC-02 | AGRIS/Duke/OE unavailable | LocalIndex cold/I/O failure; sync rebuild forbidden | Minimal (readiness exposure) | `local_index_readiness` states on adapter outcomes | diagnose command | PARTIAL | Disk I/O on `scientific_record_store.sqlite` — BLOCKED live retrieval |
| ISS-RC-06 | GBIF paper-shaped | Missing structured taxonomy metadata stamp | Minimal | `withRelevanceMetadata` structured_taxonomic fields on GBIF map | `GbifStructuredEvidenceContractTest` | VERIFIED (unit) | Live L7 for GBIF PARTIAL |
| ISS-RC-05 | Scholarly taxonomy noise | Entity-only identity path | Already gated in RelevanceGate | Preserved `hasTaxonomicAnswerabilityCues` for scholarly | existing policy tests | VERIFIED (existing) | — |
| ISS-RC-07 | FAOSTAT/Consensus | Policy by design | None (preserve) | Documented; not globally enabled | N/A | NOT_APPLICABLE defect | Explicit/enabled paths NOT_RUN |
| ISS-RC-03/04 | Stage-4 source_key / QU | Fixed previously | Regression only | No reopen | prior tests | VERIFIED prior | — |
