# Scientific Source Integration Matrix (Mission Update)

## Purpose
Ten-source L0–L7 status after Workstreams B/G/A/C implementation at mission HEAD (pending commit).

**Catalog:** `openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`, `agris`, `dr_duke`, `gbif`, `organic_eprints`, `bhl`

| key | role | Highest proven | Evidence | Limitation |
|-----|------|----------------|----------|------------|
| openalex | INTERNET_FIRST | L7 (unit ledger + prior live) | ClaimAttributionLedgerTest; prior E-AUDIT-01 | Live re-smoke pending |
| crossref | INTERNET_FIRST | L7 (paired) | same | — |
| semantic_scholar | INTERNET_FIRST | L4–L6 typical | prior live | Rate limits |
| consensus | OPTIONAL_EXPLICIT | L1 | policy | Not auto; NOT_RUN explicit |
| fao_stat | STATISTICAL | L1 | default false | Enabled path NOT_RUN |
| agris | GOVERNED | L2 unit protect; L3+ BLOCKED | CapabilityAwareEarlyStopTest; diagnose I/O | LocalIndex disk I/O |
| dr_duke | GOVERNED | L2 unit; L3+ BLOCKED | early-stop chem protect | LocalIndex |
| gbif | GOVERNED | L4+ prior live; structured contract VERIFIED | GbifStructuredEvidenceContractTest; E-AUDIT-02 | Live L7 PARTIAL |
| organic_eprints | GOVERNED | L2 unit; L3+ BLOCKED | early-stop practice | LocalIndex |
| bhl | GOVERNED | L4+ prior live | E-AUDIT-02 | Live L7 PARTIAL |

## Status legend
VERIFIED / PARTIAL / BLOCKED / NOT_RUN / NOT_APPLICABLE as defined in mission prompt §23.
