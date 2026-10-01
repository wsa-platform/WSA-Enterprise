<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * Structured path limitation / constraint (domain-level only).
 *
 * Not Projection fidelity_class. Not CSQ mutation.
 */
final readonly class PathLimitation
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
            throw new PathInvariantViolation('PathLimitation code and message must be non-empty.');
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
