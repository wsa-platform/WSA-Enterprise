<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;

/**
 * Extracts present CSQ facet codes for Projection (OD-C). Does not invent Cap cells.
 * Codes are Projection vocabulary only; Builder still owns HTTP query text (D-04).
 */
final class CsqPresentFacetCodes
{
    /**
     * @return list<string>
     */
    public static function fromQuestion(?CanonicalScientificQuestion $question): array
    {
        if ($question === null) {
            return [];
        }

        $codes = [];
        if (self::present($question->entity->surface ?? null)
            || self::present($question->entity->normalized ?? null)
        ) {
            $codes[] = 'entity';
            $codes[] = 'species';
        }
        if (self::present($question->process->surface ?? null)) {
            $codes[] = 'process';
        }
        if (self::present($question->property->surface ?? null)
            || self::present($question->property->key ?? null)
        ) {
            $codes[] = 'property';
        }
        if (($question->relation->type ?? '') !== ''
            && ($question->relation->type ?? '') !== CanonicalScientificQuestion::RELATION_NONE
        ) {
            $codes[] = 'relation';
        }
        if (self::geographyPresent($question)) {
            $codes[] = 'geography';
        }
        if (self::timePresent($question)) {
            $codes[] = 'time';
        }

        $unique = [];
        $seen = [];
        foreach ($codes as $code) {
            $normalized = ProjectionIdentityCriticalFacets::normalize($code);
            if (! isset($seen[$normalized])) {
                $seen[$normalized] = true;
                $unique[] = $normalized;
            }
        }

        return $unique;
    }

    private static function present(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private static function geographyPresent(CanonicalScientificQuestion $question): bool
    {
        $geo = $question->geography;

        return self::present($geo->label)
            || self::present($geo->country)
            || self::present($geo->region)
            || self::present($geo->canonicalId);
    }

    private static function timePresent(CanonicalScientificQuestion $question): bool
    {
        $time = $question->time;

        return $time->year !== null
            || self::present($time->period)
            || $time->startYear !== null
            || $time->endYear !== null;
    }
}
