<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\FieldCropTaxonomyCatalog;

/**
 * Deterministic query understanding for agricultural research questions.
 * Does not invent missing context and performs no external search.
 */
class QueryUnderstandingService
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function understand(array $input): AgriculturalKnowledgeQuery
    {
        $originalQuestion = trim((string) ($input['query'] ?? ''));
        $explicitDomain = trim((string) ($input['domain'] ?? $input['agricultural_domain'] ?? ''));
        $cropId = trim((string) ($input['selected_crop_id'] ?? ''));
        $cropName = trim((string) ($input['selected_crop_name'] ?? ''));
        $scientificNameInput = trim((string) ($input['scientific_name'] ?? ''));
        $location = $this->extractLocation($input, $originalQuestion);
        $constraints = $this->extractConstraints($input);

        if ($cropId !== '' && $cropName !== '') {
            return $this->understandCropProfileContext(
                $input,
                $originalQuestion,
                $cropId,
                $cropName,
                $scientificNameInput,
                $explicitDomain,
                $location,
                $constraints,
            );
        }

        $language = $this->detectLanguage($originalQuestion);
        $normalizedQuestion = $this->normalizeQuestion($originalQuestion);
        $topicFactors = AgriculturalEntityCatalog::extractTopicFactors($normalizedQuestion);
        if ($topicFactors !== []) {
            $constraints['scientific_factors'] = $topicFactors;
            $constraints['scientific_topics'] = AgriculturalEntityCatalog::englishLabelsForFactors($topicFactors);
        }

        $researchIntent = $this->detectResearchIntent($normalizedQuestion, $input, $topicFactors);
        $agriculturalDomain = $this->detectDomain($normalizedQuestion, $explicitDomain, $researchIntent);
        $cropRecognition = AgriculturalEntityCatalog::recognizeCrop($normalizedQuestion);
        $subject = $this->detectSubject($normalizedQuestion, $input, $cropRecognition, $researchIntent);

        $cropIdResolved = is_array($cropRecognition) ? $cropRecognition['crop_id'] : null;
        $cropLabel = is_array($cropRecognition) ? ($cropRecognition['label'] ?? null) : null;
        $scientificName = $this->resolveScientificName($cropIdResolved, $scientificNameInput, $normalizedQuestion);

        $intentQualifier = $this->detectIntentQualifier($normalizedQuestion);
        $scientificSense = $this->resolveScientificSense($researchIntent, $topicFactors, $normalizedQuestion);
        $domainBranch = $this->resolveDomainBranch($researchIntent, $scientificSense, $agriculturalDomain);
        $constraints['scientific_intent_qualifier'] = $intentQualifier;
        $constraints['scientific_sense'] = $scientificSense;
        $constraints['scientific_domain_branch'] = $domainBranch;

        [$topic, $subtopic] = $this->resolveTopicAndSubtopic($researchIntent, $topicFactors, $subject);
        $requestedInformation = $this->resolveRequestedInformation($researchIntent, $normalizedQuestion, $topicFactors);
        $clarificationRequirements = [];
        $ambiguityState = AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR;
        $hasExplicitEntities = is_array($input['entities'] ?? null) && $input['entities'] !== [];

        if ($this->isVagueFertilizerQuestion($normalizedQuestion, $cropRecognition !== null) && ! $hasExplicitEntities) {
            $ambiguityState = AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION;
            $clarificationRequirements = array_values(array_unique(array_merge(
                $clarificationRequirements,
                ['crop_or_crop_system', 'soil_context', 'production_target', 'fertilizer_type'],
            )));
        } elseif ($this->isVaguePlantDiseaseQuestion($normalizedQuestion)) {
            $ambiguityState = AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION;
            $clarificationRequirements = array_values(array_unique(array_merge(
                $clarificationRequirements,
                ['affected_crop_or_plant', 'disease_symptoms', 'location_or_growth_stage'],
            )));
        } elseif ($normalizedQuestion === '' || mb_strlen($normalizedQuestion) < 8) {
            $ambiguityState = AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION;
            $clarificationRequirements[] = 'specific_agricultural_question';
        } elseif ($cropIdResolved === null && $this->intentRequiresEntity($researchIntent) && $subject === null) {
            $ambiguityState = AgriculturalKnowledgeQuery::AMBIGUITY_PARTIALLY_AMBIGUOUS;
            $clarificationRequirements[] = 'subject_or_entity';
        }

        $researchRequired = $ambiguityState !== AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION;

        return new AgriculturalKnowledgeQuery(
            originalQuestion: $originalQuestion,
            normalizedQuestion: $normalizedQuestion !== '' ? $normalizedQuestion : $originalQuestion,
            language: $language,
            agriculturalDomain: $agriculturalDomain,
            subject: $subject,
            crop: $cropLabel,
            cropId: $cropIdResolved,
            scientificName: $scientificName,
            topic: $topic,
            subtopic: $subtopic,
            requestedInformation: $requestedInformation,
            constraints: $constraints,
            location: $location,
            researchRequired: $researchRequired,
            ambiguityState: $ambiguityState,
            clarificationRequirements: $clarificationRequirements,
            researchIntent: $researchIntent,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $constraints
     */
    private function understandCropProfileContext(
        array $input,
        string $originalQuestion,
        string $cropId,
        string $cropName,
        string $scientificNameInput,
        string $explicitDomain,
        ?string $location,
        array $constraints,
    ): AgriculturalKnowledgeQuery {
        $knowledgeOption = trim((string) ($input['knowledge_option'] ?? $input['service_option'] ?? 'farming-needs'));
        $language = $this->detectLanguage($cropName !== '' ? $cropName : $originalQuestion);
        $normalizedQuestion = $originalQuestion !== ''
            ? $this->normalizeQuestion($originalQuestion)
            : $this->normalizeQuestion(sprintf('%s %s', $cropName, $knowledgeOption));

        $topicFactors = AgriculturalEntityCatalog::extractTopicFactors($normalizedQuestion);
        if ($topicFactors !== []) {
            $constraints['scientific_factors'] = $topicFactors;
            $constraints['scientific_topics'] = AgriculturalEntityCatalog::englishLabelsForFactors($topicFactors);
        }

        $researchIntent = match ($knowledgeOption) {
            'scientific-research' => 'scientific_literature',
            'industries' => 'agricultural_industry',
            default => 'cultivation',
        };

        if ($topicFactors !== []) {
            foreach ($topicFactors as $factor) {
                $mapped = AgriculturalEntityCatalog::intentForTopicFactor($factor);
                if ($mapped !== null) {
                    $researchIntent = $mapped;
                    break;
                }
            }
        }

        $agriculturalDomain = $explicitDomain !== ''
            ? AgriculturalDomainCatalog::normalize($explicitDomain)
            : match ($knowledgeOption) {
                'scientific-research' => AgriculturalDomainCatalog::AGRICULTURAL_RESEARCH,
                'industries' => AgriculturalDomainCatalog::AGRICULTURAL_INDUSTRIES,
                default => AgriculturalDomainCatalog::FIELD_CROPS,
            };

        $scientificName = FieldCropTaxonomyCatalog::resolveScientificName($cropId, $scientificNameInput);
        $intentQualifier = $this->detectIntentQualifier($normalizedQuestion);
        $scientificSense = $this->resolveScientificSense($researchIntent, $topicFactors, $normalizedQuestion);
        $constraints['scientific_intent_qualifier'] = $intentQualifier;
        $constraints['scientific_sense'] = $scientificSense;
        $constraints['scientific_domain_branch'] = $this->resolveDomainBranch(
            $researchIntent,
            $scientificSense,
            $agriculturalDomain,
        );

        return new AgriculturalKnowledgeQuery(
            originalQuestion: $originalQuestion !== '' ? $originalQuestion : $normalizedQuestion,
            normalizedQuestion: $normalizedQuestion,
            language: $language,
            agriculturalDomain: $agriculturalDomain,
            subject: ['type' => 'crop', 'value' => $cropId, 'label' => $cropName],
            crop: $cropName,
            cropId: $cropId,
            scientificName: $scientificName !== '' ? $scientificName : null,
            topic: $researchIntent,
            subtopic: $knowledgeOption,
            requestedInformation: [$researchIntent, 'verified_evidence'],
            constraints: $constraints,
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $researchIntent,
        );
    }

    private function normalizeQuestion(string $question): string
    {
        $normalized = mb_strtolower(trim($question));
        $normalized = preg_replace('/[^\p{L}\p{N}\s\-×]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function detectLanguage(string $text): string
    {
        if ($text === '') {
            return 'und';
        }

        return preg_match('/\p{Arabic}/u', $text) === 1 ? 'ar' : 'en';
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $topicFactors
     */
    private function detectResearchIntent(string $normalizedQuestion, array $input, array $topicFactors = []): string
    {
        $explicitIntent = trim((string) ($input['research_intent'] ?? $input['intent'] ?? ''));
        if ($explicitIntent !== '' && in_array($explicitIntent, AgriculturalEntityCatalog::researchIntents(), true)) {
            return $explicitIntent;
        }

        $bestIntent = 'general_knowledge';
        $bestScore = 0;

        foreach (AgriculturalEntityCatalog::intentKeywordSignals() as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (AgriculturalEntityCatalog::containsTerm($normalizedQuestion, $keyword)) {
                    $score += mb_strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIntent = $intent;
            }
        }

        foreach ($topicFactors as $factor) {
            $mapped = AgriculturalEntityCatalog::intentForTopicFactor($factor);
            if ($mapped === null) {
                continue;
            }
            // Prefer post-harvest / industry senses when the question names them explicitly.
            $factorScore = in_array($factor, ['drying', 'storage', 'extraction'], true)
                ? 80 + mb_strlen($factor)
                : 40 + mb_strlen($factor);
            if ($factorScore > $bestScore || ($bestIntent === 'general_knowledge' && $factorScore >= $bestScore)) {
                $bestScore = $factorScore;
                $bestIntent = $mapped;
            }
        }

        return $bestIntent;
    }

    private function detectDomain(string $normalizedQuestion, string $explicitDomain, string $researchIntent): string
    {
        if ($explicitDomain !== '') {
            return AgriculturalDomainCatalog::normalize($explicitDomain);
        }

        $intentDomainMap = [
            'irrigation' => AgriculturalDomainCatalog::IRRIGATION_WATER,
            'fertilization' => AgriculturalDomainCatalog::FERTILIZATION,
            'soil_management' => AgriculturalDomainCatalog::SOIL,
            'plant_nutrition' => AgriculturalDomainCatalog::PLANT_NUTRITION,
            'disease' => AgriculturalDomainCatalog::PESTS_DISEASES,
            'pest' => AgriculturalDomainCatalog::PESTS_DISEASES,
            'beekeeping' => AgriculturalDomainCatalog::BEEKEEPING,
            'aquaculture' => AgriculturalDomainCatalog::AQUACULTURE,
            'poultry_production' => AgriculturalDomainCatalog::POULTRY,
            'animal_production' => AgriculturalDomainCatalog::ANIMAL_PRODUCTION,
            'feed' => AgriculturalDomainCatalog::ANIMAL_PRODUCTION,
            'agricultural_economics' => AgriculturalDomainCatalog::AGRICULTURAL_ECONOMICS,
            'agricultural_industry' => AgriculturalDomainCatalog::AGRICULTURAL_INDUSTRIES,
            'scientific_literature' => AgriculturalDomainCatalog::AGRICULTURAL_RESEARCH,
            'cultivation' => AgriculturalDomainCatalog::FIELD_CROPS,
            'environmental_requirements' => AgriculturalDomainCatalog::FIELD_CROPS,
        ];

        $intentDomain = $intentDomainMap[$researchIntent] ?? AgriculturalDomainCatalog::GENERAL_AGRICULTURE;
        $operationalIntents = [
            'irrigation', 'fertilization', 'soil_management', 'plant_nutrition',
            'disease', 'pest', 'beekeeping', 'aquaculture', 'poultry_production',
            'animal_production', 'feed', 'agricultural_economics', 'agricultural_industry',
            'scientific_literature', 'environmental_requirements',
        ];

        if (in_array($researchIntent, $operationalIntents, true)) {
            return $intentDomain;
        }

        $bestDomain = $intentDomain;
        $bestScore = 0;

        foreach (AgriculturalDomainCatalog::keywordSignals() as $candidate => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (AgriculturalEntityCatalog::containsTerm($normalizedQuestion, $keyword)) {
                    $score += mb_strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDomain = $candidate;
            }
        }

        return $bestDomain;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array{crop_id: string, label: string}|null  $cropRecognition
     * @return array{type: string, value: string, label?: string}|null
     */
    private function detectSubject(
        string $normalizedQuestion,
        array $input,
        ?array $cropRecognition,
        string $researchIntent,
    ): ?array {
        $rawEntities = $input['entities'] ?? [];
        if (is_array($rawEntities)) {
            foreach ($rawEntities as $entity) {
                if (! is_array($entity)) {
                    continue;
                }
                $type = trim((string) ($entity['type'] ?? ''));
                $value = trim((string) ($entity['value'] ?? ''));
                if ($type !== '' && $value !== '') {
                    return [
                        'type' => $type,
                        'value' => $value,
                        'label' => (string) ($entity['label'] ?? $value),
                    ];
                }
            }
        }

        if ($cropRecognition !== null) {
            return [
                'type' => 'crop',
                'value' => $cropRecognition['crop_id'],
                'label' => $cropRecognition['label'],
            ];
        }

        if (AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'soil') || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'تربة')) {
            return ['type' => 'soil', 'value' => 'soil', 'label' => 'soil'];
        }

        if (in_array($researchIntent, ['beekeeping'], true)) {
            return ['type' => 'production_system', 'value' => 'beekeeping', 'label' => 'beekeeping'];
        }

        if (in_array($researchIntent, ['aquaculture'], true)) {
            return ['type' => 'fish', 'value' => 'aquaculture', 'label' => 'aquaculture'];
        }

        if (in_array($researchIntent, ['poultry_production'], true)) {
            return ['type' => 'animal', 'value' => 'poultry', 'label' => 'poultry'];
        }

        if (in_array($researchIntent, ['animal_production', 'feed'], true)) {
            return ['type' => 'animal', 'value' => 'livestock', 'label' => 'livestock'];
        }

        if (in_array($researchIntent, ['scientific_literature', 'agricultural_economics', 'agricultural_industry'], true)) {
            return ['type' => 'research_topic', 'value' => $researchIntent, 'label' => $researchIntent];
        }

        return null;
    }

    private function resolveScientificName(?string $cropId, string $provided, string $normalizedQuestion): ?string
    {
        if ($provided !== '') {
            return $provided;
        }

        if ($cropId !== null) {
            $resolved = FieldCropTaxonomyCatalog::scientificNameFor($cropId);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        if (preg_match('/\b([A-Z][a-z]+(?:\s+[a-z]+)+)\b/u', $normalizedQuestion, $matches) === 1) {
            return trim($matches[1]);
        }

        // Scientific names are lowercased by normalizeQuestion — recover binomial patterns.
        if (preg_match('/\b([a-z]+)\s+(officinale|aestivum|mays|sativum|annuum|lycopersicum|vulgare)\b/u', $normalizedQuestion, $matches) === 1) {
            return ucfirst($matches[1]).' '.$matches[2];
        }

        return null;
    }

    /**
     * @param  list<string>  $topicFactors
     * @param  array{type: string, value: string, label?: string}|null  $subject
     * @return array{0: string, 1: string|null}
     */
    private function resolveTopicAndSubtopic(string $researchIntent, array $topicFactors, ?array $subject): array
    {
        $primaryTopic = $topicFactors[0] ?? $researchIntent;
        $subtopic = $topicFactors[1] ?? (is_array($subject) ? (string) ($subject['value'] ?? null) : null);

        return [$primaryTopic, $subtopic];
    }

    /**
     * @param  list<string>  $topicFactors
     * @return list<string>
     */
    private function resolveRequestedInformation(string $researchIntent, string $normalizedQuestion, array $topicFactors): array
    {
        $requested = [$researchIntent, 'evidence_backed_guidance'];
        foreach (AgriculturalEntityCatalog::englishLabelsForFactors($topicFactors) as $label) {
            $requested[] = $label;
        }

        if (str_contains($normalizedQuestion, 'best') || str_contains($normalizedQuestion, 'أفضل')) {
            $requested[] = 'recommendations';
        }

        return array_values(array_unique($requested));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function extractConstraints(array $input): array
    {
        $constraints = [];
        $raw = $input['constraints'] ?? null;
        if (is_array($raw)) {
            $constraints = $raw;
        }

        foreach (['season', 'climate_zone', 'production_system'] as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value !== '') {
                $constraints[$key] = $value;
            }
        }

        return $constraints;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function extractLocation(array $input, string $question): ?string
    {
        $explicit = trim((string) ($input['location'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        if (preg_match('/\b(in|at|near)\s+([a-z\s]{3,40})/i', $question, $matches) === 1) {
            return trim($matches[2]);
        }

        return null;
    }

    private function isVagueFertilizerQuestion(string $normalizedQuestion, bool $hasRecognizedCrop = false): bool
    {
        $hasFertilizer = AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'fertilizer')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'fertilisation')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'fertilization')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'سماد')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'تسميد')
            || str_contains($normalizedQuestion, 'best fertil')
            || str_contains($normalizedQuestion, 'أفضل سماد');

        if (! $hasFertilizer) {
            return false;
        }

        $hasCrop = $hasRecognizedCrop || AgriculturalEntityCatalog::recognizeCrop($normalizedQuestion) !== null;

        return ! $hasCrop && mb_strlen($normalizedQuestion) < 48;
    }

    private function isVaguePlantDiseaseQuestion(string $normalizedQuestion): bool
    {
        $hasDisease = AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'disease')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'مرض')
            || str_contains($normalizedQuestion, 'plant disease')
            || str_contains($normalizedQuestion, 'مرض في النبات');

        if (! $hasDisease) {
            return false;
        }

        $hasSpecifics = AgriculturalEntityCatalog::recognizeCrop($normalizedQuestion) !== null
            || preg_match('/\b(mildew|rust|blight|wilt|virus|fungus|bacteria)\b/u', $normalizedQuestion) === 1;

        return ! $hasSpecifics;
    }

    private function intentRequiresEntity(string $researchIntent): bool
    {
        return in_array($researchIntent, [
            'cultivation',
            'environmental_requirements',
            'fertilization',
            'irrigation',
            'disease',
            'pest',
            'varieties',
            'plant_nutrition',
        ], true);
    }

    private function detectIntentQualifier(string $normalizedQuestion): string
    {
        $best = 'general';
        $bestScore = 0;

        foreach (AgriculturalEntityCatalog::intentQualifierSignals() as $qualifier => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (AgriculturalEntityCatalog::containsTerm($normalizedQuestion, $keyword)) {
                    $score += mb_strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $qualifier;
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $topicFactors
     */
    private function resolveScientificSense(string $researchIntent, array $topicFactors, string $normalizedQuestion): string
    {
        if (in_array('germination', $topicFactors, true)) {
            return 'seed_germination';
        }
        if (in_array('drying', $topicFactors, true)) {
            return 'drying_processing';
        }
        if (in_array('storage', $topicFactors, true)) {
            return 'storage';
        }
        // Extension/adoption overrides irrigation keyword noise (e.g. "adoption of irrigation").
        if (AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'إرشاد')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'extension')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'تبني')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'adoption')) {
            return 'agricultural_extension';
        }
        if ($researchIntent === 'agricultural_economics'
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'جدوى')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'اقتصاد زراعي')
            || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'اقتصادية')) {
            return 'agricultural_economics';
        }
        if (in_array('salinity', $topicFactors, true)) {
            return 'salinity_physiology';
        }
        if (in_array('water', $topicFactors, true) || $researchIntent === 'irrigation') {
            return 'crop_water_requirement';
        }
        if (in_array($researchIntent, ['plant_nutrition'], true)
            || in_array('potassium', $topicFactors, true)
            || in_array('nitrogen', $topicFactors, true)
            || in_array('phosphorus', $topicFactors, true)) {
            return 'plant_nutrition';
        }
        if ($researchIntent === 'agricultural_industry') {
            return 'agricultural_industry';
        }
        if (in_array($researchIntent, ['environmental_requirements', 'cultivation', 'productivity'], true)
            || in_array('temperature', $topicFactors, true)) {
            return 'plant_growth';
        }

        return $researchIntent !== '' ? $researchIntent : 'general';
    }

    private function resolveDomainBranch(string $researchIntent, string $scientificSense, string $agriculturalDomain): string
    {
        return match (true) {
            $scientificSense === 'seed_germination',
            $scientificSense === 'plant_growth',
            $scientificSense === 'salinity_physiology' => 'plant_physiology',
            $scientificSense === 'crop_water_requirement' => 'irrigation_agronomy',
            $scientificSense === 'plant_nutrition' => 'plant_nutrition',
            $scientificSense === 'drying_processing',
            $scientificSense === 'storage',
            $scientificSense === 'agricultural_industry' => 'food_science',
            $scientificSense === 'agricultural_economics',
            $researchIntent === 'agricultural_economics' => 'agricultural_economics',
            $scientificSense === 'agricultural_extension' => 'agricultural_extension',
            default => $agriculturalDomain !== '' ? $agriculturalDomain : 'crop_science',
        };
    }
}
