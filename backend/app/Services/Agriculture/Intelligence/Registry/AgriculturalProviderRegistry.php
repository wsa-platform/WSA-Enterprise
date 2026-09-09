<?php

namespace App\Services\Agriculture\Intelligence\Registry;

use App\Contracts\Agriculture\AgriculturalProviderInterface;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;

/**
 * Central registry of agricultural intelligence providers (ADR-001).
 */
final class AgriculturalProviderRegistry
{
    /** @var array<string, AgriculturalProviderInterface> */
    private array $providers = [];

    public function register(AgriculturalProviderInterface $provider): void
    {
        $id = $provider->descriptor()->id;
        $this->providers[$id] = $provider;
    }

    public function get(string $id): ?AgriculturalProviderInterface
    {
        return $this->providers[$id] ?? null;
    }

    /** @return list<AgriculturalProviderInterface> */
    public function all(): array
    {
        return array_values($this->providers);
    }

    /** @return list<ProviderDescriptor> */
    public function descriptors(): array
    {
        return array_map(
            static fn (AgriculturalProviderInterface $p): ProviderDescriptor => $p->descriptor(),
            $this->all(),
        );
    }

    /**
     * @param  list<string>  $types
     * @param  list<string>  $requiredCapabilities
     * @return list<AgriculturalProviderInterface>
     */
    public function select(array $types = [], array $requiredCapabilities = [], bool $enabledOnly = true): array
    {
        $selected = [];
        foreach ($this->providers as $provider) {
            $d = $provider->descriptor();
            if ($enabledOnly && ! $d->enabled) {
                continue;
            }
            if ($types !== [] && ! in_array($d->type, $types, true)) {
                continue;
            }
            if ($requiredCapabilities !== []) {
                $caps = $d->capabilities;
                $ok = true;
                foreach ($requiredCapabilities as $need) {
                    if (! in_array($need, $caps, true)) {
                        $ok = false;
                        break;
                    }
                }
                if (! $ok) {
                    continue;
                }
            }
            $selected[] = $provider;
        }

        usort(
            $selected,
            static fn (AgriculturalProviderInterface $a, AgriculturalProviderInterface $b): int => $a->descriptor()->priority <=> $b->descriptor()->priority,
        );

        return $selected;
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_keys($this->providers);
    }
}
