<?php

namespace App\Providers;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use App\Services\Ai\Embeddings\EmbeddingConfig;
use App\Services\Ai\Embeddings\EmbeddingProviderInterface;
use App\Services\Ai\Embeddings\EmbeddingProviderResolver;
use App\Services\Ai\Retrieval\DeterministicLexicalSemanticIndex;
use App\Services\Ai\Retrieval\KnowledgeRetrievalRouter;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use App\Services\Ai\Retrieval\VectorKnowledgeSemanticIndex;
use Illuminate\Support\ServiceProvider;

class AiRetrievalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingConfig::class);
        $this->app->singleton(EmbeddingProviderResolver::class);
        $this->app->bind(EmbeddingProviderInterface::class, function ($app) {
            return $app->make(EmbeddingProviderResolver::class)->resolve();
        });
        $this->app->singleton(DeterministicLexicalSemanticIndex::class);
        $this->app->bind(KnowledgeSemanticIndexInterface::class, VectorKnowledgeSemanticIndex::class);
        $this->app->bind(AiKnowledgeRetrieverInterface::class, KnowledgeRetrievalRouter::class);
    }

    public function boot(): void
    {
        LibraryItem::deleted(function (LibraryItem $item): void {
            app(KnowledgeSemanticIndexInterface::class)->remove('library_items', (int) $item->id);
        });
        BeeKnowledgeTopic::deleted(function (BeeKnowledgeTopic $topic): void {
            app(KnowledgeSemanticIndexInterface::class)->remove('bee_knowledge_topics', (int) $topic->id);
        });
    }
}
