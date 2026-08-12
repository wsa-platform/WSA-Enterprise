<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Validation\ValidationException;

class AiActionRegistry
{
    public function __construct(private PermissionService $permissions) {}

    /** @return list<array<string, mixed>> */
    public function suggestForDomain(string $domain, array $context): array
    {
        $actions = match ($domain) {
            'jobs' => [
                ['type' => 'summarize_candidate', 'label' => 'Summarize candidate profile', 'requires_confirmation' => true, 'permission' => 'jobs.view'],
                ['type' => 'create_draft_job', 'label' => 'Create draft job posting', 'requires_confirmation' => true, 'permission' => 'jobs.manage'],
            ],
            'beekeeping' => [
                ['type' => 'summarize_inspection', 'label' => 'Summarize hive inspection', 'requires_confirmation' => true, 'permission' => 'beekeeping.view'],
                ['type' => 'create_calendar_task', 'label' => 'Create calendar reminder', 'requires_confirmation' => true, 'permission' => 'beekeeping.manage'],
            ],
            'platform' => [
                ['type' => 'retrieve_org_summary', 'label' => 'Retrieve organization summary', 'requires_confirmation' => false, 'permission' => 'platform.view'],
            ],
            default => [],
        };

        return array_values(array_filter($actions, function (array $action) use ($context): bool {
            $permissions = $context['permissions'] ?? [];

            return in_array('*', $permissions, true) || in_array($action['permission'], $permissions, true);
        }));
    }

    /** @return array<string, mixed> */
    public function execute(string $actionType, int $organizationId, User $user, array $payload = []): array
    {
        $permission = match ($actionType) {
            'summarize_candidate' => 'jobs.view',
            'create_draft_job' => 'jobs.manage',
            'summarize_inspection' => 'beekeeping.view',
            'create_calendar_task' => 'beekeeping.manage',
            'retrieve_org_summary' => 'platform.view',
            default => throw ValidationException::withMessages(['action' => ['Unknown action type.']]),
        };

        if (! $this->permissions->userCan($user, $organizationId, $permission)) {
            throw ValidationException::withMessages(['action' => ['You are not authorized to execute this action.']]);
        }

        if (($payload['confirmed'] ?? false) !== true && in_array($actionType, ['create_draft_job', 'create_calendar_task'], true)) {
            throw ValidationException::withMessages(['confirmed' => ['Explicit confirmation is required for this action.']]);
        }

        return [
            'action' => $actionType,
            'status' => 'accepted',
            'message' => 'Action queued for execution. No automatic mutations were performed without confirmation.',
            'payload' => $payload,
        ];
    }
}
