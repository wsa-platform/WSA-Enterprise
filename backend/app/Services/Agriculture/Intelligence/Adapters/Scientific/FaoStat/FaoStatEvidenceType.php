<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * FAOSTAT observations are official statistics, not journal literature.
 */
final class FaoStatEvidenceType
{
    public const DIRECT_STATISTICAL_EVIDENCE = 'DIRECT_STATISTICAL_EVIDENCE';

    public const SUPPORTING_STATISTICAL_EVIDENCE = 'SUPPORTING_STATISTICAL_EVIDENCE';

    public const INSUFFICIENT_EVIDENCE = 'INSUFFICIENT_EVIDENCE';
}
