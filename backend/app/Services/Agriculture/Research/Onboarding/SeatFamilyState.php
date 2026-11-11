<?php

namespace App\Services\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * Exactly one family state per seat, so the seat count always adds up to the register.
 *
 * Candidate-family counts count assignments: a seat may document several families or none,
 * so they never sum to the seat count. This state is the per-seat answer: which search path
 * the documented families would give the seat.
 */
enum SeatFamilyState: string
{
    /** A REST API is documented and no harvest/bulk family. */
    case REMOTE_API = 'remote_api';
    /** A REST API and a harvest/bulk family are documented; the local index is optional. */
    case REMOTE_API_AND_LOCAL_INDEX = 'remote_api_and_local_index';
    /** Only harvest/bulk families: searching needs the local index. */
    case LOCAL_INDEX = 'local_index';
    /** Only families with no implementation in this repository (RSS/Atom). */
    case UNIMPLEMENTED_FAMILY = 'unimplemented_family';
    /** No machine-readable access family is documented. */
    case NO_MACHINE_ACCESS = 'no_machine_access';

    public const LOCAL_INDEX_FAMILIES = [
        ScientificSourceAccessFamily::OAI_PMH,
        ScientificSourceAccessFamily::BULK_DATASET,
        ScientificSourceAccessFamily::STATIC_DATASET,
    ];

    /** @param  list<ScientificSourceAccessFamily>  $families */
    public static function of(array $families): self
    {
        $remote = in_array(ScientificSourceAccessFamily::REST_API, $families, true);
        $local = array_filter($families, static fn (ScientificSourceAccessFamily $f): bool => in_array($f, self::LOCAL_INDEX_FAMILIES, true)) !== [];

        return match (true) {
            $remote && $local => self::REMOTE_API_AND_LOCAL_INDEX,
            $remote => self::REMOTE_API,
            $local => self::LOCAL_INDEX,
            $families !== [] => self::UNIMPLEMENTED_FAMILY,
            default => self::NO_MACHINE_ACCESS,
        };
    }
}
