<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Diagnosis-scoped knowledge retrieval (not a generic research search engine).
 */
class DiagnosisKnowledgeRetrievalService
{
    public function __construct(
        private readonly InMemoryDiagnosisKnowledgeStore $store,
        private readonly DiagnosisKnowledgeMatcher $matcher,
        private readonly SeededDiagnosisKnowledgeCatalog $catalog,
    ) {
        $this->catalog->seed();
    }

    /**
     * @return list<DiagnosisKnowledgeMatchResult>
     */
    public function retrieve(DiagnosisKnowledgeQuery $query): array
    {
        return $this->matcher->match($query);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<DiagnosisKnowledgeMatchResult>
     */
    public function retrieveFromInput(array $input): array
    {
        return $this->retrieve(DiagnosisKnowledgeQuery::fromInput($input));
    }

    public function getById(string $id, bool $includeRaw = false): ?DiagnosisKnowledgeRecord
    {
        $this->catalog->seed();

        return $this->store->get($id, $includeRaw);
    }

    /**
     * @return list<DiagnosisKnowledgeRecord>
     */
    public function listVerified(): array
    {
        $this->catalog->seed();

        return $this->store->allVerified();
    }

    public function store(): InMemoryDiagnosisKnowledgeStore
    {
        return $this->store;
    }
}
