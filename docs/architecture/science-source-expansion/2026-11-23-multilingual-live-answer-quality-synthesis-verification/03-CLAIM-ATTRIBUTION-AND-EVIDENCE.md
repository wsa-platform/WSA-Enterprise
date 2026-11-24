# 03 — Claim Attribution and Evidence Integration

## Ledger Behavior

- Unit: `ClaimAttributionLedgerTest` — PASS (L7 when claim+evidence+source+citation resolve; unused retrieved sources not L7).
- Live probe: EN deficit `claims_count=2`, `ledger_rows=2`, `citations_count=13`.
- Relationships observed live: `conflicting`, `insufficient_evidence`, `supported` (case-dependent).

## Evidence Types Exercised

| Type | How verified | Outcome |
|---|---|---|
| Direct supporting | EN deficit findings from DIRECT evidence | Used in key findings |
| Partial / conflict-labeled | claim_relationships include conflicting | Not promoted as sole factual prose (contract tests) |
| Irrelevant / wrong act | Wastewater vs deficit | Rejected by `findingAddressesAskedAct` |
| Insufficient / exotic | Martian soil | `insufficient_evidence` |
| Context-dependent | synthesis notes + contextual_differences counts | Packet present |

## Independence Caution

Citation count ≠ independent evidence count. Probe reports citation counts from provider payloads; DOI/provider dedup independence was not re-proven as a new LIVE metric in this mission (prior synthesis mission contracts remain the authority).

## Integration Mode

AnswerComposer remains **Hybrid**: extractive preferred empirical sentences + `ScientificSynthesisIntegrator` packet in `researchMetadata.scientific_synthesis` (full path). Compact API summary may omit full packet — consumers must read research metadata / top-level claim fields as documented in prior missions.
