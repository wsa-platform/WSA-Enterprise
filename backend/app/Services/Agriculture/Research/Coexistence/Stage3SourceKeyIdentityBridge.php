<?php

namespace App\Services\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\ExternalIdentityKind;
use App\Services\Agriculture\Research\Identity\ExternalIdentityRef;
use App\Services\Agriculture\Research\Identity\SourceIdentityDomainContract;

/**
 * IU-09 Stage-3 sourceKey → IU-01 identity namespace bridge.
 *
 * Does not mint identities, invent SAME_AS, or promote EXTERNAL → ADR.
 * Optional ADR bindings must be supplied explicitly (IU-10 population later).
 *
 * EXTERNAL identifier uses the `external:{sourceKey}` form so it remains a
 * distinct namespace from Stage-3 sourceKey (IU-01 assertDistinctNamespaces).
 */
final class Stage3SourceKeyIdentityBridge
{
    /** @var list<string> */
    public const KNOWN_STAGE3_SOURCE_KEYS = [
        'openalex',
        'crossref',
        'semantic_scholar',
        'consensus',
        'fao_stat',
    ];

    /**
     * @param  array<string, array{adr_id: string, canonical_identity_id?: ?string}>  $adrBindingsBySourceKey
     */
    public function __construct(
        private readonly array $adrBindingsBySourceKey = [],
    ) {}

    public function resolve(string $sourceKey): Stage3SourceKeyResolution
    {
        $key = strtolower(trim($sourceKey));
        if ($key === '') {
            return Stage3SourceKeyResolution::unresolved($sourceKey);
        }

        if (! in_array($key, self::KNOWN_STAGE3_SOURCE_KEYS, true)) {
            return Stage3SourceKeyResolution::unresolved($key);
        }

        $external = ExternalIdentityRef::of(
            ExternalIdentityKind::EXTERNAL_DEPENDENCY,
            self::externalIdentifierForSourceKey($key),
        );

        $binding = $this->adrBindingsBySourceKey[$key] ?? null;
        if (! is_array($binding) || ! isset($binding['adr_id']) || trim((string) $binding['adr_id']) === '') {
            return Stage3SourceKeyResolution::externalOnly($key, $external);
        }

        $adrId = AdrMembershipId::fromString((string) $binding['adr_id']);
        $canonical = null;
        if (isset($binding['canonical_identity_id']) && is_string($binding['canonical_identity_id'])) {
            $c = trim($binding['canonical_identity_id']);
            if ($c !== '') {
                $canonical = CanonicalSourceIdentityId::fromString($c);
            }
        }

        SourceIdentityDomainContract::assertDistinctNamespaces($adrId, $canonical, $external, $key);

        return Stage3SourceKeyResolution::adrBound($key, $external, $adrId, $canonical);
    }

    public static function externalIdentifierForSourceKey(string $sourceKey): string
    {
        return 'external:'.strtolower(trim($sourceKey));
    }
}
