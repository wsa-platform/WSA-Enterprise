<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Identity-critical facet vocabulary (Projection + C9 Design §13) — frozen list.
 *
 * Cap/Projection support claims only; Projection never mutates CSQ facets.
 */
final class ProjectionIdentityCriticalFacets
{
    /** @var list<string> */
    public const CODES = [
        'species',
        'genus',
        'taxonomy_role',
        'cultivar',
        'breed',
        'strain',
        'genotype',
        'phenotype',
        'qtl',
        'germplasm',
        'plant_part',
        'life_stage',
        'disease',
        'pathogen',
        'pest',
        'comparator',
        'dose',
        'concentration',
        'duration',
        'frequency',
        'application_method',
        'measurement',
        'unit',
        'geography',
        'environment',
        'experimental_condition',
        'treatment',
        'outcome',
        'entity',
        'process',
        'property',
        'relation',
        'time',
    ];

    public static function isIdentityCritical(string $facetCode): bool
    {
        $normalized = strtolower(trim($facetCode));

        return in_array($normalized, self::CODES, true);
    }

    public static function normalize(string $facetCode): string
    {
        $trimmed = strtolower(trim($facetCode));
        if ($trimmed === '') {
            throw new ProjectionInvariantViolation('Facet code must be a non-empty string.');
        }

        return $trimmed;
    }
}
