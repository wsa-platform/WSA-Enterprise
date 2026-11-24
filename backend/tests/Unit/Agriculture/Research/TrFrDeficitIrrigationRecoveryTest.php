<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use ReflectionMethod;
use Tests\Feature\Phase5UnitATestFixtures;
use Tests\TestCase;

class TrFrDeficitIrrigationRecoveryTest extends TestCase
{
    use Phase5UnitATestFixtures;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_turkish_possessive_preserves_multiword_deficit_process_surface(): void
    {
        $spans = AgriculturalEntityCatalog::causalArgumentSpans(
            mb_strtolower('Eksik sulamanın buğday verimine etkisi nedir?'),
        );
        $this->assertIsArray($spans);
        $this->assertSame('eksik sulama', $spans['affector'] ?? null);
        $this->assertStringContainsString('buğday', (string) ($spans['target'] ?? ''));

        $roles = AgriculturalEntityCatalog::extractSemanticRoleGraph(
            mb_strtolower('Eksik sulamanın buğday verimine etkisi nedir?'),
        );
        $this->assertSame('eksik sulama', $roles['process_surface'] ?? null);
    }

    public function test_tr_fr_deficit_surfaces_map_to_deficit_irrigation_concept(): void
    {
        $this->assertSame(
            'deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept(
                mb_strtolower('Eksik sulamanın buğday verimine etkisi nedir?'),
            ),
        );
        $this->assertSame(
            'regulated deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept(
                mb_strtolower('Kısıntılı sulamanın buğday verimine etkisi nedir?'),
            ),
        );
        $this->assertSame(
            'deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept(
                mb_strtolower("Quel est l'effet de l'irrigation déficitaire sur le rendement du blé?"),
            ),
        );
        $this->assertSame(
            'deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept(
                mb_strtolower("Quel est l'effet du déficit hydrique d'irrigation sur le rendement du blé?"),
            ),
        );
    }

    public function test_tr_fr_process_surfaces_receive_english_deficit_retrieval_terms(): void
    {
        // Mirrors ScientificSearchQueryBuilder::specExecutableNonEntityRole policy:
        // non-Arabic irrigation acts must still compile to English deficit concepts.
        foreach ([
            'eksik sulama',
            'kısıntılı sulama',
            'irrigation déficitaire',
            'déficit hydrique',
        ] as $surface) {
            $mapped = AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept(mb_strtolower($surface));
            $this->assertStringContainsString(
                'deficit',
                mb_strtolower($mapped),
                "Expected deficit concept for surface [{$surface}], got [{$mapped}]",
            );
        }
    }

    public function test_tr_fr_executable_process_terms_use_english_deficit_not_raw_surface(): void
    {
        $qus = app(\App\Services\Agriculture\Research\QueryUnderstandingService::class);
        $qb = app(\App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder::class);
        $exec = new ReflectionMethod(
            \App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder::class,
            'specExecutableNonEntityRole',
        );
        $exec->setAccessible(true);

        foreach ([
            'Eksik sulamanın buğday verimine etkisi nedir?',
            "Quel est l'effet de l'irrigation déficitaire sur le rendement du blé?",
        ] as $question) {
            $akq = $qus->understand(['query' => $question]);
            $csq = $akq->canonicalQuestion;
            $this->assertNotNull($csq);
            $spec = \App\Services\Agriculture\Research\RetrievalSpecification::fromCanonical($csq);
            $processTerm = $exec->invoke(
                $qb,
                $spec,
                \App\Services\Agriculture\Research\CanonicalScientificQuestion::ROLE_PROCESS,
            );
            $this->assertNotNull($processTerm);
            $this->assertStringContainsString(
                'deficit',
                mb_strtolower((string) $processTerm),
                "Expected English deficit process term for [{$question}], got [{$processTerm}]",
            );
            $this->assertStringNotContainsString(
                'eksik',
                mb_strtolower((string) $processTerm),
            );
        }
    }

    public function test_deficit_question_accepts_mad_di_yield_finding_without_literal_deficit_word(): void
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'findingAddressesAskedAct');
        $method->setAccessible(true);
        $composer = $this->composer();
        $plan = $this->phase5Plan(
            question: 'Eksik sulamanın buğday verimine etkisi nedir?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'tr',
                'scientific_sense' => 'crop_water_requirement',
            ],
            language: 'tr',
            intent: 'irrigation',
        );

        $this->assertTrue($method->invoke(
            $composer,
            'The 50% MAD (DI) treatment produced the highest grain yield and water use efficiency in both seasons.',
            $plan,
        ));
        $this->assertFalse($method->invoke(
            $composer,
            'Raw wastewater irrigation substantially reduced fertilizer use for wheat biomass production.',
            $plan,
        ));
    }

    private function composer(): AnswerComposer
    {
        $relevance = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $relevance->shouldReceive('isRelevant')->andReturn(true);
        $relevance->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);

        return new AnswerComposer(
            app(ScientificSourceValidator::class),
            $relevance,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner,
            expressionAccuracyGate: new AnswerExpressionAccuracyGate,
        );
    }
}
