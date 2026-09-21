<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ResearchFeedbackRecord;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase-2 contract tests: R2 language, R7 feedback, KQP context_input.
 */
class Phase2ContractIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Organization $publicOrg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicOrg = Organization::create([
            'name' => 'WSA Demo',
            'slug' => 'wsa-demo',
            'is_active' => true,
        ]);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
            'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
            'faostatservices.fao.org/*' => Http::response(['message' => 'unavailable'], 503),
        ]);
    }

    /** @return list<array{question: string, expected: string, ui: string}> */
    public static function answerLanguageMatrix(): array
    {
        return [
            'ar_q_en_ui' => [
                'question' => 'ما درجة حرارة إنبات القمح؟',
                'expected' => 'ar',
                'ui' => 'en',
            ],
            'en_q_ar_ui' => [
                'question' => 'What is the germination temperature of wheat?',
                'expected' => 'en',
                'ui' => 'ar',
            ],
            'tr_q_en_ui' => [
                'question' => 'Buğdayın çimlenme sıcaklığı nedir?',
                'expected' => 'tr',
                'ui' => 'en',
            ],
            'fr_q_ar_ui' => [
                'question' => 'Quelle est la température de germination du blé ?',
                'expected' => 'fr',
                'ui' => 'ar',
            ],
            'ar_q_ar_ui' => [
                'question' => 'ما درجة حرارة إنبات القمح؟',
                'expected' => 'ar',
                'ui' => 'ar',
            ],
            'en_q_en_ui' => [
                'question' => 'What is the germination temperature of wheat?',
                'expected' => 'en',
                'ui' => 'en',
            ],
        ];
    }

    /**
     * R2: answer_language = question_language; UI Accept-Language must not override.
     *
     * @dataProvider answerLanguageMatrix
     */
    public function test_r2_answer_language_follows_question_not_ui(string $question, string $expected, string $ui): void
    {
        app()->setLocale($ui);
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => $question]);

        $this->assertSame($expected, $understood->language, 'question_language');
        $answerLanguage = (string) ($understood->constraints['answer_language'] ?? '');
        $this->assertSame($expected, $answerLanguage, 'answer_language must equal question_language');
        if ($ui !== $expected) {
            $this->assertNotSame($ui, $answerLanguage, 'ui_locale must not override answer_language');
        }
    }

    /** P2-C07: KnowledgeQueryPlan::toArray must include context_input. */
    public function test_kqp_to_array_preserves_context_input(): void
    {
        $planner = app(ResearchPlanner::class);
        $plan = $planner->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'selected_category_id' => 'cereals',
            'selected_category_name' => 'Cereals',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertInstanceOf(KnowledgeQueryPlan::class, $plan);

        $array = $plan->toArray();
        $this->assertArrayHasKey('context_input', $array);
        $this->assertSame('wheat', $array['context_input']['selected_crop_id'] ?? null);
        $this->assertSame('Wheat', $array['context_input']['selected_crop_name'] ?? null);
        $this->assertNotEmpty($plan->contextInput);
    }

    /** R7: positive feedback is persisted. */
    public function test_r7_positive_feedback_is_persisted(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/feedback', [
            'organization' => 'victim-ignored',
            'polarity' => 'positive',
            'question' => 'What is wheat germination temperature?',
            'question_language' => 'en',
            'answer_language' => 'en',
            'ui_locale' => 'ar',
            'research_source' => 'home',
            'what_worked' => 'clear citations',
        ]);

        $response->assertCreated()
            ->assertJsonPath('persisted', true)
            ->assertJsonPath('policy.positive_feedback_only', true)
            ->assertJsonPath('policy.mutates_scientific_behavior', false);

        $this->assertSame(1, ResearchFeedbackRecord::query()->count());
        $row = ResearchFeedbackRecord::query()->first();
        $this->assertSame('positive', $row->polarity);
        $this->assertSame($this->publicOrg->id, $row->organization_id);
        $this->assertTrue((bool) ($row->payload['does_not_mutate_scientific_behavior'] ?? false));
    }

    /** R7: negative feedback is NOT persisted. */
    public function test_r7_negative_feedback_is_not_persisted(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/feedback', [
            'polarity' => 'negative',
            'question' => 'What is wheat germination temperature?',
            'ui_locale' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('persisted', false)
            ->assertJsonPath('reason', 'negative_feedback_not_persisted');

        $this->assertSame(0, ResearchFeedbackRecord::query()->count());
    }

    /** R1/P2-C03: public tenant missing yields shared error contract. */
    public function test_public_tenant_missing_emits_shared_error_envelope(): void
    {
        config(['wsa.public_organization_slug' => 'missing-public-org']);

        $home = $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat drip irrigation',
        ]);
        $home->assertStatus(503)
            ->assertJsonPath('status', 'public_organization_unavailable')
            ->assertJsonPath('error.code', 'public_organization_unavailable')
            ->assertJsonPath('error.http_status', 503);

        $crop = $this->getJson('/api/v1/public/field-crops/farming-needs-profile?'.http_build_query([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
        ]));
        $crop->assertStatus(503)
            ->assertJsonPath('load_state', 'public_organization_unavailable')
            ->assertJsonPath('status', 'public_organization_unavailable')
            ->assertJsonPath('error.code', 'public_organization_unavailable');
    }
}
