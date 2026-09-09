<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Provider-agnostic canonical agricultural result (ADR-001).
 */
final class CanonicalAgriculturalResult
{
    /**
     * @param  list<array<string, mixed>>  $claims
     * @param  list<MeasurementValue|array<string, mixed>>  $measurements
     * @param  list<array<string, mixed>>  $entities
     * @param  list<array<string, mixed>>  $disease
     * @param  list<array<string, mixed>>  $weather
     * @param  list<array<string, mixed>>  $stats
     * @param  list<array<string, mixed>>  $scientificEvidence
     * @param  list<array<string, mixed>>  $webEvidence
     * @param  list<string>  $limitations
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $providerId,
        public readonly string $status,
        public readonly array $claims = [],
        public readonly array $measurements = [],
        public readonly array $entities = [],
        public readonly array $disease = [],
        public readonly array $weather = [],
        public readonly array $stats = [],
        public readonly array $scientificEvidence = [],
        public readonly array $webEvidence = [],
        public readonly ?float $confidence = null,
        public readonly ?string $source = null,
        public readonly ?string $timestamp = null,
        public readonly array $limitations = [],
        public readonly ?string $error = null,
        public readonly array $meta = [],
    ) {}

    public static function notConfigured(string $providerId, string $message = 'NOT_CONFIGURED'): self
    {
        return new self(
            providerId: $providerId,
            status: 'not_configured',
            limitations: [$message],
            error: $message,
            timestamp: now()->toIso8601String(),
        );
    }

    public static function blocked(string $providerId, string $message = 'BLOCKED'): self
    {
        return new self(
            providerId: $providerId,
            status: 'blocked',
            limitations: [$message],
            error: $message,
            timestamp: now()->toIso8601String(),
        );
    }

    public static function failed(string $providerId, string $error): self
    {
        return new self(
            providerId: $providerId,
            status: 'failed',
            error: $error,
            timestamp: now()->toIso8601String(),
            limitations: ['provider_failure_isolated'],
        );
    }

    public static function empty(string $providerId, string $reason = 'empty'): self
    {
        return new self(
            providerId: $providerId,
            status: 'empty',
            error: $reason,
            timestamp: now()->toIso8601String(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider_id' => $this->providerId,
            'status' => $this->status,
            'claims' => $this->claims,
            'measurements' => array_map(
                static fn ($m) => $m instanceof MeasurementValue ? $m->toArray() : $m,
                $this->measurements,
            ),
            'entities' => $this->entities,
            'disease' => $this->disease,
            'weather' => $this->weather,
            'stats' => $this->stats,
            'scientific_evidence' => $this->scientificEvidence,
            'web_evidence' => $this->webEvidence,
            'confidence' => $this->confidence,
            'source' => $this->source,
            'timestamp' => $this->timestamp,
            'limitations' => $this->limitations,
            'error' => $this->error,
            'meta' => $this->meta,
        ];
    }

    public function hasUsableEvidence(): bool
    {
        return $this->status === 'success'
            && (
                $this->claims !== []
                || $this->scientificEvidence !== []
                || $this->webEvidence !== []
                || $this->measurements !== []
                || $this->stats !== []
                || $this->disease !== []
                || $this->weather !== []
            );
    }
}
