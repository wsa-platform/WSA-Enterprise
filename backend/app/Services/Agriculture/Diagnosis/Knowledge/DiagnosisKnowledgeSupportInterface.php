<?php

namespace App\Services\Agriculture\Diagnosis\Knowledge;

use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Abstract knowledge support for Stage 6.
 * Implementations may later connect to existing diagnosis catalogs without new migrations.
 */
interface DiagnosisKnowledgeSupportInterface
{
    /**
     * Suggest candidate diagnoses from observations/context.
     * Must not fabricate scientific names; leave scientificName null unless verified.
     *
     * @param  list<VisionObservation>  $observations
     * @return list<CandidateDiagnosis>
     */
    public function suggestCandidates(PlantContext $context, array $observations): array;
}
