<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * FAOSTAT domain verification lifecycle.
 *
 * DISCOVERED means FAOSTAT listed the domain. It is not verified or activated.
 * Only ACTIVATABLE domains may be considered for activation (allowed_domains).
 * Default production activation remains QCL only.
 */
final class FaoStatDomainStatus
{
    public const DISCOVERED = 'DISCOVERED';

    public const METADATA_VERIFIED = 'METADATA_VERIFIED';

    public const DIMENSIONS_VERIFIED = 'DIMENSIONS_VERIFIED';

    public const CODES_VERIFIED = 'CODES_VERIFIED';

    public const LIVE_QUERY_VERIFIED = 'LIVE_QUERY_VERIFIED';

    public const CLAIM_MAPPING_VERIFIED = 'CLAIM_MAPPING_VERIFIED';

    public const ACTIVATABLE = 'ACTIVATABLE';

    public const NOT_VERIFIED = 'NOT_VERIFIED';

    /** @return list<string> */
    public static function progression(): array
    {
        return [
            self::DISCOVERED,
            self::METADATA_VERIFIED,
            self::DIMENSIONS_VERIFIED,
            self::CODES_VERIFIED,
            self::LIVE_QUERY_VERIFIED,
            self::CLAIM_MAPPING_VERIFIED,
            self::ACTIVATABLE,
        ];
    }
}
