# 13 — Irrigation Generality Follow-up

Based on independent audit ([irrigation generality audit](f3040a74-4ced-4461-baf8-c5ad5f3c3186)).

## Gaps closed

| Gap | Change |
|---|---|
| A Helper missed deficit/causal acts | Extended `asksExplicitIrrigationOrWaterRequirement` phrases + causal irrigation act |
| B Over-broad named-crop+water | `$explicitIrrigation` now also requires `factorAskedAsAttribute(..., 'water')` |
| D Hardcoded `deficit irrigation` fallback | Sense-/surface-conditioned: deficit → deficit; quantity → crop water requirement; else irrigation |

## Tests

- `test_helper_recognizes_deficit_irrigation_without_named_crop_side_channel`
- `test_arabic_cwr_process_fallback_is_not_always_deficit_irrigation`
- Existing negative sense suite still green

## Focused suite

16 passed / 158 assertions after follow-up.
