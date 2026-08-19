<?php

namespace App\Providers;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Services\Ai\Retrieval\DeterministicLexicalSemanticIndex;
use App\Services\Ai\Retrieval\KnowledgeRetrievalRouter;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use Illuminate\Support\ServiceProvider;

class AiRetrievalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeterministicLexicalSemanticIndex::class);
        $this->app->bind(KnowledgeSemanticIndexInterface::class, DeterministicLexicalSemanticIndex::class);
        $this->app->bind(AiKnowledgeRetrieverInterface::class, KnowledgeRetrievalRouter::class);
    }
}
