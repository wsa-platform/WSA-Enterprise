<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * P1: Home requested-vs-resolved entity retention and Home-gated genus-only search suppression.
 */
class EntitySpecificityPreservationContractTest extends TestCase
{
    public function test_home_sweet_potato_cultivation_keeps_species_and_omits_genus_only_variant(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'How should sweet potato be cultivated?',
        ]);
        $this->assertSame('sweet-potato', $plan->normalizedQuery->cropId);
        $this->assertSame('Ipomoea batatas', $plan->normalizedQuery->scientificName);
        $this->assertSame('sweet-potato', $plan->normalizedQuery->constraints['resolved_entity_id'] ?? null);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = implode(' | ', $variants);

        $this->assertTrue(
            str_contains(mb_strtolower($joined), 'ipomoea batatas')
            || str_contains(mb_strtolower($joined), 'sweet potato')
            || str_contains(mb_strtolower($joined), 'sweet-potato'),
            $joined,
        );
        $this->assertFalse($this->hasGenusOnlyCultivationVariant($variants), $joined);
        $this->assertSame('', (string) ($plan->contextInput['selected_crop_id'] ?? ''));
        $this->assertSame('', (string) ($plan->contextInput['selected_crop_name'] ?? ''));
    }

    public function test_crop_profile_sweet_potato_variant_snapshot_is_unchanged(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'sweet potato farming needs',
            'selected_crop_id' => 'sweet-potato',
            'selected_crop_name' => 'Sweet potato',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertSame('sweet-potato', $plan->normalizedQuery->cropId);
        $this->assertSame('Ipomoea batatas', $plan->normalizedQuery->scientificName);
        $this->assertSame('sweet-potato', (string) ($plan->contextInput['selected_crop_id'] ?? ''));
        $this->assertSame('Sweet potato', (string) ($plan->contextInput['selected_crop_name'] ?? ''));

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = implode(' | ', $variants);
        $this->assertTrue(
            str_contains(mb_strtolower($joined), 'ipomoea batatas')
            || str_contains(mb_strtolower($joined), 'sweet potato'),
            $joined,
        );
        // Existing Crop-profile cap (MAX_VARIANTS=5) already drops the 6th genus-only
        // variant. Freeze the live Crop snapshot so P1-B cannot change Crop output.
        $this->assertSame([
            'Ipomoea batatas cultivation growth',
            'sweet potatoes cultivation growth',
            'sweet potatoes cultivation',
            'sweet potatoes production',
            'Ipomoea batatas cultivation',
        ], $variants);
        $this->assertFalse($this->hasGenusOnlyCultivationVariant($variants), $joined);
    }

    public function test_home_chicken_question_keeps_requested_surface_beside_poultry_canonical(): void
    {
        $qus = app(QueryUnderstandingService::class);

        $arabic = $qus->understand(['query' => 'ما هي سلالات الدجاج؟']);
        $this->assertSame('animal', $arabic->subject['type'] ?? null);
        $this->assertSame('poultry', $arabic->subject['value'] ?? null);
        $this->assertSame('poultry', $arabic->constraints['resolved_entity_id'] ?? null);
        $this->assertSame('دجاج', $arabic->constraints['requested_entity_surface'] ?? null);
        $this->assertNotSame(
            $arabic->constraints['requested_entity_surface'] ?? null,
            $arabic->constraints['resolved_entity_id'] ?? null,
        );

        $english = $qus->understand(['query' => 'What chicken breeds are common?']);
        $this->assertSame('animal', $english->subject['type'] ?? null);
        $this->assertSame('poultry', $english->subject['value'] ?? null);
        $this->assertSame('poultry', $english->constraints['resolved_entity_id'] ?? null);
        $this->assertSame('chicken', $english->constraints['requested_entity_surface'] ?? null);
        $this->assertNotSame(
            mb_strtolower((string) ($english->constraints['requested_entity_surface'] ?? '')),
            mb_strtolower((string) ($english->constraints['resolved_entity_id'] ?? '')),
        );
        $this->assertSame('poultry', $english->constraints['named_entity_surface'] ?? null);
    }

    public function test_requested_chicken_is_not_silently_equivalent_to_poultry(): void
    {
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'chicken feeding requirements in arid regions',
        ]);

        $requested = mb_strtolower(trim((string) ($understood->constraints['requested_entity_surface'] ?? '')));
        $resolved = mb_strtolower(trim((string) ($understood->constraints['resolved_entity_id'] ?? '')));

        $this->assertSame('chicken', $requested);
        $this->assertSame('poultry', $resolved);
        $this->assertNotSame($requested, $resolved);
        $this->assertTrue($understood->isEntityDependent());
    }

    /**
     * @param  list<string>  $variants
     */
    private function hasGenusOnlyCultivationVariant(array $variants): bool
    {
        foreach ($variants as $variant) {
            $normalized = mb_strtolower(trim((string) $variant));
            if ($normalized === 'ipomoea cultivation') {
                return true;
            }
        }

        return false;
    }
}
