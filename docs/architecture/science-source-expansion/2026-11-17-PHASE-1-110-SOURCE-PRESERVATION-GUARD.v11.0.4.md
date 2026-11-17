# 110-Source Preservation Guard — Rev 11.0.4

## Authoritative artifacts (DO NOT rebuild/replace)

1. **Membership SoT:** `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` (`110.v1`)
2. **Status matrix:** `2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json`

## Option B technical assessment (non-membership)

The Rev 11.0.3 files named `2026-11-17-PHASE-1-110-SOURCE-*` are a **separate technical/integration assessment** linked to existing `adr_id` values.

They are **not**:
- a new membership register
- a replacement for `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json`
- a replacement for `2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json`

They **do**:
- reference membership `110.v1` and the membership register path/sha
- preserve original `adr_id` values
- distinguish VERIFIED / NOT_TESTED / UNKNOWN / BLOCKED / NOT_IMPLEMENTED
- record evidence provenance without inventing seats

## Distinction for final reporting

| Layer | Artifact |
|---|---|
| Existing membership data | `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` |
| Existing status-matrix data | `2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json` |
| Existing technical/integration evidence | EXIT-08 live/credential JSON v11.0.1; adapters/tests |
| Newly verified information | Level-1 CSQ repairs; focused EXIT-09 suite 145/0/755 |
| Missing/unverified | Scientific integration for most of 110 seats |
| External blockers | Consensus key; FAOSTAT 403; S2S 429; EXIT-05/07 originals |

Inspection confirmation (2026-11-17): membership and matrix files show **no working-tree modification** under this mission.
