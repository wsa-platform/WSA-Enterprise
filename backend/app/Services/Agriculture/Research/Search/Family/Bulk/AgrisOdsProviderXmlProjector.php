<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Projects one local AGRIS ODS provider XML (DCAT/Dublin Core) into JSONL for LocalIndex ingest.
 *
 * Never downloads. Reads only a local file path. Preserves AGRIS xml:id, titles, identifiers,
 * subjects, and source URLs when present. License text from the provider file is recorded in
 * each projected row's licence field for provenance (not Capability Store writes).
 */
final class AgrisOdsProviderXmlProjector
{
    public const CONTRACT_VERSION = 'agris_ods_provider_xml_projector.v1';

    /**
     * @return array{status: string, records: int, license: ?string, failure: ?string, path: string}
     */
    public function projectToJsonl(string $xmlPath, string $jsonlPath, int $maxRecords = 5_000): array
    {
        if (preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $xmlPath) === 1 || str_contains($xmlPath, "\0")) {
            throw new ScientificSourceContractViolation('AGRIS ODS projector refuses URLs/stream wrappers; local path required.');
        }
        if (! is_file($xmlPath) || ! is_readable($xmlPath)) {
            return ['status' => 'failed', 'records' => 0, 'license' => null, 'failure' => 'file_unreadable', 'path' => $jsonlPath];
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->load($xmlPath, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return ['status' => 'failed', 'records' => 0, 'license' => null, 'failure' => 'malformed_xml', 'path' => $jsonlPath];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $xpath->registerNamespace('dct', 'http://purl.org/dc/terms/');
        $xpath->registerNamespace('dctypes', 'http://purl.org/dc/dcmitype/');

        $license = $this->firstText($xpath, '//dc:license|//dct:license');
        $nodes = $xpath->query('//dctypes:BibliographicResource');
        if ($nodes === false) {
            return ['status' => 'failed', 'records' => 0, 'license' => $license, 'failure' => 'xpath_failed', 'path' => $jsonlPath];
        }

        $dir = dirname($jsonlPath);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return ['status' => 'failed', 'records' => 0, 'license' => $license, 'failure' => 'output_dir', 'path' => $jsonlPath];
        }

        $out = fopen($jsonlPath, 'wb');
        if ($out === false) {
            return ['status' => 'failed', 'records' => 0, 'license' => $license, 'failure' => 'output_unwritable', 'path' => $jsonlPath];
        }

        $count = 0;
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $id = trim($node->getAttribute('xml:id'));
            if ($id === '') {
                $id = trim((string) $node->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'id'));
            }
            $title = $this->childText($xpath, $node, './dc:title');
            if ($id === '' || $title === null || $title === '') {
                continue;
            }
            $identifiers = $this->childTexts($xpath, $node, './dc:identifier');
            $subjects = $this->childTexts($xpath, $node, './dc:subject|./dct:subject');
            $creators = $this->childTexts($xpath, $node, './dc:creator');
            $date = $this->childText($xpath, $node, './dc:date|./dct:issued|./dct:date');
            $url = null;
            foreach ($identifiers as $identifier) {
                if (preg_match('#^https?://#i', $identifier) === 1) {
                    $url = $identifier;
                    break;
                }
            }
            if ($url === null) {
                $url = 'https://agris.fao.org/search/en/providers/'.rawurlencode($id);
            }
            $doi = null;
            foreach ($identifiers as $identifier) {
                if (preg_match('#(10\.\d{4,9}/\S+)#', $identifier, $m) === 1) {
                    $doi = $m[1];
                    break;
                }
            }

            $record = [
                'id' => $id,
                'title' => $title,
                'abstract' => $this->childText($xpath, $node, './dct:abstract|./dc:description'),
                'doi' => $doi,
                'url' => $url,
                'published' => $date,
                'authors' => $creators,
                'subjects' => $subjects,
                'licence' => $license,
                'source_table' => 'agris_ods_provider_xml',
                'projector' => self::CONTRACT_VERSION,
            ];
            fwrite($out, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            if (++$count >= $maxRecords) {
                break;
            }
        }
        fclose($out);

        return [
            'status' => $count > 0 ? 'completed' : 'empty',
            'records' => $count,
            'license' => $license,
            'failure' => $count > 0 ? null : 'no_bibliographic_resources',
            'path' => $jsonlPath,
        ];
    }

    private function firstText(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $text = trim((string) $nodes->item(0)?->textContent);

        return $text !== '' ? $text : null;
    }

    private function childText(DOMXPath $xpath, DOMElement $context, string $query): ?string
    {
        $nodes = $xpath->query($query, $context);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $text = trim((string) $nodes->item(0)?->textContent);

        return $text !== '' ? $text : null;
    }

    /** @return list<string> */
    private function childTexts(DOMXPath $xpath, DOMElement $context, string $query): array
    {
        $nodes = $xpath->query($query, $context);
        if ($nodes === false) {
            return [];
        }
        $values = [];
        foreach ($nodes as $node) {
            $text = trim((string) $node->textContent);
            if ($text !== '') {
                $values[] = $text;
            }
        }

        return $values;
    }
}
