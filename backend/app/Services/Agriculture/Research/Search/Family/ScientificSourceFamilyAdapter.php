<?php

namespace App\Services\Agriculture\Research\Search\Family;

use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * One adapter class shared by every source of an access family (ADR-023 §8.39, increment R5).
 *
 * Each instance serves the single source its profile names. The adapter registry constructs
 * it with the registration's profile as the `profile` argument; source-specific behaviour lives
 * in the profile, never in shared orchestration or scientific evaluation.
 */
interface ScientificSourceFamilyAdapter
{
    /** A local-index adapter serves several families; a remote adapter serves its own. */
    public static function servesAccessFamily(ScientificSourceAccessFamily $family): bool;

    public function profile(): ScientificSourceFamilyProfile;
}
