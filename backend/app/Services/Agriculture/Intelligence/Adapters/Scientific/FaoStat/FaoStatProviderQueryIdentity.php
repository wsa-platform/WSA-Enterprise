<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Canonical FAOSTAT provider-query identity for duplicate-execution control.
 * Equivalent area+item+element+year(+domain) requests share one identity.
 */
final class FaoStatProviderQueryIdentity
{
    /**
     * @param  array<string, mixed>  $options
     */
    public static function fromOptions(array $options): ?string
    {
        $domain = strtoupper(trim((string) ($options['domain'] ?? $options['domain_code'] ?? FaoStatQclDimensionResolver::DOMAIN_QCL)));
        $area = self::code($options, ['area_code', 'area', 'fao_area_code']);
        $item = self::code($options, ['item_code', 'item', 'fao_item_code']);
        $element = self::code($options, ['element_code', 'element', 'query_element_code', 'fao_element_code']);
        $year = self::code($options, ['year', 'year_code', 'fao_year']);

        if ($area === null || $item === null || $element === null || $year === null) {
            return null;
        }

        return strtolower($domain).'|'.$area.'|'.$item.'|'.$element.'|'.$year;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>  $keys
     */
    private static function code(array $options, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $options)) {
                continue;
            }
            $code = trim((string) $options[$key]);
            if ($code !== '' && preg_match('/^\d{1,8}$/', $code) === 1) {
                return $code;
            }
        }

        return null;
    }
}
