<?php

namespace App\Services\Agriculture;

use App\Contracts\ScientificSectionDiscovererInterface;
use Illuminate\Support\Facades\Log;

/**
 * External scholarly section discovery only.
 *
 * Library Search discoverers are not registered. Scientific Research does not
 * dispatch this pipeline; Stage 3 adapters are the research providers.
 */
class ScientificSourceDiscoveryPipeline
{
    /** @var list<ScientificSectionDiscovererInterface> */
    private array $externalDiscoverers;

    public function __construct(
        Discoverers\ExternalScientificSectionDiscoverer $openAlex,
        Discoverers\CrossRefScientificSectionDiscoverer $crossRef,
    ) {
        $this->externalDiscoverers = [$openAlex, $crossRef];
    }

    /** @return list<string> */
    public function discovererOrder(): array
    {
        return array_map(
            fn (ScientificSectionDiscovererInterface $discoverer): string => $discoverer->name(),
            $this->externalDiscoverers,
        );
    }

    /**
     * @param  list<string>  $missingSectionKeys
     * @return array{
     *   sections: array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>,
     *   discoverers_used: list<string>,
     *   external_discoverers_used: list<string>,
     *   library_discoverers_used: list<string>,
     *   retrieval_failed: bool
     * }
     */
    public function discoverMissingSections(
        int $organizationId,
        CropKnowledgeContext $context,
        array $missingSectionKeys,
        bool $skipExternalDiscoverers = false,
    ): array {
        $empty = [
            'sections' => [],
            'discoverers_used' => [],
            'external_discoverers_used' => [],
            'library_discoverers_used' => [],
            'retrieval_failed' => false,
        ];

        if ($missingSectionKeys === [] || $skipExternalDiscoverers) {
            return $empty;
        }

        $found = [];
        $used = [];
        $externalFailures = 0;
        $externalAttempts = 0;

        foreach ($this->externalDiscoverers as $discoverer) {
            $stillMissing = array_values(array_diff($missingSectionKeys, array_keys($found)));
            if ($stillMissing === []) {
                break;
            }

            $externalAttempts++;

            try {
                $batch = $discoverer->discoverSections($organizationId, $context, $stillMissing);
                if ($batch !== []) {
                    $used[] = $discoverer->name();
                    foreach ($batch as $key => $section) {
                        if (! isset($found[$key])) {
                            $found[$key] = $section;
                        }
                    }
                }
            } catch (\Throwable $exception) {
                $externalFailures++;
                Log::warning('Scientific section discoverer failed', [
                    'discoverer' => $discoverer->name(),
                    'crop_id' => $context->cropId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'sections' => $found,
            'discoverers_used' => $used,
            'external_discoverers_used' => $used,
            'library_discoverers_used' => [],
            'retrieval_failed' => $externalAttempts > 0
                && $externalFailures === $externalAttempts
                && $found === [],
        ];
    }
}
