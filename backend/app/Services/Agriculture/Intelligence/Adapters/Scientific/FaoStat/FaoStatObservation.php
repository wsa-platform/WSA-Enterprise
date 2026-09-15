<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Structured FAOSTAT observation. Query element code and response element code are distinct.
 */
final class FaoStatObservation
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $provenance
     */
    public function __construct(
        public readonly string $domainCode,
        public readonly string $domain,
        public readonly string $areaCode,
        public readonly string $area,
        public readonly string $itemCode,
        public readonly string $item,
        public readonly string $queryElementCode,
        public readonly string $responseElementCode,
        public readonly string $element,
        public readonly string $yearCode,
        public readonly string $year,
        public readonly string $unit,
        public readonly string $value,
        public readonly string $flag,
        public readonly string $flagDescription,
        public readonly string $note,
        public readonly array $raw,
        public readonly array $provenance,
    ) {}

    public function elementCode(): string
    {
        return $this->responseElementCode;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'domain_code' => $this->domainCode,
            'domain' => $this->domain,
            'area_code' => $this->areaCode,
            'area' => $this->area,
            'item_code' => $this->itemCode,
            'item' => $this->item,
            'query_element_code' => $this->queryElementCode,
            'response_element_code' => $this->responseElementCode,
            'element_code' => $this->responseElementCode,
            'element' => $this->element,
            'year_code' => $this->yearCode,
            'year' => $this->year,
            'unit' => $this->unit,
            'value' => $this->value,
            'flag' => $this->flag,
            'flag_description' => $this->flagDescription,
            'note' => $this->note,
            'provenance' => $this->provenance,
        ];
    }
}
