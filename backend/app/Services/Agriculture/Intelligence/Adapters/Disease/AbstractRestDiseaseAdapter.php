<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

use App\Contracts\Agriculture\PlantDiseaseAnalysisProviderInterface;
use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\Normalization\DiseaseResultNormalizer;
use Illuminate\Support\Facades\Http;

/**
 * Generic REST plant-disease adapter. Config-driven; NOT_CONFIGURED without endpoint.
 * No Python execution in Laravel.
 */
abstract class AbstractRestDiseaseAdapter extends AbstractAgriculturalProvider implements PlantDiseaseAnalysisProviderInterface
{
    public function __construct(
        protected DiseaseResultNormalizer $diseaseNormalizer,
    ) {}

    abstract protected function configKey(): string;

    abstract protected function providerId(): string;

    abstract protected function providerName(): string;

    public function analyze(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        return $this->retrieve($input);
    }

    protected function buildDescriptor(): ProviderDescriptor
    {
        $cfg = (array) config('agricultural_intelligence.disease.'.$this->configKey(), []);

        return new ProviderDescriptor(
            id: $this->providerId(),
            name: $this->providerName(),
            type: ProviderType::DISEASE,
            capabilities: ['plant_disease_analysis', 'image_or_text_diagnosis'],
            version: (string) ($cfg['version'] ?? '1.0.0'),
            priority: (int) ($cfg['priority'] ?? 80),
            timeoutSeconds: max(1, min(60, (int) ($cfg['timeout'] ?? 20))),
            health: $this->isConfigured() ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: [
                'mode' => 'required_endpoint',
                'configured' => $this->isConfigured(),
            ],
            confidenceMeta: ['family' => 'disease', 'model_dependent' => true],
            evidenceCapable: true,
            limitations: ['Requires external REST endpoint', 'Not a scholarly source'],
            config: ['enabled' => (bool) ($cfg['enabled'] ?? false)],
            enabled: (bool) ($cfg['enabled'] ?? false),
        );
    }

    protected function isConfigured(): bool
    {
        $cfg = (array) config('agricultural_intelligence.disease.'.$this->configKey(), []);
        if (! filter_var($cfg['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return trim((string) ($cfg['endpoint'] ?? '')) !== '';
    }

    protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $cfg = (array) config('agricultural_intelligence.disease.'.$this->configKey(), []);
        $endpoint = (string) $cfg['endpoint'];
        $timeout = max(1, min(60, (int) ($cfg['timeout'] ?? 20)));
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));

        try {
            $pending = Http::timeout($timeout)->acceptJson();
            if ($apiKey !== '') {
                $pending = $pending->withHeaders(['Authorization' => 'Bearer '.$apiKey]);
            }
            $response = $pending->post($endpoint, [
                'query' => $input->query,
                'language' => $input->language,
                'entities' => $input->entities,
                'context' => $input->context,
                'limit' => $input->limit,
            ]);
        } catch (\Throwable $e) {
            return CanonicalAgriculturalResult::failed($this->providerId(), 'request_exception');
        }

        if (! $response->successful()) {
            return CanonicalAgriculturalResult::failed($this->providerId(), 'http_'.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            return CanonicalAgriculturalResult::empty($this->providerId());
        }

        $items = $json['results'] ?? $json['predictions'] ?? [$json];
        if (! is_array($items)) {
            $items = [];
        }
        $disease = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $disease[] = $this->diseaseNormalizer->normalize($item, $this->providerId());
            }
        }

        if ($disease === []) {
            return CanonicalAgriculturalResult::empty($this->providerId());
        }

        return new CanonicalAgriculturalResult(
            providerId: $this->providerId(),
            status: 'success',
            disease: $disease,
            confidence: isset($disease[0]['confidence']) && is_numeric($disease[0]['confidence'])
                ? (float) $disease[0]['confidence']
                : 0.5,
            source: $this->providerId(),
            timestamp: now()->toIso8601String(),
            limitations: ['disease_model_output_not_scientific_peer_review'],
        );
    }
}
