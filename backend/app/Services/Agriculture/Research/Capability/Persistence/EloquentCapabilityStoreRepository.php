<?php

namespace App\Services\Agriculture\Research\Capability\Persistence;

use App\Models\CghiaCapabilityCell;
use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityDimensionFamily;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityInvariantViolation;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent adapter for Cap Store Persistence.
 */
final class EloquentCapabilityStoreRepository implements CapabilityStoreRepository
{
    public function persistCurrent(CapabilityRecord $record, array $evidenceRefs): CapabilityCellFact
    {
        $fact = CapabilityCellFact::forWrite($record, $evidenceRefs);

        return $this->translateUniqueViolation(fn (): CapabilityCellFact => DB::transaction(
            function () use ($fact): CapabilityCellFact {
                $this->assertRecordIdUnused($fact->record->recordId);

                if ($this->findCurrentFact(
                    $fact->record->subject,
                    $fact->record->dimension,
                    $fact->record->seatOverrideApplied,
                ) !== null) {
                    throw new CapabilityInvariantViolation(
                        'A CURRENT Cap cell fact already exists for this subject/dimension/override layer; use supersedeCurrent().'
                    );
                }

                return $this->insert($fact);
            }
        ));
    }

    public function supersedeCurrent(
        CapabilityRecordId $priorRecordId,
        CapabilityRecord $replacement,
        array $evidenceRefs,
    ): CapabilityCellFact {
        $fact = CapabilityCellFact::forWrite($replacement, $evidenceRefs);

        return $this->translateUniqueViolation(fn (): CapabilityCellFact => DB::transaction(
            function () use ($priorRecordId, $fact): CapabilityCellFact {
                $priorRow = CghiaCapabilityCell::query()
                    ->where('capability_record_id', $priorRecordId->value)
                    ->lockForUpdate()
                    ->first();
                if ($priorRow === null) {
                    throw new CapabilityInvariantViolation('Cannot supersede: prior capability_record_id not found.');
                }

                $prior = $this->mapRow($priorRow);
                if (! $prior->isCurrent()) {
                    throw new CapabilityInvariantViolation('Cannot supersede: prior Cap cell fact is not CURRENT.');
                }

                if ($prior->naturalKey() !== $fact->naturalKey()) {
                    throw new CapabilityInvariantViolation(
                        'Cannot supersede: replacement must share subject, dimension and seat_override_applied with the prior fact.'
                    );
                }

                $this->assertRecordIdUnused($fact->record->recordId);

                $current = $this->findCurrentFact(
                    $prior->record->subject,
                    $prior->record->dimension,
                    $prior->record->seatOverrideApplied,
                );
                if ($current === null || ! $current->record->recordId->equals($prior->record->recordId)) {
                    throw new CapabilityInvariantViolation(
                        'Cannot supersede: CURRENT head for the natural key does not match the prior fact.'
                    );
                }

                // Lifecycle marker transition only — Cap claim columns stay immutable.
                $priorRow->cell_lifecycle = CapabilityCellLifecycle::HISTORICAL->value;
                $priorRow->save();

                return $this->insert($fact);
            }
        ));
    }

    public function findFact(CapabilityRecordId $recordId): ?CapabilityCellFact
    {
        $row = CghiaCapabilityCell::query()->where('capability_record_id', $recordId->value)->first();

        return $row === null ? null : $this->mapRow($row);
    }

    public function findCurrentFact(
        CapabilitySubject $subject,
        CapabilityDimension $dimension,
        bool $seatOverrideApplied,
    ): ?CapabilityCellFact {
        $rows = $this->currentForSubjectQuery($subject)
            ->where('dimension_family', $dimension->family->value)
            ->where('dimension_code', $dimension->code)
            ->where('seat_override_applied', $seatOverrideApplied)
            ->limit(2)
            ->get();

        if ($rows->count() > 1) {
            throw new CapabilityInvariantViolation(
                'Multiple CURRENT Cap cell facts for one natural key; current state is ambiguous and must fail closed.'
            );
        }

        $row = $rows->first();

        return $row === null ? null : $this->mapRow($row);
    }

    public function loadCurrentForSubject(CapabilitySubject $subject): array
    {
        // Deterministic natural-key order — never recency.
        $rows = $this->currentForSubjectQuery($subject)
            ->orderBy('dimension_family')
            ->orderBy('dimension_code')
            ->orderBy('seat_override_applied')
            ->orderBy('capability_record_id')
            ->get();

        $seen = [];
        $records = [];
        foreach ($rows as $row) {
            $fact = $this->mapRow($row);
            $key = $fact->naturalKey();
            if (isset($seen[$key])) {
                throw new CapabilityInvariantViolation(
                    'Multiple CURRENT Cap cell facts for one natural key; current state is ambiguous and must fail closed.'
                );
            }
            $seen[$key] = true;
            $records[] = $fact->record;
        }

        return $records;
    }

    /**
     * @return Builder<CghiaCapabilityCell>
     */
    private function currentForSubjectQuery(CapabilitySubject $subject): Builder
    {
        $query = CghiaCapabilityCell::query()
            ->where('adr_id', $subject->adrId->value)
            ->where('cell_lifecycle', CapabilityCellLifecycle::CURRENT->value);

        $canonical = $subject->canonicalIdentityId?->value;

        return $canonical === null
            ? $query->whereNull('canonical_identity_id')
            : $query->where('canonical_identity_id', $canonical);
    }

    private function assertRecordIdUnused(CapabilityRecordId $recordId): void
    {
        if (CghiaCapabilityCell::query()->where('capability_record_id', $recordId->value)->exists()) {
            throw new CapabilityInvariantViolation(
                "Duplicate capability_record_id [{$recordId->value}]; Cap cell facts are immutable."
            );
        }
    }

    private function insert(CapabilityCellFact $fact): CapabilityCellFact
    {
        $record = $fact->record;

        $row = CghiaCapabilityCell::query()->create([
            'capability_record_id' => $record->recordId->value,
            'adr_id' => $record->subject->adrId->value,
            'canonical_identity_id' => $record->subject->canonicalIdentityId?->value,
            'dimension_family' => $record->dimension->family->value,
            'dimension_code' => $record->dimension->code,
            'seat_override_applied' => $record->seatOverrideApplied,
            'capability_state' => $record->state->value,
            'evidence_freshness_class' => $record->evidenceFreshness->value,
            'snapshot_version' => $record->snapshotVersion,
            'update_method' => $record->updateMethod,
            'limitation_text' => $record->limitationText,
            'verified_at' => $record->verifiedAt,
            'observed_at' => $record->observedAt,
            'capability_evidence_refs' => $fact->evidenceRefs,
            'cell_lifecycle' => $fact->lifecycle->value,
        ]);

        return $this->mapRow($row);
    }

    private function mapRow(CghiaCapabilityCell $row): CapabilityCellFact
    {
        $canonical = $row->canonical_identity_id === null
            ? null
            : CanonicalSourceIdentityId::fromString((string) $row->canonical_identity_id);

        $record = CapabilityRecord::create(
            CapabilityRecordId::fromString((string) $row->capability_record_id),
            CapabilitySubject::of(AdrMembershipId::fromString((string) $row->adr_id), $canonical),
            $this->mapDimension((string) $row->dimension_family, (string) $row->dimension_code),
            CapabilityState::from((string) $row->capability_state),
            CapabilityEvidenceFreshness::from((string) $row->evidence_freshness_class),
            (string) $row->snapshot_version,
            (string) $row->update_method,
            $row->limitation_text !== null ? (string) $row->limitation_text : null,
            $row->verified_at !== null ? (string) $row->verified_at : null,
            $row->observed_at !== null ? (string) $row->observed_at : null,
            (bool) $row->seat_override_applied,
        );

        $refs = $row->capability_evidence_refs;
        if (! is_array($refs)) {
            throw new CapabilityInvariantViolation('Persisted capability_evidence_refs is malformed; expected a list.');
        }

        return CapabilityCellFact::fromPersisted(
            $record,
            array_values($refs),
            CapabilityCellLifecycle::from((string) $row->cell_lifecycle),
        );
    }

    private function mapDimension(string $family, string $code): CapabilityDimension
    {
        return match (CapabilityDimensionFamily::from($family)) {
            CapabilityDimensionFamily::ACCESS_METHOD => CapabilityDimension::accessMethod(CapabilityAccessMethod::from($code)),
            CapabilityDimensionFamily::SCIENTIFIC_FACET_CONTENT_ABOUT => CapabilityDimension::scientificFacetContentAbout($code),
            CapabilityDimensionFamily::SCIENTIFIC_FACET_QUERY_CONSTRAINABLE => CapabilityDimension::scientificFacetQueryConstrainable($code),
            CapabilityDimensionFamily::LICENSE_CONSTRAINT => CapabilityDimension::licenseConstraint($code),
        };
    }

    /**
     * Storage-level uniqueness (capability_record_id / CURRENT partial indexes) maps to the Cap invariant.
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     */
    private function translateUniqueViolation(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (QueryException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $message = strtolower($e->getMessage());
            if ($sqlState === '23505' || str_contains($message, 'unique constraint failed')) {
                throw new CapabilityInvariantViolation(
                    'Cap cell fact violates storage uniqueness (duplicate capability_record_id or second CURRENT fact).',
                    0,
                    $e,
                );
            }

            throw $e;
        }
    }
}
