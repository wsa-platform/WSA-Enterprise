<?php

namespace App\Services\Agriculture\Diagnosis\Knowledge;

use App\Services\Agriculture\Diagnosis\PlantContext;

/**
 * Null knowledge support for wiring tests / later DB-backed connection.
 * Intentionally returns no candidates (no migration / no Research Agent).
 */
class NullDiagnosisKnowledgeSupport implements DiagnosisKnowledgeSupportInterface
{
    public function suggestCandidates(PlantContext $context, array $observations): array
    {
        return [];
    }
}
