<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Validation boundary for diagnosis knowledge records.
 * Rejects fabricated provenance; requires sources when scientific evidence is claimed.
 */
class DiagnosisKnowledgeRecordValidator
{
    /** @var list<string> */
    private const FABRICATED_HOST_MARKERS = [
        'example.com',
        'example.org',
        'localhost',
        '127.0.0.1',
        'fake-doi',
        'placeholder',
        'invented',
        'lorem',
    ];

    public function validate(DiagnosisKnowledgeRecord $record): DiagnosisKnowledgeValidationResult
    {
        $errors = [];
        $warnings = [];

        if (trim($record->id) === '') {
            $errors[] = 'id_required';
        }

        if (trim($record->commonName) === '') {
            $errors[] = 'common_name_required';
        }

        if (trim($record->category) === '') {
            $errors[] = 'category_required';
        }

        if (trim($record->causalClass) === '') {
            $errors[] = 'causal_class_required';
        }

        if (! DiagnosisKnowledgeVerificationStatus::isValid($record->verificationStatus)) {
            $errors[] = 'invalid_verification_status';
        }

        if ($record->scientificNameVerified && (trim((string) $record->scientificName) === '')) {
            $errors[] = 'scientific_name_required_when_verified';
        }

        $claimsScientific = false;
        foreach ($record->sources as $source) {
            if ($source->claimsScientificEvidence) {
                $claimsScientific = true;
            }

            if (trim($source->label) === '') {
                $errors[] = 'source_label_required';
            }

            if ($source->url !== null) {
                if (! $this->isAcceptableUrl($source->url)) {
                    $errors[] = 'fabricated_or_invalid_url';
                }
            }

            if ($source->doi !== null && ! $this->isAcceptableDoi($source->doi)) {
                $errors[] = 'fabricated_or_invalid_doi';
            }

            if ($source->claimsScientificEvidence && trim($source->label) === '') {
                $errors[] = 'provenance_required_for_scientific_claim';
            }
        }

        if ($claimsScientific && $record->sources === []) {
            $errors[] = 'provenance_required_when_scientific_evidence_claimed';
        }

        if ($record->scientificNameVerified && $record->sources === []) {
            $errors[] = 'provenance_required_for_verified_scientific_name';
        }

        if ($record->verificationStatus === DiagnosisKnowledgeVerificationStatus::VERIFIED) {
            if ($record->sources === [] && $record->observationPatterns === []) {
                $errors[] = 'verified_record_requires_sources_or_patterns';
            }
        }

        foreach ($record->managementReferences as $ref) {
            if ($this->containsDosageClaim($ref->summary)) {
                $errors[] = 'management_dosage_forbidden';
            }
        }

        if ($record->contradictingEvidenceNotes !== [] && $record->supportingEvidenceNotes === []) {
            $warnings[] = 'contradictions_present_without_supporting_notes';
        }

        return new DiagnosisKnowledgeValidationResult(
            valid: $errors === [],
            errors: array_values(array_unique($errors)),
            warnings: array_values(array_unique($warnings)),
        );
    }

    /** @param  array<string, mixed>  $payload */
    public function validatePayload(array $payload): DiagnosisKnowledgeValidationResult
    {
        try {
            $record = DiagnosisKnowledgeRecord::fromArray($payload);
        } catch (\Throwable) {
            return new DiagnosisKnowledgeValidationResult(valid: false, errors: ['malformed_payload']);
        }

        return $this->validate($record);
    }

    private function isAcceptableUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return false;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $lower = strtolower($trimmed);
        foreach (self::FABRICATED_HOST_MARKERS as $marker) {
            if (str_contains($lower, $marker)) {
                return false;
            }
        }

        return str_starts_with($lower, 'https://') || str_starts_with($lower, 'http://');
    }

    private function isAcceptableDoi(string $doi): bool
    {
        $trimmed = trim($doi);
        if ($trimmed === '') {
            return false;
        }

        $lower = strtolower($trimmed);
        foreach (self::FABRICATED_HOST_MARKERS as $marker) {
            if (str_contains($lower, $marker)) {
                return false;
            }
        }

        // Accept canonical DOI forms only; reject free-text inventions.
        return (bool) preg_match('/^10\.\d{4,9}\/[-._;()\/:a-zA-Z0-9]+$/', $trimmed);
    }

    private function containsDosageClaim(string $text): bool
    {
        return (bool) preg_match('/\b\d+(\.\d+)?\s?(ml|mg|g|kg|l|liter|litre|ppm|%)\b/i', $text)
            || (bool) preg_match('/\b(dose|dosage|concentration|ppm)\b/i', $text);
    }
}
