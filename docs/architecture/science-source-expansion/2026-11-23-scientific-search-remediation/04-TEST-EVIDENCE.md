# 04-TEST-EVIDENCE

## Focused PHPUnit (post-implementation)

Command:

```text
php artisan test --filter="ArabicScientificSearchRemediationContractTest|ScientificResearchLanguageContractTest"
```

Result: **12 passed (142 assertions)** — Duration ~38s.

| Suite | Result |
|---|---|
| ArabicScientificSearchRemediationContractTest (6) | PASS |
| ScientificResearchLanguageContractTest (6) | PASS |

## Related regressions observed

`ScientificUpstreamRetrievalFixTest` showed failures (rhizome / ginger cultivation / tomato suitability) when run in a broader filter. Classification: **UNDETERMINED / likely PRE_EXISTING WIP** on that already-dirty test file; not required for mission acceptance gates 1–7. Mission remediation suite is green.

## Live LocalIndex AGRIS (post-fix)

Artifact: `mission_post_agris_probe.json`

| Lang | sense | arabic_in_variants | AGRIS | openalex | raw | survivors |
|---|---|---|---|---|---|---|
| en | crop_water_requirement | 0 | success | success | 16 | 16 |
| ar | crop_water_requirement | 0 | **success** | success | 16 | 16 |

## QUS baseline → post

| Metric | Pre | Post |
|---|---|---|
| AR wheat sense | plant_growth | crop_water_requirement |
| AR arabic_in_variants | 1 | 0 |
| OE AR eligible | 0 | 1 |
