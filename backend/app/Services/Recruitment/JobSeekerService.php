<?php

namespace App\Services\Recruitment;

use App\Models\EmploymentStatusHistory;
use App\Models\JobSeekerProfile;
use App\Models\RecruiterNote;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class JobSeekerService
{
    public function __construct(
        private AuditService $audit,
        private NotificationService $notifications,
        private UserGlobalOwnershipAuthorizer $ownership,
    ) {}

    /** @param  array<string, mixed>  $filters */
    public function search(array $filters, int $perPage = 15, bool $canSearchPrivate = false): LengthAwarePaginator
    {
        // Platform-global recruiter CRM (same user-global model as M17 talent). Private contact/CV require jobs.private_data.
        $query = JobSeekerProfile::query()->with('user:id,name,email');

        if (! empty($filters['status'])) {
            $query->where('recruitment_status', $filters['status']);
        }
        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        if (! empty($filters['specialization'])) {
            $query->where('specialization', 'like', '%'.$filters['specialization'].'%');
        }
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term, $canSearchPrivate): void {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('specialization', 'like', "%{$term}%");
                if ($canSearchPrivate) {
                    $q->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                }
            });
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    /** @param  array<string, mixed>  $data */
    public function upsertForUser(User $user, array $data): JobSeekerProfile
    {
        $data = $this->ownership->stripOwnerKeys($data);
        unset($data['recruitment_status'], $data['is_active']);
        $data = JobSeekerProfile::sanitizeNested($data);

        $profile = JobSeekerProfile::firstOrNew(['user_id' => $user->id]);
        $profile->user_id = $user->id;
        $profile->fill([
            ...$data,
            'email' => $data['email'] ?? $profile->email ?? $user->email,
            'full_name' => $data['full_name'] ?? $profile->full_name ?? $user->name,
        ]);

        if (! $profile->exists) {
            $profile->recruitment_status = JobSeekerProfile::STATUS_NEW;
            $profile->is_active = true;
        }

        $profile->save();

        if ($profile->wasRecentlyCreated) {
            EmploymentStatusHistory::create([
                'job_seeker_profile_id' => $profile->id,
                'status' => JobSeekerProfile::STATUS_NEW,
                'changed_by_user_id' => $user->id,
                'notes' => 'Profile created',
            ]);
        }

        return $profile->fresh();
    }

    public function updateStatus(
        JobSeekerProfile $profile,
        string $status,
        User $actor,
        ?string $notes,
        ?int $organizationId,
        ?Request $request = null,
    ): JobSeekerProfile {
        abort_unless(in_array($status, JobSeekerProfile::STATUSES, true), 422, 'Invalid recruitment status.');
        abort_unless(
            JobSeekerProfile::canTransition((string) $profile->recruitment_status, $status),
            422,
            'Invalid recruitment status transition.',
        );

        $oldStatus = $profile->recruitment_status;
        $profile->recruitment_status = $status;
        $profile->save();

        EmploymentStatusHistory::create([
            'job_seeker_profile_id' => $profile->id,
            'status' => $status,
            'changed_by_user_id' => $actor->id,
            'notes' => $notes,
        ]);

        $this->audit->record(
            'recruitment.status_changed',
            $organizationId,
            $actor->id,
            $profile,
            ['recruitment_status' => $oldStatus],
            ['recruitment_status' => $status],
            $request,
        );

        if ($organizationId !== null) {
            $this->notifications->notify(
                organizationId: $organizationId,
                userId: $profile->user_id,
                type: 'recruitment.status_changed',
                title: 'تحديث حالة طلب التوظيف',
                body: sprintf('تم تحديث حالة ملفك إلى: %s', $status),
                data: ['profile_id' => $profile->id, 'status' => $status],
            );
        }

        return $profile->fresh();
    }

    public function addNote(
        JobSeekerProfile $profile,
        User $author,
        string $body,
        bool $isPrivate,
        ?int $organizationId,
        ?Request $request = null,
    ): RecruiterNote {
        $note = RecruiterNote::create([
            'job_seeker_profile_id' => $profile->id,
            'author_user_id' => $author->id,
            'body' => $body,
            'is_private' => $isPrivate,
        ]);

        $this->audit->record(
            'recruitment.note_added',
            $organizationId,
            $author->id,
            $note,
            null,
            ['body' => $body, 'is_private' => $isPrivate],
            $request,
        );

        return $note->load('author:id,name');
    }

    /** @return array<string, mixed> */
    public function report(int $days): array
    {
        // Aggregate pipeline counts only — no email, phone, or cv_path.
        $days = min(max($days, 1), 365);
        $since = now()->subDays($days);
        $profiles = JobSeekerProfile::query();
        $statusCounts = (clone $profiles)
            ->selectRaw('recruitment_status, count(*) as total')
            ->groupBy('recruitment_status')
            ->pluck('total', 'recruitment_status');

        $byDay = JobSeekerProfile::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[] = [
                'date' => $day,
                'count' => (int) ($byDay[$day] ?? 0),
            ];
        }

        return [
            'period_days' => $days,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_profiles' => (clone $profiles)->count(),
                'active_profiles' => (clone $profiles)->where('is_active', true)->count(),
                'new_in_period' => (clone $profiles)->where('created_at', '>=', $since)->count(),
                'hired_total' => (clone $profiles)->where('recruitment_status', JobSeekerProfile::STATUS_HIRED)->count(),
            ],
            'by_status' => $statusCounts,
            'charts' => [
                'profiles_over_time' => $series,
            ],
        ];
    }
}
