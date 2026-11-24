# 01 — Substance Parity Benchmark

## Purpose

Measure whether EN/AR/TR/FR answers deliver **scientifically useful substance** for the same research intent — not lexical equality.

## Completeness labels

| Label | Meaning |
|---|---|
| `SUBSTANTIVELY_COMPLETE` | Required evidence-backed signals for the question type are present |
| `PARTIALLY_COMPLETE` | Some required signals present; material gaps remain |
| `INSUFFICIENT_EVIDENCE` | System correctly reports insufficiency |
| `NOT_RESPONSIVE` | Answer does not address the asked scientific need |
| `NOT_ASSESSABLE` | Evidence class unavailable |

## Families

1. **CWR** — crop water requirement / water need (quantitative preferred).
2. **Deficit** — effects of deficit irrigation on yield.
3. **Salinity** — salinity effects on growth/yield.
4. **Insufficient** — exotic domain must stay insufficient.

## Required signals (evidence-dependent)

| Family | Signals |
|---|---|
| CWR | `water_req`, `quantity_or_etc` when corpus supports numbers |
| Deficit | `deficit`, `yield_effect` |
| Salinity | `salinity`, `growth_or_yield` |
| Insufficient | `honest_insufficient` |

Anti-signal: `treatment_inventory_only` without water-requirement substance → `NOT_RESPONSIVE`.

## Artifacts

- Probe script: `backend/storage/app/e2e-tmp/_substance_parity_probe.php`
- Results: `substance_parity_probe.json`
