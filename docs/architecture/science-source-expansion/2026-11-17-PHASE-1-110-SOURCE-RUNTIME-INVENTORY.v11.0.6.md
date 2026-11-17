# 110-Source Runtime Inventory — Rev 11.0.6 (Option B)

**Membership SoT:** `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` (untouched)  
**Matrix SoT:** `2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json` (untouched)  
**Machine ledger:** `2026-11-17-PHASE-1-110-SOURCE-RUNTIME-INVENTORY.v11.0.6.json`  
**HEAD:** `32988d0`

## Counts (full 110 — not a sample)

| Metric | Count |
|---|---:|
| Membership seats | 110 |
| Runtime registered (governed keys in adapter registry) | 5 |
| Runtime ACTIVE | 5 |
| Adapter present in production catalog | 5 |
| Catalogued NextGate/NearReady but not ACTIVE | 14 |
| Runtime registration absent | 105 |
| `SCIENTIFIC_INTEGRATION_VERIFIED` | **0** |

## ACTIVE seats (110.v1)

G1-01 agris · G1-02 organic_eprints · G1-17 dr_duke · G5-04 gbif · G6-20 bhl

## Claim guard

- Inventory covers all 110 membership identities.
- ACTIVE ≠ SCIENTIFIC_INTEGRATION_VERIFIED.
- No membership/matrix rewrite.
- EXIT-08/09/10 remain PARTIAL; EXIT-05/07 BLOCKED; EXIT-11 NOT_RUN.
