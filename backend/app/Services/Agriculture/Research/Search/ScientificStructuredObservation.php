<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Provider-independent structured observation extracted from a search result.
 */
final class ScientificStructuredObservation
{
    public function __construct(
        public readonly string $entity,
        public readonly string $location,
        public readonly string $year,
        public readonly string $property,
        public readonly string $unit,
        public readonly string $value,
        public readonly string $domain = '',
        public readonly string $entityCode = '',
        public readonly string $locationCode = '',
        public readonly string $propertyCode = '',
        public readonly string $flag = '',
    ) {}

    public static function fromResult(ScientificSearchResult $result): ?self
    {
        $raw = is_array($result->rawMetadata) ? $result->rawMetadata : [];
        $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];
        $bag = [];
        foreach (['observation', 'statistical', 'faostat', 'structured'] as $key) {
            if (is_array($raw[$key] ?? null)) {
                $bag = $raw[$key];
                break;
            }
        }
        if ($bag === [] && is_array($meta['observation'] ?? null)) {
            $bag = $meta['observation'];
        }

        return self::fromBag($bag);
    }

    public static function fromEvidenceItem(ScientificEvidenceItem $item): ?self
    {
        $bag = [];
        foreach ([$item->qualityFactors, $item->sourceAttribution] as $meta) {
            if (! is_array($meta)) {
                continue;
            }
            foreach (['observation', 'statistical', 'structured'] as $key) {
                if (is_array($meta[$key] ?? null)) {
                    $bag = $meta[$key];
                    break 2;
                }
            }
        }

        $parsed = self::fromBag($bag) ?? self::fromEvidenceSurfaces($item);
        if ($parsed === null) {
            return null;
        }

        $entity = $parsed->entity !== '' ? $parsed->entity : trim((string) ($item->cropOrEntity ?? ''));
        $year = $parsed->year !== ''
            ? $parsed->year
            : trim((string) ($item->qualityFactors['observation_year'] ?? $item->publicationYear ?? ''));

        if ($entity === '' && $parsed->location === '' && $year === '' && $parsed->value === '') {
            return null;
        }

        return new self(
            entity: $entity,
            location: $parsed->location,
            year: $year,
            property: $parsed->property,
            unit: $parsed->unit,
            value: $parsed->value,
            domain: $parsed->domain,
            entityCode: $parsed->entityCode,
            locationCode: $parsed->locationCode,
            propertyCode: $parsed->propertyCode,
            flag: $parsed->flag,
        );
    }

    /**
     * @param  array<string, mixed>  $bag
     */
    private static function fromBag(array $bag): ?self
    {
        $entity = self::first($bag, ['item', 'entity', 'crop']);
        $location = self::first($bag, ['area', 'location', 'country']);
        $year = self::first($bag, ['year', 'year_code', 'time']);
        $property = self::first($bag, ['element', 'property', 'measure']);
        $unit = self::first($bag, ['unit']);
        $value = self::first($bag, ['value']);
        $domain = self::first($bag, ['domain', 'domain_code']);
        $entityCode = self::first($bag, ['item_code', 'entity_code']);
        $locationCode = self::first($bag, ['area_code', 'location_code']);
        $propertyCode = self::first($bag, ['query_element_code', 'element_code', 'property_code']);
        $flag = self::first($bag, ['flag']);

        if ($entity === '' && $location === '' && $year === '' && $value === '') {
            return null;
        }

        return new self(
            entity: $entity,
            location: $location,
            year: $year,
            property: $property,
            unit: $unit,
            value: $value,
            domain: $domain,
            entityCode: $entityCode,
            locationCode: $locationCode,
            propertyCode: $propertyCode,
            flag: $flag,
        );
    }

    private static function fromEvidenceSurfaces(ScientificEvidenceItem $item): ?self
    {
        $parts = preg_split('/\s+[—–-]\s+/u', trim($item->publicationTitle)) ?: [];
        $parts = array_values(array_filter(array_map(
            static fn (mixed $part): string => trim((string) $part),
            $parts,
        ), static fn (string $part): bool => $part !== ''));

        $entity = $parts[0] ?? '';
        $property = $parts[1] ?? '';
        $location = $parts[2] ?? '';
        $year = $parts[3] ?? '';
        if ($year === '' && isset($parts[2]) && preg_match('/^(?:19|20)\d{2}$/', $parts[2]) === 1) {
            $year = $parts[2];
            $location = '';
        }

        $value = '';
        $unit = '';
        $text = trim((string) $item->evidenceText);
        if (preg_match('/Value:\s*([0-9][0-9.,]*)\s*([^\s,;]+)/iu', $text, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            $unit = trim((string) ($matches[2] ?? ''));
        }

        if ($entity === '') {
            $entity = trim((string) ($item->cropOrEntity ?? ''));
        }
        if ($year === '' && $item->publicationYear !== null) {
            $year = (string) $item->publicationYear;
        }

        if ($entity === '' && $location === '' && $year === '' && $value === '') {
            return null;
        }

        return new self(
            entity: $entity,
            location: $location,
            year: $year,
            property: $property,
            unit: $unit,
            value: $value,
        );
    }

    public function isComplete(): bool
    {
        return $this->entity !== ''
            && $this->location !== ''
            && $this->year !== ''
            && $this->property !== ''
            && $this->value !== '';
    }

    public function identityKey(): string
    {
        return implode('|', [
            mb_strtolower(trim($this->entityCode !== '' ? $this->entityCode : $this->entity)),
            mb_strtolower(trim($this->locationCode !== '' ? $this->locationCode : $this->location)),
            trim($this->year),
            ScientificStatisticalClaimAligner::measureFamily($this->property),
            mb_strtolower(trim($this->unit)),
            trim($this->value),
        ]);
    }

    /**
     * @param  array<string, mixed>  $bag
     * @param  list<string>  $keys
     */
    private static function first(array $bag, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($bag[$key]) && trim((string) $bag[$key]) !== '') {
                return trim((string) $bag[$key]);
            }
        }

        return '';
    }
}
