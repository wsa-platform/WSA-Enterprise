<?php

namespace App\Services\Agriculture\Research;

/**
 * Immutable Canonical Scientific Question (CSQ) V1.
 *
 * Semantic authority structure only. Phase 1 does not populate or consume roles.
 * Provider queries, FAOSTAT codes, evidence, ranking, and answers do not belong here.
 */
final readonly class CanonicalScientificQuestion
{
    public const RESEARCH_CONTEXT_HOME = 'home';

    public const RESEARCH_CONTEXT_CROP_PROFILE = 'crop_profile';

    public const RESOLUTION_NONE = 'none';

    public const RESOLUTION_UNRESOLVED = 'unresolved';

    public const RESOLUTION_RESOLVED = 'resolved';

    public const TARGET_KIND_PRODUCT_OUTPUT = 'product_output';

    public const TARGET_KIND_AFFECTED_OBJECT = 'affected_object';

    public const TARGET_KIND_NONE = 'none';

    public const PROPERTY_OF_ENTITY = 'entity';

    public const PROPERTY_OF_TARGET = 'target';

    public const PROPERTY_OF_PROCESS = 'process';

    public const RELATION_CAUSAL = 'causal';

    public const RELATION_COMPARATIVE = 'comparative';

    public const RELATION_ASSOCIATIVE = 'associative';

    public const RELATION_DESCRIPTIVE = 'descriptive';

    public const RELATION_TEMPORAL = 'temporal';

    public const RELATION_SPATIAL = 'spatial';

    public const RELATION_NONE = 'none';

    public const ROLE_ENTITY = 'entity';

    public const ROLE_TARGET = 'target';

    public const ROLE_PROCESS = 'process';

    public const ROLE_PROPERTY = 'property';

    public const NAMESPACE_TAXONOMY_CROP = 'taxonomy.crop';

    public const NAMESPACE_CATALOG_LIVESTOCK = 'catalog.livestock';

    /** @var list<string> */
    public const GENERIC_PROCESS_SURFACES = [
        'physiology', 'water', 'uptake', 'germination', 'production', 'yield',
        'growth', 'stress', 'temperature', 'salinity', 'irrigation', 'fertilization',
        'quantity', 'rate', 'classification', 'types', 'inventory', 'requirement',
        'moisture', 'humidity', 'evapotranspiration', 'concentration',
        'فسيولوجيا', 'مياه', 'ماء', 'ري', 'إنتاج', 'غلة', 'نمو', 'حرارة',
        'ملوحة', 'كمية', 'إنبات',
    ];

    /** @var list<string> */
    public const PRODUCTIVITY_FALLBACK_SURFACES = ['yield', 'production'];

    public function __construct(
        public string $originalQuestion,
        public string $language,
        public string $normalizedForm = '',
        public string $researchContext = '',
        public CsqEntity $entity = new CsqEntity(),
        public CsqTarget $target = new CsqTarget(),
        public CsqProcess $process = new CsqProcess(),
        public CsqProperty $property = new CsqProperty(),
        public CsqRelation $relation = new CsqRelation(),
        public CsqContext $context = new CsqContext(),
        public CsqConditions $conditions = new CsqConditions(),
        public CsqTime $time = new CsqTime(),
        public CsqGeography $geography = new CsqGeography(),
        public CsqEvidenceRequirement $evidenceRequirement = new CsqEvidenceRequirement(),
        public CsqResolution $resolution = new CsqResolution(),
        public CsqCropBinding $cropBinding = new CsqCropBinding(),
    ) {}

    public static function unpopulated(
        string $originalQuestion,
        string $language,
        string $normalizedForm = '',
        string $researchContext = '',
    ): self {
        return new self(
            originalQuestion: $originalQuestion,
            language: $language,
            normalizedForm: $normalizedForm,
            researchContext: $researchContext,
        );
    }

    /**
     * Freeze a provider-independent role graph. Unknown fields stay null/unresolved.
     *
     * @param  array<string, mixed>  $graph
     * @param  array<string, mixed>  $context
     */
    public static function fromRoleGraph(
        string $originalQuestion,
        string $language,
        string $normalizedForm,
        string $researchContext,
        array $graph,
        array $context = [],
        CsqResolution $resolution = new CsqResolution(),
        CsqCropBinding $cropBinding = new CsqCropBinding(),
        CsqConditions $conditions = new CsqConditions(),
        CsqTime $time = new CsqTime(),
        CsqGeography $geography = new CsqGeography(),
        CsqEvidenceRequirement $evidenceRequirement = new CsqEvidenceRequirement(),
    ): self {
        $entitySurface = self::nullableString($graph['entity_surface'] ?? null);
        $targetSurface = self::nullableString($graph['target_surface'] ?? null);
        $processSurface = self::nullableString($graph['process_surface'] ?? null);
        $propertyKey = self::nullableString($graph['property_key'] ?? null);
        $propertySurface = self::nullableString($graph['property_surface'] ?? null);

        return new self(
            originalQuestion: $originalQuestion,
            language: $language,
            normalizedForm: $normalizedForm,
            researchContext: $researchContext,
            entity: self::entity(
                surface: $entitySurface,
                normalized: self::nullableString($graph['entity_normalized'] ?? null),
                resolution: self::resolutionValue($graph['entity_resolution'] ?? null, $entitySurface),
                canonicalId: self::nullableString($graph['entity_canonical_id'] ?? null),
                canonicalNamespace: self::nullableString($graph['entity_canonical_namespace'] ?? null),
            ),
            target: self::target(
                surface: $targetSurface,
                kind: self::targetKindValue($graph['target_kind'] ?? null, $targetSurface),
                resolution: self::resolutionValue($graph['target_resolution'] ?? null, $targetSurface),
                normalizedKey: self::nullableString($graph['target_normalized_key'] ?? null),
                canonicalId: self::nullableString($graph['target_canonical_id'] ?? null),
                canonicalNamespace: self::nullableString($graph['target_canonical_namespace'] ?? null),
            ),
            process: self::process(
                surface: $processSurface,
                resolution: self::resolutionValue($graph['process_resolution'] ?? null, $processSurface),
            ),
            property: self::property(
                key: $propertyKey,
                surface: $propertySurface,
                ofRole: self::nullableString($graph['property_of_role'] ?? null),
                resolution: ($propertyKey !== null || $propertySurface !== null)
                    ? self::RESOLUTION_RESOLVED
                    : self::RESOLUTION_NONE,
            ),
            relation: self::relation(
                type: is_string($graph['relation_type'] ?? null) && $graph['relation_type'] !== ''
                    ? (string) $graph['relation_type']
                    : self::RELATION_NONE,
                from: self::nullableString($graph['relation_from'] ?? null),
                to: self::nullableString($graph['relation_to'] ?? null),
                state: is_string($graph['relation_state'] ?? null) && $graph['relation_state'] !== ''
                    ? (string) $graph['relation_state']
                    : self::RESOLUTION_NONE,
                operands: self::comparisonOperands($graph['comparison_operands'] ?? []),
            ),
            context: new CsqContext(
                domain: self::nullableString($context['domain'] ?? null),
                scientificSense: self::nullableString($context['scientific_sense'] ?? null),
                researchIntent: self::nullableString($context['research_intent'] ?? null),
                questionType: self::nullableString($context['question_type'] ?? null),
                requestedInformation: is_array($context['requested_information'] ?? null)
                    ? array_values(array_filter($context['requested_information'], 'is_string'))
                    : [],
            ),
            conditions: $conditions,
            time: $time,
            geography: $geography,
            evidenceRequirement: $evidenceRequirement,
            resolution: $resolution,
            cropBinding: $cropBinding,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private static function resolutionValue(mixed $value, ?string $surface): string
    {
        if (is_string($value) && in_array($value, [self::RESOLUTION_NONE, self::RESOLUTION_UNRESOLVED, self::RESOLUTION_RESOLVED], true)) {
            return $value;
        }

        return $surface === null ? self::RESOLUTION_NONE : self::RESOLUTION_UNRESOLVED;
    }

    private static function targetKindValue(mixed $value, ?string $surface): string
    {
        if (is_string($value) && in_array($value, [self::TARGET_KIND_PRODUCT_OUTPUT, self::TARGET_KIND_AFFECTED_OBJECT, self::TARGET_KIND_NONE], true)) {
            return $value;
        }

        return $surface === null ? self::TARGET_KIND_NONE : self::TARGET_KIND_AFFECTED_OBJECT;
    }

    public static function entity(
        ?string $surface = null,
        ?string $normalized = null,
        ?string $role = self::ROLE_ENTITY,
        string $resolution = self::RESOLUTION_NONE,
        ?string $canonicalId = null,
        ?string $canonicalNamespace = null,
        ?string $label = null,
    ): CsqEntity {
        return new CsqEntity(
            surface: $surface,
            normalized: $normalized,
            role: $role,
            resolution: $resolution,
            canonicalId: $canonicalId,
            canonicalNamespace: $canonicalNamespace,
            label: $label,
        );
    }

    public static function target(
        ?string $surface = null,
        string $kind = self::TARGET_KIND_NONE,
        ?string $normalizedKey = null,
        string $resolution = self::RESOLUTION_NONE,
        ?string $canonicalId = null,
        ?string $canonicalNamespace = null,
        ?string $label = null,
    ): CsqTarget {
        return new CsqTarget(
            surface: $surface,
            kind: $kind,
            normalizedKey: $normalizedKey,
            resolution: $resolution,
            canonicalId: $canonicalId,
            canonicalNamespace: $canonicalNamespace,
            label: $label,
        );
    }

    public static function process(
        ?string $surface = null,
        ?string $normalized = null,
        string $resolution = self::RESOLUTION_NONE,
        ?string $canonicalId = null,
        ?string $canonicalNamespace = null,
        ?string $label = null,
    ): CsqProcess {
        return new CsqProcess(
            surface: $surface,
            normalized: $normalized,
            resolution: $resolution,
            canonicalId: $canonicalId,
            canonicalNamespace: $canonicalNamespace,
            label: $label,
        );
    }

    public static function property(
        ?string $key = null,
        ?string $surface = null,
        ?string $ofRole = null,
        ?string $unit = null,
        string $resolution = self::RESOLUTION_NONE,
    ): CsqProperty {
        return new CsqProperty(
            key: $key,
            surface: $surface,
            ofRole: $ofRole,
            unit: $unit,
            resolution: $resolution,
        );
    }

    /**
     * @param  list<array{surface: string, normalized: ?string, resolution: string}>  $operands
     */
    public static function relation(
        string $type = self::RELATION_NONE,
        ?string $from = null,
        ?string $to = null,
        string $state = self::RESOLUTION_NONE,
        array $operands = [],
    ): CsqRelation {
        return new CsqRelation(
            type: $type,
            from: $from,
            to: $to,
            state: $state,
            operands: $operands,
        );
    }

    public static function isGenericProcessSurface(?string $surface): bool
    {
        $folded = self::foldGenericProcessSurface((string) $surface);
        if ($folded === '') {
            return false;
        }
        foreach (self::inflectionalSurfaceCandidates($folded) as $candidate) {
            if (in_array($candidate, self::GENERIC_PROCESS_SURFACES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fold a surface for generic-process matching. Does not invent an entity.
     */
    public static function foldGenericProcessSurface(string $surface): string
    {
        return mb_strtolower(trim($surface));
    }

    /**
     * Arabic proclitics attached to a process token (لـ / ال / بـ / و).
     */
    public static function stripProcessProclitics(string $folded): string
    {
        $stripped = preg_replace('/^(?:ال|لل|ل|ب|و)+/u', '', $folded);

        return is_string($stripped) ? trim($stripped) : $folded;
    }

    /**
     * Catalog lookup candidates for a single identity token.
     * Strips Arabic proclitics and agglutinative possessive/case/plural suffixes,
     * then tries terminal-consonant voicing variants. Does not invent an id.
     *
     * @return list<string>
     */
    public static function inflectionalSurfaceCandidates(string $folded): array
    {
        $folded = mb_strtolower(trim($folded));
        if ($folded === '') {
            return [];
        }

        $bases = [$folded];
        $stripped = self::stripProcessProclitics($folded);
        if ($stripped !== '' && $stripped !== $folded) {
            $bases[] = $stripped;
        }

        $suffixes = [
            'nın', 'nin', 'nun', 'nün',
            'dan', 'den', 'tan', 'ten',
            'lar', 'ler',
            'ın', 'in', 'un', 'ün',
            'sı', 'si', 'su', 'sü',
            'da', 'de', 'ta', 'te',
        ];

        $out = $bases;
        foreach ($bases as $base) {
            $baseLen = mb_strlen($base);
            foreach ($suffixes as $suffix) {
                $suffixLen = mb_strlen($suffix);
                if ($baseLen - $suffixLen < 3) {
                    continue;
                }
                if (mb_substr($base, $baseLen - $suffixLen) !== $suffix) {
                    continue;
                }
                $stem = mb_substr($base, 0, $baseLen - $suffixLen);
                if ($stem === '') {
                    continue;
                }
                $out[] = $stem;
                foreach (self::terminalConsonantVariants($stem) as $variant) {
                    $out[] = $variant;
                }
            }
        }

        return array_values(array_unique(array_filter(
            $out,
            static fn (string $candidate): bool => $candidate !== '',
        )));
    }

    /**
     * @return list<string>
     */
    private static function terminalConsonantVariants(string $stem): array
    {
        $map = [
            'c' => 'ç', 'ç' => 'c',
            'k' => 'ğ', 'ğ' => 'k',
            'p' => 'b', 'b' => 'p',
            't' => 'd', 'd' => 't',
        ];
        $last = mb_substr($stem, -1);
        if ($last === '' || ! isset($map[$last])) {
            return [];
        }

        return [mb_substr($stem, 0, mb_strlen($stem) - 1).$map[$last]];
    }

    public static function isProductivityFallbackSurface(?string $surface): bool
    {
        $folded = mb_strtolower(trim((string) $surface));

        return $folded !== '' && in_array($folded, self::PRODUCTIVITY_FALLBACK_SURFACES, true);
    }

    /**
     * @return list<array{surface: string, normalized: ?string, resolution: string}>
     */
    private static function comparisonOperands(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $operands = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $surface = self::nullableString($item);
                if ($surface === null) {
                    continue;
                }
                $operands[] = [
                    'surface' => $surface,
                    'normalized' => null,
                    'resolution' => self::RESOLUTION_UNRESOLVED,
                ];

                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $surface = self::nullableString($item['surface'] ?? $item['label'] ?? null);
            if ($surface === null) {
                continue;
            }
            $operands[] = [
                'surface' => $surface,
                'normalized' => self::nullableString($item['normalized'] ?? null),
                'resolution' => self::resolutionValue($item['resolution'] ?? null, $surface),
            ];
        }

        return $operands;
    }
}

/**
 * ENTITY role: the scientific subject being studied.
 */
final readonly class CsqEntity
{
    public function __construct(
        public ?string $surface = null,
        public ?string $normalized = null,
        public ?string $role = CanonicalScientificQuestion::ROLE_ENTITY,
        public string $resolution = CanonicalScientificQuestion::RESOLUTION_NONE,
        public ?string $canonicalId = null,
        public ?string $canonicalNamespace = null,
        public ?string $label = null,
    ) {}
}

/**
 * TARGET role: product, output, or affected object. Canonical IDs are optional.
 */
final readonly class CsqTarget
{
    public function __construct(
        public ?string $surface = null,
        public string $kind = CanonicalScientificQuestion::TARGET_KIND_NONE,
        public ?string $normalizedKey = null,
        public string $resolution = CanonicalScientificQuestion::RESOLUTION_NONE,
        public ?string $canonicalId = null,
        public ?string $canonicalNamespace = null,
        public ?string $label = null,
    ) {}
}

/**
 * PROCESS role: cause, intervention, or requested factor.
 */
final readonly class CsqProcess
{
    public function __construct(
        public ?string $surface = null,
        public ?string $normalized = null,
        public string $resolution = CanonicalScientificQuestion::RESOLUTION_NONE,
        public ?string $canonicalId = null,
        public ?string $canonicalNamespace = null,
        public ?string $label = null,
    ) {}
}

/**
 * PROPERTY role: requested measure, related to entity, target, or process.
 */
final readonly class CsqProperty
{
    public function __construct(
        public ?string $key = null,
        public ?string $surface = null,
        public ?string $ofRole = null,
        public ?string $unit = null,
        public string $resolution = CanonicalScientificQuestion::RESOLUTION_NONE,
    ) {}
}

/**
 * RELATION role: explicit relationship between semantic roles.
 */
final readonly class CsqRelation
{
    /**
     * @param  list<array{surface: string, normalized: ?string, resolution: string}>  $operands
     */
    public function __construct(
        public string $type = CanonicalScientificQuestion::RELATION_NONE,
        public ?string $from = null,
        public ?string $to = null,
        public string $state = CanonicalScientificQuestion::RESOLUTION_NONE,
        public array $operands = [],
    ) {}
}

/**
 * Frozen question context. Not a provider query.
 */
final readonly class CsqContext
{
    /**
     * @param  list<string>  $requestedInformation
     */
    public function __construct(
        public ?string $domain = null,
        public ?string $scientificSense = null,
        public ?string $researchIntent = null,
        public ?string $questionType = null,
        public array $requestedInformation = [],
    ) {}
}

/**
 * Typed conditions container. Empty until a later phase populates it.
 */
final readonly class CsqConditions
{
    /**
     * @param  list<array{type: string, value?: string|null, label?: string|null}>  $items
     */
    public function __construct(
        public array $items = [],
    ) {}
}

final readonly class CsqTime
{
    public function __construct(
        public ?int $year = null,
        public ?string $period = null,
        public ?int $startYear = null,
        public ?int $endYear = null,
    ) {}
}

final readonly class CsqGeography
{
    public function __construct(
        public ?string $label = null,
        public ?string $country = null,
        public ?string $region = null,
        public ?string $canonicalId = null,
        public ?string $canonicalNamespace = null,
    ) {}
}

final readonly class CsqEvidenceRequirement
{
    public function __construct(
        public ?string $requiredEvidenceType = null,
        public ?bool $requiresFactualDirect = null,
    ) {}
}

final readonly class CsqResolution
{
    /**
     * @param  list<string>  $clarificationRequirements
     */
    public function __construct(
        public string $ambiguityState = CanonicalScientificQuestion::RESOLUTION_NONE,
        public array $clarificationRequirements = [],
        public ?float $understandingConfidence = null,
    ) {}
}

/**
 * Optional Home/Crop context binding. Not semantic authority for ENTITY/TARGET.
 */
final readonly class CsqCropBinding
{
    public function __construct(
        public ?string $cropId = null,
        public ?string $cropLabel = null,
        public ?string $scientificName = null,
        public ?string $context = null,
    ) {}
}
