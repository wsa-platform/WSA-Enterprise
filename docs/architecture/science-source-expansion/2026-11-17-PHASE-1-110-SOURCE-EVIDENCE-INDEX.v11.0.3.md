# Phase 1 — 110-Source Evidence Index (Rev 11.0.3)

Recorded: `2026-11-17T19:49:01Z`

## Authoritative membership / identity

| artifact | role |
|---|---|
| `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` | CURRENT membership 110.v1 seats |
| `2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json` | Phase-1 source matrix rows |
| `ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY.v3.json` | Canonical seat IDs |
| `2026-11-17-PHASE-1-110-SOURCE-MASTER-STATUS-REGISTRY.v11.0.3.json` | This revision master status registry |

## Historical live connectivity (NOT re-probed)

Source: `2026-11-17-PHASE-1-EXIT-08-LIVE-CONNECTIVITY.v11.0.1.json` (+ `2026-11-17-PHASE-1-EXIT-08-CREDENTIAL-REQUIREMENTS.v11.0.1.json`)

### Mapped onto 110 membership seats

| adr_id | source_key | historical_label | controlled live_connectivity_status |
|---|---|---|---|
| G1-01 | agris | LIVE_CONNECTIVITY_VERIFIED | VERIFIED |
| G1-02 | organic_eprints | LIVE_CONNECTIVITY_VERIFIED | VERIFIED |
| G1-17 | dr_duke | CONTRACT_VERIFIED_ONLY | PARTIALLY_VERIFIED |
| G5-04 | gbif | LIVE_CONNECTIVITY_VERIFIED | VERIFIED |
| G6-20 | bhl | LIVE_CONNECTIVITY_VERIFIED | VERIFIED |

### Legacy Stage3 keys outside 110.v1 membership (evidence preserved)

| source_key | historical_label | http_status | membership_seat |
|---|---|---|---|
| openalex | LIVE_CONNECTIVITY_VERIFIED | 200 | none (EXTERNAL_ONLY / LEGACY_STAGE3) |
| crossref | LIVE_CONNECTIVITY_VERIFIED | 200 | none (EXTERNAL_ONLY / LEGACY_STAGE3) |
| semantic_scholar | BLOCKED_EXTERNAL_SERVICE | 429 | none (EXTERNAL_ONLY / LEGACY_STAGE3) |
| fao_stat | BLOCKED_CREDENTIALS | 403 | none (EXTERNAL_ONLY / LEGACY_STAGE3) |
| consensus | BLOCKED_CREDENTIALS | None | none (EXTERNAL_ONLY / LEGACY_STAGE3) |

## Runtime adapter / registry code evidence

| path | use |
|---|---|
| `backend/app/Services/Agriculture/Research/Search/Registry/ScientificSourceCatalog.php` | production registrations |
| `backend/app/Services/Agriculture/Research/Search/ScientificSourceAdapterRegistry.php` | adapter resolution |
| `backend/app/Services/Agriculture/Research/Onboarding/NextGateEligibleSeatCatalog.php` | next-gate 17 seat defs / activations |
| `backend/app/Services/Agriculture/Research/Onboarding/CurrentMembershipNearReadySeatCatalog.php` | G1-02 / G6-20 activations |

## CSQ / scientific path residual evidence

- `2026-11-17-PHASE-1-EXIT-09-FIVE-SOURCE-CSQ-RUN.v11.0.1.json` — five-source/CSQ suite PARTIAL (140 passed / 5 failed historically; shared component pass ≠ per-seat scientific integration).
- `2026-11-17-PHASE-1-EXIT-09-RESIDUAL.v11.0.1.json` — EXIT-09 residual.

## Per-source evidence references

Each registry entry carries its own `evidence_references` array. Shared suite references are attached only to runtime-active seats and explicitly labeled as non-seat-specific.
