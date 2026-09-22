<?php

namespace App\Services\Agriculture\Research\Feedback;

use App\Models\ResearchFeedbackRecord;
use App\Services\Audit\AuditService;
use App\Services\Tenancy\PublicTenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * R7 — Persist ONLY positive research feedback into the Feedback Dataset.
 *
 * Feedback is not scientific truth and must not mutate production scientific behavior.
 */
final class PositiveResearchFeedbackService
{
    public const POLARITY_POSITIVE = 'positive';

    public const POLARITY_NEGATIVE = 'negative';

    /** Must match ScientificKnowledgePersistenceService::ACTION_PERSISTENCE_ACCEPTED (≤50 chars). */
    public const ACTION_PERSISTENCE_ACCEPTED = 'security.public_tenant_persistence_accepted';

    public function __construct(
        private PublicTenantResolver $publicTenantResolver,
        private AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{status: string, persisted: bool, id?: int, reason?: string}
     */
    public function record(array $input): array
    {
        $polarity = strtolower(trim((string) ($input['polarity'] ?? $input['useful'] ?? '')));
        if ($polarity === 'true' || $polarity === '1' || $polarity === 'yes' || $polarity === 'useful') {
            $polarity = self::POLARITY_POSITIVE;
        }
        if ($polarity === 'false' || $polarity === '0' || $polarity === 'no' || $polarity === 'not_useful') {
            $polarity = self::POLARITY_NEGATIVE;
        }

        if ($polarity !== self::POLARITY_POSITIVE) {
            Log::info('research.feedback.negative_discarded', [
                'polarity' => $polarity !== '' ? $polarity : 'empty',
                'reason' => 'r7_positive_feedback_only',
            ]);

            return [
                'status' => 'discarded',
                'persisted' => false,
                'reason' => 'negative_feedback_not_persisted',
            ];
        }

        $publicTenant = $this->publicTenantResolver->bindPublicTenant();
        $this->publicTenantResolver->recordIgnoredClientOrganizationInput($input, $publicTenant);

        $record = ResearchFeedbackRecord::query()->create([
            'organization_id' => $publicTenant->organizationId,
            'polarity' => self::POLARITY_POSITIVE,
            'research_source' => $this->normalizeSource((string) ($input['research_source'] ?? 'home')),
            'question_language' => $this->nullableLocale($input['question_language'] ?? null),
            'answer_language' => $this->nullableLocale($input['answer_language'] ?? null),
            'ui_locale' => $this->nullableLocale($input['ui_locale'] ?? null),
            'question' => isset($input['question']) ? mb_substr(trim((string) $input['question']), 0, 2000) : null,
            'library_item_id' => isset($input['library_item_id']) ? (int) $input['library_item_id'] : null,
            'research_fingerprint' => isset($input['research_fingerprint'])
                ? mb_substr(trim((string) $input['research_fingerprint']), 0, 64)
                : null,
            'payload' => [
                'what_worked' => $input['what_worked'] ?? null,
                'search_notes' => $input['search_notes'] ?? null,
                'source_selection_notes' => $input['source_selection_notes'] ?? null,
                'evidence_notes' => $input['evidence_notes'] ?? null,
                'ranking_notes' => $input['ranking_notes'] ?? null,
                'composition_notes' => $input['composition_notes'] ?? null,
                'client_meta' => is_array($input['client_meta'] ?? null) ? $input['client_meta'] : null,
                // Explicit non-mutation contract marker for analysts.
                'does_not_mutate_scientific_behavior' => true,
            ],
        ]);

        $this->audit->record(
            action: self::ACTION_PERSISTENCE_ACCEPTED,
            organizationId: $publicTenant->organizationId,
            userId: null,
            auditable: $record,
            newValues: [
                'category' => 'public_persistence_accepted',
                'persistence_surface' => 'feedback',
                'persist_action' => 'created',
            ],
            request: $this->currentRequest(),
        );

        return [
            'status' => 'recorded',
            'persisted' => true,
            'id' => (int) $record->id,
        ];
    }

    private function normalizeSource(string $source): string
    {
        $source = strtolower(trim($source));

        return in_array($source, ['home', 'crop'], true) ? $source : 'home';
    }

    private function nullableLocale(mixed $value): ?string
    {
        $locale = strtolower(substr(trim((string) $value), 0, 2));
        if ($locale === '' || $locale === 'un') {
            return null;
        }

        return in_array($locale, ['ar', 'en', 'tr', 'fr'], true) ? $locale : null;
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }
}
