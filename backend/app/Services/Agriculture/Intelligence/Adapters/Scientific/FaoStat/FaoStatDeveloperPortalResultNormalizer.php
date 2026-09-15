<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Live Developer Portal observation → FaoStatObservation.
 * Query element code and response Element Code are stored separately.
 */
final class FaoStatDeveloperPortalResultNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function normalize(array $row, string $queryElementCode, string $queryUrl): ?FaoStatObservation
    {
        $item = $this->field($row, ['Item', 'item']);
        $value = $this->field($row, ['Value', 'value']);
        if ($item === '' && $value === '') {
            return null;
        }

        $responseElementCode = $this->field($row, ['Element Code', 'ElementCode']);
        $areaCode = $this->field($row, ['Area Code', 'AreaCode']);
        $itemCode = $this->field($row, ['Item Code', 'ItemCode']);
        $domainCode = $this->field($row, ['Domain Code', 'DomainCode']);
        $year = $this->field($row, ['Year', 'year']);

        $observation = [
            'domain_code' => $domainCode,
            'domain' => $this->field($row, ['Domain', 'domain']),
            'area_code' => $areaCode,
            'area' => $this->field($row, ['Area', 'area']),
            'item_code' => $itemCode,
            'item' => $item,
            'query_element_code' => $queryElementCode,
            'response_element_code' => $responseElementCode,
            'element_code' => $responseElementCode,
            'element' => $this->field($row, ['Element', 'element']),
            'year_code' => $this->field($row, ['Year Code', 'YearCode']) ?: $year,
            'year' => $year,
            'unit' => $this->field($row, ['Unit', 'unit']),
            'value' => $value,
            'flag' => $this->field($row, ['Flag', 'flag']),
            'flag_description' => $this->field($row, ['Flag Description', 'FlagDescription']),
            'note' => $this->field($row, ['Note', 'note']),
        ];

        $provenance = [
            'source' => 'FAOSTAT',
            'provider' => 'fao_stat',
            'host' => FaoStatDeveloperPortalClient::DEFAULT_HOST,
            'domain' => $observation['domain'],
            'domain_code' => $observation['domain_code'],
            'area' => $observation['area'],
            'area_code' => $observation['area_code'],
            'item' => $observation['item'],
            'item_code' => $observation['item_code'],
            'element' => $observation['element'],
            'query_element_code' => $queryElementCode,
            'response_element_code' => $responseElementCode,
            'year' => $observation['year'],
            'unit' => $observation['unit'],
            'value' => $observation['value'],
            'flag' => $observation['flag'],
            'flag_description' => $observation['flag_description'],
            'query_url' => $queryUrl,
            'retrieved_at' => gmdate('c'),
        ];

        return new FaoStatObservation(
            domainCode: $observation['domain_code'],
            domain: $observation['domain'],
            areaCode: $observation['area_code'],
            area: $observation['area'],
            itemCode: $observation['item_code'],
            item: $observation['item'],
            queryElementCode: $queryElementCode,
            responseElementCode: $responseElementCode,
            element: $observation['element'],
            yearCode: $observation['year_code'],
            year: $observation['year'],
            unit: $observation['unit'],
            value: $observation['value'],
            flag: $observation['flag'],
            flagDescription: $observation['flag_description'],
            note: $observation['note'],
            raw: $row,
            provenance: $provenance,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function field(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }
}
