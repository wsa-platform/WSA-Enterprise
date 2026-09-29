<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;

/**
 * R8.30-B5 — explicit upstream Answer-Level provider boundary.
 *
 * Implementations must supply already-identified Answer-level content. This
 * contract deliberately does not accept claims/evidence as input so that an
 * implementation cannot use this boundary to infer independent answers from
 * scientific evidence or claim relationships.
 */
interface ScientificIndependentAnswerProvider
{
    public function provide(
        CanonicalScientificQuestion $question,
    ): ScientificIndependentAnswerSource;
}
