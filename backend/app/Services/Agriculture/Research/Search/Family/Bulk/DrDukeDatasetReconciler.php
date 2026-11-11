<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

/**
 * Quantitative reconciliation of Dr. Duke CSV relationships (streaming / bounded memory).
 * Does not invent joins; reports match quality for documented columns only.
 */
final class DrDukeDatasetReconciler
{
    public const CONTRACT_VERSION = 'dr_duke_dataset_reconciler.v1';

    /**
     * @param  array<string, string>  $csvPaths
     * @return array<string, mixed>
     */
    public function reconcile(array $csvPaths): array
    {
        $fnfKeys = $this->loadKeySet($csvPaths['FNFTAX'] ?? null, 'FNFNUM');
        $chemNames = $this->loadKeySet($csvPaths['CHEMICALS'] ?? null, 'CHEM');
        $chemIds = $this->loadKeySet($csvPaths['CHEMICALS'] ?? null, 'CHEMID');
        $taxonNames = $this->loadNameCounts($csvPaths['FNFTAX'] ?? null, 'TAXON');

        $farmacyFnf = $this->countFk($csvPaths['FARMACY'] ?? null, 'FNFNUM', $fnfKeys, 50_000);
        $farmacyChem = $this->countFk($csvPaths['FARMACY'] ?? null, 'CHEM', $chemNames, 50_000);
        $farmacyNewFnf = $this->countFk($csvPaths['FARMACY_NEW'] ?? null, 'FNFNUM', $fnfKeys, 50_000);
        $farmacyNewChemId = $this->countFk($csvPaths['FARMACY_NEW'] ?? null, 'CHEMID', $chemIds, 50_000);
        $commonFnf = $this->countFk($csvPaths['COMMON_NAMES'] ?? null, 'FNFNUM', $fnfKeys, 50_000);
        $ethno = $this->countNameMatches($csvPaths['ETHNOBOT'] ?? null, 'TAXON', $taxonNames, 50_000);

        $farmacyHeaders = $this->headers($csvPaths['FARMACY'] ?? null);
        $farmacyNewHeaders = $this->headers($csvPaths['FARMACY_NEW'] ?? null);
        $shared = array_values(array_intersect($farmacyHeaders, $farmacyNewHeaders));
        $onlyOld = array_values(array_diff($farmacyHeaders, $farmacyNewHeaders));
        $onlyNew = array_values(array_diff($farmacyNewHeaders, $farmacyHeaders));

        $rowCounts = [];
        foreach ($csvPaths as $name => $path) {
            $rowCounts[$name] = $this->countDataRows($path);
        }

        return [
            'schema_id' => 'wsa.dr_duke_reconciliation.v1',
            'contract_version' => self::CONTRACT_VERSION,
            'tables_present' => array_keys($csvPaths),
            'row_counts' => $rowCounts,
            'fnftax_keys' => count($fnfKeys),
            'chemicals_by_chem' => count($chemNames),
            'chemicals_by_chemid' => count($chemIds),
            'samples_read_cap' => 50_000,
            'relationships' => [
                'FARMACY.FNFNUM->FNFTAX' => $farmacyFnf,
                'FARMACY.CHEM->CHEMICALS.CHEM' => $farmacyChem,
                'FARMACY_NEW.FNFNUM->FNFTAX' => $farmacyNewFnf,
                'FARMACY_NEW.CHEMID->CHEMICALS.CHEMID' => $farmacyNewChemId,
                'ETHNOBOT.TAXON<->FNFTAX.TAXON_name' => $ethno,
                'COMMON_NAMES.FNFNUM->FNFTAX' => $commonFnf,
            ],
            'farmacy_vs_farmacy_new' => [
                'interpretation' => 'Distinct overlapping schemas: FARMACY keys chemicals by CHEM name; FARMACY_NEW prefers CHEMID and adds CHEMICLASS/PLCO/quantity fields. Do not blind-merge.',
                'shared_headers' => $shared,
                'only_farmacy' => $onlyOld,
                'only_farmacy_new' => $onlyNew,
            ],
            'projection_policy' => [
                'fully_projected_for_search' => ['FNFTAX', 'CHEMICALS', 'ETHNOBOT'],
                'relationship_validated_not_row_projected' => ['FARMACY', 'FARMACY_NEW', 'COMMON_NAMES'],
                'inspected_not_projected' => [
                    'ACTIVITIES', 'AGGREGAC', 'ASSAY', 'CHEM_MEANS', 'CODES',
                    'DOSAGE', 'PARTS', 'REFERENCES', 'SUPERACT', 'YIELDS',
                ],
            ],
        ];
    }

    /** @return array<string, true> */
    private function loadKeySet(?string $path, string $keyField): array
    {
        $set = [];
        foreach ($this->iterateAssoc($path) as $row) {
            $k = trim((string) ($row[$keyField] ?? ''));
            if ($k !== '') {
                $set[$k] = true;
            }
        }

        return $set;
    }

    /** @return array<string, int> */
    private function loadNameCounts(?string $path, string $field): array
    {
        $counts = [];
        foreach ($this->iterateAssoc($path) as $row) {
            $n = strtolower(trim((string) ($row[$field] ?? '')));
            if ($n !== '') {
                $counts[$n] = ($counts[$n] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, true>  $keys
     * @return array{matched: int, unmatched: int, sampled: int}
     */
    private function countFk(?string $path, string $field, array $keys, int $maxRows): array
    {
        $matched = 0;
        $unmatched = 0;
        $sampled = 0;
        foreach ($this->iterateAssoc($path) as $row) {
            $v = trim((string) ($row[$field] ?? ''));
            if ($v === '') {
                continue;
            }
            $sampled++;
            if (isset($keys[$v])) {
                $matched++;
            } else {
                $unmatched++;
            }
            if ($sampled >= $maxRows) {
                break;
            }
        }

        return ['matched' => $matched, 'unmatched' => $unmatched, 'sampled' => $sampled];
    }

    /**
     * @param  array<string, int>  $nameCounts
     * @return array{matched_name: int, unmatched_name: int, ambiguous_name: int, sampled: int}
     */
    private function countNameMatches(?string $path, string $field, array $nameCounts, int $maxRows): array
    {
        $matched = 0;
        $unmatched = 0;
        $ambiguous = 0;
        $sampled = 0;
        foreach ($this->iterateAssoc($path) as $row) {
            $n = strtolower(trim((string) ($row[$field] ?? '')));
            if ($n === '') {
                continue;
            }
            $sampled++;
            $hits = $nameCounts[$n] ?? 0;
            if ($hits === 1) {
                $matched++;
            } elseif ($hits > 1) {
                $ambiguous++;
            } else {
                $unmatched++;
            }
            if ($sampled >= $maxRows) {
                break;
            }
        }

        return [
            'matched_name' => $matched,
            'unmatched_name' => $unmatched,
            'ambiguous_name' => $ambiguous,
            'sampled' => $sampled,
        ];
    }

    /** @return list<string> */
    private function headers(?string $path): array
    {
        if ($path === null || ! is_file($path)) {
            return [];
        }
        $h = fopen($path, 'rb');
        if ($h === false) {
            return [];
        }
        $line = fgets($h);
        fclose($h);
        if ($line === false) {
            return [];
        }
        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;

        return array_map('strval', str_getcsv(rtrim($line, "\r\n"), ',', '"', ''));
    }

    private function countDataRows(string $path): int
    {
        $h = fopen($path, 'rb');
        if ($h === false) {
            return 0;
        }
        $n = 0;
        fgets($h); // header
        while (fgets($h) !== false) {
            $n++;
        }
        fclose($h);

        return $n;
    }

    /**
     * @return \Generator<int, array<string, string>>
     */
    private function iterateAssoc(?string $path): \Generator
    {
        if ($path === null || ! is_file($path) || ! is_readable($path)) {
            return;
        }
        $h = fopen($path, 'rb');
        if ($h === false) {
            return;
        }
        $headerLine = fgets($h);
        if ($headerLine === false) {
            fclose($h);

            return;
        }
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine) ?? $headerLine;
        $header = str_getcsv(rtrim($headerLine, "\r\n"), ',', '"', '');
        while (($cols = fgetcsv($h, 0, ',', '"', '')) !== false) {
            if (count($cols) < count($header)) {
                continue;
            }
            $assoc = [];
            foreach ($header as $i => $name) {
                $assoc[(string) $name] = (string) ($cols[$i] ?? '');
            }
            yield $assoc;
        }
        fclose($h);
    }
}
