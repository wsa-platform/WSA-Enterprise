<?php

namespace App\Services\Agriculture\Diagnosis\Vision;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiProviderTimeoutException;
use App\Exceptions\AiProviderUnavailableException;
use App\Services\Agriculture\Diagnosis\PlantDiagnosisRequest;
use App\Services\Agriculture\Diagnosis\VisionObservation;
use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Adapts existing AiProviderInterface for plant vision observation analysis.
 * Does not use the Research Agent stack or library research orchestration.
 */
class AiProviderVisionAdapter implements VisionAnalysisProviderInterface
{
    public function __construct(
        private readonly AiProviderInterface $provider,
    ) {}

    public function analyze(PlantDiagnosisRequest $request): array
    {
        $input = [
            'notes' => $this->buildPrompt($request),
            'image_mime' => $request->imageMetadata->detectedMime,
            'image_width' => $request->imageMetadata->width,
            'image_height' => $request->imageMetadata->height,
            'image_size_bytes' => $request->imageMetadata->sizeBytes,
            'image_content_hash' => $request->imageMetadata->contentHash,
            'image_base64' => base64_encode($request->imageBinary),
            'plant_context' => $request->plantContext->toArray(),
            'locale' => $request->locale,
        ];

        try {
            $response = $this->provider->complete('plant_vision_analysis', $input);
        } catch (AiProviderTimeoutException|AiProviderUnavailableException $exception) {
            Log::warning('plant_diagnosis.vision_provider_failure', [
                'category' => AiErrorSanitizer::category($exception),
                'message' => AiErrorSanitizer::logMessage($exception),
                'provider' => $this->provider->name(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('plant_diagnosis.vision_provider_unexpected', [
                'category' => AiErrorSanitizer::category($exception),
                'message' => AiErrorSanitizer::logMessage($exception),
                'provider' => $this->provider->name(),
            ]);

            throw new AiProviderUnavailableException(
                $this->provider->name(),
                502,
                'Vision analysis provider failed.',
            );
        }

        return $this->normalize($response);
    }

    private function buildPrompt(PlantDiagnosisRequest $request): string
    {
        $context = $request->plantContext;

        return implode("\n", array_filter([
            'Analyze the provided plant image for visible symptoms as OBSERVATIONS only.',
            'Do not assert a definitive disease. Do not invent scientific names.',
            'Do not recommend pesticide dosages or concentrations.',
            'Return structured observations describing what is visible.',
            $context->plantName ? 'Plant name: '.$context->plantName : null,
            $context->cropType ? 'Crop type: '.$context->cropType : null,
            $context->growthStage ? 'Growth stage: '.$context->growthStage : null,
            $context->location ? 'Location: '.$context->location : null,
            $context->notes ? 'Grower notes: '.$context->notes : null,
            $context->symptomsDescribed !== [] ? 'Described symptoms: '.implode(', ', $context->symptomsDescribed) : null,
            sprintf(
                'Image metadata: mime=%s size=%d width=%d height=%d hash=%s',
                $request->imageMetadata->detectedMime,
                $request->imageMetadata->sizeBytes,
                $request->imageMetadata->width,
                $request->imageMetadata->height,
                $request->imageMetadata->contentHash,
            ),
        ]));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{
     *     image_quality: string,
     *     plant_visible: bool,
     *     symptoms_visible: bool,
     *     quality_notes: list<string>,
     *     observations: list<VisionObservation>,
     *     provider: string,
     *     model: string|null,
     *     raw_status: string
     * }
     */
    private function normalize(array $response): array
    {
        $quality = is_string($response['image_quality'] ?? null)
            ? strtolower((string) $response['image_quality'])
            : 'adequate';

        $qualityNotes = [];
        if (isset($response['quality_notes']) && is_array($response['quality_notes'])) {
            foreach ($response['quality_notes'] as $note) {
                if (is_string($note) && trim($note) !== '') {
                    $qualityNotes[] = trim($note);
                }
            }
        }

        $observations = $this->parseObservations($response);

        return [
            'image_quality' => $quality,
            'plant_visible' => (bool) ($response['plant_visible'] ?? ($observations !== [])),
            'symptoms_visible' => (bool) ($response['symptoms_visible'] ?? ($observations !== [])),
            'quality_notes' => $qualityNotes,
            'observations' => $observations,
            'provider' => is_string($response['provider'] ?? null) ? (string) $response['provider'] : $this->provider->name(),
            'model' => is_string($response['model'] ?? null) ? (string) $response['model'] : $this->provider->model(),
            'raw_status' => is_string($response['status'] ?? null) ? (string) $response['status'] : 'completed',
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<VisionObservation>
     */
    private function parseObservations(array $response): array
    {
        $raw = $response['observations'] ?? null;

        if (! is_array($raw) && isset($response['summary']) && is_string($response['summary'])) {
            $decoded = json_decode($response['summary'], true);
            if (is_array($decoded) && isset($decoded['observations']) && is_array($decoded['observations'])) {
                $raw = $decoded['observations'];
            }
        }

        if (! is_array($raw)) {
            return [];
        }

        $observations = [];
        foreach (array_values($raw) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $description = trim((string) ($item['description'] ?? $item['text'] ?? ''));
            if ($description === '') {
                continue;
            }

            $type = trim((string) ($item['type'] ?? $item['category'] ?? 'visual_symptom'));
            $cues = [];
            if (isset($item['supporting_cues']) && is_array($item['supporting_cues'])) {
                foreach ($item['supporting_cues'] as $cue) {
                    if (is_string($cue) && trim($cue) !== '') {
                        $cues[] = trim($cue);
                    }
                }
            }

            $observations[] = new VisionObservation(
                id: is_string($item['id'] ?? null) && $item['id'] !== '' ? (string) $item['id'] : 'obs-'.($index + 1),
                type: $type !== '' ? $type : 'visual_symptom',
                description: $description,
                locationOnPlant: isset($item['location']) && is_string($item['location']) ? $item['location'] : (isset($item['location_on_plant']) && is_string($item['location_on_plant']) ? $item['location_on_plant'] : null),
                severityHint: isset($item['severity_hint']) && is_string($item['severity_hint']) ? $item['severity_hint'] : null,
                observationConfidence: $this->normalizeConfidence($item['observation_confidence'] ?? $item['confidence'] ?? 0.5),
                supportingCues: $cues,
            );
        }

        return $observations;
    }

    private function normalizeConfidence(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.5;
        }

        return max(0.0, min(1.0, (float) $value));
    }
}
