<?php

namespace App\Services\Agriculture\Research;

/**
 * Execution-oriented projection of a frozen CSQ (or isolated legacy AKQ fields).
 *
 * Not a second semantic authority. CSQ remains meaning.
 * fromCanonical(CSQ) is a deterministic projection and never reads AKQ/legacy fields.
 */
final readonly class RetrievalSpecification
{
    public const SOURCE_CSQ = 'csq';

    public const SOURCE_LEGACY = 'legacy';

    public const PRIORITY_REQUIRED = 'required';

    public const PRIORITY_SUPPORTING = 'supporting';

    public const PRIORITY_CONTEXTUAL = 'contextual';

    public const CLASS_REQUIRED = 'required_for_retrieval';

    public const CLASS_SUPPORTING = 'supporting';

    public const CLASS_CONTEXTUAL = 'contextual';

    public const CLASS_EXECUTION_ONLY = 'execution_only';

    public const CLASS_EXPLICITLY_WAIVED = 'explicitly_waived';

    public const WAIVER_TIME = 'CSQ time is preserved as metadata. Scholarly NL compilation must not inject year; structured/statistical retrieval (FAOSTAT) remains the year consumer.';

    public const WAIVER_EVIDENCE = 'CSQ evidence requirements are an execution/evidence-layer concern. They are not scholarly NL query terms and must not be compiled as keywords.';

    public const WAIVER_EMPTY_CONDITIONS = 'No CSQ conditions were populated.';

    public const ROLE_SCHOLARLY_PROPERTY = 'scholarly_property_term';

    public const ROLE_FACTOR = 'factor';

    /** @var list<string> */
    public const SCHOLARLY_WATER_TERMS = [
        'irrigation',
        'water requirement',
        'evapotranspiration',
        'quantity',
    ];

    /** @var list<string> */
    public const SCHOLARLY_TEMPERATURE_TERMS = [
        'temperature',
        'germination',
    ];

    /** @var list<string> */
    public const SCHOLARLY_CLASSIFICATION_TERMS = [
        'classification',
        'types',
    ];

    /** @var list<string> */
    public const SCHOLARLY_QUANTITY_TERMS = [
        'quantity',
        'rate',
    ];

    /** @var list<string> */
    public const SCHOLARLY_PRODUCTIVITY_TERMS = [
        'production',
        'quantity',
    ];

    /** @var list<string> */
    public const PRODUCTIVITY_FALLBACK_TERMS = ['yield', 'production'];

    /** @var list<string> */
    public const FAMILY_KEYS = ['irrigation', 'temperature', 'classification', 'quantity'];

    /**
     * @param  list<array<string, mixed>>  $concepts
     * @param  array<string, string>  $roleAccounting
     * @param  array<string, string>  $waivers
     * @param  array<string, mixed>  $projections
     */
    public function __construct(
        public array $concepts,
        public string $relationType,
        public ?string $relationFrom,
        public ?string $relationTo,
        public string $source,
        public bool $hasIndependentQuestionRoles,
        public ?string $propertyOfRole = null,
        public string $researchContext = '',
        public array $roleAccounting = [],
        public array $waivers = [],
        public array $projections = [],
    ) {}

    public static function fromCanonical(CanonicalScientificQuestion $csq): self
    {
        $entity = self::entityProjection($csq);
        $target = self::targetProjection($csq);
        $process = self::processProjection($csq);
        $property = self::propertyProjection($csq);
        $relation = [
            'type' => $csq->relation->type,
            'from' => $csq->relation->from,
            'to' => $csq->relation->to,
            'state' => $csq->relation->state,
            'operands' => $csq->relation->operands,
        ];
        $context = [
            'domain' => $csq->context->domain,
            'scientificSense' => $csq->context->scientificSense,
            'researchIntent' => $csq->context->researchIntent,
            'questionType' => $csq->context->questionType,
            'requestedInformation' => $csq->context->requestedInformation,
        ];
        $conditions = $csq->conditions->items;
        $time = [
            'year' => $csq->time->year,
            'period' => $csq->time->period,
            'startYear' => $csq->time->startYear,
            'endYear' => $csq->time->endYear,
        ];
        $geography = [
            'label' => $csq->geography->label,
            'country' => $csq->geography->country,
            'region' => $csq->geography->region,
            'canonicalId' => $csq->geography->canonicalId,
            'canonicalNamespace' => $csq->geography->canonicalNamespace,
        ];
        $evidence = [
            'requiredEvidenceType' => $csq->evidenceRequirement->requiredEvidenceType,
            'requiresFactualDirect' => $csq->evidenceRequirement->requiresFactualDirect,
        ];
        $resolution = [
            'ambiguityState' => $csq->resolution->ambiguityState,
            'clarificationRequirements' => $csq->resolution->clarificationRequirements,
            'understandingConfidence' => $csq->resolution->understandingConfidence,
        ];
        $cropBinding = [
            'cropId' => $csq->cropBinding->cropId,
            'cropLabel' => $csq->cropBinding->cropLabel,
            'scientificName' => $csq->cropBinding->scientificName,
            'context' => $csq->cropBinding->context,
        ];

        $waivers = [
            'time' => self::WAIVER_TIME,
            'evidence' => self::WAIVER_EVIDENCE,
        ];
        $conditionClass = $conditions === []
            ? self::CLASS_EXPLICITLY_WAIVED
            : self::CLASS_CONTEXTUAL;
        if ($conditionClass === self::CLASS_EXPLICITLY_WAIVED) {
            $waivers['conditions'] = self::WAIVER_EMPTY_CONDITIONS;
        }

        $roleAccounting = [
            'entity' => self::CLASS_REQUIRED,
            'target' => self::CLASS_REQUIRED,
            'process' => self::CLASS_REQUIRED,
            'property' => self::CLASS_REQUIRED,
            'relation' => self::CLASS_REQUIRED,
            'context' => self::CLASS_SUPPORTING,
            'conditions' => $conditionClass,
            'time' => self::CLASS_EXPLICITLY_WAIVED,
            'geography' => self::CLASS_CONTEXTUAL,
            'evidence' => self::CLASS_EXECUTION_ONLY,
            'resolution' => self::CLASS_SUPPORTING,
            'crop_binding' => self::CLASS_CONTEXTUAL,
            'scholarly_property' => self::CLASS_REQUIRED,
            'factor' => self::CLASS_REQUIRED,
        ];

        $concepts = [];
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_ENTITY, $entity['surface'], $entity['normalized'], self::PRIORITY_REQUIRED, null, null, $entity['resolution']);
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_PROCESS, $process['surface'], $process['normalized'], self::PRIORITY_REQUIRED, null, null, $process['resolution']);
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_TARGET, $target['surface'], $target['normalizedKey'], self::PRIORITY_REQUIRED, null, $target['kind'], $target['resolution']);
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_PROPERTY, $property['surface'] ?? $property['key'], $property['key'], self::PRIORITY_REQUIRED, $property['ofRole'], null, $property['resolution']);
        foreach ($csq->relation->operands as $operand) {
            if (! is_array($operand)) {
                continue;
            }
            self::pushConcept(
                $concepts,
                'comparison_operand',
                isset($operand['surface']) ? (string) $operand['surface'] : null,
                isset($operand['normalized']) && is_string($operand['normalized']) ? $operand['normalized'] : null,
                self::PRIORITY_REQUIRED,
                null,
                null,
                isset($operand['resolution']) ? (string) $operand['resolution'] : CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            );
        }

        $scholarlyRole = self::propertyRoleFromCsq($csq);
        $scholarlySurface = trim((string) ($property['surface'] ?? $property['key'] ?? ''));
        $scholarlyBag = self::materializeScholarlyTerms($scholarlyRole, $scholarlySurface, []);
        $factors = self::factorsFromCsq($csq);
        $scholarlyOrdered = self::interleaveRequiredFactors($scholarlyBag, $factors);
        foreach ($factors as $factor) {
            self::pushConcept($concepts, self::ROLE_FACTOR, $factor, null, self::PRIORITY_REQUIRED);
        }
        foreach ($scholarlyOrdered as $term) {
            self::pushConcept($concepts, self::ROLE_SCHOLARLY_PROPERTY, $term, $term, self::PRIORITY_REQUIRED);
        }

        $sense = $context['scientificSense'];
        if (is_string($sense) && trim($sense) !== '') {
            self::pushConcept(
                $concepts,
                'context_sense',
                str_replace('_', ' ', trim($sense)),
                trim($sense),
                self::PRIORITY_SUPPORTING,
            );
        }
        if (is_string($geography['label']) && trim($geography['label']) !== '') {
            self::pushConcept($concepts, 'geography', trim($geography['label']), null, self::PRIORITY_CONTEXTUAL);
        }
        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }
            $label = trim((string) ($condition['label'] ?? $condition['value'] ?? $condition['type'] ?? ''));
            if ($label !== '') {
                self::pushConcept($concepts, 'condition', $label, null, self::PRIORITY_CONTEXTUAL);
            }
        }

        $independent = self::csqHasIndependentQuestionRoles($csq);

        return new self(
            concepts: $concepts,
            relationType: $csq->relation->type,
            relationFrom: $csq->relation->from,
            relationTo: $csq->relation->to,
            source: self::SOURCE_CSQ,
            hasIndependentQuestionRoles: $independent,
            propertyOfRole: $csq->property->ofRole,
            researchContext: $csq->researchContext,
            roleAccounting: $roleAccounting,
            waivers: $waivers,
            projections: [
                'entity' => $entity,
                'target' => $target,
                'process' => $process,
                'property' => $property,
                'relation' => $relation,
                'context' => $context,
                'conditions' => $conditions,
                'time' => $time,
                'geography' => $geography,
                'evidence' => $evidence,
                'resolution' => $resolution,
                'crop_binding' => $cropBinding,
                'scholarly_property_role' => $scholarlyRole,
                'scholarly_property_terms' => $scholarlyOrdered,
                'scholarly_factors' => $factors,
            ],
        );
    }

    /**
     * Isolated compatibility path. Never called by fromCanonical().
     */
    public static function fromLegacyQuery(AgriculturalKnowledgeQuery $query): self
    {
        $concepts = [];
        $entity = $query->namedEntitySurface();
        if ($entity === null || trim($entity) === '') {
            $entity = is_string($query->crop) && trim($query->crop) !== '' ? trim($query->crop) : null;
        }
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_ENTITY, $entity, $query->cropId, self::PRIORITY_REQUIRED);

        $process = trim((string) ($query->constraints['process_surface'] ?? $query->constraints['causal_affector'] ?? ''));
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_PROCESS, $process !== '' ? $process : null, null, self::PRIORITY_REQUIRED);

        $target = trim((string) ($query->constraints['target_surface'] ?? ''));
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_TARGET, $target !== '' ? $target : null, null, self::PRIORITY_REQUIRED);

        $property = trim((string) ($query->constraints['requested_property_surface'] ?? $query->constraints['requested_property'] ?? ''));
        $ofRole = isset($query->constraints['property_of_role']) ? (string) $query->constraints['property_of_role'] : null;
        self::pushConcept($concepts, CanonicalScientificQuestion::ROLE_PROPERTY, $property !== '' ? $property : null, null, self::PRIORITY_REQUIRED, $ofRole);

        $factors = $query->constraints['scientific_factors'] ?? [];
        if (is_array($factors)) {
            foreach ($factors as $factor) {
                $label = trim((string) $factor);
                if ($label !== '') {
                    self::pushConcept($concepts, 'factor', $label, null, self::PRIORITY_REQUIRED);
                }
            }
        }

        $sense = trim((string) ($query->constraints['scientific_sense'] ?? ''));
        if ($sense !== '') {
            self::pushConcept($concepts, 'context_sense', str_replace('_', ' ', $sense), $sense, self::PRIORITY_SUPPORTING);
        }

        $relationType = trim((string) ($query->constraints['relation_type'] ?? CanonicalScientificQuestion::RELATION_NONE));
        if ($relationType === '') {
            $relationType = CanonicalScientificQuestion::RELATION_NONE;
        }

        return new self(
            concepts: $concepts,
            relationType: $relationType,
            relationFrom: isset($query->constraints['relation_from']) ? (string) $query->constraints['relation_from'] : null,
            relationTo: isset($query->constraints['relation_to']) ? (string) $query->constraints['relation_to'] : null,
            source: self::SOURCE_LEGACY,
            hasIndependentQuestionRoles: $target !== '' || $process !== ''
                || $relationType !== CanonicalScientificQuestion::RELATION_NONE,
            propertyOfRole: $ofRole,
            researchContext: '',
            roleAccounting: [],
            waivers: [],
            projections: [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'concepts' => $this->concepts,
            'relation_type' => $this->relationType,
            'relation_from' => $this->relationFrom,
            'relation_to' => $this->relationTo,
            'source' => $this->source,
            'has_independent_question_roles' => $this->hasIndependentQuestionRoles,
            'property_of_role' => $this->propertyOfRole,
            'research_context' => $this->researchContext,
            'role_accounting' => $this->roleAccounting,
            'waivers' => $this->waivers,
            'projections' => $this->projections,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $concepts = [];
        foreach ($data['concepts'] ?? [] as $concept) {
            if (! is_array($concept) || trim((string) ($concept['surface'] ?? '')) === '') {
                continue;
            }
            $concepts[] = [
                'role' => (string) ($concept['role'] ?? ''),
                'surface' => trim((string) $concept['surface']),
                'normalized' => isset($concept['normalized']) ? (string) $concept['normalized'] : null,
                'priority' => (string) ($concept['priority'] ?? self::PRIORITY_REQUIRED),
                'of_role' => isset($concept['of_role']) ? (string) $concept['of_role'] : null,
                'kind' => isset($concept['kind']) ? (string) $concept['kind'] : null,
                'resolution' => isset($concept['resolution']) ? (string) $concept['resolution'] : null,
            ];
        }

        return new self(
            concepts: $concepts,
            relationType: (string) ($data['relation_type'] ?? CanonicalScientificQuestion::RELATION_NONE),
            relationFrom: isset($data['relation_from']) ? (string) $data['relation_from'] : null,
            relationTo: isset($data['relation_to']) ? (string) $data['relation_to'] : null,
            source: (string) ($data['source'] ?? self::SOURCE_LEGACY),
            hasIndependentQuestionRoles: (bool) ($data['has_independent_question_roles'] ?? false),
            propertyOfRole: isset($data['property_of_role']) ? (string) $data['property_of_role'] : null,
            researchContext: (string) ($data['research_context'] ?? ''),
            roleAccounting: is_array($data['role_accounting'] ?? null) ? $data['role_accounting'] : [],
            waivers: is_array($data['waivers'] ?? null) ? $data['waivers'] : [],
            projections: is_array($data['projections'] ?? null) ? $data['projections'] : [],
        );
    }

    /**
     * Ordered scholarly property bag after R8 factor interleave.
     *
     * @return list<string>
     */
    public function orderedScholarlyPropertyTerms(): array
    {
        $projected = $this->projections['scholarly_property_terms'] ?? null;
        if (is_array($projected) && $projected !== []) {
            $terms = [];
            foreach ($projected as $term) {
                $label = trim((string) $term);
                if ($label !== '' && ! in_array($label, $terms, true)) {
                    $terms[] = $label;
                }
            }

            return $terms;
        }

        $terms = [];
        foreach ($this->conceptsForRole(self::ROLE_SCHOLARLY_PROPERTY) as $concept) {
            $term = $this->compileTerm($concept);
            if ($term !== '' && ! in_array($term, $terms, true)) {
                $terms[] = $term;
            }
        }

        return $terms;
    }

    /**
     * CSQ-derived factors in freeze order. Never re-reads the question.
     *
     * @return list<string>
     */
    public function orderedFactors(): array
    {
        $projected = $this->projections['scholarly_factors'] ?? null;
        if (is_array($projected)) {
            $factors = [];
            foreach ($projected as $factor) {
                $label = trim((string) $factor);
                if ($label !== '' && ! in_array($label, $factors, true)) {
                    $factors[] = $label;
                }
            }

            return $factors;
        }

        $factors = [];
        foreach ($this->conceptsForRole(self::ROLE_FACTOR) as $concept) {
            $term = $this->compileTerm($concept);
            if ($term !== '' && ! in_array($term, $factors, true)) {
                $factors[] = $term;
            }
        }

        return $factors;
    }

    public function scholarlyPropertyRole(): string
    {
        $role = $this->projections['scholarly_property_role'] ?? '';

        return is_string($role) ? $role : '';
    }

    /**
     * Family key for freeze (R4). Surface may be longer than the family token.
     */
    public static function canonicalFamilyKey(?string $key, ?string $surface, ?string $sense = null): ?string
    {
        $key = mb_strtolower(trim((string) $key));
        $surface = mb_strtolower(trim((string) $surface));
        $sense = mb_strtolower(trim((string) $sense));
        foreach ([$key, $surface] as $candidate) {
            if ($candidate === '') {
                continue;
            }
            if ($candidate === 'range') {
                return 'temperature';
            }
            if (in_array($candidate, ['rate', 'dose'], true)) {
                return 'quantity';
            }
            if (in_array($candidate, self::FAMILY_KEYS, true)) {
                return $candidate;
            }
        }

        $hay = trim($key.' '.$surface.' '.$sense);
        if ($hay === '') {
            return $key !== '' ? $key : null;
        }
        if (self::hayContainsAny($hay, ['temperature', 'حرارة', 'sıcaklık', 'sicaklik', 'température', 'thermal', 'germination', 'إنبات', 'انبات'])) {
            return 'temperature';
        }
        if (self::hayContainsAny($hay, ['irrigation', 'water requirement', 'evapotranspiration', 'ري', 'الري', 'ماء'])) {
            return 'irrigation';
        }
        if (self::hayContainsAny($hay, ['classification', 'types', 'varieties', 'breeds', 'inventory'])) {
            return 'classification';
        }
        if (self::hayContainsAny($hay, ['quantity', 'rate', 'dose', 'كمية'])) {
            return 'quantity';
        }

        return $key !== '' ? $key : null;
    }

    /**
     * Same role taxonomy as RSC, classified only from frozen CSQ fields.
     */
    public static function propertyRoleFromCsq(CanonicalScientificQuestion $csq): string
    {
        $key = mb_strtolower(trim((string) $csq->property->key));
        $surface = mb_strtolower(trim((string) ($csq->property->surface ?? '')));
        $sense = mb_strtolower(trim((string) $csq->context->scientificSense));
        $intent = mb_strtolower(trim((string) $csq->context->researchIntent));
        $family = self::canonicalFamilyKey($key, $surface, $sense);

        if ($family === 'irrigation'
            || str_contains($sense, 'water')
            || str_contains($sense, 'irrigation')
            || str_contains($sense, 'evapotranspiration')
            || in_array($intent, ['irrigation', 'water_management', 'crop_water_requirement'], true)
            || self::hayContainsAny($surface, ['water', 'irrigation', 'moisture', 'rainfall', 'humidity', 'evapotranspiration', 'مياه', 'ماء', 'ري'])) {
            return RetrievalSemanticContract::ROLE_IRRIGATION_WATER;
        }
        if ($family === 'temperature'
            || in_array($key, ['temperature', 'range'], true)
            || str_contains($sense, 'germinat')
            || str_contains($sense, 'thermal')
            || $sense === 'seed_germination'
            || in_array($intent, ['thermal_requirement', 'germination'], true)
            || self::hayContainsAny($surface, ['temperature', 'heat', 'thermal', 'germination', 'حرارة', 'درجة'])) {
            return RetrievalSemanticContract::ROLE_TEMPERATURE;
        }
        if ($family === 'classification'
            || in_array($key, ['classification', 'types', 'inventory'], true)
            || in_array($sense, ['varieties', 'breeds', 'classification', 'plant_family_members', 'land_classification'], true)
            || in_array($intent, ['classification', 'inventory'], true)) {
            return RetrievalSemanticContract::ROLE_CLASSIFICATION;
        }
        if (in_array($key, ['yield', 'production'], true)
            || in_array($sense, ['production_quantity', 'yield'], true)
            || in_array($intent, ['statistical_lookup', 'productivity'], true)) {
            return RetrievalSemanticContract::ROLE_PRODUCTIVITY;
        }
        if ($family === 'quantity' || in_array($key, ['quantity', 'rate', 'dose'], true)
            || self::hayContainsAny($surface, ['quantity', 'rate', 'dose', 'كمية'])) {
            return RetrievalSemanticContract::ROLE_QUANTITY;
        }
        if ($key === '' && $surface === '' && $sense === '') {
            return RetrievalSemanticContract::ROLE_UNRESOLVED;
        }

        return RetrievalSemanticContract::ROLE_OTHER;
    }

    /**
     * @param  list<string>  $incomingTerms
     * @return list<string>
     */
    public static function materializeScholarlyTerms(string $role, string $surface, array $incomingTerms): array
    {
        $terms = [];
        foreach ($incomingTerms as $term) {
            $label = trim((string) $term);
            if ($label !== '' && ! in_array($label, $terms, true)) {
                $terms[] = $label;
            }
        }
        $surface = trim($surface);
        if ($surface !== '' && ! self::listContainsTerm($terms, $surface)) {
            array_unshift($terms, $surface);
        }

        $extra = match ($role) {
            RetrievalSemanticContract::ROLE_IRRIGATION_WATER => self::SCHOLARLY_WATER_TERMS,
            RetrievalSemanticContract::ROLE_TEMPERATURE => self::SCHOLARLY_TEMPERATURE_TERMS,
            RetrievalSemanticContract::ROLE_CLASSIFICATION => self::SCHOLARLY_CLASSIFICATION_TERMS,
            RetrievalSemanticContract::ROLE_PRODUCTIVITY => self::SCHOLARLY_PRODUCTIVITY_TERMS,
            RetrievalSemanticContract::ROLE_QUANTITY => self::SCHOLARLY_QUANTITY_TERMS,
            default => [],
        };
        foreach ($extra as $term) {
            if (! in_array($term, $terms, true)) {
                $terms[] = $term;
            }
        }

        if (in_array($role, [
            RetrievalSemanticContract::ROLE_IRRIGATION_WATER,
            RetrievalSemanticContract::ROLE_TEMPERATURE,
            RetrievalSemanticContract::ROLE_CLASSIFICATION,
            RetrievalSemanticContract::ROLE_QUANTITY,
            RetrievalSemanticContract::ROLE_UNRESOLVED,
        ], true)) {
            $terms = array_values(array_filter(
                $terms,
                static fn (string $term): bool => ! in_array(mb_strtolower(trim($term)), self::PRODUCTIVITY_FALLBACK_TERMS, true),
            ));
        }

        return $terms;
    }

    /**
     * R8: factors occupy the slot immediately after the first property term.
     *
     * @param  list<string>  $terms
     * @param  list<string>  $factors
     * @return list<string>
     */
    public static function interleaveRequiredFactors(array $terms, array $factors): array
    {
        if ($factors === []) {
            return array_values(array_unique($terms));
        }
        $head = array_slice($terms, 0, 1);
        $rest = array_slice($terms, 1);
        $ordered = [];
        foreach ([...$head, ...$factors, ...$rest] as $term) {
            $label = trim((string) $term);
            if ($label !== '' && ! in_array($label, $ordered, true)) {
                $ordered[] = $label;
            }
        }

        return $ordered;
    }

    /**
     * @return list<string>
     */
    public static function factorsFromCsq(CanonicalScientificQuestion $csq): array
    {
        $factors = [];
        $process = trim((string) ($csq->process->surface ?? ''));
        if ($process !== '') {
            $factors[] = $process;
        }
        foreach ($csq->conditions->items as $condition) {
            if (! is_array($condition)) {
                continue;
            }
            $label = trim((string) ($condition['label'] ?? $condition['value'] ?? $condition['type'] ?? ''));
            if ($label !== '' && ! in_array($label, $factors, true)) {
                $factors[] = $label;
            }
        }

        return $factors;
    }

    /**
     * @param  list<string>  $needles
     */
    private static function hayContainsAny(string $hay, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($hay, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $terms
     */
    private static function listContainsTerm(array $terms, string $needle): bool
    {
        $folded = mb_strtolower(trim($needle));
        foreach ($terms as $term) {
            if (mb_strtolower(trim((string) $term)) === $folded) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function requiredTerms(): array
    {
        $terms = [];
        foreach ($this->concepts as $concept) {
            if (($concept['priority'] ?? '') !== self::PRIORITY_REQUIRED) {
                continue;
            }
            $term = $this->compileTerm($concept);
            if ($term !== '' && ! in_array($term, $terms, true)) {
                $terms[] = $term;
            }
        }

        return $terms;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function conceptsForRole(string $role): array
    {
        return array_values(array_filter(
            $this->concepts,
            static fn (array $concept): bool => ($concept['role'] ?? '') === $role,
        ));
    }

    /**
     * Execution normalization for current consumers (RSC / QueryBuilder).
     * Not the frozen CSQ surface. Use frozenSurface() for source-language identity.
     *
     * @param  array<string, mixed>  $concept
     */
    public function compileTerm(array $concept): string
    {
        $normalized = trim((string) ($concept['normalized'] ?? ''));
        if ($normalized !== '' && preg_match('/\p{Arabic}/u', $normalized) !== 1) {
            return str_replace('_', ' ', $normalized);
        }

        return trim((string) ($concept['surface'] ?? ''));
    }

    /**
     * Frozen CSQ surface. Does not replace meaning with key/normalized.
     *
     * @param  array<string, mixed>  $concept
     */
    public function frozenSurface(array $concept): string
    {
        return trim((string) ($concept['surface'] ?? ''));
    }

    /**
     * Role-driven independence. Crop binding, sense, intent, domain, geography,
     * time, evidence, and ENTITY-only (including crop-echo entity) do not count.
     */
    public static function csqHasIndependentQuestionRoles(CanonicalScientificQuestion $csq): bool
    {
        if (self::hasQuestionSurface($csq->target->surface)
            || self::hasQuestionSurface($csq->process->surface)
            || self::hasQuestionSurface($csq->property->surface)) {
            return true;
        }

        $relationType = trim((string) $csq->relation->type);

        return $relationType !== '' && $relationType !== CanonicalScientificQuestion::RELATION_NONE;
    }

    private static function hasQuestionSurface(?string $surface): bool
    {
        return is_string($surface) && trim($surface) !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private static function entityProjection(CanonicalScientificQuestion $csq): array
    {
        return [
            'surface' => $csq->entity->surface,
            'normalized' => $csq->entity->normalized,
            'role' => $csq->entity->role,
            'resolution' => $csq->entity->resolution,
            'canonicalId' => $csq->entity->canonicalId,
            'canonicalNamespace' => $csq->entity->canonicalNamespace,
            'label' => $csq->entity->label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function targetProjection(CanonicalScientificQuestion $csq): array
    {
        return [
            'surface' => $csq->target->surface,
            'kind' => $csq->target->kind,
            'normalizedKey' => $csq->target->normalizedKey,
            'resolution' => $csq->target->resolution,
            'canonicalId' => $csq->target->canonicalId,
            'canonicalNamespace' => $csq->target->canonicalNamespace,
            'label' => $csq->target->label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function processProjection(CanonicalScientificQuestion $csq): array
    {
        return [
            'surface' => $csq->process->surface,
            'normalized' => $csq->process->normalized,
            'resolution' => $csq->process->resolution,
            'canonicalId' => $csq->process->canonicalId,
            'canonicalNamespace' => $csq->process->canonicalNamespace,
            'label' => $csq->process->label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function propertyProjection(CanonicalScientificQuestion $csq): array
    {
        return [
            'key' => $csq->property->key,
            'surface' => $csq->property->surface,
            'ofRole' => $csq->property->ofRole,
            'unit' => $csq->property->unit,
            'resolution' => $csq->property->resolution,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $concepts
     */
    private static function pushConcept(
        array &$concepts,
        string $role,
        ?string $surface,
        ?string $normalized,
        string $priority,
        ?string $ofRole = null,
        ?string $kind = null,
        ?string $resolution = null,
    ): void {
        $surface = is_string($surface) ? trim($surface) : '';
        if ($surface === '') {
            return;
        }
        $concepts[] = [
            'role' => $role,
            'surface' => $surface,
            'normalized' => is_string($normalized) && trim($normalized) !== '' ? trim($normalized) : null,
            'priority' => $priority,
            'of_role' => $ofRole,
            'kind' => $kind,
            'resolution' => $resolution,
        ];
    }
}
