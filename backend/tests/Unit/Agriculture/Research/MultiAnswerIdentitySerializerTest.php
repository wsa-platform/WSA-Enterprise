<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\Synthesis\MultiAnswerIdentitySerializer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MultiAnswerIdentitySerializerTest extends TestCase
{
    private MultiAnswerIdentitySerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new MultiAnswerIdentitySerializer();
    }

    public function test_same_identity_is_deterministic(): void
    {
        $question = $this->question();
        $json1 = $this->serializer->serialize($question, 'Optimal temperature is 20°C.', ['e2', 'e1']);
        $json2 = $this->serializer->serialize($question, 'Optimal temperature is 20°C.', ['e1', 'e2']);

        $this->assertSame($json1, $json2);
        $this->assertSame(
            $this->serializer->answerId($question, 'Optimal temperature is 20°C.', ['e1', 'e2']),
            $this->serializer->answerId($question, 'Optimal temperature is 20°C.', ['e2', 'e1', 'e1']),
        );
    }

    public function test_identity_excludes_non_scientific_and_presentation_fields(): void
    {
        $base = $this->question(originalQuestion: 'What is the temperature?', language: 'en', researchContext: 'home');
        $variant = $this->question(originalQuestion: '  Quelle est la température ?  ', language: 'fr', researchContext: 'crop_profile');

        $this->assertSame(
            $this->serializer->answerId($base, '20°C', ['e1']),
            $this->serializer->answerId($variant, '20°C', ['e1']),
        );
    }

    public function test_crop_binding_is_excluded_from_v1_identity(): void
    {
        $base = $this->question();
        $bound = new CanonicalScientificQuestion(
            originalQuestion: $base->originalQuestion,
            language: $base->language,
            normalizedForm: $base->normalizedForm,
            researchContext: 'crop_profile',
            entity: $base->entity,
            target: $base->target,
            process: $base->process,
            property: $base->property,
            relation: $base->relation,
            context: $base->context,
            conditions: $base->conditions,
            time: $base->time,
            geography: $base->geography,
            evidenceRequirement: $base->evidenceRequirement,
            resolution: $base->resolution,
            cropBinding: new \App\Services\Agriculture\Research\CsqCropBinding('wheat', 'Wheat', 'Triticum aestivum', 'crop_profile'),
        );

        $this->assertSame(
            $this->serializer->answerId($base, '20°C', ['e1']),
            $this->serializer->answerId($bound, '20°C', ['e1']),
        );
    }

    public function test_surface_fallback_is_used_when_normalized_identity_is_missing(): void
    {
        $question = CanonicalScientificQuestion::fromRoleGraph(
            originalQuestion: 'What is the optimal temperature for wheat growth?',
            language: 'en',
            normalizedForm: 'normalized question',
            researchContext: 'home',
            graph: [
                'entity_surface' => 'wheat',
                'property_key' => 'temperature',
                'property_surface' => 'temperature',
                'relation_type' => 'descriptive',
            ],
            context: [
                'scientific_sense' => 'crop_growth',
                'question_type' => 'range',
                'requested_information' => ['temperature'],
            ],
        );

        $json = $this->serializer->serialize($question, '20°C', ['e1']);
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['mode' => 'surface', 'value' => 'wheat'], $payload['question_identity']['entity']);
    }

    public function test_missing_identity_role_is_rejected(): void
    {
        $question = CanonicalScientificQuestion::unpopulated(
            originalQuestion: 'What is the answer?',
            language: 'en',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->serializer->serialize($question, '20°C', ['e1']);
    }

    public function test_answer_changes_identity(): void
    {
        $question = $this->question();
        $this->assertNotSame(
            $this->serializer->answerId($question, '20°C', ['e1']),
            $this->serializer->answerId($question, '25°C', ['e1']),
        );
    }

    public function test_scientific_time_changes_identity(): void
    {
        $a = $this->question(time: new \App\Services\Agriculture\Research\CsqTime(2022));
        $b = $this->question(time: new \App\Services\Agriculture\Research\CsqTime(2023));
        $this->assertNotSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_scientific_geography_changes_identity(): void
    {
        $a = $this->question(geography: new \App\Services\Agriculture\Research\CsqGeography(country: 'Turkey'));
        $b = $this->question(geography: new \App\Services\Agriculture\Research\CsqGeography(country: 'Egypt'));
        $this->assertNotSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_order_of_non_set_lists_is_preserved(): void
    {
        $a = $this->question(context: new \App\Services\Agriculture\Research\CsqContext(questionType: 'comparison', requestedInformation: ['temperature', 'range']));
        $b = $this->question(context: new \App\Services\Agriculture\Research\CsqContext(questionType: 'comparison', requestedInformation: ['range', 'temperature']));
        $this->assertNotSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_evidence_set_changes_identity(): void
    {
        $question = $this->question();
        $this->assertNotSame(
            $this->serializer->answerId($question, '20°C', ['e1']),
            $this->serializer->answerId($question, '20°C', ['e1', 'e2']),
        );
    }

    public function test_scientific_sense_changes_identity(): void
    {
        $a = $this->question(context: new \App\Services\Agriculture\Research\CsqContext(scientificSense: 'crop_growth', questionType: 'range', requestedInformation: ['temperature']));
        $b = $this->question(context: new \App\Services\Agriculture\Research\CsqContext(scientificSense: 'seed_germination', questionType: 'range', requestedInformation: ['temperature']));
        $this->assertNotSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_question_type_changes_identity(): void
    {
        $a = $this->question(context: new \App\Services\Agriculture\Research\CsqContext(scientificSense: 'crop_growth', questionType: 'range', requestedInformation: ['temperature']));
        $b = $this->question(context: new \App\Services\Agriculture\Research\CsqContext(scientificSense: 'crop_growth', questionType: 'comparison', requestedInformation: ['temperature']));
        $this->assertNotSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_conditions_change_identity(): void
    {
        $a = $this->question(conditions: new \App\Services\Agriculture\Research\CsqConditions([['type' => 'day', 'value' => '12h', 'label' => null]]));
        $b = $this->question(conditions: new \App\Services\Agriculture\Research\CsqConditions([['type' => 'day', 'value' => '16h', 'label' => null]]));
        $this->assertNotSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_crop_binding_changes_do_not_change_v1_identity(): void
    {
        $a = $this->question();
        $b = new CanonicalScientificQuestion(
            originalQuestion: $a->originalQuestion,
            language: $a->language,
            normalizedForm: $a->normalizedForm,
            researchContext: 'crop_profile',
            entity: $a->entity,
            target: $a->target,
            process: $a->process,
            property: $a->property,
            relation: $a->relation,
            context: $a->context,
            conditions: $a->conditions,
            time: $a->time,
            geography: $a->geography,
            evidenceRequirement: $a->evidenceRequirement,
            resolution: $a->resolution,
            cropBinding: new \App\Services\Agriculture\Research\CsqCropBinding('rice', 'Rice', 'Oryza sativa', 'crop_profile'),
        );
        $this->assertSame($this->serializer->answerId($a, '20°C', ['e1']), $this->serializer->answerId($b, '20°C', ['e1']));
    }

    public function test_version_is_embedded_and_hash_is_sha256_hex(): void
    {
        $question = $this->question();
        $json = $this->serializer->serialize($question, '20°C', ['e1']);
        $id = $this->serializer->answerId($question, '20°C', ['e1']);

        $this->assertStringContainsString('"version":"multi-answer-id-v1"', $json);
        $this->assertMatchesRegularExpression('/^sha256:[0-9a-f]{64}$/', $id);
    }

    public function test_invalid_evidence_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->serializer->serialize($this->question(), '20°C', ['e1', '']);
    }

    private function question(
        string $originalQuestion = 'What is the optimal temperature for wheat growth?',
        string $language = 'en',
        string $researchContext = 'home',
        ?\App\Services\Agriculture\Research\CsqContext $context = null,
        ?\App\Services\Agriculture\Research\CsqTime $time = null,
        ?\App\Services\Agriculture\Research\CsqGeography $geography = null,
    ): CanonicalScientificQuestion {
        return CanonicalScientificQuestion::fromRoleGraph(
            originalQuestion: $originalQuestion,
            language: $language,
            normalizedForm: 'normalized question',
            researchContext: $researchContext,
            graph: [
                'entity_surface' => 'wheat',
                'entity_normalized' => 'wheat',
                'entity_canonical_id' => 'wheat',
                'entity_canonical_namespace' => 'taxonomy.crop',
                'entity_resolution' => 'resolved',
                'property_key' => 'temperature',
                'property_surface' => 'temperature',
                'property_of_role' => 'process',
                'relation_type' => 'descriptive',
            ],
            context: [
                'scientific_sense' => 'crop_growth',
                'question_type' => 'range',
                'requested_information' => ['temperature'],
            ],
            time: $time ?? new \App\Services\Agriculture\Research\CsqTime(),
            geography: $geography ?? new \App\Services\Agriculture\Research\CsqGeography(),
        );
    }
}
