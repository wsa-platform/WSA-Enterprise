<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Structured projection_warning — disclosure only, not evidence/confidence/ranking.
 */
final readonly class ProjectionWarning
{
    private function __construct(
        public string $code,
        public string $message,
    ) {}

    public static function of(string $code, string $message): self
    {
        $c = trim($code);
        $m = trim($message);
        if ($c === '' || $m === '') {
            throw new ProjectionInvariantViolation('ProjectionWarning code and message must be non-empty.');
        }

        return new self($c, $m);
    }

    /**
     * @return array{code: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
