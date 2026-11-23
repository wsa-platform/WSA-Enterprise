<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Per-source language capability contract (ISS-SRC-002).
 *
 * Separate from Membership / Identity / Eligibility / Activation / SEARCH operation capability.
 * Unknown dimensions stay explicit — never invent native support from adapted-query success.
 */
final readonly class ScientificSourceLanguageCapability
{
    public const SUPPORT_NATIVE = 'native';

    public const SUPPORT_ADAPTED_QUERY = 'adapted_query';

    public const SUPPORT_UNKNOWN = 'unknown';

    public const SUPPORT_UNSUPPORTED = 'unsupported';

    /**
     * @param  list<string>  $queryInterfaceLanguages  Languages the search interface accepts natively
     * @param  list<string>  $adaptedQueryLanguages    Languages reachable via retrieval compilation
     * @param  list<string>  $indexedContentLanguages  Dominant indexed/content languages (may be UNKNOWN)
     * @param  list<string>  $liveVerifiedLanguages    Live-verified query languages
     */
    public function __construct(
        public string $sourceKey,
        public array $queryInterfaceLanguages,
        public array $adaptedQueryLanguages,
        public array $indexedContentLanguages,
        public array $liveVerifiedLanguages,
        public string $adapterLanguageSupport,
        public string $evidenceReference,
    ) {}

    /** Prefer English compilation for English-indexed scholarly/LocalIndex corpora. */
    public function requiresEnglishRetrievalCompilation(): bool
    {
        if ($this->adapterLanguageSupport === self::SUPPORT_NATIVE
            && in_array('ar', $this->queryInterfaceLanguages, true)) {
            return false;
        }

        return in_array('en', $this->adaptedQueryLanguages, true)
            || in_array('en', $this->indexedContentLanguages, true)
            || $this->indexedContentLanguages === ['unknown'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'query_interface_languages' => $this->queryInterfaceLanguages,
            'adapted_query_languages' => $this->adaptedQueryLanguages,
            'indexed_content_languages' => $this->indexedContentLanguages,
            'live_verified_languages' => $this->liveVerifiedLanguages,
            'adapter_language_support' => $this->adapterLanguageSupport,
            'requires_english_retrieval_compilation' => $this->requiresEnglishRetrievalCompilation(),
            'evidence_reference' => $this->evidenceReference,
        ];
    }
}
