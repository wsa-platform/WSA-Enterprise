# Observability and Failure Diagnostics — Gate 7

## Available on scientific_search / observability

- `selected_sources`, `attempted_sources`, `successful_sources`, `failed_sources`, `empty_sources`
- `source_outcomes[]` with per-source status, result_count, duration_ms, observability
- Early-stop reason `adequate_results_early_stop` + adequate_result_count / scholarly_attempted
- `citation_provider_contribution` (independent_provider_count, by_provider)
- `sources_received`, `validated_evidence_count`, `rejected_evidence_count`, `composer_eligible_count`
- `lifecycle_status`, `search_status`, `faostat_pipeline_outcome`
- Selector trace fields: `relevance_excluded`, `capability_denied`, `skipped_inactive`

## Distinctions preserved

| Condition | How surfaced |
|---|---|
| No eligible governed source | `relevance_excluded` reason codes |
| Source not selected (optional) | consensus in `optional_not_selected` |
| Early-stop skip | outcome error `adequate_results_early_stop` |
| Execution failure | `failed_sources` + outcome error |
| Single-provider citations | `independent_provider_count=1` (Wheat) |
| Multi-provider citations | Mentha `independent_provider_count=2` |
| Insufficient evidence | distinct from composer_used / SCIENTIFIC_VERIFIED |

No credentials exposed in reports.
