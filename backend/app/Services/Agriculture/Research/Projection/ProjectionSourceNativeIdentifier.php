<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Source-native identifier in the Projection — ≠ adr_id / canonical / path_id / projection_identity / Stage-3 sourceKey.
 */
final readonly class ProjectionSourceNativeIdentifier
{
    private function __construct(
        public string $kind,
        public string $value,
    ) {}

    public static function of(string $kind, string $value): self
    {
        $k = trim($kind);
        $v = trim($value);
        if ($k === '' || $v === '') {
            throw new ProjectionInvariantViolation('Source-native identifier kind and value must be non-empty.');
        }

        return new self($k, $v);
    }

    /**
     * @return array{kind: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'value' => $this->value,
        ];
    }
}
