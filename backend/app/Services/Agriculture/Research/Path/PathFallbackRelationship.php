<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * Fallback relationship L1 → L2 → L3 (domain metadata only).
 *
 * INV: relationship does not auto-execute next layer.
 * INV: fallback must not increase scientific fidelity (domain flag; C9 not implemented).
 */
final readonly class PathFallbackRelationship
{
    /** @var list<PathFallbackLayer> */
    public const DEFAULT_ORDER = [
        PathFallbackLayer::L1,
        PathFallbackLayer::L2,
        PathFallbackLayer::L3,
    ];

    /**
     * @param  list<PathFallbackLayer>  $orderedLayers
     */
    private function __construct(
        public array $orderedLayers,
        public bool $forbidsFidelityIncrease,
        public bool $autoExecuteForbidden,
    ) {}

    public static function cghiaDefault(): self
    {
        return new self(self::DEFAULT_ORDER, forbidsFidelityIncrease: true, autoExecuteForbidden: true);
    }

    /**
     * @param  list<PathFallbackLayer>  $orderedLayers
     */
    public static function of(array $orderedLayers): self
    {
        if ($orderedLayers === []) {
            throw new PathInvariantViolation('Fallback relationship requires at least one layer.');
        }

        $seen = [];
        foreach ($orderedLayers as $layer) {
            if (! $layer instanceof PathFallbackLayer) {
                throw new PathInvariantViolation('Fallback layers must be PathFallbackLayer.');
            }
            if (isset($seen[$layer->value])) {
                throw new PathInvariantViolation('Fallback layers must be unique in ordering.');
            }
            $seen[$layer->value] = true;
        }

        // Enforce conceptual L1→L2→L3 order when all three present.
        if (count($orderedLayers) >= 2) {
            for ($i = 1, $n = count($orderedLayers); $i < $n; $i++) {
                if ($orderedLayers[$i]->orderIndex() < $orderedLayers[$i - 1]->orderIndex()) {
                    throw new PathInvariantViolation(
                        'Fallback layers must follow L1 → L2 → L3 conceptual order.'
                    );
                }
            }
        }

        return new self(array_values($orderedLayers), true, true);
    }

    public function precedes(PathFallbackLayer $a, PathFallbackLayer $b): bool
    {
        return $a->orderIndex() < $b->orderIndex();
    }

    /**
     * @return list<string>
     */
    public function layerValues(): array
    {
        return array_map(static fn (PathFallbackLayer $l) => $l->value, $this->orderedLayers);
    }
}
