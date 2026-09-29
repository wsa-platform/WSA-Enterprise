<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Source query modality for capability profiles.
 * Distinct from ScientificEvidenceModality (evidence classification).
 */
final class ScientificSourceQueryModality
{
    public const LITERATURE = 'literature';

    public const SCIENTIFIC_DATA = 'scientific_data';

    public const INSTITUTIONAL = 'institutional';
}
