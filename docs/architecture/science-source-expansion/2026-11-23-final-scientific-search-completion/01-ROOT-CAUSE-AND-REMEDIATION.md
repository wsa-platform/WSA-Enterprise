# 01 — Root Cause and Remediation

## Confirmed gaps (post independent verification)

| Gap | Root cause | Fix |
|---|---|---|
| AR/EN doc identity unknown | Probe omitted stable IDs | New full pipeline probe with DOI/sid keys — Jaccard **0.8824** |
| Chrome-only Arabic irrigation answer | `crop_water_requirement` forced `range` mode; empty numerics → intro only; prose findings filtered | Causal questions → explanatory; range fallback lists facts; prose → labeled excerpts |
| Excerpt oversplit | `extractListItems` split attributed English on commas | Keep attributed excerpts whole |
| OE beyond eligibility | Needed live path proof | Probe: OE eligible+successful, validated=10, ledger populated |
| Frontend answer vs homepage | Needed live browser | Live React shows `الإجابة` + heading + excerpts + citations (not homepage-as-answer) |

## Not reopened

Prior ISS-AR-001…006 / FTS / status / R2 fixes remain; verified still green in focused suites.
