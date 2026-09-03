<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * In-memory persistence for Stage 7 — no DB migration required.
 * Keeps RAW/UNVERIFIED separate from VERIFIED.
 */
class InMemoryDiagnosisKnowledgeStore
{
    /** @var array<string, DiagnosisKnowledgeRecord> */
    private array $verified = [];

    /** @var array<string, DiagnosisKnowledgeRecord> */
    private array $raw = [];

    public function putVerified(DiagnosisKnowledgeRecord $record): void
    {
        $this->verified[$record->id] = $record;
        unset($this->raw[$record->id]);
    }

    public function putRaw(DiagnosisKnowledgeRecord $record): void
    {
        $this->raw[$record->id] = $record;
    }

    public function get(string $id, bool $includeRaw = false): ?DiagnosisKnowledgeRecord
    {
        if (isset($this->verified[$id])) {
            return $this->verified[$id];
        }

        if ($includeRaw && isset($this->raw[$id])) {
            return $this->raw[$id];
        }

        return null;
    }

    public function has(string $id, bool $includeRaw = true): bool
    {
        return isset($this->verified[$id]) || ($includeRaw && isset($this->raw[$id]));
    }

    /**
     * @return list<DiagnosisKnowledgeRecord>
     */
    public function allVerified(): array
    {
        return array_values($this->verified);
    }

    /**
     * @return list<DiagnosisKnowledgeRecord>
     */
    public function allRaw(): array
    {
        return array_values($this->raw);
    }

    /**
     * @return list<DiagnosisKnowledgeRecord>
     */
    public function all(bool $includeRaw = false): array
    {
        if (! $includeRaw) {
            return $this->allVerified();
        }

        $merged = $this->verified;
        foreach ($this->raw as $id => $record) {
            if (! isset($merged[$id])) {
                $merged[$id] = $record;
            }
        }

        return array_values($merged);
    }

    public function countVerified(): int
    {
        return count($this->verified);
    }

    public function countRaw(): int
    {
        return count($this->raw);
    }

    public function clear(): void
    {
        $this->verified = [];
        $this->raw = [];
    }
}
