<?php

namespace App\Services\Recruitment;

use App\Models\JobSeekerProfile;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecruitmentRoleService
{
    public function __construct(private PermissionService $permissions) {}

    public function isJobSeeker(User $user): bool
    {
        return JobSeekerProfile::query()->where('user_id', $user->id)->exists();
    }

    public function isEmployer(User $user): bool
    {
        foreach ($user->organizations()->wherePivot('is_active', true)->get() as $organization) {
            if ($this->permissions->userCan($user, (int) $organization->id, 'jobs.manage')) {
                return true;
            }
        }

        return false;
    }

    public function roleFor(User $user): ?string
    {
        $jobSeeker = $this->isJobSeeker($user);
        $employer = $this->isEmployer($user);

        if ($jobSeeker && $employer) {
            return 'conflict';
        }
        if ($jobSeeker) {
            return 'job_seeker';
        }
        if ($employer) {
            return 'employer';
        }

        return null;
    }

    public function assertCanRegisterJobSeeker(User $user): void
    {
        if ($this->isEmployer($user)) {
            throw new HttpException(403, __('jobs.employer_cannot_be_job_seeker'));
        }
    }

    public function assertCanAccessJobSeeker(User $user): void
    {
        if ($this->isEmployer($user)) {
            throw new HttpException(403, __('jobs.employer_cannot_be_job_seeker'));
        }
    }

    public function assertCanRegisterEmployer(User $user): void
    {
        if ($this->isJobSeeker($user)) {
            throw new HttpException(403, __('jobs.job_seeker_cannot_be_employer'));
        }
    }

    public function assertCanAccessEmployer(User $user): void
    {
        if ($this->isJobSeeker($user)) {
            throw new HttpException(403, __('jobs.job_seeker_cannot_be_employer'));
        }

        abort_unless($this->isEmployer($user), 403, __('jobs.employer_access_required'));
    }

    /** @return array{role: string|null, is_job_seeker: bool, is_employer: bool} */
    public function payload(User $user): array
    {
        return [
            'role' => $this->roleFor($user),
            'is_job_seeker' => $this->isJobSeeker($user),
            'is_employer' => $this->isEmployer($user),
        ];
    }
}
