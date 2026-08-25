<?php

namespace App\Support;

/**
 * Canonical currency → country pairs for Marketplace listings.
 * Mirrors frontend/src/marketplace/currencyCountries.ts.
 */
final class MarketplaceCurrencyCountries
{
    /** @var array<string, string> */
    public const PAIRS = [
        'SAR' => 'SA',
        'AED' => 'AE',
        'KWD' => 'KW',
        'QAR' => 'QA',
        'BHD' => 'BH',
        'OMR' => 'OM',
        'EGP' => 'EG',
        'JOD' => 'JO',
        'LBP' => 'LB',
        'IQD' => 'IQ',
        'SYP' => 'SY',
        'YER' => 'YE',
        'ILS' => 'IL',
        'MAD' => 'MA',
        'TND' => 'TN',
        'DZD' => 'DZ',
        'LYD' => 'LY',
        'SDG' => 'SD',
        'SOS' => 'SO',
        'TRY' => 'TR',
        'EUR' => 'EU',
        'USD' => 'US',
        'GBP' => 'GB',
        'CHF' => 'CH',
        'CNY' => 'CN',
        'JPY' => 'JP',
        'KRW' => 'KR',
        'INR' => 'IN',
        'PKR' => 'PK',
        'BDT' => 'BD',
        'IDR' => 'ID',
        'MYR' => 'MY',
        'SGD' => 'SG',
        'THB' => 'TH',
        'VND' => 'VN',
        'PHP' => 'PH',
        'AUD' => 'AU',
        'CAD' => 'CA',
        'NZD' => 'NZ',
        'MXN' => 'MX',
        'BRL' => 'BR',
        'ARS' => 'AR',
        'ZAR' => 'ZA',
        'NGN' => 'NG',
        'KES' => 'KE',
        'GHS' => 'GH',
        'RUB' => 'RU',
        'UAH' => 'UA',
        'PLN' => 'PL',
        'SEK' => 'SE',
        'NOK' => 'NO',
        'DKK' => 'DK',
    ];

    /** @return list<string> */
    public static function currencyCodes(): array
    {
        return array_keys(self::PAIRS);
    }

    public static function countryFor(string $currency): ?string
    {
        $code = strtoupper(trim($currency));

        return self::PAIRS[$code] ?? null;
    }

    public static function isValidPair(string $currency, string $country): bool
    {
        $expected = self::countryFor($currency);

        return $expected !== null && $expected === strtoupper(trim($country));
    }
}
