<?php

namespace App\Services\Agriculture\Intelligence\DTO;

use App\Contracts\Agriculture\AgriculturalProviderInterface;

/**
 * Capability-aware selection with skip reasons (no blind execution).
 */
final class ProviderSelectionResult
{
    /**
     * @param  list<AgriculturalProviderInterface>  $selected
     * @param  list<array{id: string, type: string, reason: string}>  $skipped
     * @param  list<string>  $requiredCapabilities
     */
    public function __construct(
        public readonly array $selected,
        public readonly array $skipped,
        public readonly array $requiredCapabilities,
    ) {}

    /** @return list<string> */
    public function selectedIds(): array
    {
        return array_map(
            static fn (AgriculturalProviderInterface $p): string => $p->descriptor()->id,
            $this->selected,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'required_capabilities' => $this->requiredCapabilities,
            'selected_providers' => $this->selectedIds(),
            'skipped_providers' => $this->skipped,
        ];
    }
}
