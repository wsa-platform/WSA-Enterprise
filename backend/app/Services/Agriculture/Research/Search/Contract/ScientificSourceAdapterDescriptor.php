<?php

namespace App\Services\Agriculture\Research\Search\Contract;

use App\Contracts\ScientificSourceAdapterV2Interface;

/**
 * What a v2 adapter declares about itself (adapter contract v2, ADR-023 §8.36).
 *
 * Null means undeclared, never "unlimited" or "supported": a value is declared only when
 * it is backed by the adapter's own code, provider documentation, or a recorded observation.
 */
final readonly class ScientificSourceAdapterDescriptor
{
    public function __construct(
        public string $sourceKey,
        public ScientificSourceLicensingDeclaration $licensing,
        public ScientificSourceRetryPolicy $retryPolicy,
        public int $maxConcurrentRequests = 1,
        public ?int $maxResultsPerRequest = null,
        public ?bool $paginationSupported = null,
        public ?int $requestsPerMinute = null,
        public string $contractVersion = ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $sourceKey) !== 1) {
            throw new ScientificSourceContractViolation("Descriptor sourceKey must be lower-case snake_case; got '{$sourceKey}'.");
        }
        if ($contractVersion !== ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION) {
            throw new ScientificSourceContractViolation(
                "Descriptor contract version must be '".ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION."'; got '{$contractVersion}'."
            );
        }
        foreach (['maxConcurrentRequests' => $maxConcurrentRequests, 'maxResultsPerRequest' => $maxResultsPerRequest, 'requestsPerMinute' => $requestsPerMinute] as $field => $value) {
            if ($value !== null && $value < 1) {
                throw new ScientificSourceContractViolation("Descriptor {$field} must be at least 1 when declared; got {$value}.");
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'contract_version' => $this->contractVersion,
            'max_concurrent_requests' => $this->maxConcurrentRequests,
            'max_results_per_request' => $this->maxResultsPerRequest,
            'pagination_supported' => $this->paginationSupported,
            'requests_per_minute' => $this->requestsPerMinute,
            'retry_policy' => $this->retryPolicy->toArray(),
            'licensing' => $this->licensing->toArray(),
        ];
    }
}
