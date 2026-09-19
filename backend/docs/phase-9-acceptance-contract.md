# Phase 9 — Acceptance Contract (Clean Rebuild)

Baseline: `06282aba525268aa67172dd34963f8a8666259a2`  
Scope: Home Free Question only. Crop early-return path unchanged.

## Internal property keys (existing project contract)

| Semantic meaning | Internal key / evidence |
|---|---|
| Germination temperature | `requested_property=temperature` + `scientific_sense=seed_germination` |
| Irrigation | `requested_property=irrigation` (or compatible) + `scientific_sense=crop_water_requirement` |
| Yield / production quantity | `requested_property=quantity` |
| Soil suitability (with crop) | `requested_property=soil` + topic/factor `soil` (not land_classification inventory) |
| Causal | `question_type=causes` + factors temperature/germination (do not require `is_causal`) |

## Required dimensions

language, entity/entities, requested_property, location, year, comparison, causal meaning, residual safety, AgriculturalKnowledgeQuery assembly.

## Standalone maize yield (Q7a)

Historical Functional Accuracy Gate filler rows Q7a-AR/EN/FR must remain independently asserted (not only as Q8 comparison peers):

| Case | Expected |
|---|---|
| Q7a-AR / Q7a-EN / Q7a-FR | `crop_id=corn`, `requested_property=quantity`, `is_comparison=false`, no Egypt location |
| Q7a-TR (via mısır context) | same maize+yield; distinct from locative Egypt |

## Hard fails

- soil/germination/summer as crop entity
- summer/seasonal phrases as location
- comparison collapsing to one crop
- TR locative `Mısır'da` → maize
- TR maize yield → Egypt
- Crop path consuming Home helpers
- standalone maize yield treated as comparison
