<?php

namespace App\Services\Agriculture\Diagnosis;

use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use App\Services\Agriculture\Diagnosis\Clarification\AdditionalInformationSelector;
use App\Services\Agriculture\Diagnosis\Image\DiagnosisImageValidator;
use App\Services\Agriculture\Diagnosis\Knowledge\DiagnosisKnowledgeSupportInterface;
use App\Services\Agriculture\Diagnosis\Observability\DiagnosisObservabilityLogger;
use App\Services\Agriculture\Diagnosis\Ranking\CandidateDiagnosisRanker;
use App\Services\Agriculture\Diagnosis\Safety\DiagnosisSafetyGuard;
use App\Services\Agriculture\Diagnosis\Vision\VisionAnalysisService;
use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Stage 6 Plant AI Diagnosis Engine.
 *
 * Runtime-independent from the Research Agent stack and scientific knowledge engine.
 * Uses AI provider vision analysis only — never calls research orchestration services.
 */
class PlantAiDiagnosisEngine
{
    public function __construct(
        private readonly DiagnosisImageValidator $imageValidator,
        private readonly VisionAnalysisService $visionAnalysis,
        private readonly DiagnosisKnowledgeSupportInterface $knowledgeSupport,
        private readonly CandidateDiagnosisRanker $ranker,
        private readonly DiagnosisSafetyGuard $safetyGuard,
        private readonly AdditionalInformationSelector $additionalInfoSelector,
        private readonly DiagnosisObservabilityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function diagnose(array $input): PlantDiagnosisResult
    {
        $started = microtime(true);

        try {
            $request = $this->buildRequest($input);
        } catch (ValidationException $exception) {
            $this->logger->warning('invalid_input', [
                'errors' => $exception->errors(),
            ]);

            return new PlantDiagnosisResult(
                status: DiagnosisStatus::INVALID_INPUT,
                message: 'Invalid diagnosis input.',
                safety: SafetyLimitations::defaults(),
                observability: [
                    'event' => 'invalid_input',
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                ],
            );
        }

        $this->logger->info('started', [
            'organization_id' => $request->organizationId,
            'image_hash' => $request->imageMetadata->contentHash,
            'image_mime' => $request->imageMetadata->detectedMime,
            'image_bytes' => $request->imageMetadata->sizeBytes,
            'has_context' => $request->plantContext->hasUsefulContext(),
        ]);

        try {
            $vision = $this->visionAnalysis->analyze($request);
        } catch (AiProviderTimeoutException|AiProviderUnavailableException $exception) {
            $this->logger->warning('analysis_unavailable', [
                'category' => AiErrorSanitizer::category($exception),
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return new PlantDiagnosisResult(
                status: DiagnosisStatus::ANALYSIS_UNAVAILABLE,
                message: 'Plant vision analysis is temporarily unavailable.',
                safety: SafetyLimitations::defaults(),
                observability: [
                    'event' => 'analysis_unavailable',
                    'category' => AiErrorSanitizer::category($exception),
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                ],
                imageMetadata: $request->imageMetadata,
                plantContext: $request->plantContext,
            );
        } catch (Throwable $exception) {
            $this->logger->warning('analysis_unavailable_unexpected', [
                'category' => AiErrorSanitizer::category($exception),
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return new PlantDiagnosisResult(
                status: DiagnosisStatus::ANALYSIS_UNAVAILABLE,
                message: 'Plant vision analysis failed.',
                safety: SafetyLimitations::defaults(),
                observability: [
                    'event' => 'analysis_unavailable_unexpected',
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                ],
                imageMetadata: $request->imageMetadata,
                plantContext: $request->plantContext,
            );
        }

        if (($vision['status'] ?? null) === DiagnosisStatus::INSUFFICIENT_IMAGE) {
            $this->logger->info('insufficient_image', [
                'image_quality' => $vision['image_quality'] ?? null,
                'plant_visible' => $vision['plant_visible'] ?? false,
            ]);

            return new PlantDiagnosisResult(
                status: DiagnosisStatus::INSUFFICIENT_IMAGE,
                message: (string) ($vision['message'] ?? 'Insufficient image for diagnosis.'),
                observations: $vision['observations'] ?? [],
                additionalInfoRequests: $this->additionalInfoSelector->select(
                    new UncertaintyAssessment(
                        overallUncertainty: 0.9,
                        factors: ['insufficient_image'],
                        missingSignals: ['clearer_images'],
                        imageQualityLimited: true,
                        contextLimited: ! $request->plantContext->hasUsefulContext(),
                    ),
                    $request->plantContext,
                    [],
                ),
                safety: SafetyLimitations::defaults(),
                observability: [
                    'event' => 'insufficient_image',
                    'image_quality' => $vision['image_quality'] ?? null,
                    'provider' => $vision['provider'] ?? null,
                    'model' => $vision['model'] ?? null,
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                ],
                imageMetadata: $request->imageMetadata,
                plantContext: $request->plantContext,
            );
        }

        /** @var list<VisionObservation> $observations */
        $observations = $vision['observations'] ?? [];
        $candidates = $this->knowledgeSupport->suggestCandidates($request->plantContext, $observations);
        $ranked = $this->ranker->rank(
            $candidates,
            $observations,
            $request->plantContext,
            (string) ($vision['image_quality'] ?? 'adequate'),
        );

        $safe = $this->safetyGuard->apply(
            $ranked['candidates'],
            $ranked['uncertainty'],
            [],
            $request->allowManagementGuidance,
        );

        $additional = $this->additionalInfoSelector->select(
            $ranked['uncertainty'],
            $request->plantContext,
            $safe['candidates'],
        );

        $status = $safe['status'];
        $message = match ($status) {
            DiagnosisStatus::DIAGNOSED => 'A leading diagnosis candidate was identified with high confidence (decision support only).',
            DiagnosisStatus::PROBABLE => 'Probable diagnosis candidates were identified; confirmation is recommended.',
            DiagnosisStatus::INSUFFICIENT_CONTEXT => 'Additional plant or environmental context is needed to refine diagnosis.',
            DiagnosisStatus::UNCERTAIN => 'Diagnosis remains uncertain based on available visual evidence.',
            default => 'Plant diagnosis analysis completed.',
        };

        $this->logger->info('completed', [
            'status' => $status,
            'candidate_count' => count($safe['candidates']),
            'observation_count' => count($observations),
            'provider' => $vision['provider'] ?? null,
            'model' => $vision['model'] ?? null,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);

        return new PlantDiagnosisResult(
            status: $status,
            message: $message,
            observations: $observations,
            candidates: $safe['candidates'],
            uncertainty: $ranked['uncertainty'],
            additionalInfoRequests: $additional,
            safety: $safe['safety'],
            managementNotes: $safe['management_notes'],
            observability: [
                'event' => 'completed',
                'provider' => $vision['provider'] ?? null,
                'model' => $vision['model'] ?? null,
                'image_quality' => $vision['image_quality'] ?? null,
                'candidate_count' => count($safe['candidates']),
                'observation_count' => count($observations),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'knowledge_support' => $this->knowledgeSupport::class,
            ],
            imageMetadata: $request->imageMetadata,
            plantContext: $request->plantContext,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function buildRequest(array $input): PlantDiagnosisRequest
    {
        if (isset($input['image']) && $input['image'] instanceof UploadedFile) {
            $validated = $this->imageValidator->validateUploadedFile($input['image']);
        } elseif (isset($input['image_base64']) && is_string($input['image_base64'])) {
            $validated = $this->imageValidator->validateBase64(
                $input['image_base64'],
                originalClientName: is_string($input['image_name'] ?? null) ? (string) $input['image_name'] : 'upload.bin',
                clientClaimedMime: is_string($input['image_mime'] ?? null) ? (string) $input['image_mime'] : '',
            );
        } elseif (isset($input['image_binary']) && is_string($input['image_binary'])) {
            $validated = $this->imageValidator->validateBinary(
                $input['image_binary'],
                originalClientName: is_string($input['image_name'] ?? null) ? (string) $input['image_name'] : '',
                clientClaimedMime: is_string($input['image_mime'] ?? null) ? (string) $input['image_mime'] : '',
            );
        } else {
            throw ValidationException::withMessages([
                'image' => ['An image file or image_base64 payload is required.'],
            ]);
        }

        // Reject any attempt to pass filesystem paths as the image source.
        foreach (['image_path', 'storage_path', 'file_path', 'path'] as $pathKey) {
            if (array_key_exists($pathKey, $input) && $input[$pathKey] !== null && $input[$pathKey] !== '') {
                throw ValidationException::withMessages([
                    $pathKey => ['Filesystem paths are not accepted for plant diagnosis. Upload image bytes instead.'],
                ]);
            }
        }

        $organizationId = null;
        if (isset($input['organization_id']) && is_numeric($input['organization_id'])) {
            $organizationId = (int) $input['organization_id'];
        }

        $locale = is_string($input['locale'] ?? null) && trim((string) $input['locale']) !== ''
            ? trim((string) $input['locale'])
            : 'en';

        $allowManagement = filter_var($input['allow_management_guidance'] ?? true, FILTER_VALIDATE_BOOL);

        return new PlantDiagnosisRequest(
            imageBinary: $validated['binary'],
            imageMetadata: $validated['metadata'],
            plantContext: PlantContext::fromInput($input),
            organizationId: $organizationId,
            locale: $locale,
            allowManagementGuidance: $allowManagement,
        );
    }
}
