<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Discovered vs verified vs activated FAOSTAT domains.
 *
 * Discovery from /en/groupsanddomains does not activate a domain.
 * QCL is the Phase 1–3 verified and default-activated domain.
 * Phase 4 candidates are inspection targets only.
 */
final class FaoStatDomainCatalog
{
    public const QCL = 'QCL';

    /** @var list<string> */
    public const PHASE4_CANDIDATES = ['RFN', 'RP', 'RL', 'ESB', 'EMN', 'ET'];

    public const OPENALEX_DOMAIN_AGRI = 'AGRI';

    /**
     * @return list<string>
     */
    public static function activatedDomains(): array
    {
        return FaoStatDeveloperPortalClient::allowedDomains();
    }

    /**
     * ACTIVATABLE catalog. Not the same as live activation (allowed_domains).
     *
     * @return list<string>
     */
    public static function verifiedDomains(): array
    {
        $raw = config('agricultural_intelligence.faostat.verified_domains', [self::QCL]);
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }
        if (! is_array($raw)) {
            return [self::QCL];
        }

        $out = [];
        foreach ($raw as $domain) {
            $code = strtoupper(trim((string) $domain));
            if ($code !== '' && $code !== self::OPENALEX_DOMAIN_AGRI) {
                $out[] = $code;
            }
        }

        return $out === [] ? [self::QCL] : array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    public static function candidateDomains(): array
    {
        $raw = config('agricultural_intelligence.faostat.candidate_domains', self::PHASE4_CANDIDATES);
        if (! is_array($raw)) {
            return self::PHASE4_CANDIDATES;
        }

        $out = [];
        foreach ($raw as $domain) {
            $code = strtoupper(trim((string) $domain));
            if ($code !== '' && $code !== self::OPENALEX_DOMAIN_AGRI) {
                $out[] = $code;
            }
        }

        return $out === [] ? self::PHASE4_CANDIDATES : array_values(array_unique($out));
    }

    public static function isActivated(string $domain): bool
    {
        return in_array(strtoupper(trim($domain)), self::activatedDomains(), true);
    }

    public static function isVerified(string $domain): bool
    {
        return in_array(strtoupper(trim($domain)), self::verifiedDomains(), true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{domain_code: string, domain_name: string, group_code: string, status: string}>
     */
    public static function discoveredFromPayload(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $out = [];
        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = strtoupper(trim((string) ($row['domain_code'] ?? $row['DomainCode'] ?? $row['code'] ?? '')));
            if ($code === '' || $code === self::OPENALEX_DOMAIN_AGRI) {
                continue;
            }
            $out[] = [
                'domain_code' => $code,
                'domain_name' => (string) ($row['domain_name'] ?? $row['DomainName'] ?? $row['domain'] ?? ''),
                'group_code' => (string) ($row['group_code'] ?? $row['GroupCode'] ?? $row['group'] ?? ''),
                'status' => FaoStatDomainStatus::DISCOVERED,
            ];
        }

        return $out;
    }

    public static function isDiscovered(string $domain, array $discovered): bool
    {
        $code = strtoupper(trim($domain));
        foreach ($discovered as $row) {
            if (($row['domain_code'] ?? '') === $code) {
                return true;
            }
        }

        return false;
    }

    public static function assertActivatable(string $domain, FaoStatDomainVerificationReport $report): void
    {
        $code = strtoupper(trim($domain));
        if ($report->domain !== $code || $report->status !== FaoStatDomainStatus::ACTIVATABLE) {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::DOMAIN_NOT_VERIFIED,
                'domain_not_activatable',
            );
        }
    }
}
