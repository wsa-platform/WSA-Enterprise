<?php

namespace App\Services\Agriculture\Intelligence\Contracts;

/**
 * Capability tokens used for provider/tool selection. Question-agnostic.
 */
final class ProviderCapability
{
    public const WEB_SEARCH = 'web_search';

    public const SCIENTIFIC_SEARCH = 'scientific_search';

    public const SCHOLARLY_EVIDENCE = 'scholarly_evidence';

    public const CITATION_METADATA = 'citation_metadata';

    public const OFFICIAL_AGRICULTURAL_DATA = 'official_agricultural_data';

    public const AGRICULTURAL_STATISTICS = 'agricultural_statistics';

    public const WEATHER = 'weather';

    public const PLANT_DISEASE_ANALYSIS = 'plant_disease_analysis';

    public const FIELD_SENSORS = 'field_sensors';

    public const MARKET_SIGNALS = 'market_signals';

    public const TOOL_EXECUTION = 'tool_execution';
}
