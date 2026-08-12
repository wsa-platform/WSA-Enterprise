<?php

namespace App\Services\Jobs;

use App\Models\JobTalentProfile;
use App\Services\Ai\AiService;
use Illuminate\Support\Collection;

class JobMatchingService
{
    public function __construct(private AiService $aiService) {}

    /** @param  array<string, mixed>  $requirements */
    public function match(int $organizationId, int $userId, array $requirements, int $limit = 10): Collection
    {
        $candidates = JobTalentProfile::query()
            ->where('is_public', true)
            ->where('employment_status', JobTalentProfile::STATUS_AVAILABLE)
            ->limit(50)
            ->get();

        $result = $this->aiService->run(
            organizationId: $organizationId,
            requestType: 'job_match',
            input: [
                'requirements' => $requirements,
                'candidates' => $candidates->map(fn (JobTalentProfile $p) => $p->toPublicArray())->values()->all(),
                'limit' => $limit,
            ],
            userId: $userId,
        );

        $output = is_array($result->output) ? $result->output : [];
        $matches = collect($output['matches'] ?? [])->take($limit);

        return $matches->map(function (array $match) use ($candidates) {
            $profile = $candidates->firstWhere('id', $match['talent_profile_id'] ?? null);

            return [
                'talent_profile' => $profile?->toPublicArray(),
                'score' => $match['score'] ?? null,
                'explanation' => $match['explanation'] ?? null,
                'confidence' => $match['confidence'] ?? null,
            ];
        })->filter(fn (array $row) => $row['talent_profile'] !== null)->values();
    }
}
