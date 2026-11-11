<?php

namespace App\Services\Agriculture\Research\Search\Contract;

/** Uses of source content that licensing must permit separately (ADR-023 §8.36). */
enum ScientificSourceUsage: string
{
    case RETRIEVAL = 'retrieval';
    case METADATA_STORAGE = 'metadata_storage';
    case FULL_TEXT_STORAGE = 'full_text_storage';
    case EXCERPT_DISPLAY = 'excerpt_display';
    case REDISTRIBUTION = 'redistribution';
    case CITATION_LINKING = 'citation_linking';
}
