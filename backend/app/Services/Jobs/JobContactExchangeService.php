<?php

namespace App\Services\Jobs;

use App\Contracts\JobsPaymentProviderInterface;
use App\Models\AppNotification;
use App\Models\EmploymentStatusHistory;
use App\Models\JobContactRequest;
use App\Models\JobContactTransaction;
use App\Models\JobEmploymentRecord;
use App\Models\JobSeekerProfile;
use App\Models\JobTalentProfile;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobContactExchangeService
{
    public function __construct(
        private JobsPaymentProviderInterface $paymentProvider,
        private AuditService $auditService,
        private NotificationService $notifications,
    ) {}

    /** @param  array<string, mixed>  $employerContact */
    public function createRequest(
        int $organizationId,
        int $userId,
        JobTalentProfile $talent,
        array $employerContact,
        ?string $jobReference = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): JobContactRequest {
        if ($talent->employment_status === JobTalentProfile::STATUS_HIRED) {
            throw new HttpException(409, 'Candidate is already hired.');
        }

        if (! $talent->isContactExchangeAvailable()) {
            throw ValidationException::withMessages([
                'talent_profile_id' => ['Candidate is not available for contact exchange.'],
            ]);
        }

        $active = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('talent_profile_id', $talent->id)
            ->whereIn('status', ['pending', 'payment_pending', 'paid', 'contact_exchanged'])
            ->exists();

        if ($active) {
            throw ValidationException::withMessages([
                'talent_profile_id' => ['An active contact request already exists for this candidate.'],
            ]);
        }

        return JobContactRequest::create([
            'organization_id' => $organizationId,
            'talent_profile_id' => $talent->id,
            'requested_by_user_id' => $userId,
            'employer_contact_name' => (string) ($employerContact['name'] ?? ''),
            'employer_contact_email' => (string) ($employerContact['email'] ?? ''),
            'employer_contact_phone' => $employerContact['phone'] ?? null,
            'employer_contact_whatsapp' => $employerContact['whatsapp'] ?? null,
            'status' => 'payment_pending',
            'job_reference' => $jobReference,
            'notes' => $notes,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function initiatePayment(JobContactRequest $request, string $idempotencyKey, ?Request $httpRequest = null, ?int $organizationId = null): JobContactTransaction
    {
        $organizationId ??= (int) $request->organization_id;
        if ($request->organization_id === null || (int) $request->organization_id !== $organizationId) {
            throw ValidationException::withMessages(['request' => ['Invalid organization context.']]);
        }

        $existing = JobContactTransaction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            if (
                (int) $existing->contact_request_id !== (int) $request->id
                || ! $this->isUnlockActive($existing, $request)
            ) {
                throw ValidationException::withMessages(['payment' => ['Payment could not be verified.']]);
            }

            return $existing->load('employmentRecord');
        }

        $newlyFinalized = false;
        $transaction = DB::transaction(function () use ($request, $idempotencyKey, $httpRequest, &$newlyFinalized): JobContactTransaction {
            // Concurrent hire attempts serialize on this row; a second employer then receives 409.
            $talent = JobTalentProfile::query()
                ->whereKey($request->talent_profile_id)
                ->lockForUpdate()
                ->firstOrFail();

            $transaction = JobContactTransaction::firstOrCreate(
                ['contact_request_id' => $request->id],
                [
                    'amount' => config('jobs.contact_exchange.amount', 49.00),
                    'currency' => config('jobs.contact_exchange.currency', 'USD'),
                    'payment_provider' => config('jobs.contact_exchange.payment_provider', 'mock'),
                    'payment_status' => 'pending',
                    'contact_exchange_status' => 'pending',
                ],
            );

            $transaction->loadMissing('employmentRecord');
            if ($this->isUnlockActive($transaction, $request)) {
                return $transaction;
            }

            if ($talent->employment_status === JobTalentProfile::STATUS_HIRED) {
                throw new HttpException(409, 'Candidate is already hired.');
            }

            $this->paymentProvider->charge($transaction, $idempotencyKey);
            $transaction->refresh();

            if ($transaction->payment_status !== 'completed') {
                return $transaction;
            }

            $newlyFinalized = true;

            return $this->completeVerifiedHiring($request, $transaction, $httpRequest);
        });

        if ($transaction->payment_status !== 'completed' || ! $this->isUnlockActive($transaction, $request)) {
            $request->update(['status' => 'failed']);
            throw ValidationException::withMessages(['payment' => ['Payment could not be verified.']]);
        }

        if ($newlyFinalized) {
            $this->notifyHiringCompleted($request, $transaction);
        }

        return $transaction->load('employmentRecord');
    }

    public function isUnlockActive(?JobContactTransaction $transaction, JobContactRequest $request): bool
    {
        if ($transaction === null) {
            return false;
        }

        if (
            (int) $transaction->contact_request_id !== (int) $request->id
            || $request->organization_id === null
            || $transaction->payment_status !== 'completed'
            || $transaction->contact_exchange_status !== 'completed'
        ) {
            return false;
        }

        $record = JobEmploymentRecord::withoutGlobalScopes()
            ->where('contact_transaction_id', $transaction->id)
            ->first();

        return $record !== null
            && (int) $record->talent_profile_id === (int) $request->talent_profile_id
            && (int) $record->organization_id === (int) $request->organization_id;
    }

    public function assertUnlockedContact(JobContactRequest $request, ?JobContactTransaction $transaction, int $organizationId): JobContactTransaction
    {
        if (
            $transaction === null
            || (int) $request->organization_id !== $organizationId
            || (int) $transaction->contact_request_id !== (int) $request->id
            || $request->talent_profile_id === null
            || ! $this->isUnlockActive($transaction, $request)
        ) {
            throw new HttpException(403, 'Contact information is locked until payment is verified.');
        }

        return $transaction;
    }

    /** @return array<string, mixed> */
    public function exchangePayload(JobContactTransaction $transaction): array
    {
        $request = $transaction->contactRequest()->firstOrFail();
        $transaction->loadMissing('employmentRecord');
        if (! $this->isUnlockActive($transaction, $request)) {
            throw new HttpException(403, 'Contact information is locked until payment is verified.');
        }

        $request->loadMissing('talentProfile.contact');

        return [
            'transaction_id' => $transaction->id,
            'exchanged_at' => $transaction->exchanged_at,
            'employer_contact' => $request->employerContactArray(),
            'candidate_contact' => $request->talentProfile?->contact?->toExchangeArray() ?? [],
            'hiring_record_id' => $transaction->employmentRecord?->id,
        ];
    }

    public function markHired(
        JobContactRequest $request,
        ?JobContactTransaction $transaction,
        ?string $jobReference = null,
        ?Request $httpRequest = null,
        ?int $organizationId = null,
    ): JobEmploymentRecord {
        $organizationId ??= (int) $request->organization_id;
        if (
            $transaction === null
            || (int) $transaction->contact_request_id !== (int) $request->id
            || $request->organization_id === null
            || (int) $request->organization_id !== $organizationId
            || ! $this->isUnlockActive($transaction, $request)
        ) {
            throw new HttpException(403, 'Hiring requires a verified payment and active contact unlock.');
        }

        return DB::transaction(function () use ($request, $transaction, $jobReference, $httpRequest): JobEmploymentRecord {
            $existing = JobEmploymentRecord::withoutGlobalScopes()
                ->where('contact_transaction_id', $transaction->id)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                abort_unless(
                    (int) $existing->organization_id === (int) $request->organization_id
                    && (int) $existing->talent_profile_id === (int) $request->talent_profile_id,
                    403,
                    'Hiring requires a verified payment and active contact unlock.',
                );

                return $existing;
            }

            return $this->createHiringRecord($request, $transaction, $jobReference, $httpRequest);
        });
    }

    private function completeVerifiedHiring(
        JobContactRequest $request,
        JobContactTransaction $transaction,
        ?Request $httpRequest,
    ): JobContactTransaction {
        $talent = $request->talentProfile()->lockForUpdate()->firstOrFail();

        if ($talent->employment_status === JobTalentProfile::STATUS_HIRED) {
            throw new HttpException(409, 'Candidate is already hired.');
        }

        if (! $talent->contact()->exists()) {
            $transaction->update(['payment_status' => 'failed']);
            $request->update(['status' => 'failed']);
            throw ValidationException::withMessages(['contact' => ['Candidate contact information is not configured.']]);
        }

        if ((int) $request->talent_profile_id !== (int) $talent->id) {
            throw new HttpException(403, 'Payment does not belong to this candidate.');
        }

        $transaction->update([
            'contact_exchange_status' => 'completed',
            'exchanged_at' => now(),
        ]);
        $request->update(['status' => 'contact_exchanged']);

        $this->auditService->record(
            action: 'jobs.contact_exchanged',
            organizationId: $request->organization_id,
            userId: $request->requested_by_user_id,
            auditable: $transaction,
            newValues: [
                'talent_profile_id' => $talent->id,
                'contact_request_id' => $request->id,
                'exchange' => 'two_way',
            ],
            request: $httpRequest,
        );

        $this->createHiringRecord($request, $transaction->fresh(), $request->job_reference, $httpRequest);

        return $transaction->fresh()->load('employmentRecord');
    }

    private function createHiringRecord(
        JobContactRequest $request,
        JobContactTransaction $transaction,
        ?string $jobReference = null,
        ?Request $httpRequest = null,
    ): JobEmploymentRecord {
        $talent = JobTalentProfile::query()
            ->whereKey($request->talent_profile_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($talent->employment_status === JobTalentProfile::STATUS_HIRED) {
            $existing = JobEmploymentRecord::withoutGlobalScopes()
                ->where('contact_transaction_id', $transaction->id)
                ->first();
            if (
                $existing !== null
                && (int) $existing->organization_id === (int) $request->organization_id
                && (int) $existing->talent_profile_id === (int) $talent->id
            ) {
                return $existing;
            }

            throw new HttpException(409, 'Candidate is already hired.');
        }

        $record = JobEmploymentRecord::query()->firstOrCreate(
            ['contact_transaction_id' => $transaction->id],
            [
                'organization_id' => $request->organization_id,
                'talent_profile_id' => $talent->id,
                'job_reference' => $jobReference ?? $request->job_reference,
                'employment_status' => JobTalentProfile::STATUS_HIRED,
                'hired_at' => now(),
            ],
        );

        if (! $record->wasRecentlyCreated) {
            return $record;
        }

        $talent->update([
            'employment_status' => JobTalentProfile::STATUS_HIRED,
            'is_public' => true,
        ]);
        $this->syncJobSeekerHired($talent, $request->requested_by_user_id);

        $this->auditService->record(
            action: 'jobs.candidate_hired',
            organizationId: $request->organization_id,
            userId: $request->requested_by_user_id,
            auditable: $record,
            newValues: ['talent_profile_id' => $talent->id],
            request: $httpRequest,
        );

        return $record;
    }

    private function notifyHiringCompleted(JobContactRequest $request, JobContactTransaction $transaction): void
    {
        $request->loadMissing('talentProfile');
        $organizationId = (int) $request->organization_id;
        $candidateUserId = $request->talentProfile?->user_id;
        $hiringRecordId = $transaction->employmentRecord?->id ?? $transaction->fresh()?->employmentRecord?->id;
        $payload = [
            'contact_request_id' => $request->id,
            'hiring_record_id' => $hiringRecordId,
        ];

        if ($this->hiringNotificationExists($organizationId, $request->requested_by_user_id, $request->id)) {
            return;
        }

        $this->notifications->notify(
            organizationId: $organizationId,
            userId: $request->requested_by_user_id,
            type: 'jobs.hiring.completed',
            title: 'Hiring completed',
            body: 'Payment was verified. Contact details are available from the authorized contact endpoint.',
            data: $payload,
        );

        if ($candidateUserId && ! $this->hiringNotificationExists($organizationId, $candidateUserId, $request->id)) {
            $this->notifications->notify(
                organizationId: $organizationId,
                userId: $candidateUserId,
                type: 'jobs.hiring.completed',
                title: 'Congratulations — you have been hired',
                body: 'An employer completed hiring after verified payment.',
                data: $payload,
            );
        }
    }

    private function hiringNotificationExists(int $organizationId, ?int $userId, int $contactRequestId): bool
    {
        if ($userId === null) {
            return false;
        }

        return AppNotification::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where('type', 'jobs.hiring.completed')
            ->get()
            ->contains(fn (AppNotification $notification) => (int) ($notification->data['contact_request_id'] ?? 0) === $contactRequestId);
    }

    private function syncJobSeekerHired(JobTalentProfile $talent, ?int $actorUserId): void
    {
        $profile = JobSeekerProfile::query()->where('user_id', $talent->user_id)->first();
        if ($profile === null || $profile->recruitment_status === JobSeekerProfile::STATUS_HIRED) {
            return;
        }

        $profile->recruitment_status = JobSeekerProfile::STATUS_HIRED;
        $profile->save();

        EmploymentStatusHistory::create([
            'job_seeker_profile_id' => $profile->id,
            'status' => JobSeekerProfile::STATUS_HIRED,
            'changed_by_user_id' => $actorUserId,
            'notes' => 'Hired after verified contact-exchange payment',
        ]);
    }
}
