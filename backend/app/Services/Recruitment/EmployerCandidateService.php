<?php

namespace App\Services\Recruitment;

use App\Models\JobSeekerProfile;
use App\Models\JobTalentProfile;
use App\Services\Jobs\JobTalentProfileService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployerCandidateService
{
    public function __construct(private JobTalentProfileService $talentProfiles) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = JobSeekerProfile::query()->where('is_active', true);

        $this->applyText($query, 'target_job_title', $filters['target_job_title'] ?? $filters['job_title'] ?? null);
        $this->applyText($query, 'country', $filters['country'] ?? null, exact: true);
        $this->applyText($query, 'city', $filters['city'] ?? null);
        $this->applyText($query, 'specialization', $filters['specialization'] ?? null);

        if (($qualification = trim((string) ($filters['qualification'] ?? $filters['education'] ?? ''))) !== '') {
            $query->where(function ($builder) use ($qualification): void {
                $needle = '%'.mb_strtolower($qualification).'%';
                $builder->whereRaw('LOWER(CAST(education AS TEXT)) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(specialization, \'\')) LIKE ?', [$needle]);
            });
        }

        if (isset($filters['years_of_experience']) && $filters['years_of_experience'] !== '') {
            $query->where('years_of_experience', '>=', (int) $filters['years_of_experience']);
        }

        $this->applyJsonContains($query, 'skills', $filters['skills'] ?? $filters['skill'] ?? null);
        $this->applyJsonContains($query, 'languages', $filters['languages'] ?? $filters['language'] ?? null);

        if (($workType = trim((string) ($filters['work_type'] ?? ''))) !== '') {
            $needle = '%'.mb_strtolower($workType).'%';
            $query->where(function ($builder) use ($needle): void {
                $builder->whereRaw('LOWER(COALESCE(specialization, \'\')) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(CAST(experience AS TEXT)) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(biography, \'\')) LIKE ?', [$needle]);
            });
        }

        if (isset($filters['desired_salary']) && $filters['desired_salary'] !== '') {
            $query->where('desired_salary', '<=', (float) $filters['desired_salary']);
        }

        $paginator = $query->orderByDesc('updated_at')->paginate(min(max($perPage, 1), 100));
        $paginator->setCollection(
            Collection::make($paginator->items())->map(fn (JobSeekerProfile $profile) => $profile->toEmployerPublicArray())
        );

        return $paginator;
    }

    public function publicProfile(JobSeekerProfile $profile): array
    {
        abort_unless($profile->is_active, 404);

        return $profile->toEmployerPublicArray();
    }

    public function talentProfileFor(JobSeekerProfile $seeker): JobTalentProfile
    {
        $user = $seeker->user;
        abort_unless($user !== null, 404, 'Candidate account was not found.');

        $talent = $this->talentProfiles->registerOrUpdate($user, [
            'professional_name' => $seeker->full_name,
            'specialization' => $seeker->specialization,
            'biography' => $seeker->biography,
            'country' => $seeker->country,
            'region' => $seeker->region,
            'city' => $seeker->city,
            'skills' => $seeker->skills,
            'experience' => $seeker->experience,
            'education' => $seeker->education,
            'certificates' => $seeker->certifications,
            'languages' => $seeker->languages,
            'is_public' => true,
        ], [
            'email' => $seeker->email,
            'phone' => $seeker->phone,
        ]);

        if ($seeker->recruitment_status === JobSeekerProfile::STATUS_HIRED) {
            $talent->update(['employment_status' => JobTalentProfile::STATUS_HIRED]);
        }

        return $talent->fresh(['contact']);
    }

    private function applyText($query, string $column, mixed $value, bool $exact = false): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        if ($exact) {
            $query->whereRaw('LOWER(COALESCE('.$column.', \'\')) = ?', [mb_strtolower($value)]);

            return;
        }

        $query->whereRaw('LOWER(COALESCE('.$column.', \'\')) LIKE ?', ['%'.mb_strtolower($value).'%']);
    }

    private function applyJsonContains($query, string $column, mixed $value): void
    {
        $parts = is_array($value)
            ? $value
            : (preg_split('/\s*,\s*/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            $query->where(function ($builder) use ($column, $part): void {
                $builder->whereJsonContains($column, $part)
                    ->orWhereRaw('LOWER(CAST('.$column.' AS TEXT)) LIKE ?', ['%'.mb_strtolower($part).'%']);
            });
        }
    }
}
