<?php

namespace App\Services\Ai\Retrieval;

use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Support\Facades\Log;

class KnowledgeSemanticIndexSync
{
    public function __construct(
        private KnowledgeSemanticIndexInterface $index,
        private KnowledgeIndexer $indexer,
    ) {}

    public function syncLibraryItem(LibraryItem $item): void
    {
        $this->sync($this->indexer->fromLibraryItem($item));
    }

    public function syncBeeTopic(BeeKnowledgeTopic $topic): void
    {
        $this->sync($this->indexer->fromBeeTopic($topic));
    }

    public function sync(AiKnowledgeDocument $document): void
    {
        try {
            if (! $this->index->isAvailable()) {
                return;
            }
            if ($document->visible) {
                $this->index->index($document);
            } else {
                $this->index->remove($document->sourceType, $document->sourceId);
            }
        } catch (\Throwable $exception) {
            Log::warning('AI semantic index sync failed', [
                'source_type' => $document->sourceType,
                'source_id' => $document->sourceId,
                'organization_id' => $document->organizationId,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }
    }
}
