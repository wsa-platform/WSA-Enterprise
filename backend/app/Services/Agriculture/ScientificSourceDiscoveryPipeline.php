<?php

namespace App\Services\Agriculture;

use App\Contracts\ScientificSectionDiscovererInterface;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates extensible scientific section discovery (internet-first, no fabrication).
 *
 * External scholarly providers run before library memory/enrichment discoverers.
 */
class ScientificSourceDiscoveryPipeline
{
    /** @var list<ScientificSectionDiscovererInterface> */
    private array $externalDiscoverers;

    /** @var list<ScientificSectionDiscovererInterface> */
    private array $libraryDiscoverers;

    /** @var list<ScientificSectionDiscovererInterface> */
    private array $discoverers;

    public function __construct(
        Discoverers\ExternalScientificSectionDiscoverer $openAlex,
        Discoverers\CrossRefScientificSectionDiscoverer $crossRef,
        Discoverers\LibraryStructuredSectionDiscoverer $structured,
        Discoverers\LibraryCropFilesSectionDiscoverer $cropFiles,
        Discoverers\LibraryRagSectionDiscoverer $rag,
        Discoverers\LibraryKeywordSectionDiscoverer $keyword,
    ) {
        $this->externalDiscoverers = [$openAlex, $crossRef];
        $this->libraryDiscoverers = [$structured, $cropFiles, $rag, $keyword];
        $this->discoverers = array_merge($this->externalDiscoverers, $this->libraryDiscoverers);
    }

    /** @return list<string> */
    public function discovererOrder(): array
    {
        return array_map(fn (ScientificSectionDiscovererInterface $discoverer): string => $discoverer->name(), $this->discoverers);
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
    ): array {
        if ($missingSectionKeys === []) {
            return [
                'sections' => [],
                'discoverers_used' => [],
                'external_discoverers_used' => [],
                'library_discoverers_used' => [],
                'retrieval_failed' => false,
            ];
        }

        $found = [];
        $used = [];
        $externalUsed = [];
        $libraryUsed = [];
        $externalFailures = 0;
        $externalAttempts = 0;

        foreach ($this->discoverers as $discoverer) {
            $stillMissing = array_values(array_diff($missingSectionKeys, array_keys($found)));
            if ($stillMissing === []) {
                break;
            }

            $isExternal = in_array($discoverer, $this->externalDiscoverers, true);
            if ($isExternal) {
                $externalAttempts++;
            }

            try {
                $batch = $discoverer->discoverSections($organizationId, $context, $stillMissing);
                if ($batch !== []) {
                    $used[] = $discoverer->name();
                    if ($isExternal) {
                        $externalUsed[] = $discoverer->name();
                    } else {
                        $libraryUsed[] = $discoverer->name();
                    }
                    foreach ($batch as $key => $section) {
                        if (! isset($found[$key])) {
                            $found[$key] = $section;
                        }
                    }
                }
            } catch (\Throwable $exception) {
                if ($isExternal) {
                    $externalFailures++;
                }
                Log::warning('Scientific section discoverer failed', [
                    'discoverer' => $discoverer->name(),
                    'crop_id' => $context->cropId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $retrievalFailed = $externalAttempts > 0
            && $externalFailures === $externalAttempts
            && $found === [];

        return [
            'sections' => $found,
            'discoverers_used' => $used,
            'external_discoverers_used' => $externalUsed,
            'library_discoverers_used' => $libraryUsed,
            'retrieval_failed' => $retrievalFailed,
        ];
    }
}
