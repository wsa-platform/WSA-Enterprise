# 04 — Engineering Design

## Chosen approach

Extend existing catalog helpers and tighten QUS sense/intent gates. Avoid new subsystems.

### GAP A

- Centralize act signals in `explicitIrrigationActSignals()` (EN/AR/TR/FR).
- Separate `asksIrrigationSystemsOrTechnologyInventory` and `hasPlantDiseaseOrPathogenSignal`.
- Helper returns false for systems/disease unless a true requirement/deficit/causal-non-disease cue exists.

### GAP B

- Block irrigation promotion when disease or systems inventory dominates.
- Force disease intent when pathogen framing exists without a genuine irrigation-requirement act.
- Map pure systems inventory away from irrigation-requirement intent (`agricultural_industry`).

### GAP C

- Precedence: extension → economics → disease → varieties → organic/soil → systems → salinity → CWR.
- CWR requires `asksExplicitIrrigationOrWaterRequirement` **or** (requested water **and** `factorAskedAsAttribute`).
- Remove bare `researchIntent === 'irrigation'` sufficiency.

### GAP D

- `irrigationRetrievalEnglishConcept($surface)` maps meaning → English concept.
- `englishRetrievalFallbackForRole` delegates to that helper for CWR/process.

## Why this is superior to fixture-specific patches

- Crop-agnostic signals (no wheat/maize branches).
- Language families, not single translations.
- Reusable helpers keep QUS and QueryBuilder from duplicating policy.
