<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Safe ingestion boundary: AI text alone cannot become verified knowledge.
 */
class DiagnosisKnowledgeIngestionService
{
    public function __construct(
        private readonly InMemoryDiagnosisKnowledgeStore $store,
        private readonly DiagnosisKnowledgeRecordValidator $validator,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     accepted: bool,
     *     status: string,
     *     record: ?DiagnosisKnowledgeRecord,
     *     validation: DiagnosisKnowledgeValidationResult,
     *     reason: ?string
     * }
     */
    public function ingest(array $payload, bool $markVerified = false, bool $fromAiOnly = false): array
    {
        $payload['verification_status'] = $markVerified && ! $fromAiOnly
            ? DiagnosisKnowledgeVerificationStatus::VERIFIED
            : DiagnosisKnowledgeVerificationStatus::RAW_UNVERIFIED;

        if ($fromAiOnly) {
            $payload['verification_status'] = DiagnosisKnowledgeVerificationStatus::RAW_UNVERIFIED;
            $markVerified = false;
        }

        $record = DiagnosisKnowledgeRecord::fromArray($payload);
        $validation = $this->validator->validate($record);

        if (! $validation->valid) {
            return [
                'accepted' => false,
                'status' => 'rejected',
                'record' => null,
                'validation' => $validation,
                'reason' => 'validation_failed',
            ];
        }

        if ($this->store->has($record->id, includeRaw: true)) {
            return [
                'accepted' => false,
                'status' => 'duplicate',
                'record' => $this->store->get($record->id, includeRaw: true),
                'validation' => $validation,
                'reason' => 'duplicate_id',
            ];
        }

        if ($markVerified && ! $fromAiOnly) {
            $verified = DiagnosisKnowledgeRecord::fromArray(array_merge($record->toArray(), [
                'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                // Preserve scientific name in storage even when toArray redacts unverified names.
                'scientific_name' => $record->scientificName,
                'scientific_name_verified' => $record->scientificNameVerified,
            ]));
            $this->store->putVerified($verified);

            return [
                'accepted' => true,
                'status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
                'record' => $verified,
                'validation' => $validation,
                'reason' => null,
            ];
        }

        $raw = DiagnosisKnowledgeRecord::fromArray(array_merge($record->toArray(), [
            'verification_status' => DiagnosisKnowledgeVerificationStatus::RAW_UNVERIFIED,
            'scientific_name' => $record->scientificName,
            'scientific_name_verified' => false,
        ]));
        $this->store->putRaw($raw);

        return [
            'accepted' => true,
            'status' => DiagnosisKnowledgeVerificationStatus::RAW_UNVERIFIED,
            'record' => $raw,
            'validation' => $validation,
            'reason' => $fromAiOnly ? 'ai_text_not_auto_verified' : null,
        ];
    }

    /**
     * Promote an existing RAW record to VERIFIED after human/curator review.
     */
    public function promoteToVerified(string $id): array
    {
        $existing = null;
        foreach ($this->store->allRaw() as $raw) {
            if ($raw->id === $id) {
                $existing = $raw;
                break;
            }
        }

        if ($existing === null) {
            return [
                'accepted' => false,
                'status' => 'not_found',
                'record' => null,
                'reason' => 'raw_record_not_found',
            ];
        }

        $promoted = DiagnosisKnowledgeRecord::fromArray(array_merge($existing->toArray(), [
            'verification_status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
            'scientific_name' => $existing->scientificName,
            'scientific_name_verified' => $existing->scientificNameVerified,
        ]));

        $validation = $this->validator->validate($promoted);
        if (! $validation->valid) {
            return [
                'accepted' => false,
                'status' => 'rejected',
                'record' => null,
                'validation' => $validation,
                'reason' => 'validation_failed',
            ];
        }

        $this->store->putVerified($promoted);

        return [
            'accepted' => true,
            'status' => DiagnosisKnowledgeVerificationStatus::VERIFIED,
            'record' => $promoted,
            'validation' => $validation,
            'reason' => null,
        ];
    }
}
