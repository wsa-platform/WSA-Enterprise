<?php

namespace App\Services\Agriculture\Research;

use App\Http\Middleware\SetLocaleFromHeader;
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

        // Home Free Question only — Crop Page returned above.
        $location = $this->extractHomeLocation($input, $originalQuestion);
        $language = $this->detectLanguage($originalQuestion);
        $normalizedQuestion = $this->normalizeQuestion($originalQuestion);
        $topicFactors = array_values(array_unique(array_merge(
            AgriculturalEntityCatalog::extractTopicFactors($normalizedQuestion),
            AgriculturalEntityCatalog::extractHomeMultilingualTopicFactors($normalizedQuestion),
        )));
        $environmentalConstraints = AgriculturalEntityCatalog::extractEnvironmentalConstraints($normalizedQuestion);
        $factorRoles = [];
        foreach ($topicFactors as $factor) {
            $factorRoles[$factor] = AgriculturalEntityCatalog::topicFactorRole(
                $normalizedQuestion,
                $factor,
                $environmentalConstraints,
            );
        }
        $requestedFactors = [];
        foreach ($topicFactors as $factor) {
            if (($factorRoles[$factor] ?? 'requested') !== 'constraint') {
                $requestedFactors[] = $factor;
            }
        }
        if ($topicFactors !== []) {
            $constraints['scientific_factors'] = $topicFactors;
            $constraints['scientific_factor_roles'] = $factorRoles;
            $constraints['scientific_topics'] = AgriculturalEntityCatalog::englishLabelsForFactors($requestedFactors);
        }
        if ($environmentalConstraints !== []) {
            $constraints['environmental_constraints'] = $environmentalConstraints;
        }

        $recognizedCrops = AgriculturalEntityCatalog::recognizeHomeMultilingualCrops($normalizedQuestion);
        $cropRecognition = $recognizedCrops[0] ?? AgriculturalEntityCatalog::recognizeCrop($normalizedQuestion);
        if (count($recognizedCrops) >= 2) {
            $constraints['is_comparison'] = true;
            $constraints['comparison_entities'] = array_values(array_map(
                static fn (array $crop): array => [
                    'crop_id' => $crop['crop_id'],
                    'label' => $crop['label'],
                    'category' => $crop['category'] ?? null,
                ],
                $recognizedCrops,
            ));
        }
        $intentQualifier = $this->detectIntentQualifier($normalizedQuestion);
        $researchIntent = $this->detectResearchIntent(
            $normalizedQuestion,
            $input,
            $requestedFactors,
            $factorRoles,
            $cropRecognition,
            $intentQualifier,
            $environmentalConstraints,
        );
        $agriculturalDomain = $this->detectDomain($normalizedQuestion, $explicitDomain, $researchIntent);
        $subject = $this->detectSubject($normalizedQuestion, $input, $cropRecognition, $researchIntent);
        [$researchIntent, $agriculturalDomain] = $this->alignLivestockIntentAndDomain(
            $researchIntent,
            $agriculturalDomain,
            $subject,
        );

        $cropIdResolved = is_array($cropRecognition) ? $cropRecognition['crop_id'] : null;
        $cropLabel = is_array($cropRecognition) ? ($cropRecognition['label'] ?? null) : null;
        $scientificName = $this->resolveScientificName($cropIdResolved, $scientificNameInput, $normalizedQuestion);
        $entityCategory = is_array($cropRecognition)
            ? (string) ($cropRecognition['category'] ?? FieldCropTaxonomyCatalog::categoryFor((string) $cropIdResolved))
            : null;

        $scientificSense = $this->resolveScientificSense(
            $researchIntent,
            $topicFactors,
            $normalizedQuestion,
            $factorRoles,
            $intentQualifier,
        );
        // Home: crop soil-suitability must not collapse to bare land inventory.
        if ($cropIdResolved !== null
            && AgriculturalEntityCatalog::asksHomeCropSoilSuitability($normalizedQuestion)) {
            $scientificSense = 'plant_growth';
            $researchIntent = 'cultivation';
            $topicFactors = array_values(array_unique(array_merge($topicFactors, ['soil'])));
            $constraints['scientific_factors'] = $topicFactors;
            $constraints['scientific_topics'] = AgriculturalEntityCatalog::englishLabelsForFactors(
                array_values(array_filter(
                    $topicFactors,
                    static fn ($f): bool => ($factorRoles[$f] ?? 'requested') !== 'constraint',
                )),
            );
        }
        $constraints['scientific_intent_qualifier'] = $intentQualifier;
        $constraints['scientific_sense'] = $scientificSense;

        if ($scientificSense === 'land_classification') {
            // Preserve land-classification intent — do not leave AR "زراعة" inside "الزراعية"
            // (or EN agriculture substring) collapsed to cultivation/general_knowledge.
            $researchIntent = 'land_classification';
            $topics = is_array($constraints['scientific_topics'] ?? null) ? $constraints['scientific_topics'] : [];
            // Prefer inventory topics ahead of any residual cultivation/factor noise.
            $topics = array_values(array_filter(
                $topics,
                static fn ($topic): bool => ! in_array(mb_strtolower(trim((string) $topic)), [
                    'cultivation', 'crop production', 'agriculture',
                ], true),
            ));
            foreach (['land types', 'soil classification', 'land classification'] as $landTopic) {
                if (! in_array($landTopic, $topics, true)) {
                    $topics[] = $landTopic;
                }
            }
            $constraints['scientific_topics'] = $topics;
            if ($cropIdResolved === null) {
                $subject = [
                    'type' => 'land',
                    'value' => 'agricultural_land',
                    'label' => 'agricultural land',
                ];
            }
            if ($agriculturalDomain === AgriculturalDomainCatalog::GENERAL_AGRICULTURE
                || $agriculturalDomain === AgriculturalDomainCatalog::FIELD_CROPS) {
                $agriculturalDomain = AgriculturalDomainCatalog::SOIL;
            }
        }

        // Botanical-family subjects: propagate entity + sense + inventory topics so
        // validation receives matchable signals (QueryBuilder already searches families).
        if (is_array($subject) && ($subject['type'] ?? '') === 'plant_family') {
            $familyValue = trim((string) ($subject['value'] ?? $subject['label'] ?? ''));
            $researchIntent = 'plant_family_members';
            $scientificSense = 'plant_family_members';
            $constraints['scientific_sense'] = $scientificSense;
            $topics = is_array($constraints['scientific_topics'] ?? null) ? $constraints['scientific_topics'] : [];
            $topics = array_values(array_filter(
                $topics,
                static fn ($topic): bool => ! in_array(mb_strtolower(trim((string) $topic)), [
                    'cultivation', 'crop production', 'agriculture', 'farming',
                ], true),
            ));
            foreach (['family members', 'species', 'botanical classification', 'taxonomy'] as $familyTopic) {
                if (! in_array($familyTopic, $topics, true)) {
                    $topics[] = $familyTopic;
                }
            }
            if ($familyValue !== '' && ! in_array($familyValue, $topics, true)) {
                array_unshift($topics, $familyValue);
            }
            $constraints['scientific_topics'] = array_values(array_unique($topics));
            if ($agriculturalDomain === AgriculturalDomainCatalog::GENERAL_AGRICULTURE) {
                $agriculturalDomain = AgriculturalDomainCatalog::FIELD_CROPS;
            }
        }

        if ($scientificSense === 'planting_timing') {
            $topics = is_array($constraints['scientific_topics'] ?? null) ? $constraints['scientific_topics'] : [];
            $topics = array_values(array_filter(
                $topics,
                static fn ($topic): bool => ! in_array(mb_strtolower(trim((string) $topic)), [
                    'growth', 'physiology', 'plant growth',
                ], true),
            ));
            foreach (['planting date', 'sowing date', 'planting season'] as $timingTopic) {
                if (! in_array($timingTopic, $topics, true)) {
                    array_unshift($topics, $timingTopic);
                }
            }
            $constraints['scientific_topics'] = array_values(array_unique($topics));
        }

        $domainBranch = $this->resolveDomainBranch($researchIntent, $scientificSense, $agriculturalDomain);
        $constraints['scientific_domain_branch'] = $domainBranch;

        $productionSystem = $this->detectProductionSystem($normalizedQuestion);
        if ($productionSystem !== null) {
            $constraints['production_system'] = $productionSystem;
            $topics = is_array($constraints['scientific_topics'] ?? null) ? $constraints['scientific_topics'] : [];
            if (! in_array($productionSystem, $topics, true)) {
                $topics[] = $productionSystem;
                $constraints['scientific_topics'] = $topics;
            }
            if ($subject === null) {
                $subject = [
                    'type' => 'production_system',
                    'value' => $productionSystem,
                    'label' => $productionSystem,
                ];
            }
            if ($productionSystem === 'hydroponics'
                && ($researchIntent === 'general_knowledge' || $researchIntent === 'cultivation')) {
                // Keep cultivation intent but ensure hydroponics is first-class topic.
                $researchIntent = 'cultivation';
            }
        }

        if ($location !== null) {
            $constraints['location'] = $location;
        }

        [$topic, $subtopic] = $this->resolveTopicAndSubtopic($researchIntent, $requestedFactors, $subject);
        if ($scientificSense === 'land_classification') {
            $topic = 'land classification';
            if ($subtopic === null || $subtopic === 'cultivation' || $subtopic === 'agricultural_land') {
                $subtopic = 'classification_inventory';
            }
        }
        if ($scientificSense === 'plant_family_members') {
            $topic = 'plant family members';
            if ($subtopic === null || $subtopic === 'general_knowledge' || $subtopic === 'cultivation') {
                $subtopic = is_array($subject) ? (string) ($subject['value'] ?? 'family_members') : 'family_members';
            }
        }
        $questionType = $this->detectQuestionType(
            $normalizedQuestion,
            $originalQuestion,
            $scientificSense,
            $intentQualifier,
            $researchIntent,
        );
        if (! empty($constraints['is_comparison'])
            && ! in_array($questionType, ['causes', 'range'], true)) {
            $questionType = 'comparison';
        }
        $requestedInformation = $this->resolveRequestedInformation(
            $researchIntent,
            $normalizedQuestion,
            $requestedFactors,
            $questionType,
            $scientificSense,
        );
        foreach ($environmentalConstraints as $constraint) {
            $type = trim((string) ($constraint['type'] ?? ''));
            if ($type !== '' && ! in_array($type, $requestedInformation, true)) {
                $requestedInformation[] = $type;
            }
        }
        $requiredEvidenceType = AgriculturalEntityCatalog::requiredEvidenceTypeForQuestionType($questionType);
        [$constraints, $subject, $propertyTerms, $topic] = $this->applySemanticTargetBaseline(
            $normalizedQuestion,
            $constraints,
            $subject,
            $questionType,
            $topic,
        );
        $this->applyHomeSemanticPropertyContract(
            $constraints,
            $normalizedQuestion,
            $questionType,
            $scientificSense,
            $researchIntent,
            $cropIdResolved,
            $topicFactors,
        );
        if ($location !== null && $this->isHomeUnsafeLocationCandidate($location)) {
            $location = null;
            unset($constraints['location']);
        }
        if (AgriculturalEntityCatalog::asksCausalAffectQuestion($normalizedQuestion)) {
            $constraints['is_causal'] = true;
            $causalSpans = AgriculturalEntityCatalog::causalArgumentSpans($normalizedQuestion);
            if (is_array($causalSpans)) {
                $constraints['causal_affector'] = $causalSpans['affector'];
                $constraints['causal_target'] = $causalSpans['target'];
            }
        }
        $constraints['question_type'] = $questionType;
        $constraints['requested_information'] = $requestedInformation;
        $constraints['required_evidence_type'] = $requiredEvidenceType;
        $constraints['required_evidence_characteristics'] = AgriculturalEntityCatalog::requiredEvidenceCharacteristics(
            $requiredEvidenceType,
        );
        $negativeConstraints = AgriculturalEntityCatalog::negativeConstraintsForEvidenceType($requiredEvidenceType);
        $constraints['negative_constraints'] = $negativeConstraints;
        $constraints['exclusions'] = $negativeConstraints;
        $this->applyQuestionTypeKnowledgeTargets(
            $constraints,
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
        );
        $constraints['question_language'] = $language;
        // R2: answer language follows question language (UI locale is independent).
        $constraints['answer_language'] = $this->resolveAnswerLanguageFromQuestion($language);
        $constraints['time_context'] = $this->detectTimeContext($normalizedQuestion, $originalQuestion);
        $explicitYear = $this->extractExplicitCalendarYear($normalizedQuestion, $originalQuestion);
        if ($explicitYear !== null) {
            $constraints['year'] = $explicitYear;
        }

        if ($propertyTerms !== []) {
            $researchIntent = $this->refineResearchIntentFromRequestedProperty(
                $researchIntent,
                $constraints,
                $factorRoles,
                $normalizedQuestion,
                $scientificSense,
            );
        }
        [$researchIntent, $agriculturalDomain] = $this->alignLivestockIntentAndDomain(
            $researchIntent,
            $agriculturalDomain,
            $subject,
        );
        if (is_array($subject) && ($subject['type'] ?? '') === 'animal'
            && in_array($scientificSense, ['varieties', 'general_knowledge', 'general', ''], true)) {
            $scientificSense = $researchIntent;
            $constraints['scientific_sense'] = $scientificSense;
        }

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
        } elseif ($cropIdResolved === null
            && ! AgriculturalEntityCatalog::asksCausalAffectQuestion($normalizedQuestion)
            && $this->intentRequiresNamedEntity($researchIntent, $subject)) {
            $ambiguityState = AgriculturalKnowledgeQuery::AMBIGUITY_PARTIALLY_AMBIGUOUS;
            $clarificationRequirements[] = 'subject_or_entity';
        }

        $constraints['understanding_confidence'] = match ($ambiguityState) {
            AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR => 0.9,
            AgriculturalKnowledgeQuery::AMBIGUITY_PARTIALLY_AMBIGUOUS => 0.6,
            default => 0.3,
        };
        $constraints['primary_user_act'] = $researchIntent;

        $namedEntityState = 'none';
        $namedEntitySurface = null;
        $subjectType = is_array($subject) ? (string) ($subject['type'] ?? '') : '';
        if ($cropIdResolved !== null) {
            $namedEntityState = 'resolved';
            $namedEntitySurface = is_string($cropLabel) && $cropLabel !== '' ? $cropLabel : $cropIdResolved;
            $entityCategory = $entityCategory ?: FieldCropTaxonomyCatalog::categoryFor($cropIdResolved);
        } elseif (in_array($subjectType, ['crop', 'named_entity', 'animal', 'plant_family'], true)) {
            $namedEntityState = (($subject['resolution'] ?? '') === 'unresolved') ? 'unresolved' : 'resolved';
            $namedEntitySurface = trim((string) ($subject['label'] ?? $subject['value'] ?? ''));
            $namedEntitySurface = $namedEntitySurface !== '' ? $namedEntitySurface : null;
        }
        $entityDependent = $namedEntityState !== 'none';
        $constraints['entity_dependent'] = $entityDependent;
        $constraints['named_entity_state'] = $namedEntityState;
        $constraints['named_entity_surface'] = $namedEntitySurface;
        $constraints['entity_category'] = $entityCategory;
        $this->retainHomeRequestedVersusResolvedEntity(
            $constraints,
            $originalQuestion,
            $normalizedQuestion,
            $subject,
            $cropIdResolved,
            $namedEntitySurface,
            is_string($cropLabel) ? $cropLabel : null,
        );

        $researchRequired = $ambiguityState !== AgriculturalKnowledgeQuery::AMBIGUITY_NEEDS_CLARIFICATION;

        $crop = is_string($cropLabel) && $cropLabel !== ''
            ? $cropLabel
            : $namedEntitySurface;

        return new AgriculturalKnowledgeQuery(
            originalQuestion: $originalQuestion,
            normalizedQuestion: $normalizedQuestion !== '' ? $normalizedQuestion : $originalQuestion,
            language: $language,
            agriculturalDomain: $agriculturalDomain,
            subject: $subject,
            crop: $crop,
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
     * Baseline: entity/property semantic target and requested-property query terms.
     *
     * @param  array<string, mixed>  $constraints
     * @param  array{type?: string, value?: string, label?: string, resolution?: string}|null  $subject
     * @return array{0: array<string, mixed>, 1: array{type?: string, value?: string, label?: string, resolution?: string}|null, 2: list<string>, 3: string}
     */
    private function applySemanticTargetBaseline(
        string $normalizedQuestion,
        array $constraints,
        ?array $subject,
        string $questionType,
        string $topic,
    ): array {
        $semanticTarget = AgriculturalEntityCatalog::extractSemanticTarget($normalizedQuestion);
        if (is_array($semanticTarget)) {
            if ($subject === null && trim((string) ($semanticTarget['entity_surface'] ?? '')) !== ''
                && AgriculturalEntityCatalog::isDistinctiveNamedEntitySurface((string) $semanticTarget['entity_surface'])
                && ! AgriculturalEntityCatalog::isLocationAliasToken((string) $semanticTarget['entity_surface'])
                && ! AgriculturalEntityCatalog::isUnsafeResidualEntitySurface((string) $semanticTarget['entity_surface'])) {
                $subject = [
                    'type' => 'named_entity',
                    'value' => (string) ($semanticTarget['entity_normalized'] ?? $semanticTarget['entity_surface']),
                    'label' => (string) $semanticTarget['entity_surface'],
                    'resolution' => 'unresolved',
                ];
            }
            $propertyKey = trim((string) ($semanticTarget['property_key'] ?? ''));
            $propertySurface = trim((string) ($semanticTarget['property_surface'] ?? ''));
            if ($propertyKey !== '') {
                $constraints['requested_property'] = $propertyKey;
            } elseif ($questionType !== '' && ! in_array($questionType, ['general', 'definition', 'comparison'], true)) {
                $constraints['requested_property'] = $questionType;
            }
            if ($propertySurface !== '') {
                $constraints['requested_property_surface'] = $propertySurface;
            }
            $constraints['semantic_target'] = $semanticTarget;
            $causalSpans = AgriculturalEntityCatalog::causalArgumentSpans($normalizedQuestion);
            if (is_array($causalSpans)) {
                $constraints['causal_affector'] = $causalSpans['affector'];
                $constraints['causal_target'] = $causalSpans['target'];
                $constraints['is_causal'] = true;
            }
        } elseif ($questionType !== '' && ! in_array($questionType, ['general', 'definition', 'comparison'], true)) {
            $constraints['requested_property'] = $questionType;
        }

        $propertyTerms = AgriculturalEntityCatalog::requestedPropertyQueryTerms(
            is_array($semanticTarget) ? $semanticTarget : [
                'property_key' => (string) ($constraints['requested_property'] ?? ''),
                'property_surface' => (string) ($constraints['requested_property_surface'] ?? ''),
            ],
            $questionType,
        );
        if ($propertyTerms !== []) {
            $constraints['requested_property_query_terms'] = $propertyTerms;
            $topics = is_array($constraints['scientific_topics'] ?? null) ? $constraints['scientific_topics'] : [];
            $topics = array_values(array_filter(
                $topics,
                static fn ($item): bool => ! in_array(mb_strtolower(trim((string) $item)), [
                    'general_knowledge', 'agriculture', 'farming', 'general agriculture',
                ], true),
            ));
            $constraints['scientific_topics'] = array_values(array_unique(array_merge($propertyTerms, $topics)));
            $genericTopics = ['general_knowledge', 'agriculture', 'farming', 'general agriculture'];
            if (in_array(mb_strtolower((string) $topic), $genericTopics, true)) {
                $topic = $propertyTerms[0];
            }
        }

        return [$constraints, $subject, $propertyTerms, $topic];
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
        $scientificSense = $this->resolveScientificSense($researchIntent, $topicFactors, $normalizedQuestion, [], $intentQualifier);
        $constraints['scientific_intent_qualifier'] = $intentQualifier;
        $constraints['scientific_sense'] = $scientificSense;
        $constraints['scientific_domain_branch'] = $this->resolveDomainBranch(
            $researchIntent,
            $scientificSense,
            $agriculturalDomain,
        );
        $questionType = $this->detectQuestionType(
            $normalizedQuestion,
            $originalQuestion,
            $scientificSense,
            $intentQualifier,
            $researchIntent,
        );
        $requested = [$researchIntent, 'verified_evidence'];
        $constraints['question_type'] = $questionType;
        $constraints['requested_information'] = $requested;
        $requiredEvidenceType = AgriculturalEntityCatalog::requiredEvidenceTypeForQuestionType($questionType);
        $constraints['required_evidence_type'] = $requiredEvidenceType;
        $constraints['required_evidence_characteristics'] = AgriculturalEntityCatalog::requiredEvidenceCharacteristics(
            $requiredEvidenceType,
        );
        $negativeConstraints = AgriculturalEntityCatalog::negativeConstraintsForEvidenceType($requiredEvidenceType);
        $constraints['negative_constraints'] = $negativeConstraints;
        $constraints['exclusions'] = $negativeConstraints;
        // knowledge_option is only a producer of question_type (e.g. "{crop} farming-needs"
        // → requirements). Knowledge targets come from question semantics, not the option.
        $this->applyQuestionTypeKnowledgeTargets(
            $constraints,
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
        );
        $constraints['question_language'] = $language;
        // R2: answer language follows question language (UI locale is independent).
        $constraints['answer_language'] = $this->resolveAnswerLanguageFromQuestion($language);
        $constraints['understanding_confidence'] = 0.9;
        $constraints['time_context'] = $this->detectTimeContext($normalizedQuestion, $originalQuestion);
        $explicitYear = $this->extractExplicitCalendarYear($normalizedQuestion, $originalQuestion);
        if ($explicitYear !== null) {
            $constraints['year'] = $explicitYear;
        }

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
            requestedInformation: $requested,
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

    /**
     * Stable Arabic orthography fold for sense matching (hamza/alef/ya/ta-marbuta).
     * Does not replace normalizeQuestion output stored on the query.
     */
    private function normalizeArabicOrthography(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        // Tatweel + common Arabic diacritics / Quranic marks.
        $normalized = str_replace("\u{0640}", '', $normalized);
        $normalized = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $normalized) ?? $normalized;
        // Alef with hamza / madda / wasla → bare alef.
        $normalized = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $normalized);
        // Waw/yeh hamza and alif maqsura.
        $normalized = str_replace(['ؤ'], 'و', $normalized);
        $normalized = str_replace(['ئ', 'ى'], 'ي', $normalized);
        // Ta marbuta → ha so تربة/تربه match equivalently.
        $normalized = str_replace('ة', 'ه', $normalized);

        return $normalized;
    }

    private function detectLanguage(string $text): string
    {
        if ($text === '') {
            return 'und';
        }

        if (preg_match('/\p{Arabic}/u', $text) === 1) {
            return 'ar';
        }

        // Turkish-specific letters or common TR question tokens.
        if (preg_match('/[ğüşıöçĞÜŞİÖÇ]/u', $text) === 1
            || preg_match('/\b(nelerdir|nedir|nasıl|hangi|türleri|mısır|misir)\b/iu', $text) === 1) {
            return 'tr';
        }

        // French accents or strong FR question framing.
        if (preg_match('/[àâäéèêëïîôùûüçœæÀÂÄÉÈÊËÏÎÔÙÛÜÇ]/u', $text) === 1
            || preg_match('/\b(quels?|quelles?|sont|terres?\s+agricoles|égypte|egypte|pourquoi|comment)\b/iu', $text) === 1) {
            return 'fr';
        }

        return 'en';
    }

    /**
     * R2: Answer prose follows the question language (not platform UI locale).
     * Supported: ar|en|tr|fr; und/unknown → en.
     */
    private function resolveAnswerLanguageFromQuestion(string $questionLanguage): string
    {
        $language = strtolower(substr(trim($questionLanguage), 0, 2));

        if ($language === 'un') {
            $language = 'und';
        }

        if (in_array($language, SetLocaleFromHeader::SUPPORTED_LOCALES, true)) {
            return $language;
        }

        return 'en';
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $topicFactors  requested-role factors only
     * @param  array<string, string>  $factorRoles
     * @param  array{crop_id: string, label: string}|null  $cropRecognition
     * @param  list<array<string, mixed>>  $environmentalConstraints
     */
    private function detectResearchIntent(
        string $normalizedQuestion,
        array $input,
        array $topicFactors = [],
        array $factorRoles = [],
        ?array $cropRecognition = null,
        string $intentQualifier = 'general',
        array $environmentalConstraints = [],
    ): string {
        $explicitIntent = trim((string) ($input['research_intent'] ?? $input['intent'] ?? ''));
        if ($explicitIntent !== '' && in_array($explicitIntent, AgriculturalEntityCatalog::researchIntents(), true)) {
            return $explicitIntent;
        }

        $bestIntent = 'general_knowledge';
        $bestScore = 0;

        $intentSignals = AgriculturalEntityCatalog::intentKeywordSignals();
        foreach (AgriculturalEntityCatalog::homeIntentKeywordSignals() as $intent => $keywords) {
            $intentSignals[$intent] = array_values(array_unique(array_merge(
                $intentSignals[$intent] ?? [],
                $keywords,
            )));
        }

        foreach ($intentSignals as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (AgriculturalEntityCatalog::matchesSemanticToken($normalizedQuestion, $keyword)) {
                    $score += mb_strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIntent = $intent;
            }
        }

        foreach ($topicFactors as $factor) {
            if (($factorRoles[$factor] ?? 'requested') === 'constraint') {
                continue;
            }
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

        $hasNamedCrop = $cropRecognition !== null;
        $hasCategory = AgriculturalEntityCatalog::resolveCropCategory($normalizedQuestion) !== null;
        $suitabilityFraming = AgriculturalEntityCatalog::hasSuitabilityOrSelectionFraming($normalizedQuestion)
            || $intentQualifier === 'optimal_range';
        $categoryRecommendation = $hasCategory && ! $hasNamedCrop && $suitabilityFraming;
        $explicitIrrigation = AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement($normalizedQuestion)
            || ($hasNamedCrop && ($factorRoles['water'] ?? null) === 'requested' && in_array('water', $topicFactors, true));

        // Constraint-role water/salinity/temperature must not replace a crop-selection/cultivation act.
        if (! $explicitIrrigation
            && $categoryRecommendation
            && in_array($bestIntent, ['irrigation', 'environmental_requirements'], true)
        ) {
            $bestIntent = 'cultivation';
        }

        if ($categoryRecommendation && in_array($bestIntent, ['general_knowledge', 'productivity'], true)) {
            $bestIntent = 'cultivation';
        }

        return $bestIntent;
    }

    /**
     * Existing Q1/Q3/varieties intent refinement. Independent of semantic-target capture.
     *
     * @param  array<string, mixed>  $constraints
     * @param  array<string, string>  $factorRoles
     */
    private function refineResearchIntentFromRequestedProperty(
        string $researchIntent,
        array $constraints,
        array $factorRoles,
        string $normalizedQuestion,
        string $scientificSense,
    ): string {
        $requestedProperty = trim((string) ($constraints['requested_property'] ?? ''));
        $temperatureIsConstraint = ($factorRoles['temperature'] ?? 'requested') === 'constraint'
            || AgriculturalEntityCatalog::hasSuitabilityOrSelectionFraming($normalizedQuestion);
        if ($requestedProperty === 'temperature'
            && in_array($researchIntent, ['cultivation', 'general_knowledge', 'productivity'], true)
            && ! $temperatureIsConstraint) {
            $researchIntent = 'environmental_requirements';
        }
        if ($requestedProperty === 'irrigation'
            && in_array($researchIntent, ['cultivation', 'general_knowledge'], true)
            && AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement($normalizedQuestion)) {
            $researchIntent = 'irrigation';
        }
        if ($researchIntent === 'general_knowledge'
            && $requestedProperty === 'classification'
            && $scientificSense !== 'land_classification'
            && $scientificSense !== 'animal_production'
            && $scientificSense !== 'poultry_production') {
            $researchIntent = 'varieties';
        }

        return $researchIntent;
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
            'scientific_literature', 'environmental_requirements', 'cultivation',
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
                'category' => (string) ($cropRecognition['category'] ?? FieldCropTaxonomyCatalog::categoryFor($cropRecognition['crop_id'])),
                'resolution' => 'resolved',
            ];
        }

        // Botanical / entity-family subjects (table-driven via Catalog aliases).
        $botanicalFamily = AgriculturalEntityCatalog::resolveBotanicalFamily($normalizedQuestion);
        if ($botanicalFamily !== null) {
            return [
                'type' => 'plant_family',
                'value' => $botanicalFamily,
                'label' => $botanicalFamily,
            ];
        }

        $livestockEntity = AgriculturalEntityCatalog::recognizeLivestockEntity($normalizedQuestion);
        if ($livestockEntity !== null) {
            return $livestockEntity;
        }

        $unresolvedEntity = AgriculturalEntityCatalog::extractNamedAgriculturalEntityCandidate($normalizedQuestion);
        if ($unresolvedEntity !== null) {
            return [
                'type' => 'crop',
                'value' => $unresolvedEntity['normalized'],
                'label' => $unresolvedEntity['surface'],
                'resolution' => 'unresolved',
            ];
        }

        $cropCategory = AgriculturalEntityCatalog::resolveCropCategory($normalizedQuestion);
        if ($cropCategory !== null) {
            return $cropCategory;
        }

        if (! AgriculturalEntityCatalog::asksCausalAffectQuestion($normalizedQuestion)
            && ! AgriculturalEntityCatalog::isThermalGerminationRangeQuestion($normalizedQuestion)
            && (
                AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'soil')
                || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'تربة')
                || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'toprak')
                || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, 'sols')
            )
        ) {
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

    /**
     * Livestock subjects must not inherit crop-variety intent or plant domains.
     *
     * @param  array{type?: string, value?: string, label?: string}|null  $subject
     * @return array{0: string, 1: string}
     */
    private function alignLivestockIntentAndDomain(
        string $researchIntent,
        string $agriculturalDomain,
        ?array $subject,
    ): array {
        if (! is_array($subject) || ($subject['type'] ?? '') !== 'animal') {
            return [$researchIntent, $agriculturalDomain];
        }

        $value = (string) ($subject['value'] ?? '');
        if (! in_array($value, ['cattle', 'sheep', 'goats', 'poultry', 'camels', 'buffalo', 'livestock'], true)) {
            return [$researchIntent, $agriculturalDomain];
        }

        $livestockIntent = $value === 'poultry' ? 'poultry_production' : 'animal_production';
        $livestockDomain = $value === 'poultry'
            ? AgriculturalDomainCatalog::POULTRY
            : AgriculturalDomainCatalog::ANIMAL_PRODUCTION;

        if (in_array($researchIntent, ['general_knowledge', 'varieties'], true)) {
            $researchIntent = $livestockIntent;
        }

        if (in_array($agriculturalDomain, [
            AgriculturalDomainCatalog::GENERAL_AGRICULTURE,
            AgriculturalDomainCatalog::FIELD_CROPS,
            AgriculturalDomainCatalog::PLANT_PRODUCTION,
        ], true)) {
            $agriculturalDomain = $livestockDomain;
        }

        return [$researchIntent, $agriculturalDomain];
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
    private function resolveRequestedInformation(
        string $researchIntent,
        string $normalizedQuestion,
        array $topicFactors,
        string $questionType = 'general',
        string $scientificSense = '',
    ): array {
        $requested = [$researchIntent, 'evidence_backed_guidance'];
        foreach (AgriculturalEntityCatalog::englishLabelsForFactors($topicFactors) as $label) {
            $requested[] = $label;
        }

        $requested[] = match ($questionType) {
            'classification' => 'types_or_classification',
            'quantity' => 'quantity_or_rate',
            'range' => 'optimal_value_or_range',
            'timing' => 'timing_or_season',
            'causes' => 'causes',
            'symptoms' => 'symptoms',
            'comparison' => 'comparison',
            'species' => 'species_list',
            'definition' => 'definition',
            'recommendation' => 'recommendations',
            'requirements' => 'requirements',
            default => 'topic_answer',
        };

        if ($scientificSense === 'land_classification') {
            $requested[] = 'types_or_classification';
            $requested[] = 'agricultural_land_or_soil_types';
            $requested[] = 'classification_inventory';
        }

        if ($scientificSense === 'plant_family_members') {
            $requested[] = 'species_list';
            $requested[] = 'family_members_inventory';
            $requested[] = 'types_or_classification';
        }

        if (str_contains($normalizedQuestion, 'best') || str_contains($normalizedQuestion, 'أفضل')) {
            $requested[] = 'recommendations';
        }

        return array_values(array_unique($requested));
    }

    /**
     * @param  list<string>  $topicFactors
     */
    private function detectQuestionType(
        string $normalizedQuestion,
        string $originalQuestion,
        string $scientificSense,
        string $intentQualifier,
        string $researchIntent,
    ): string {
        $haystack = mb_strtolower(trim($normalizedQuestion.' '.$originalQuestion));

        // Entity-family inventory first (separable from land WIP below).
        if ($scientificSense === 'plant_family_members'
            || $researchIntent === 'plant_family_members') {
            if (AgriculturalEntityCatalog::asksPlantFamilyMemberInventory($haystack)
                || preg_match('/(?:انواع|أنواع|types?|kinds?|categories|تصنيف)/u', $haystack) === 1) {
                // Member/types inventory of a botanical family.
                return AgriculturalEntityCatalog::asksPlantFamilyMemberInventory($haystack)
                    ? 'species'
                    : 'classification';
            }
        }

        // Biological-category inventories are species lists, not crop-variety classification.
        if (in_array($researchIntent, ['aquaculture'], true)
            && preg_match('/(?:أنواع|انواع|اصناف|أصناف|سلالات|species|types?|kinds?|breeds?)/u', $haystack) === 1) {
            return 'species';
        }

        if ($researchIntent === 'varieties'
            || preg_match('/(?:اصناف|أصناف|أنواع|انواع|سلالات|سلالة|\bvarieties\b|\bcultivars\b|\btypes?\b|\bkinds?\b|\bbreeds?\b|\bstrains?\b)/u', $haystack) === 1) {
            return 'classification';
        }

        // Land WIP: method questions about soil/land classification are not types-inventory.
        if (AgriculturalEntityCatalog::isLandOrSoilClassificationMethodQuestion($haystack)) {
            return 'general';
        }

        if ($scientificSense === 'land_classification') {
            return 'classification';
        }

        if (AgriculturalEntityCatalog::asksCausalAffectQuestion($haystack)) {
            return 'causes';
        }

        if (AgriculturalEntityCatalog::asksHowToProcedureQuestion($haystack)) {
            return 'recommendation';
        }

        // Causal "why" questions about symptoms are causes, not symptom inventories.
        if (preg_match('/\b(why|cause|causes|reason)\b/u', $haystack) === 1
            || AgriculturalEntityCatalog::containsTerm($haystack, 'لماذا')
            || AgriculturalEntityCatalog::containsTerm($haystack, 'سبب')
            || mb_strpos($haystack, 'لماذا') !== false) {
            return 'causes';
        }

        // Thermal germination optimal/suitable/range precedes generic definition/quantity.
        if (AgriculturalEntityCatalog::isThermalGerminationRangeQuestion(
            $haystack,
            $scientificSense,
            $intentQualifier,
        )) {
            return 'range';
        }

        $best = 'general';
        $bestScore = 0;
        foreach (AgriculturalEntityCatalog::questionTypeSignals() as $type => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && $this->questionTypeKeywordHits($haystack, $keyword)) {
                    $score += mb_strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $type;
            }
        }

        if ($bestScore > 0) {
            $factors = AgriculturalEntityCatalog::extractTopicFactors($normalizedQuestion);
            if (ScientificQuestionSemantics::preferRangeOverRequirements(
                $best,
                $intentQualifier,
                $factors,
                $scientificSense,
                $haystack,
            )) {
                return 'range';
            }

            return $best;
        }

        if (AgriculturalEntityCatalog::hasSuitabilityOrSelectionFraming($haystack)
            || (
                AgriculturalEntityCatalog::resolveCropCategory($haystack) !== null
                && $intentQualifier === 'optimal_range'
            )
        ) {
            return 'recommendation';
        }

        if (ScientificQuestionSemantics::prefersDiseaseInventory($haystack, $researchIntent)) {
            return 'symptoms';
        }

        // Bare "what is" / "ما هي" is definition only when no typed factual signal matched.
        if (preg_match('/\bwhat\s+is\b/u', $haystack) === 1
            || preg_match('/\bwhat\s+are\b/u', $haystack) === 1
            || AgriculturalEntityCatalog::containsTerm($haystack, 'ما هو')
            || AgriculturalEntityCatalog::containsTerm($haystack, 'ما هي')
            || mb_strpos($haystack, 'ما هو') !== false
            || mb_strpos($haystack, 'ما هي') !== false) {
            return 'definition';
        }

        $factors = AgriculturalEntityCatalog::extractTopicFactors($normalizedQuestion);

        return match (true) {
            $intentQualifier === 'optimal_range'
                && (in_array('temperature', $factors, true) || $scientificSense === 'seed_germination') => 'range',
            $intentQualifier === 'optimal_range' => 'recommendation',
            $intentQualifier === 'requirement' => 'requirements',
            $intentQualifier === 'effect' => 'causes',
            $researchIntent === 'disease' => 'symptoms',
            default => 'general',
        };
    }

    private function questionTypeKeywordHits(string $haystack, string $keyword): bool
    {
        $normalized = mb_strtolower(trim($keyword));
        if ($normalized === '') {
            return false;
        }

        // Short particles must be token-bounded so "كم" cannot match inside "كيف".
        if (mb_strlen($normalized) <= 3) {
            return preg_match('/(?<!\p{L})'.preg_quote($normalized, '/').'(?!\p{L})/u', $haystack) === 1;
        }

        return AgriculturalEntityCatalog::containsTerm($haystack, $normalized);
    }

    private function detectTimeContext(string $normalizedQuestion, string $originalQuestion): ?string
    {
        $haystack = mb_strtolower(trim($normalizedQuestion.' '.$originalQuestion));
        foreach (['season', 'موسم', 'planting date', 'موعد', 'période', 'mevsim', 'يوم', 'اليوم', 'daily', 'per day'] as $marker) {
            if ($marker !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $marker)
                || mb_strpos($haystack, $marker) !== false
            )) {
                return $marker;
            }
        }

        return null;
    }

    private function extractExplicitCalendarYear(string $normalizedQuestion, string $originalQuestion): ?string
    {
        $haystack = trim($normalizedQuestion.' '.$originalQuestion);
        if (preg_match('/\b((?:19|20)\d{2})\b/u', $haystack, $matches) === 1) {
            return $matches[1];
        }

        return null;
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

        $haystack = mb_strtolower(trim($question));
        // Prefer longer aliases first (e.g. egyptian before egypt).
        $aliases = AgriculturalEntityCatalog::locationAliases();
        uksort($aliases, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($aliases as $alias => $canonical) {
            $aliasFold = mb_strtolower((string) $alias);
            if ($alias !== '' && AgriculturalEntityCatalog::containsTerm($haystack, $aliasFold)) {
                return $canonical;
            }
            // Arabic aliases may not tokenize via containsTerm the same way — substring check.
            if (preg_match('/\p{Arabic}/u', $alias) === 1 && mb_strpos($haystack, mb_strtolower($alias)) !== false) {
                return $canonical;
            }
        }

        if (preg_match('/\b(in|at|near)\s+([a-z\s]{3,40})/i', $question, $matches) === 1) {
            $candidate = trim($matches[2]);
            $candidate = trim((string) preg_replace('/\s+\d{4}\b.*$/u', '', $candidate));
            $candidate = trim((string) preg_replace('/\s+(?:in|at|near|on)\b.*$/iu', '', $candidate));
            if ($candidate === '') {
                return null;
            }
            foreach ($aliases as $alias => $canonical) {
                if (strcasecmp($candidate, $alias) === 0 || strcasecmp($candidate, $canonical) === 0) {
                    return $canonical;
                }
            }

            return $candidate;
        }

        return null;
    }

    /**
     * Home Free Question location. Crop Page keeps extractLocation() unchanged.
     *
     * @param  array<string, mixed>  $input
     */
    private function extractHomeLocation(array $input, string $question): ?string
    {
        $explicit = trim((string) ($input['location'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $haystack = mb_strtolower(trim($question));
        if (AgriculturalEntityCatalog::isTurkishMisirCountryLocative($haystack)) {
            return 'Egypt';
        }

        $homeAliases = AgriculturalEntityCatalog::homeLocationAliases();
        uksort($homeAliases, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($homeAliases as $alias => $canonical) {
            $aliasFold = mb_strtolower((string) $alias);
            if ($alias !== '' && AgriculturalEntityCatalog::containsTerm($haystack, $aliasFold)) {
                return $canonical;
            }
        }

        $fallback = $this->extractLocation($input, $question);
        if ($fallback !== null && $this->isHomeUnsafeLocationCandidate($fallback)) {
            return null;
        }

        return $fallback;
    }

    private function isHomeUnsafeLocationCandidate(string $candidate): bool
    {
        $folded = mb_strtolower(trim($candidate));
        if ($folded === '') {
            return true;
        }
        $blocked = [
            'summer', 'été', 'ete', 'yaz', 'الصيف', 'saison', 'season', 'winter', 'spring', 'autumn', 'fall',
            'soil', 'soils', 'تربة', 'toprak', 'sol', 'germination', 'yield', 'terms of yield',
            'hangi toprak uygundur', 'terms',
        ];
        foreach ($blocked as $token) {
            if ($folded === $token || str_contains($folded, $token)) {
                return true;
            }
        }

        return AgriculturalEntityCatalog::isUnsafeResidualEntitySurface($folded);
    }

    /**
     * Shared Home/Crop contract: question semantics determine knowledge-target
     * topics and property needles. Crop identity is not an input.
     *
     * @param  array<string, mixed>  $constraints
     * @param  list<string>  $topicFactors
     */
    private function applyQuestionTypeKnowledgeTargets(
        array &$constraints,
        string $questionType,
        string $requiredEvidenceType,
        array $topicFactors,
        string $scientificSense,
        string $intentQualifier,
        string $normalizedQuestion,
    ): void {
        $requestedProperty = trim((string) ($constraints['requested_property'] ?? ''));
        $targets = ScientificQuestionSemantics::knowledgeTargetTopics(
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
            $requestedProperty,
        );
        if ($targets !== []) {
            $existingTopics = is_array($constraints['scientific_topics'] ?? null)
                ? $constraints['scientific_topics']
                : [];
            $physiologyNoise = [
                'growth', 'physiology', 'plant growth', 'yield',
                'general_knowledge', 'agriculture', 'farming', 'general agriculture',
            ];
            $existingTopics = array_values(array_filter(
                $existingTopics,
                static fn ($topic): bool => ! in_array(mb_strtolower(trim((string) $topic)), $physiologyNoise, true),
            ));
            $constraints['scientific_topics'] = array_values(array_unique(array_merge($targets, $existingTopics)));
        }

        $propertyTerms = ScientificQuestionSemantics::propertyQueryTerms(
            $questionType,
            $requiredEvidenceType,
            $topicFactors,
            $scientificSense,
            $intentQualifier,
            $normalizedQuestion,
            $requestedProperty,
        );
        $existingPropertyTerms = $constraints['requested_property_query_terms'] ?? [];
        if ($propertyTerms !== [] && (! is_array($existingPropertyTerms) || $existingPropertyTerms === [])) {
            $constraints['requested_property_query_terms'] = $propertyTerms;
        }

        $preferredSense = ScientificQuestionSemantics::preferredSenseForRequirement(
            $scientificSense,
            $intentQualifier,
            $topicFactors,
            $normalizedQuestion,
        );
        if ($preferredSense !== $scientificSense) {
            $constraints['scientific_sense'] = $preferredSense;
        }
    }

    /**
     * Home-only: reinforce semantic property contract after baseline assembly.
     *
     * @param  array<string, mixed>  $constraints
     * @param  list<string>  $topicFactors
     */
    private function applyHomeSemanticPropertyContract(
        array &$constraints,
        string $normalizedQuestion,
        string $questionType,
        string $scientificSense,
        string $researchIntent,
        ?string $cropIdResolved,
        array $topicFactors,
    ): void {
        if ($scientificSense === 'seed_germination' || in_array('germination', $topicFactors, true)) {
            if (in_array('temperature', $topicFactors, true)
                || $questionType === 'range'
                || str_contains($normalizedQuestion, 'temperature')
                || str_contains($normalizedQuestion, 'حرارة')
                || str_contains($normalizedQuestion, 'sıcaklık')
                || str_contains($normalizedQuestion, 'température')) {
                $constraints['requested_property'] = 'temperature';
            }
        }

        if ($scientificSense === 'crop_water_requirement' || $researchIntent === 'irrigation') {
            $constraints['requested_property'] = 'irrigation';
        }
        if (preg_match('/(?:irrigat|sulama|sulan|irriguer)/u', $normalizedQuestion) === 1
            || str_contains($normalizedQuestion, 'ري')
            || str_contains($normalizedQuestion, 'الري')) {
            $constraints['requested_property'] = 'irrigation';
        }

        if ($cropIdResolved !== null
            && AgriculturalEntityCatalog::asksHomeCropSoilSuitability($normalizedQuestion)) {
            $constraints['requested_property'] = 'soil';
            if (($constraints['location'] ?? null) !== null
                && $this->isHomeUnsafeLocationCandidate((string) $constraints['location'])) {
                unset($constraints['location']);
            }
        }

        $yieldLike = in_array('productivity', [$researchIntent], true)
            || preg_match('/\b(yield|production|verim|rendement|إنتاج|انتاج|إنتاجية)\b/u', $normalizedQuestion) === 1;
        if ($yieldLike && in_array($questionType, ['quantity', 'comparison', 'general', 'definition', ''], true)) {
            if (($constraints['requested_property'] ?? null) === null
                || in_array((string) ($constraints['requested_property'] ?? ''), ['general', 'definition', 'comparison', 'recommendation', 'classification'], true)) {
                $constraints['requested_property'] = 'quantity';
            }
        }

        if (! empty($constraints['is_comparison'])) {
            if (($constraints['requested_property'] ?? null) === null
                || in_array((string) ($constraints['requested_property'] ?? ''), ['comparison', 'general', 'definition'], true)) {
                if ($yieldLike) {
                    $constraints['requested_property'] = 'quantity';
                }
            }
        }

        // Never keep residual/temporal strings on Home location.
        if (isset($constraints['location'])
            && $this->isHomeUnsafeLocationCandidate((string) $constraints['location'])) {
            unset($constraints['location']);
        }
    }

    private function detectProductionSystem(string $normalizedQuestion): ?string
    {
        foreach (AgriculturalEntityCatalog::productionSystemSignals() as $system => $keywords) {
            foreach ($keywords as $keyword) {
                if (AgriculturalEntityCatalog::containsTerm($normalizedQuestion, $keyword)
                    || mb_strpos($normalizedQuestion, mb_strtolower($keyword)) !== false) {
                    return $system;
                }
            }
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
        return AgriculturalEntityCatalog::intentRequiresNamedEntity($researchIntent);
    }

    /**
     * Category-level subjects (crops, families, land) are valid without a named entity.
     *
     * @param  array{type?: string, value?: string, label?: string}|null  $subject
     */
    private function intentRequiresNamedEntity(string $researchIntent, ?array $subject): bool
    {
        $type = is_array($subject) ? (string) ($subject['type'] ?? '') : '';
        if (in_array($type, ['crop_category', 'plant_family', 'land', 'production_system'], true)) {
            return false;
        }

        return $this->intentRequiresEntity($researchIntent) && $subject === null;
    }

    /**
     * @param  list<string>  $topicFactors
     * @param  array<string, string>  $factorRoles
     */
    private function isRequestedTopicFactor(string $factor, array $topicFactors, array $factorRoles): bool
    {
        if (! in_array($factor, $topicFactors, true)) {
            return false;
        }

        return ($factorRoles[$factor] ?? 'requested') !== 'constraint';
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
     * @param  array<string, string>  $factorRoles
     */
    private function resolveScientificSense(
        string $researchIntent,
        array $topicFactors,
        string $normalizedQuestion,
        array $factorRoles = [],
        string $intentQualifier = 'general',
    ): string {
        // Match land-type inventory on orthography-folded Arabic so انواع/أنواع + اراضي/أراضي agree.
        // Also cover EN/FR/TR wording for the same semantic intent.
        $senseHaystack = $this->normalizeArabicOrthography($normalizedQuestion);
        // Method/framework questions about soil classification are NOT type-inventory sense.
        if (! AgriculturalEntityCatalog::isLandOrSoilClassificationMethodQuestion($senseHaystack)
            && (
                AgriculturalEntityCatalog::asksLandOrSoilTypesInventory($senseHaystack)
                || preg_match(
                    '/land\s*types?|soil\s*classification|land\s*classification|agricultural\s+land\s+types?|'
                    .'types?\s+of\s+(?:agricultural\s+)?(?:land|soil)|types?\s+de\s+terres?\s+agricoles|'
                    .'classification\s+des\s+sols?|tar[ıi]m\s+arazilerinin\s+t[üu]rleri|'
                    .'toprak\s+s[ıi]n[ıi]fland[ıi]rmas[ıi]|انواع\s*(?:ال)?اراضي|تصنيف\s*(?:ال)?اراضي|انواع\s*(?:ال)?تربه/u',
                    $senseHaystack,
                ) === 1
            )
        ) {
            return 'land_classification';
        }
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
        if ($this->isRequestedTopicFactor('salinity', $topicFactors, $factorRoles)
            || (
                in_array('salinity', $topicFactors, true)
                && $intentQualifier === 'effect'
            )
        ) {
            return 'salinity_physiology';
        }
        if ($this->isRequestedTopicFactor('water', $topicFactors, $factorRoles)
            || $researchIntent === 'irrigation') {
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
        // Planting-date / sowing-window questions must not collapse to plant_growth
        // (which emits "growth" queries and leaks "plant growth" into answers).
        if ($this->isPlantingDateTimingQuestion($normalizedQuestion)) {
            return 'planting_timing';
        }
        if (in_array($researchIntent, ['environmental_requirements', 'cultivation', 'productivity'], true)
            || in_array('temperature', $topicFactors, true)) {
            return ScientificQuestionSemantics::preferredSenseForRequirement(
                'plant_growth',
                $intentQualifier,
                $topicFactors,
                $normalizedQuestion,
            );
        }

        $fallback = $researchIntent !== '' ? $researchIntent : 'general';

        return ScientificQuestionSemantics::preferredSenseForRequirement(
            $fallback,
            $intentQualifier,
            $topicFactors,
            $normalizedQuestion,
        );
    }

    private function isPlantingDateTimingQuestion(string $normalizedQuestion): bool
    {
        $hay = mb_strtolower(trim($normalizedQuestion));
        if ($hay === '') {
            return false;
        }

        $hasTiming = false;
        foreach ([
            'موعد', 'توقيت', 'موسم زراعة', 'planting date', 'sowing date',
            'planting time', 'sowing time', 'when to plant', 'when to sow',
            'best time to plant', 'best time to sow', 'ne zaman ekilir',
        ] as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $marker) || mb_strpos($hay, $marker) !== false) {
                $hasTiming = true;
                break;
            }
        }
        if (! $hasTiming) {
            return false;
        }

        foreach ([
            'زراعة', 'يزرع', 'planting', 'sowing', 'sow', 'plant', 'cultivation',
        ] as $plantMarker) {
            if (AgriculturalEntityCatalog::containsTerm($hay, $plantMarker) || mb_strpos($hay, $plantMarker) !== false) {
                return true;
            }
        }

        // "موعد لزراعة X" / "أنسب موعد" with crop context already implies planting.
        return preg_match('/موعد.{0,12}ل?زرا/u', $hay) === 1
            || preg_match('/\b(?:planting|sowing)\b/u', $hay) === 1;
    }

    private function resolveDomainBranch(string $researchIntent, string $scientificSense, string $agriculturalDomain): string
    {
        return match (true) {
            // Entity-family taxonomy branch (stage separately from land/timing).
            $scientificSense === 'plant_family_members' => 'plant_taxonomy',
            $scientificSense === 'land_classification' => 'soil',
            $scientificSense === 'planting_timing' => 'agronomy',
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

    /**
     * Home-only: keep the requested surface token beside the canonical resolved id.
     * Does not change cropId, subject, or named_entity_surface meaning.
     *
     * @param  array<string, mixed>  $constraints
     * @param  array{type?: string, value?: string, label?: string, resolution?: string}|null  $subject
     */
    private function retainHomeRequestedVersusResolvedEntity(
        array &$constraints,
        string $originalQuestion,
        string $normalizedQuestion,
        ?array $subject,
        ?string $cropIdResolved,
        ?string $namedEntitySurface,
        ?string $cropLabel,
    ): void {
        $subjectType = is_array($subject) ? (string) ($subject['type'] ?? '') : '';
        $resolvedId = $cropIdResolved !== null && trim($cropIdResolved) !== '' ? $cropIdResolved : null;
        if ($resolvedId === null && in_array($subjectType, ['crop', 'named_entity', 'animal', 'plant_family'], true)) {
            $value = trim((string) ($subject['value'] ?? ''));
            $resolvedId = $value !== '' ? $value : null;
        }

        $requested = $this->requestedEntitySurfaceFromQuestion(
            $originalQuestion,
            $normalizedQuestion,
            $subject,
            $cropLabel,
            $namedEntitySurface,
        );

        if ($requested !== null && $requested !== '') {
            $constraints['requested_entity_surface'] = $requested;
        }
        if ($resolvedId !== null) {
            $constraints['resolved_entity_id'] = $resolvedId;
        }
    }

    /**
     * @param  array{type?: string, value?: string, label?: string}|null  $subject
     */
    private function requestedEntitySurfaceFromQuestion(
        string $originalQuestion,
        string $normalizedQuestion,
        ?array $subject,
        ?string $cropLabel,
        ?string $namedEntitySurface,
    ): ?string {
        $subjectType = is_array($subject) ? (string) ($subject['type'] ?? '') : '';
        if ($subjectType === 'animal') {
            $canonical = trim((string) ($subject['value'] ?? ''));
            $fromAlias = $this->requestedLivestockSurface($originalQuestion, $normalizedQuestion, $canonical);
            if ($fromAlias !== null) {
                return $fromAlias;
            }
        }

        $cropLabel = is_string($cropLabel) ? trim($cropLabel) : '';
        if ($cropLabel !== '') {
            return $cropLabel;
        }

        $surface = is_string($namedEntitySurface) ? trim($namedEntitySurface) : '';
        if ($surface !== '') {
            return $surface;
        }

        if (is_array($subject)) {
            $label = trim((string) ($subject['label'] ?? $subject['value'] ?? ''));

            return $label !== '' ? $label : null;
        }

        return null;
    }

    private function requestedLivestockSurface(
        string $originalQuestion,
        string $normalizedQuestion,
        string $canonical,
    ): ?string {
        if ($canonical === '') {
            return null;
        }

        $signals = AgriculturalEntityCatalog::livestockEntitySignals()[$canonical] ?? [];
        if (! is_array($signals) || $signals === []) {
            return null;
        }

        $best = null;
        $bestLength = 0;
        $originalLower = mb_strtolower($originalQuestion);
        foreach ($signals as $alias) {
            $alias = trim((string) $alias);
            if ($alias === '') {
                continue;
            }
            $aliasLower = mb_strtolower($alias);
            $matched = AgriculturalEntityCatalog::containsTerm($originalLower, $aliasLower)
                || AgriculturalEntityCatalog::containsTerm($normalizedQuestion, $aliasLower)
                || mb_strpos($originalQuestion, $alias) !== false;
            if (! $matched) {
                continue;
            }
            $length = mb_strlen($alias);
            if ($length > $bestLength) {
                $bestLength = $length;
                $best = $alias;
            }
        }

        return $best;
    }
}
