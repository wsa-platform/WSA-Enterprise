<?php

namespace App\Services\Marketing;

use App\Models\JobTalentProfile;
use App\Models\MarketingAudienceSegment;
use App\Models\User;
use Illuminate\Support\Collection;

class MarketingAudienceResolver
{
    /** @return Collection<int, array{user_id:int,email:string,phone:?string,type:string}> */
    public function resolve(MarketingAudienceSegment $segment): Collection
    {
        $criteria = $segment->criteria ?? [];
        $organizationId = $segment->organization_id;

        if (($criteria['user_type'] ?? null) === 'job_seeker') {
            return JobTalentProfile::query()
                ->whereHas('user.organizations', fn ($q) => $q->where('organizations.id', $organizationId))
                ->with('user', 'contact')
                ->get()
                ->map(fn (JobTalentProfile $profile) => [
                    'user_id' => $profile->user_id,
                    'email' => $profile->contact?->email ?? $profile->user?->email ?? '',
                    'phone' => $profile->contact?->phone,
                    'type' => 'job_seeker',
                ])
                ->filter(fn (array $row) => $row['email'] !== '');
        }

        return User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->limit(100)
            ->get()
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => null,
                'type' => 'member',
            ]);
    }
}
