<?php

namespace App\Services\Agriculture\Research\Search\Contract;

enum ScientificSourceUsageStatus: string
{
    case PERMITTED = 'permitted';
    case CONDITIONAL = 'conditional';
    case PROHIBITED = 'prohibited';
    case UNRESOLVED = 'unresolved';
}
