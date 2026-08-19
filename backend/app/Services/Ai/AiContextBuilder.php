<?php

namespace App\Services\Ai;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\PermissionService;

class AiContextBuilder
{
    public function __construct(private PermissionService $permissions) {}

    /** @return array<string, mixed> */
    public function build(int $organizationId, User $user, string $domain): array
    {
        $domain = AiDomain::assert($domain);
        $organization = Organization::find($organizationId);
        $permissions = $this->permissions->permissionsFor($user, $organizationId);

        $context = [
            'organization' => [
                'id' => $organizationId,
                'name' => $organization?->name,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'permissions' => $permissions,
            'domain' => $domain,
        ];

        if ($domain === 'jobs' && $this->permissions->userCan($user, $organizationId, 'jobs.view')) {
            $context['jobs'] = [
                'can_view_candidates' => true,
                'can_manage' => $this->permissions->userCan($user, $organizationId, 'jobs.manage'),
            ];
        }

        if ($domain === 'beekeeping' && $this->permissions->userCan($user, $organizationId, 'beekeeping.view')) {
            $context['beekeeping'] = [
                'can_manage' => $this->permissions->userCan($user, $organizationId, 'beekeeping.manage'),
            ];
        }

        if ($domain === 'marketing' && $this->permissions->userCan($user, $organizationId, 'marketing.view')) {
            $context['marketing'] = [
                'can_manage' => $this->permissions->userCan($user, $organizationId, 'marketing.manage'),
                'can_admin' => $this->permissions->userCan($user, $organizationId, 'marketing.admin'),
            ];
        }

        return $context;
    }

    /**
     * Minimized payload for provider execution. Keeps ids and capability flags, not display names.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function forProvider(array $context): array
    {
        return [
            'organization_id' => $context['organization']['id'] ?? null,
            'user_id' => $context['user']['id'] ?? null,
            'domain' => $context['domain'] ?? null,
            'capabilities' => [
                'jobs' => $context['jobs'] ?? null,
                'beekeeping' => $context['beekeeping'] ?? null,
                'marketing' => $context['marketing'] ?? null,
            ],
        ];
    }
}
