<?php

namespace App\Services\Ai\Retrieval;

use App\Models\AiUsageRecord;
use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use DateTimeInterface;

class KnowledgeRetrievalHealthService
{
    public function __construct(private KnowledgeFreshnessService $freshness) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(int $organizationId, ?DateTimeInterface $now = null): array
    {
        $now = $now ?? now();

        $library = LibraryItem::query()->where('organization_id', $organizationId);
        $libraryPublished = (clone $library)->where('publication_status', 'published')->count();
        $libraryTotal = (clone $library)->count();
        $libraryUnpublished = $libraryTotal - $libraryPublished;
        $libraryEmpty = (clone $library)
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNull('content')->orWhere('content', '');
                })->where(function ($inner): void {
                    $inner->whereNull('content_ar')->orWhere('content_ar', '');
                });
            })
            ->count();

        $beeTotal = BeeKnowledgeTopic::query()->count();
        $beePublished = BeeKnowledgeTopic::query()->where('is_active', true)->count();
        $beeEmpty = BeeKnowledgeTopic::query()
            ->where(function ($query): void {
                $query->whereNull('body')->orWhere('body', '');
            })
            ->count();

        $libraryFreshness = $this->freshness->distribution(
            LibraryItem::query()->where('organization_id', $organizationId)->pluck('updated_at'),
            $now,
        );
        $beeFreshness = $this->freshness->distribution(
            BeeKnowledgeTopic::query()->pluck('updated_at'),
            $now,
        );

        $sourceDistribution = [
            'library_items' => $libraryPublished,
            'bee_knowledge_topics' => $beePublished,
        ];
        ksort($sourceDistribution);

        return [
            'organization_id' => $organizationId,
            'indexed_available' => $libraryPublished + $beePublished,
            'published_count' => $libraryPublished + $beePublished,
            'unpublished_count' => $libraryUnpublished + ($beeTotal - $beePublished),
            'empty_body_count' => $libraryEmpty + $beeEmpty,
            'fresh_count' => $libraryFreshness['fresh'] + $beeFreshness['fresh'],
            'stale_count' => $libraryFreshness['stale'] + $beeFreshness['stale'],
            'unknown_freshness_count' => $libraryFreshness['unknown'] + $beeFreshness['unknown'],
            'source_type_distribution' => $sourceDistribution,
            'library_items' => [
                'total' => $libraryTotal,
                'published' => $libraryPublished,
                'unpublished' => $libraryUnpublished,
                'empty_body' => $libraryEmpty,
                'freshness' => $libraryFreshness,
            ],
            'bee_knowledge_topics' => [
                'scope' => 'platform_catalog',
                'total' => $beeTotal,
                'published' => $beePublished,
                'unpublished' => $beeTotal - $beePublished,
                'empty_body' => $beeEmpty,
                'freshness' => $beeFreshness,
            ],
            'retrieval' => $this->retrievalCounts($organizationId),
        ];
    }

    /**
     * @return array{success: int, empty: int, failed: int}
     */
    private function retrievalCounts(int $organizationId): array
    {
        $counts = ['success' => 0, 'empty' => 0, 'failed' => 0];
        $records = AiUsageRecord::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereNotNull('retrieval')
            ->get(['retrieval']);

        foreach ($records as $record) {
            $status = is_array($record->retrieval) ? (string) ($record->retrieval['retrieval_status'] ?? '') : '';
            match ($status) {
                'ok' => $counts['success']++,
                'empty' => $counts['empty']++,
                'failed' => $counts['failed']++,
                default => null,
            };
        }

        return $counts;
    }
}
