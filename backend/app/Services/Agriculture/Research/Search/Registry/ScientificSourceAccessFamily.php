<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Access family of a registered source: the protocol shape its adapter implements.
 *
 * Declared only from the adapter implementation, never inferred from a source name
 * or website (ADR-023 D-10.8.2).
 */
enum ScientificSourceAccessFamily: string
{
    case REST_API = 'rest_api';
    case OAI_PMH = 'oai_pmh';
    case RSS_ATOM = 'rss_atom';
    case BULK_DATASET = 'bulk_dataset';
    case STATIC_DATASET = 'static_dataset';
}
