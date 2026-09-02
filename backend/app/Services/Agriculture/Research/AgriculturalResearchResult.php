<?php

namespace App\Services\Agriculture\Research;

/**
 * Structured research output from the knowledge engine / agent.
 */
final class AgriculturalResearchResult
{
    /**
     * @param  array<string, mixed>  $researchContext
     * @param  list<string>  $discoverersUsed
     * @param  list<string>  $externalDiscoverersUsed
     * @param  list<string>  $libraryDiscoverersUsed
     * @param  array<string, mixed>  $planSummary
     */
    public function __construct(
        public readonly array $researchContext,
        public readonly array $planSummary,
        public readonly array $discoverersUsed = [],
        public readonly array $externalDiscoverersUsed = [],
        public readonly array $libraryDiscoverersUsed = [],
        public readonly string $status = 'completed',
    ) {}

    /** @return array<string, mixed> */
    public function toAgentResponse(): array
    {
        return [
            'status' => $this->status,
            'plan' => $this->planSummary,
            'research' => $this->researchContext,
            'discovery' => [
                'discoverers_used' => $this->discoverersUsed,
                'external_discoverers_used' => $this->externalDiscoverersUsed,
                'library_discoverers_used' => $this->libraryDiscoverersUsed,
                'internet_first' => $this->internetFirstOrderingPreserved(),
            ],
        ];
    }

    /**
     * For crop-profile backward compatibility, return the legacy profile shape directly.
     *
     * @return array<string, mixed>
     */
    public function toLegacyProfileResponse(): array
    {
        return $this->researchContext;
    }

    private function internetFirstOrderingPreserved(): bool
    {
        if ($this->externalDiscoverersUsed === [] || $this->libraryDiscoverersUsed === []) {
            return true;
        }

        $firstExternal = null;
        $firstLibrary = null;

        foreach ($this->discoverersUsed as $index => $name) {
            if ($firstExternal === null && in_array($name, $this->externalDiscoverersUsed, true)) {
                $firstExternal = $index;
            }
            if ($firstLibrary === null && in_array($name, $this->libraryDiscoverersUsed, true)) {
                $firstLibrary = $index;
            }
        }

        if ($firstExternal === null || $firstLibrary === null) {
            return true;
        }

        return $firstExternal < $firstLibrary;
    }
}
