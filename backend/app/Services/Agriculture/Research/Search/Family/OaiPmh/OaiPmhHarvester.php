<?php

namespace App\Services\Agriculture\Research\Search\Family\OaiPmh;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureCategory;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureClassifier;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyRecord;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSearchTimeBudget;
use App\Support\ScientificHttp;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OAI-PMH 2.0 harvesting family (ADR-023 §8.39, increment R5).
 *
 * Implements ListRecords with `oai_dc` and resumption tokens. It is a bulk-access component,
 * not a search adapter: OAI-PMH defines no query verb. Responses with a DOCTYPE are refused so
 * no external or recursive entity is ever processed.
 */
final class OaiPmhHarvester
{
    public const CONTRACT_VERSION = 'scientific_source_harvester.v1';

    public const NO_RECORDS_MATCH = 'noRecordsMatch';

    private const NS_OAI = 'http://www.openarchives.org/OAI/2.0/';

    private const NS_OAI_DC = 'http://www.openarchives.org/OAI/2.0/oai_dc/';

    private const NS_DC = 'http://purl.org/dc/elements/1.1/';

    private const DC_ELEMENTS = ['title', 'creator', 'subject', 'description', 'publisher', 'date', 'type', 'identifier', 'source', 'language', 'rights'];

    /**
     * A first page takes from/until; a following page takes only the resumption token, which
     * OAI-PMH defines as an exclusive argument.
     */
    public function listRecords(
        OaiPmhSourceProfile $profile,
        ?string $from = null,
        ?string $until = null,
        ?string $resumptionToken = null,
    ): OaiPmhHarvestPage {
        $parameters = $this->parameters($profile, $from, $until, $resumptionToken);

        $remaining = ScientificSearchTimeBudget::current()?->remainingSeconds();
        if ($remaining !== null && $remaining < 1.0) {
            return OaiPmhHarvestPage::failed($profile->sourceKey, ScientificSourceFailureCategory::BUDGET_EXHAUSTED, 'time_budget_exhausted');
        }

        try {
            $response = Http::timeout(ScientificHttp::timeoutSeconds($remaining))
                ->accept('text/xml')
                ->get($profile->baseUrl, $parameters);
        } catch (\Throwable $exception) {
            $category = ScientificSourceFailureClassifier::classifyThrowable($exception);
            if ($category === null) {
                throw $exception;
            }
            Log::warning('OAI-PMH harvest request failed', [
                'source' => $profile->sourceKey,
                'exception' => $exception::class,
            ]);

            return OaiPmhHarvestPage::failed($profile->sourceKey, $category, 'request_exception');
        }

        if (! $response->successful()) {
            $status = $response->status();
            $category = $status === 503 && ScientificHttp::retryAfterHeaderSeconds($response) !== null
                ? ScientificSourceFailureCategory::RATE_LIMITED
                : ScientificSourceFailureClassifier::categoryForHttpStatus($status);

            return OaiPmhHarvestPage::failed($profile->sourceKey, $category, 'http_error', $status);
        }

        $retrievedAt = now()->utc()->toIso8601String();
        $xpath = $this->xpath($response->body());
        $root = $xpath?->query('/oai:OAI-PMH')->item(0);
        if ($xpath === null || ! $root instanceof DOMElement) {
            return OaiPmhHarvestPage::failed($profile->sourceKey, ScientificSourceFailureCategory::MALFORMED_RESPONSE, 'malformed_response');
        }
        $responseDate = $this->text($xpath, 'oai:responseDate', $root);

        $errorCodes = [];
        foreach ($xpath->query('oai:error', $root) as $error) {
            if ($error instanceof DOMElement) {
                $errorCodes[] = $error->getAttribute('code');
            }
        }
        if ($errorCodes !== []) {
            if (array_unique($errorCodes) === [self::NO_RECORDS_MATCH]) {
                return new OaiPmhHarvestPage(
                    sourceKey: $profile->sourceKey,
                    status: OaiPmhHarvestPage::STATUS_EMPTY,
                    responseDate: $responseDate,
                    errorCodes: $errorCodes,
                );
            }

            return OaiPmhHarvestPage::failed(
                $profile->sourceKey,
                ScientificSourceFailureCategory::REQUEST_REJECTED,
                'oai_pmh_error',
                errorCodes: $errorCodes,
            );
        }

        $list = $xpath->query('oai:ListRecords', $root)->item(0);
        if (! $list instanceof DOMElement) {
            return OaiPmhHarvestPage::failed($profile->sourceKey, ScientificSourceFailureCategory::MALFORMED_RESPONSE, 'malformed_response');
        }

        $records = [];
        $deleted = [];
        $skipped = 0;
        foreach ($xpath->query('oai:record', $list) as $record) {
            if (! $record instanceof DOMElement) {
                continue;
            }
            $identifier = $this->text($xpath, 'oai:header/oai:identifier', $record);
            $header = $xpath->query('oai:header', $record)->item(0);
            if ($identifier === null || ! $header instanceof DOMElement) {
                $skipped++;

                continue;
            }
            if ($header->getAttribute('status') === 'deleted') {
                $deleted[] = $identifier;

                continue;
            }
            $dc = $xpath->query('oai:metadata/oai_dc:dc', $record)->item(0);
            $result = $dc instanceof DOMElement
                ? $this->toResult($profile, $xpath, $identifier, $header, $dc, $retrievedAt)
                : null;
            if ($result === null) {
                $skipped++;

                continue;
            }
            $records[] = $result;
        }

        $token = $xpath->query('oai:resumptionToken', $list)->item(0);
        $tokenValue = $token instanceof DOMElement ? trim($token->textContent) : '';
        $listSize = $token instanceof DOMElement ? $token->getAttribute('completeListSize') : '';

        return new OaiPmhHarvestPage(
            sourceKey: $profile->sourceKey,
            status: $records === [] && $tokenValue === '' ? OaiPmhHarvestPage::STATUS_EMPTY : OaiPmhHarvestPage::STATUS_SUCCESS,
            records: $records,
            deletedIdentifiers: $deleted,
            skippedRecords: $skipped,
            resumptionToken: $tokenValue !== '' ? $tokenValue : null,
            completeListSize: ctype_digit($listSize) ? (int) $listSize : null,
            responseDate: $responseDate,
        );
    }

    /** @return array<string, string> */
    private function parameters(OaiPmhSourceProfile $profile, ?string $from, ?string $until, ?string $resumptionToken): array
    {
        if ($resumptionToken !== null) {
            if (trim($resumptionToken) === '') {
                throw new ScientificSourceContractViolation('An OAI-PMH resumption token must not be empty.');
            }
            if ($from !== null || $until !== null) {
                throw new ScientificSourceContractViolation('An OAI-PMH resumption token is an exclusive argument; from and until must be null.');
            }

            return ['verb' => 'ListRecords', 'resumptionToken' => $resumptionToken];
        }

        foreach (['from' => $from, 'until' => $until] as $name => $value) {
            if ($value !== null && preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', $value) !== 1) {
                throw new ScientificSourceContractViolation("OAI-PMH {$name} must be YYYY-MM-DD or YYYY-MM-DDThh:mm:ssZ; got '{$value}'.");
            }
        }
        if ($from !== null && $until !== null && (strlen($from) !== strlen($until) || $from > $until)) {
            throw new ScientificSourceContractViolation('OAI-PMH from and until must share one granularity and from must not be later than until.');
        }

        return array_filter([
            'verb' => 'ListRecords',
            'metadataPrefix' => OaiPmhSourceProfile::METADATA_PREFIX,
            'set' => $profile->setSpec,
            'from' => $from,
            'until' => $until,
        ], static fn (?string $value): bool => $value !== null);
    }

    private function toResult(
        OaiPmhSourceProfile $profile,
        DOMXPath $xpath,
        string $identifier,
        DOMElement $header,
        DOMElement $dc,
        string $retrievedAt,
    ): ?ScientificSearchResult {
        $fields = [];
        foreach (self::DC_ELEMENTS as $element) {
            $fields[$element] = $this->texts($xpath, 'dc:'.$element, $dc);
        }

        $doi = null;
        $url = null;
        foreach ($fields['identifier'] as $value) {
            if ($doi === null && ScientificSourceFamilyRecord::bareDoi($value) !== null) {
                $doi = $value;

                continue;
            }
            if ($url === null && ScientificSourceFamilyRecord::httpUrl($value) !== null) {
                $url = $value;
            }
        }
        // Policy-linked summary URLs when DC only has relative file paths (Organic Eprints).
        $url ??= $profile->recordUrlForOaiIdentifier($identifier);

        return ScientificSourceFamilyRecord::toResult(
            profile: $profile,
            rawTitle: $fields['title'][0] ?? null,
            originalIdentifier: $identifier,
            originalIdentifierField: 'header.identifier',
            rawDoi: $doi,
            rawUrl: $url,
            rawAbstract: $fields['description'][0] ?? null,
            publishedAt: $fields['date'][0] ?? null,
            year: null,
            authors: $fields['creator'],
            journal: null,
            raw: [
                'header' => [
                    'identifier' => $identifier,
                    'datestamp' => $this->text($xpath, 'oai:datestamp', $header),
                    'set_spec' => $this->texts($xpath, 'oai:setSpec', $header),
                ],
                'dc' => array_filter($fields, static fn (array $values): bool => $values !== []),
            ],
            retrievedAt: $retrievedAt,
            adapterClass: self::class,
            contractVersion: self::CONTRACT_VERSION,
        );
    }

    private function xpath(string $body): ?DOMXPath
    {
        if (trim($body) === '' || stripos($body, '<!DOCTYPE') !== false) {
            return null;
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (! $document->loadXML($body, LIBXML_NONET) || $document->doctype !== null) {
                return null;
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('oai', self::NS_OAI);
        $xpath->registerNamespace('oai_dc', self::NS_OAI_DC);
        $xpath->registerNamespace('dc', self::NS_DC);

        return $xpath;
    }

    private function text(DOMXPath $xpath, string $expression, DOMElement $context): ?string
    {
        return $this->texts($xpath, $expression, $context)[0] ?? null;
    }

    /** @return list<string> */
    private function texts(DOMXPath $xpath, string $expression, DOMElement $context): array
    {
        $values = [];
        foreach ($xpath->query($expression, $context) as $node) {
            $value = trim($node->textContent);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }
}
