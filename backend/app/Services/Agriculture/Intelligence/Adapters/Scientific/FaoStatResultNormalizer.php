<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * FAOSTAT row → ScientificSearchResult. Never invents codes or credentials.
 */
final class FaoStatResultNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function toScientificSearchResult(array $row, string $query): ?ScientificSearchResult
    {
        $item = trim((string) ($row['item'] ?? $row['Item'] ?? $row['item_name'] ?? ''));
        $area = trim((string) ($row['area'] ?? $row['Area'] ?? ''));
        $year = $row['year'] ?? $row['Year'] ?? null;
        $value = $row['value'] ?? $row['Value'] ?? null;
        $unit = trim((string) ($row['unit'] ?? $row['Unit'] ?? ''));
        $element = trim((string) ($row['element'] ?? $row['Element'] ?? ''));

        if ($item === '' && $value === null) {
            return null;
        }

        $titleParts = array_filter([$item, $element, $area, is_numeric($year) ? (string) $year : null]);
        $title = implode(' — ', $titleParts);
        if ($title === '') {
            $title = 'FAOSTAT observation';
        }

        $abstract = trim(implode(' ', array_filter([
            $value !== null ? 'Value: '.$value.($unit !== '' ? ' '.$unit : '') : null,
            $query !== '' ? 'Query context: '.$query : null,
        ])));

        $identifier = implode('|', array_filter([
            (string) ($row['itemCode'] ?? $row['item_code'] ?? ''),
            $area,
            is_numeric($year) ? (string) $year : '',
            $element,
        ]));

        return new ScientificSearchResult(
            sourceKey: FaoStatScientificSourceAdapter::SOURCE_KEY,
            sourceIdentifier: $identifier !== '' ? $identifier : null,
            title: $title,
            authors: ['FAO / FAOSTAT'],
            publicationYear: is_numeric($year) ? (int) $year : null,
            doi: null,
            canonicalUrl: 'https://www.fao.org/faostat/',
            abstract: $abstract !== '' ? $abstract : null,
            journal: 'FAOSTAT',
            foundBySources: [FaoStatScientificSourceAdapter::SOURCE_KEY],
            rawMetadata: ['faostat' => $row],
        );
    }
}
