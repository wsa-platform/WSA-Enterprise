<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * An operation a runtime source can be asked to perform. Each needs its own capability evidence.
 */
enum ScientificSourceOperation: string
{
    case SEARCH = 'search';
    case PAGINATION = 'pagination';
    case FILTERING = 'filtering';
    case FULL_TEXT_RETRIEVAL = 'full_text_retrieval';
    case BULK_ACCESS = 'bulk_access';
}
