# 08 — Test Evidence

## Focused PHPUnit (post-fix)

```
php artisan test --filter="ArabicScientificSearchRemediationContractTest|ScientificResearchLanguageContractTest"
```

**14 passed (153 assertions)**

New/extended:
- `test_arabic_causal_irrigation_answer_includes_attributed_excerpts_not_chrome_only`
- `test_irrigation_sense_not_applied_to_incidental_water_or_disease`

## Live probe

`completion_full_pipeline_probe.json` — AR/EN overlap + OE + sense smoke + composer.

## Browser

Manual Cursor browser on `:8079` — answer rendered (see `07`).

## Upstream

WIP dirty file not mixed. Isolated HEAD baseline load via artisan filter returned no tests in container (sync limitation). Prior failures remain **UNDETERMINED** / WIP-associated; not attributed as INTRODUCED_REGRESSION of this Composer fix.
