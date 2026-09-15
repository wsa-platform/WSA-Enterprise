<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Sanitizes FAOSTAT domain configuration. OpenAlex AGRI and random strings never activate.
 */
final class FaoStatConfigurationValidator
{
    /**
     * @return array{accepted: list<string>, rejected: list<string>}
     */
    public static function sanitizeDomainList(mixed $raw, bool $fallbackQcl = false): array
    {
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        $accepted = [];
        $rejected = [];
        foreach ($raw as $domain) {
            $code = strtoupper(trim((string) $domain));
            if ($code === '') {
                continue;
            }
            if (! self::isFaostatDomainCode($code)) {
                $rejected[] = $code;

                continue;
            }
            $accepted[] = $code;
        }

        $accepted = array_values(array_unique($accepted));
        if ($accepted === [] && $fallbackQcl) {
            $accepted = [FaoStatDomainCatalog::QCL];
        }

        return [
            'accepted' => $accepted,
            'rejected' => array_values(array_unique($rejected)),
        ];
    }

    public static function isFaostatDomainCode(string $code): bool
    {
        $code = strtoupper(trim($code));
        if ($code === '' || $code === FaoStatDomainCatalog::OPENALEX_DOMAIN_AGRI) {
            return false;
        }

        return preg_match('/^[A-Z][A-Z0-9]{1,7}$/', $code) === 1;
    }
}
