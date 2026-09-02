<?php

namespace App\Contracts;

use App\Services\Agriculture\CropKnowledgeContext;

interface ScientificSectionDiscovererInterface
{
    public function name(): string;

    /**
     * @param  list<string>  $sectionKeys
     * @return array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>
     */
    public function discoverSections(int $organizationId, CropKnowledgeContext $context, array $sectionKeys): array;
}
