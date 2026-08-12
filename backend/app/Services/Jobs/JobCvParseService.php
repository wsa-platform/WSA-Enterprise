<?php

namespace App\Services\Jobs;

use App\Models\JobTalentProfile;
use App\Services\Ai\AiService;

class JobCvParseService
{
    public function __construct(private AiService $aiService) {}

    /** @return array<string, mixed> */
    public function parse(JobTalentProfile $profile, int $organizationId, int $userId): array
    {
        if ($profile->cv_path === null) {
            return [
                'status' => 'skipped',
                'message' => 'No CV uploaded.',
                'confidence' => 0,
            ];
        }

        $result = $this->aiService->run(
            organizationId: $organizationId,
            requestType: 'cv_parse',
            input: [
                'cv_path' => $profile->cv_path,
                'profile_id' => $profile->id,
            ],
            userId: $userId,
        );

        $output = $result->output ?? [];
        $structured = is_array($output) ? $output : [];

        if (($structured['skills'] ?? null) !== null) {
            $profile->update(['skills' => $structured['skills']]);
        }
        if (($structured['experience'] ?? null) !== null) {
            $profile->update(['experience' => $structured['experience']]);
        }
        if (($structured['education'] ?? null) !== null) {
            $profile->update(['education' => $structured['education']]);
        }
        if (($structured['specialization'] ?? null) !== null) {
            $profile->update(['specialization' => $structured['specialization']]);
        }

        $profile->update([
            'cv_parse_status' => $structured['status'] ?? 'completed',
            'cv_parsed_at' => now(),
        ]);

        return $structured;
    }
}
