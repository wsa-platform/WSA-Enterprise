<?php

namespace App\Services\Ai\Evaluation;

final class RetrievalEvaluationReport
{
    /**
     * @param  list<RetrievalEvaluationResult>  $results
     */
    public function __construct(
        public readonly array $results,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $rows = array_map(static fn (RetrievalEvaluationResult $result): array => $result->toArray(), $this->results);
        $count = count($rows);
        $mean = static function (string $key) use ($rows, $count): float {
            if ($count === 0) {
                return 0.0;
            }
            $sum = 0.0;
            foreach ($rows as $row) {
                $sum += (float) ($row[$key] ?? 0);
            }

            return round($sum / $count, 4);
        };

        return [
            'results' => $rows,
            'summary' => [
                'cases' => $count,
                'mean_precision' => $mean('precision'),
                'mean_recall' => $mean('recall'),
                'mean_f1' => $mean('f1'),
                'mean_hit' => $mean('hit'),
                'mean_mrr' => $mean('mrr'),
            ],
        ];
    }
}
