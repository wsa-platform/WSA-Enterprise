<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Search\ScientificSearchRestorationHarness;
use Illuminate\Support\Facades\Http;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * Workstream B restoration scenario — documented ginger temperature question.
 * Live path may still yield honest insufficient if sources lack physiology papers.
 */
class ScientificSearchRestorationHarnessTest extends TestCase
{
    public function test_ginger_temperature_scenario_preserves_identity_and_classifies_honestly(): void
    {
        Http::allowStrayRequests();

        $report = (new ScientificSearchRestorationHarness())->runGingerTemperatureScenario([
            'composer' => ScientificEvidenceAnswerCitationBridge::makeComposer(),
            'limit' => 8,
        ]);

        $this->assertTrue($report['ok']);
        $this->assertSame('ginger', $report['crop_id']);
        $this->assertSame('Zingiber officinale', $report['scientific_name']);
        $this->assertSame('plant_growth', $report['scientific_sense']);
        $this->assertNotEmpty($report['query_variants_budgeted']);
        $this->assertTrue($report['gates']['B1_search_execution']);
        $this->assertTrue($report['gates']['B9_negative_classification']);

        // Prefer survivors when retrieval quality improves; never invent answers.
        if ($report['valid_scientific_answer']) {
            $this->assertGreaterThan(0, (int) $report['validated_count']);
            $this->assertTrue($report['gates']['B5_scientific_answer']);
        } else {
            $this->assertFalse($report['gates']['B5_scientific_answer']);
            $this->assertNotSame('', (string) $report['validation_status']);
        }
    }
}
