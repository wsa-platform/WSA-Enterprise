<?php

namespace App\Services\Agriculture\Intelligence\Fusion;

use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\FusedEvidenceBundle;
use App\Services\Agriculture\Intelligence\DTO\WebConsensusResult;

/**
 * Generic evidence fusion — separates architectural, provider/model, and scientific confidence.
 */
final class EvidenceFusionService
{
    public function __construct(
        private WebConsensusService $webConsensus,
    ) {}

    /**
     * @param  list<CanonicalAgriculturalResult>  $results
     */
    public function fuse(array $results): FusedEvidenceBundle
    {
        $providersUsed = [];
        $deduped = [];
        $seen = [];
        $conflicts = [];
        $archScores = [];
        $providerScores = [];
        $scientificScores = [];

        foreach ($results as $result) {
            if (! $result instanceof CanonicalAgriculturalResult) {
                continue;
            }
            $providersUsed[] = $result->providerId;

            if ($result->confidence !== null) {
                $providerScores[] = $result->confidence;
            }

            foreach ($this->evidenceStreams($result) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $key = $this->dedupeKey($item);
                if (isset($seen[$key])) {
                    $prior = $seen[$key];
                    if ($this->isConflicting($prior, $item)) {
                        $conflicts[] = [
                            'type' => 'value_conflict',
                            'a' => $prior,
                            'b' => $item,
                        ];
                    }

                    continue;
                }
                $seen[$key] = $item;
                $deduped[] = $item;

                if (isset($item['confidence']) && is_numeric($item['confidence'])) {
                    $role = (string) ($item['source_role'] ?? '');
                    if ($role === SourceRole::SCIENTIFIC_EVIDENCE) {
                        $scientificScores[] = (float) $item['confidence'];
                    } elseif ($role === SourceRole::CITATION_METADATA
                        || $role === SourceRole::OFFICIAL_AGRICULTURAL_DATA
                        || $role === SourceRole::WEB_SOURCE) {
                        $archScores[] = (float) $item['confidence'];
                    } elseif (($item['evidence_family'] ?? '') === 'scientific' && $role !== SourceRole::CITATION_METADATA) {
                        $scientificScores[] = (float) $item['confidence'];
                    } else {
                        $archScores[] = (float) $item['confidence'];
                    }
                }
            }
        }

        $webItems = [];
        foreach ($results as $result) {
            if ($result instanceof CanonicalAgriculturalResult) {
                foreach ($result->webEvidence as $w) {
                    if (is_array($w)) {
                        $webItems[] = $w;
                    }
                }
            }
        }
        $webConsensus = $this->webConsensus->build($webItems);

        $providersUsed = array_values(array_unique($providersUsed));

        return new FusedEvidenceBundle(
            results: array_values(array_filter(
                $results,
                static fn ($r): bool => $r instanceof CanonicalAgriculturalResult,
            )),
            dedupedEvidence: $deduped,
            conflicts: $conflicts,
            providersUsed: $providersUsed,
            confidence: [
                'architectural' => $this->avg($archScores),
                'provider_model' => $this->avg($providerScores),
                'scientific' => $this->avg($scientificScores),
            ],
            summary: [
                'result_count' => count($results),
                'deduped_count' => count($deduped),
                'conflict_count' => count($conflicts),
                'usable_providers' => array_values(array_filter(
                    $providersUsed,
                    function (string $id) use ($results): bool {
                        foreach ($results as $r) {
                            if ($r instanceof CanonicalAgriculturalResult && $r->providerId === $id && $r->hasUsableEvidence()) {
                                return true;
                            }
                        }

                        return false;
                    },
                )),
            ],
            webConsensus: $webConsensus,
        );
    }

    /** @param  array<string, mixed>  $item */
    private function dedupeKey(array $item): string
    {
        $doi = strtolower(trim((string) ($item['doi'] ?? '')));
        if ($doi !== '') {
            return 'doi:'.$doi;
        }
        $url = strtolower(trim((string) ($item['url'] ?? $item['canonical_url'] ?? '')));
        if ($url !== '') {
            return 'url:'.$url;
        }
        $title = strtolower(trim((string) ($item['title'] ?? $item['claim'] ?? $item['text'] ?? '')));

        return 'title:'.$title.'|'.($item['provider_id'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private function isConflicting(array $a, array $b): bool
    {
        $va = $a['numeric_value'] ?? $a['value'] ?? null;
        $vb = $b['numeric_value'] ?? $b['value'] ?? null;
        if (! is_numeric($va) || ! is_numeric($vb)) {
            return false;
        }
        $fa = (float) $va;
        $fb = (float) $vb;
        if ($fa == 0.0 && $fb == 0.0) {
            return false;
        }
        $delta = abs($fa - $fb) / max(abs($fa), abs($fb), 1e-9);

        return $delta > 0.25;
    }

    /** @param  list<float>  $scores */
    private function avg(array $scores): ?float
    {
        if ($scores === []) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 4);
    }

    /**
     * Preserve all canonical buckets. Do not drop official/weather/disease/measurements.
     *
     * @return list<array<string, mixed>>
     */
    private function evidenceStreams(CanonicalAgriculturalResult $result): array
    {
        $streams = [];
        foreach ([
            $result->scientificEvidence,
            $result->webEvidence,
            $result->claims,
            $result->stats,
            $result->weather,
            $result->disease,
            $result->measurements,
            $result->entities,
        ] as $bucket) {
            foreach ($bucket as $item) {
                if (is_array($item)) {
                    $streams[] = $item;
                } elseif (is_object($item) && method_exists($item, 'toArray')) {
                    $streams[] = $item->toArray();
                }
            }
        }

        return $streams;
    }
}
