<?php

namespace App\Services\Ai;

final class AiGroundedAnswerDecision
{
    /**
     * @param  list<array<string, mixed>>  $citations
     * @param  array<string, mixed>  $providerInput
     * @param  array<string, mixed>  $retrievalTelemetry
     */
    public function __construct(
        public readonly bool $grounded,
        public readonly bool $retrievalFailed,
        public readonly array $citations,
        public readonly string $retrievedContext,
        public readonly array $providerInput,
        public readonly array $retrievalTelemetry = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $telemetry
     */
    public static function ungrounded(array $input, bool $retrievalFailed = false, array $telemetry = []): self
    {
        unset(
            $input['retrieved_context'],
            $input['retrieved_sources'],
            $input['sources'],
            $input['citations'],
            $input['grounded'],
        );

        return new self(false, $retrievalFailed, [], '', $input, $telemetry);
    }
}
