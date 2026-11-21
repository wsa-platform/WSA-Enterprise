# Test Evidence — Gate 8 (automated)

## Commands and results

```text
docker compose exec -T backend php artisan test --filter="MultiSourceTaxonomyRetrievalTest|test_mentha_taxonomy_stage3_includes_governed"
→ 5 passed (16 assertions)

docker compose exec -T backend php artisan test --filter="TaxonomicClassificationRelevanceRoutingTest|Stage4SourceKeyPropagationTest"
→ 8 passed (28 assertions)
```

## Coverage map

| Area | Test |
|---|---|
| BHL+GBIF early-stop protection | `MultiSourceTaxonomyRetrievalTest::test_taxonomy_plan_protects_gbif_and_bhl_from_early_stop` |
| Taxonomy query variants | `…prefer_taxonomy_not_cultivation` |
| Selector includes GBIF+BHL | `…selector_includes_gbif_and_bhl_for_mentha_taxonomy` |
| Capability subset insufficient | `…capability_only_subset_is_not_sufficient_without_selector_merge` |
| Universal Stage-3 merge | `UniversalAnswerOrchestratorFeatureTest::test_mentha_taxonomy_stage3_includes_governed_gbif_not_capability_subset_only` |
| Taxonomy answerability / oil reject | `TaxonomicClassificationRelevanceRoutingTest` |
| Stage-4 source_key | `Stage4SourceKeyPropagationTest` |

## Live retrieval (not fixtures)

| Query | Command | Result |
|---|---|---|
| Mentha | `php r2_live_query.php mentha` | SCIENTIFIC_VERIFIED; citations openalex+gbif; BHL attempted |
| Wheat | `php r2_live_query.php wheat` | SCIENTIFIC_VERIFIED; openalex citations |

## Model inference

Composer used deterministic/template scientific synthesis path (`lifecycle_status=composer_used`). No new paid model inference introduced. Status: available path exercised; paid external LLM **NOT_RUN** / not required for this mission.
