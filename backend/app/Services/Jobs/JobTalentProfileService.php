<?php

namespace App\Services\Jobs;

use App\Models\JobTalentContact;
use App\Models\JobTalentProfile;
use App\Models\User;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class JobTalentProfileService
{
    public function __construct(private UserGlobalOwnershipAuthorizer $ownership) {}

    /** @param  array<string, mixed>  $data */
    public function registerOrUpdate(User $user, array $data, ?array $contact = null): JobTalentProfile
    {
        unset($data['employment_status'], $data['payment_status'], $data['user_id'], $data['organization_id']);

        $profile = JobTalentProfile::unguarded(fn () => JobTalentProfile::updateOrCreate(
            ['user_id' => $user->id],
            $this->ownership->assignOwnerFromSession(
                collect($data)->only([
                    'professional_name', 'specialization', 'biography', 'country', 'region', 'city',
                    'skills', 'experience', 'education', 'certificates', 'languages', 'disciplines',
                    'work_preferences', 'availability', 'is_public',
                ])->all(),
                $user,
            ),
        ));

        if ($contact !== null) {
            JobTalentContact::updateOrCreate(
                ['talent_profile_id' => $profile->id],
                collect($contact)->only(['email', 'phone', 'whatsapp', 'other_channels'])->all(),
            );
        }

        return $profile->fresh(['contact']);
    }

    public function storeCv(JobTalentProfile $profile, string $path): JobTalentProfile
    {
        $profile->update([
            'cv_path' => $path,
            'cv_parse_status' => 'pending',
        ]);

        return $profile->fresh();
    }

    /** @param  array<string, mixed>  $filters */
    public function searchPublic(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = JobTalentProfile::query()
            ->where('is_public', true)
            ->where('employment_status', JobTalentProfile::STATUS_AVAILABLE);

        if ($country = $filters['country'] ?? null) {
            $query->where('country', $country);
        }
        if ($region = $filters['region'] ?? null) {
            $query->where('region', $region);
        }
        if ($city = $filters['city'] ?? null) {
            $query->where('city', $city);
        }
        if ($specialization = $filters['specialization'] ?? null) {
            $query->where('specialization', 'ilike', '%'.$specialization.'%');
        }
        if ($discipline = $filters['discipline'] ?? null) {
            $query->whereJsonContains('disciplines', $discipline);
        }
        if ($skill = $filters['skill'] ?? null) {
            $query->whereJsonContains('skills', $skill);
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }
}
