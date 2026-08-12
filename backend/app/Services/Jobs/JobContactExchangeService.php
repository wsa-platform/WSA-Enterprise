<?php

namespace App\Services\Jobs;

use App\Contracts\JobsPaymentProviderInterface;
use App\Models\JobContactRequest;
use App\Models\JobContactTransaction;
use App\Models\JobEmploymentRecord;
use App\Models\JobTalentProfile;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobContactExchangeService
{
    public function __construct(
        private JobsPaymentProviderInterface $paymentProvider,
        private AuditService $auditService,
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

    public function initiatePayment(JobContactRequest $request, string $idempotencyKey, ?Request $httpRequest = null): JobContactTransaction
    {
        if ($request->organization_id === null) {
            throw ValidationException::withMessages(['request' => ['Invalid organization context.']]);
        }

        $existing = JobContactTransaction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            if ($existing->payment_status !== 'completed') {
                throw ValidationException::withMessages(['payment' => ['Payment could not be verified.']]);
            }

            return $existing->load('contactRequest.talentProfile.contact');
        }

        $transaction = DB::transaction(function () use ($request, $idempotencyKey, $httpRequest): JobContactTransaction {
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

            if ($transaction->payment_status === 'completed' && $transaction->contact_exchange_status === 'completed') {
                return $transaction->load('contactRequest.talentProfile.contact');
            }

            $this->paymentProvider->charge($transaction, $idempotencyKey);
            $transaction->refresh();

            if ($transaction->payment_status !== 'completed') {
                return $transaction;
            }

            return $this->completeExchange($request, $transaction, $httpRequest);
        });

        if ($transaction->payment_status !== 'completed') {
            $request->update(['status' => 'failed']);
            throw ValidationException::withMessages(['payment' => ['Payment could not be verified.']]);
        }

        return $transaction;
    }

    private function completeExchange(
        JobContactRequest $request,
        JobContactTransaction $transaction,
        ?Request $httpRequest,
    ): JobContactTransaction {
        $talent = $request->talentProfile()->with('contact')->firstOrFail();
        $talentContact = $talent->contact;

        if ($talentContact === null) {
            $transaction->update(['payment_status' => 'failed']);
            $request->update(['status' => 'failed']);
            throw ValidationException::withMessages(['contact' => ['Candidate contact information is not configured.']]);
        }

        $transaction->update([
            'contact_exchange_status' => 'completed',
            'exchanged_at' => now(),
        ]);

        $request->update(['status' => 'contact_exchanged']);
        $talent->update(['employment_status' => 'contact_exchanged']);

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

        return $transaction->fresh()->load('contactRequest.talentProfile.contact');
    }

    /** @return array<string, mixed> */
    public function exchangePayload(JobContactTransaction $transaction): array
    {
        if ($transaction->payment_status !== 'completed' || $transaction->contact_exchange_status !== 'completed') {
            throw ValidationException::withMessages(['transaction' => ['Contact exchange is not complete.']]);
        }

        $request = $transaction->contactRequest()->with('talentProfile.contact')->firstOrFail();

        return [
            'transaction_id' => $transaction->id,
            'exchanged_at' => $transaction->exchanged_at,
            'employer_contact' => $request->employerContactArray(),
            'candidate_contact' => $request->talentProfile?->contact?->toExchangeArray() ?? [],
        ];
    }

    public function markHired(
        JobContactRequest $request,
        JobContactTransaction $transaction,
        ?string $jobReference = null,
        ?Request $httpRequest = null,
    ): JobEmploymentRecord {
        $talent = $request->talentProfile()->firstOrFail();

        $record = JobEmploymentRecord::create([
            'organization_id' => $request->organization_id,
            'talent_profile_id' => $talent->id,
            'contact_transaction_id' => $transaction->id,
            'job_reference' => $jobReference ?? $request->job_reference,
            'employment_status' => 'hired',
            'hired_at' => now(),
        ]);

        $talent->update([
            'employment_status' => JobTalentProfile::STATUS_HIRED,
            'is_public' => true,
        ]);

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
}
