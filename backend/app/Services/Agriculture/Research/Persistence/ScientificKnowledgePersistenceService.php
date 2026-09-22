<?php

namespace App\Services\Agriculture\Research\Persistence;

use App\Models\LibraryCategory;
use App\Models\LibraryItem;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexSync;
use App\Services\Audit\AuditService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Persists verified research knowledge with evidence into the WSA Library memory layer.
 *
 * Library is memory — Internet-First discovery remains primary for future research.
 */
class ScientificKnowledgePersistenceService
{
    public const ACTION_PERSISTENCE_REJECTED = 'security.public_tenant_persistence_rejected';

    public const ACTION_PERSISTENCE_ACCEPTED = 'security.public_tenant_persistence_accepted';

    public function __construct(
        private KnowledgeSemanticIndexSync $semanticIndex,
        private ScientificSourceValidator $sourceValidator,
        private TenantContext $tenantContext,
        private AuditService $audit,
    ) {}

    public function persist(
        int $organizationId,
        KnowledgeQueryPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
        EvidenceValidationExecutionReport $validationReport,
    ): KnowledgePersistenceExecutionReport {
        if ($organizationId < 1) {
            return $this->skippedReport('invalid_organization', 'invalid_organization_id');
        }

        // Defense-in-depth (MODEL B): when a public request bound TenantContext, refuse mismatch.
        if ($this->tenantContext->isPublicBound()) {
            $boundPublicOrganizationId = $this->tenantContext->organizationId();
            if ($boundPublicOrganizationId === null || $organizationId !== $boundPublicOrganizationId) {
                Log::warning('security.public_tenant_persistence_rejected', [
                    'attempted_organization_id' => $organizationId,
                    'bound_public_organization_id' => $boundPublicOrganizationId,
                ]);

                $this->audit->record(
                    action: self::ACTION_PERSISTENCE_REJECTED,
                    organizationId: $boundPublicOrganizationId,
                    userId: null,
                    newValues: [
                        'category' => 'public_tenant_mismatch',
                        'mismatch' => true,
                        'persistence_surface' => 'library',
                    ],
                    request: $this->currentRequest(),
                );

                return $this->skippedReport('public_tenant_mismatch', 'public_tenant_persistence_rejected');
            }
        }

        if (! $synthesisReport->performed || $synthesisReport->claims === []) {
            return $this->skippedReport('nothing_to_persist', 'no_verified_claims');
        }

        if ($synthesisReport->citations === []) {
            return $this->skippedReport('nothing_to_persist', 'no_verified_citations');
        }

        if ($validationReport->validatedCount < 1) {
            return $this->skippedReport('nothing_to_persist', 'no_validated_evidence');
        }

        // R5: Composer is not sole authority — require validation sufficiency for auto-save.
        if (! $validationReport->evidenceSufficient) {
            return $this->skippedReport('insufficiently_verified', 'evidence_not_sufficient_for_library');
        }

        $synthesisStatus = strtolower(trim((string) $synthesisReport->status));
        if (in_array($synthesisStatus, ['insufficient_evidence', 'needs_clarification', 'no_search_results'], true)) {
            return $this->skippedReport('insufficiently_verified', 'synthesis_not_save_eligible:'.$synthesisStatus);
        }

        $slug = $this->slugFor($plan);
        $fingerprint = $this->evidenceFingerprint($validationReport->validatedEvidence);
        $existing = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->first();

        if ($existing !== null) {
            $existingMeta = is_array($existing->metadata) ? $existing->metadata : [];
            $existingResearch = is_array($existingMeta['research_agent'] ?? null) ? $existingMeta['research_agent'] : [];
            $existingFingerprint = (string) ($existingResearch['evidence_fingerprint'] ?? '');

            if ($existingFingerprint === $fingerprint) {
                $report = new KnowledgePersistenceExecutionReport(
                    status: 'persistence_unchanged',
                    performed: true,
                    libraryItemId: (int) $existing->id,
                    slug: $slug,
                    action: 'unchanged',
                    provenance: is_array($existingResearch['provenance'] ?? null) ? $existingResearch['provenance'] : null,
                    observability: [
                        'duplicate_protection' => 'same_evidence_fingerprint',
                        'internet_first_preserved' => true,
                    ],
                );
                $this->auditPublicPersistenceAccepted($organizationId, $existing, $report->action);

                return $report;
            }

            $existingFreshness = (int) ($existingResearch['newest_publication_year'] ?? 0);
            $incomingFreshness = $this->newestPublicationYear($validationReport->validatedEvidence);
            if ($existingFreshness > $incomingFreshness && $existingFingerprint !== '') {
                return new KnowledgePersistenceExecutionReport(
                    status: 'persistence_skipped',
                    performed: false,
                    libraryItemId: (int) $existing->id,
                    slug: $slug,
                    action: 'skipped_newer_existing',
                    provenance: is_array($existingResearch['provenance'] ?? null) ? $existingResearch['provenance'] : null,
                    observability: [
                        'duplicate_protection' => 'existing_evidence_is_newer',
                        'internet_first_preserved' => true,
                    ],
                );
            }
        }

        try {
            $item = $this->writeLibraryItem(
                $organizationId,
                $plan,
                $synthesisReport,
                $validationReport,
                $slug,
                $fingerprint,
                $existing,
            );

            $report = new KnowledgePersistenceExecutionReport(
                status: 'persistence_completed',
                performed: true,
                libraryItemId: (int) $item->id,
                slug: $slug,
                action: $existing === null ? 'created' : 'updated',
                provenance: $this->provenance($plan, $synthesisReport, $validationReport),
                observability: [
                    'claims_persisted' => count($synthesisReport->claims),
                    'evidence_persisted' => $validationReport->validatedCount,
                    'citations_persisted' => count($synthesisReport->citations),
                    'internet_first_preserved' => true,
                ],
            );
            $this->auditPublicPersistenceAccepted($organizationId, $item, $report->action);

            return $report;
        } catch (\Throwable $exception) {
            Log::warning('Research agent knowledge persistence failed', [
                'organization_id' => $organizationId,
                'slug' => $slug,
                'message' => $exception->getMessage(),
            ]);

            return new KnowledgePersistenceExecutionReport(
                status: 'persistence_failed',
                performed: false,
                libraryItemId: $existing !== null ? (int) $existing->id : null,
                slug: $slug,
                action: 'failed',
                provenance: null,
                observability: [
                    'failure_reason' => 'persistence_exception',
                    'internet_first_preserved' => true,
                ],
            );
        }
    }

    private function writeLibraryItem(
        int $organizationId,
        KnowledgeQueryPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
        EvidenceValidationExecutionReport $validationReport,
        string $slug,
        string $fingerprint,
        ?LibraryItem $existing,
    ): LibraryItem {
        // R2: persist answer_language (= question_language); never platform UI locale.
        $language = $this->resolvePersistedAnswerLanguage($plan);
        $item = $existing ?? new LibraryItem;
        $references = $this->scientificReferences($synthesisReport->citations);
        $primarySource = $references[0] ?? null;
        $cropFolder = $this->ensureCropScientificResearchFolder($organizationId, $plan);

        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $metadata['research_agent'] = [
            'stage' => 5,
            'provenance' => $this->provenance($plan, $synthesisReport, $validationReport),
            'verified_at' => now()->toIso8601String(),
            'query' => $plan->normalizedQuery->originalQuestion,
            'normalized_query' => $plan->normalizedQuery->normalizedQuestion,
            'research_intent' => $plan->researchIntent,
            'agricultural_domain' => $plan->agriculturalDomain,
            'subject_entity' => $plan->subjectEntity,
            'question_language' => $plan->normalizedQuery->language,
            'answer_language' => $language,
            'language' => $language,
            'claims' => array_map(
                static fn ($claim): array => $claim->toArray(),
                $synthesisReport->claims,
            ),
            'validated_evidence' => array_map(
                static fn (ScientificEvidenceItem $evidence): array => $evidence->toArray(),
                $validationReport->validatedEvidence,
            ),
            'citations' => array_map(
                static fn (ResearchAnswerCitation $citation): array => $citation->toArray(),
                $synthesisReport->citations,
            ),
            'confidence' => $synthesisReport->confidence,
            'limitations' => $synthesisReport->limitations,
            'uncertainty' => $synthesisReport->uncertainty,
            'conflicts' => $synthesisReport->conflicts,
            'synthesis_metadata' => $synthesisReport->researchMetadata,
            'evidence_fingerprint' => $fingerprint,
            'newest_publication_year' => $this->newestPublicationYear($validationReport->validatedEvidence),
            'internet_first' => $plan->isInternetFirst(),
        ];

        if (is_array($primarySource)) {
            $metadata['scientific_source'] = $primarySource;
        }

        if ($references !== []) {
            $metadata['scientific_references'] = $references;
        }

        if ($cropFolder !== null) {
            $metadata['field_crop_id'] = $cropFolder['crop_id'];
            $metadata['field_crop_name'] = $cropFolder['crop_name'];
            $metadata['field_crop_category_id'] = $cropFolder['category_id'];
            $metadata['field_crop_category_name'] = $cropFolder['category_name'];
            $metadata['library_file_section'] = 'scientific-research';
            $metadata['knowledge_option'] = 'scientific-research';
            $metadata['service_option'] = 'scientific-research';
            $metadata['library_folder'] = [
                'crop_category_id' => $cropFolder['crop_category_row_id'],
                'scientific_research_category_id' => $cropFolder['scientific_category_row_id'],
                'path' => 'Crop → Scientific Research',
            ];
        }

        $title = $this->titleFor($plan, $language);
        $summary = $synthesisReport->conciseSummary ?? '';

        $item->organization_id = $organizationId;
        $item->slug = $slug;
        $item->item_type = 'verified_research_knowledge';
        if ($cropFolder !== null) {
            $item->category_id = $cropFolder['scientific_category_row_id'];
        }
        // R2/R3 + P2-C08: persist supported answer languages without collapsing tr/fr → en.
        $persistedLocale = in_array($language, ['ar', 'en', 'tr', 'fr'], true) ? $language : 'en';
        $item->locale = $persistedLocale;
        $item->publication_status = 'published';
        $item->published_at = $item->published_at ?? now();
        $item->metadata = $metadata;

        if ($persistedLocale === 'ar') {
            $item->title_ar = $title;
            $item->summary_ar = $summary;
            $item->content_ar = (string) ($synthesisReport->answer ?? $summary);
            $item->title = $plan->normalizedQuery->normalizedQuestion;
            $item->summary = $summary;
            $item->content = (string) ($synthesisReport->detailedExplanation ?? $summary);
        } else {
            $item->title = $title;
            $item->summary = $summary;
            $item->content = (string) ($synthesisReport->answer ?? $summary);
            // Keep AR columns populated for bilingual library surfaces without translating.
            $item->title_ar = $title;
            $item->summary_ar = $summary;
            $item->content_ar = (string) ($synthesisReport->answer ?? $summary);
        }

        $item->source = $this->sourceAttribution($references);
        $item->save();

        // R3: preserve original source file bytes when a verified URL is downloadable.
        $this->preserveOriginalSourceFile($item, $primarySource);

        $this->semanticIndex->syncLibraryItem($item);

        return $item->fresh() ?? $item;
    }

    /**
     * Download and store the original source file unchanged (no translation/rewrite).
     *
     * @param  array<string, mixed>|null  $primarySource
     */
    private function preserveOriginalSourceFile(LibraryItem $item, ?array $primarySource): void
    {
        if ($primarySource === null) {
            return;
        }

        $url = trim((string) ($primarySource['url'] ?? ''));
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }

        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)
                ->withHeaders(['User-Agent' => 'WSA-Enterprise-Research-Agent/1.0'])
                ->withOptions(['allow_redirects' => ['max' => 3]])
                ->get($url);

            if (! $response->successful()) {
                return;
            }

            $bytes = $response->body();
            if ($bytes === '' || strlen($bytes) > 15_000_000) {
                return;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = match (true) {
                str_contains($contentType, 'pdf') => 'pdf',
                str_contains($contentType, 'html') => 'html',
                str_contains($contentType, 'xml') => 'xml',
                str_contains($contentType, 'json') => 'json',
                default => 'bin',
            };

            $disk = 'local';
            $path = sprintf(
                'research-sources/%d/%s.%s',
                (int) $item->organization_id,
                $item->slug !== '' ? $item->slug : ('item-'.$item->id),
                $extension,
            );

            \Illuminate\Support\Facades\Storage::disk($disk)->put($path, $bytes);

            $item->file_disk = $disk;
            $item->file_path = $path;
            $metadata = is_array($item->metadata) ? $item->metadata : [];
            $metadata['original_source_file'] = [
                'preserved' => true,
                'source_url' => $url,
                'content_type' => $contentType !== '' ? $contentType : null,
                'byte_length' => strlen($bytes),
                'transformed' => false,
                'translated' => false,
            ];
            $item->metadata = $metadata;
            $item->save();
        } catch (\Throwable $exception) {
            Log::info('research.original_source_preserve_skipped', [
                'library_item_id' => $item->id,
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function provenance(
        KnowledgeQueryPlan $plan,
        AnswerSynthesisExecutionReport $synthesisReport,
        EvidenceValidationExecutionReport $validationReport,
    ): array {
        return [
            'research_timestamp' => now()->toIso8601String(),
            'query' => $plan->normalizedQuery->originalQuestion,
            'research_intent' => $plan->researchIntent,
            'agricultural_domain' => $plan->agriculturalDomain,
            'validation_status' => $validationReport->status,
            'validation_summary' => [
                'validated_count' => $validationReport->validatedCount,
                'rejected_count' => $validationReport->rejectedCount,
                'evidence_sufficient' => $validationReport->evidenceSufficient,
            ],
            'synthesis_status' => $synthesisReport->status,
            'source_types_used' => $validationReport->observability['source_types_used'] ?? [],
            'internet_first' => $plan->isInternetFirst(),
            'pipeline' => 'agricultural_research_agent_stage_5',
        ];
    }

    /**
     * @param  list<ResearchAnswerCitation>  $citations
     * @return list<array<string, mixed>>
     */
    private function scientificReferences(array $citations): array
    {
        $references = [];
        foreach ($citations as $citation) {
            $reference = $citation->toScientificReference();
            if ($this->sourceValidator->isVerifiedSource($reference)) {
                $key = (string) ($reference['url'] ?? $reference['doi'] ?? json_encode($reference));
                $references[$key] = $reference;
            }
        }

        return array_values($references);
    }

    /**
     * @param  list<array<string, mixed>>  $references
     */
    private function sourceAttribution(array $references): ?string
    {
        if ($references === []) {
            return null;
        }

        $first = $references[0];
        $org = (string) ($first['organization'] ?? '');
        $title = (string) ($first['title'] ?? '');

        return trim($org.' — '.$title) ?: null;
    }

    private function slugFor(KnowledgeQueryPlan $plan): string
    {
        $parts = [
            mb_strtolower(trim($plan->normalizedQuery->normalizedQuestion)),
            mb_strtolower(trim($plan->agriculturalDomain)),
            mb_strtolower(trim((string) ($plan->subjectEntity['value'] ?? ''))),
            mb_strtolower(trim((string) ($plan->normalizedQuery->cropId ?? ''))),
        ];
        $hash = substr(md5(implode('|', array_filter($parts))), 0, 16);

        return 'research-knowledge-'.$hash;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $evidence
     */
    private function evidenceFingerprint(array $evidence): string
    {
        $ids = array_map(
            static fn (ScientificEvidenceItem $item): string => $item->evidenceId,
            $evidence,
        );
        sort($ids);

        return md5(implode('|', $ids));
    }

    /**
     * @param  list<ScientificEvidenceItem>  $evidence
     */
    private function newestPublicationYear(array $evidence): int
    {
        $years = array_filter(array_map(
            static fn (ScientificEvidenceItem $item): int => (int) ($item->publicationYear ?? 0),
            $evidence,
        ));

        return $years === [] ? 0 : max($years);
    }

    private function titleFor(KnowledgeQueryPlan $plan, string $language): string
    {
        $query = trim($plan->normalizedQuery->originalQuestion);
        if ($query === '') {
            return $language === 'ar' ? 'معرفة زراعية موثقة' : 'Verified agricultural knowledge';
        }

        return mb_strlen($query) > 200 ? mb_substr($query, 0, 197).'...' : $query;
    }

    /**
     * R2: answer_language follows question_language (constraints), not UI locale.
     */
    private function resolvePersistedAnswerLanguage(KnowledgeQueryPlan $plan): string
    {
        $fromConstraint = strtolower(substr(trim((string) ($plan->normalizedQuery->constraints['answer_language'] ?? '')), 0, 2));
        if (in_array($fromConstraint, ['ar', 'en', 'tr', 'fr'], true)) {
            return $fromConstraint;
        }

        $question = strtolower(substr(trim($plan->normalizedQuery->language), 0, 2));

        return in_array($question, ['ar', 'en', 'tr', 'fr'], true) ? $question : 'en';
    }

    /**
     * R19: organize verified research under Crop → Scientific Research folders.
     * Uses LibraryCategory hierarchy; does not invent FAOSTAT codes as crop IDs.
     *
     * @return array{
     *     crop_id: string,
     *     crop_name: string,
     *     category_id: string,
     *     category_name: string,
     *     crop_category_row_id: int,
     *     scientific_category_row_id: int
     * }|null
     */
    private function ensureCropScientificResearchFolder(int $organizationId, KnowledgeQueryPlan $plan): ?array
    {
        $cropId = trim((string) ($plan->contextInput['selected_crop_id'] ?? $plan->normalizedQuery->cropId ?? ''));
        $cropName = trim((string) ($plan->contextInput['selected_crop_name'] ?? $plan->normalizedQuery->crop ?? ''));
        if ($cropId === '' && is_array($plan->subjectEntity) && ($plan->subjectEntity['type'] ?? '') === 'crop') {
            $cropId = trim((string) ($plan->subjectEntity['value'] ?? ''));
            $cropName = trim((string) ($plan->subjectEntity['label'] ?? $cropName));
        }
        if ($cropId === '') {
            return null;
        }
        if ($cropName === '') {
            $cropName = $cropId;
        }

        $categoryId = trim((string) ($plan->contextInput['selected_category_id'] ?? ''));
        $categoryName = trim((string) ($plan->contextInput['selected_category_name'] ?? ''));

        $cropCode = 'crop-'.Str::slug(mb_substr($cropId, 0, 48));
        if ($cropCode === 'crop-' || strlen($cropCode) < 6) {
            $cropCode = 'crop-'.substr(md5($cropId), 0, 12);
        }

        $cropCategory = LibraryCategory::query()->firstOrCreate(
            [
                'organization_id' => $organizationId,
                'code' => mb_substr($cropCode, 0, 32),
            ],
            [
                'parent_id' => null,
                'name' => $cropName,
                'name_ar' => $cropName,
            ],
        );

        $scientific = LibraryCategory::query()->firstOrCreate(
            [
                'organization_id' => $organizationId,
                'code' => mb_substr($cropCode.'-sci', 0, 32),
            ],
            [
                'parent_id' => (int) $cropCategory->id,
                'name' => 'Scientific Research',
                'name_ar' => 'الأبحاث العلمية',
            ],
        );

        if ($scientific->parent_id === null || (int) $scientific->parent_id !== (int) $cropCategory->id) {
            $scientific->parent_id = (int) $cropCategory->id;
            $scientific->save();
        }

        return [
            'crop_id' => $cropId,
            'crop_name' => $cropName,
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'crop_category_row_id' => (int) $cropCategory->id,
            'scientific_category_row_id' => (int) $scientific->id,
        ];
    }

    private function auditPublicPersistenceAccepted(
        int $organizationId,
        LibraryItem $item,
        string $persistAction,
    ): void {
        if (! $this->tenantContext->isPublicBound()) {
            return;
        }

        $this->audit->record(
            action: self::ACTION_PERSISTENCE_ACCEPTED,
            organizationId: $organizationId,
            userId: null,
            auditable: $item,
            newValues: [
                'category' => 'public_persistence_accepted',
                'persistence_surface' => 'library',
                'persist_action' => $persistAction,
            ],
            request: $this->currentRequest(),
        );
    }

    private function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }

    private function skippedReport(string $status, string $reason): KnowledgePersistenceExecutionReport
    {
        return new KnowledgePersistenceExecutionReport(
            status: $status,
            performed: false,
            libraryItemId: null,
            slug: null,
            action: 'skipped',
            provenance: null,
            observability: [
                'failure_reason' => $reason,
                'internet_first_preserved' => true,
            ],
        );
    }
}
