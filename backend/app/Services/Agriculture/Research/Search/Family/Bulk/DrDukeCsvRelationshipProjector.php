<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;

/**
 * Projects selected Dr. Duke CSV tables into LocalIndex JSONL using documented column keys only.
 *
 * Supported joins (real headers from Duke-Source-CSV.zip):
 * - FNFTAX.FNFNUM ↔ FARMACY.FNFNUM / COMMON_NAMES.FNFNUM
 * - CHEMICALS.CHEM ↔ FARMACY.CHEM / AGGREGAC.CHEM
 * - ETHNOBOT.TAXON ↔ FNFTAX.TAXON (name match only; not a numeric FK)
 *
 * FARMACY and FARMACY_NEW remain distinct tables (never merged by name similarity).
 * Does not invent efficacy from ethnobotanical ACTIVITY values.
 * Large association tables are streamed to avoid exhausting PHP memory.
 */
final class DrDukeCsvRelationshipProjector
{
    public const CONTRACT_VERSION = 'dr_duke_csv_relationship_projector.v2';

    /**
     * @param  array<string, string>  $csvPaths  keyed by logical table name
     * @return array{status: string, records: int, relationships: array<string, int>, table_counts: array<string, int>, failure: ?string, path: string}
     */
    public function projectSearchableJsonl(array $csvPaths, string $jsonlPath, int $maxRecords = 2_000): array
    {
        foreach ($csvPaths as $path) {
            if (preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $path) === 1) {
                throw new ScientificSourceContractViolation('Dr. Duke projector refuses URLs; local CSV paths required.');
            }
        }

        $fnftax = $this->loadMap($csvPaths['FNFTAX'] ?? null, 'FNFNUM');
        $chemicals = $this->loadMap($csvPaths['CHEMICALS'] ?? null, 'CHEM');

        $hasAny = $fnftax !== []
            || $chemicals !== []
            || $this->filePresent($csvPaths['ETHNOBOT'] ?? null)
            || $this->filePresent($csvPaths['FARMACY'] ?? null)
            || $this->filePresent($csvPaths['FARMACY_NEW'] ?? null);

        if (! $hasAny) {
            return [
                'status' => 'failed',
                'records' => 0,
                'relationships' => [],
                'table_counts' => [],
                'failure' => 'no_input_tables',
                'path' => $jsonlPath,
            ];
        }

        $dir = dirname($jsonlPath);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return [
                'status' => 'failed',
                'records' => 0,
                'relationships' => [],
                'table_counts' => [],
                'failure' => 'output_dir',
                'path' => $jsonlPath,
            ];
        }
        $out = fopen($jsonlPath, 'wb');
        if ($out === false) {
            return [
                'status' => 'failed',
                'records' => 0,
                'relationships' => [],
                'table_counts' => [],
                'failure' => 'output_unwritable',
                'path' => $jsonlPath,
            ];
        }

        $relationships = [
            'taxon_rows' => count($fnftax),
            'chemical_rows' => count($chemicals),
            'ethnobot_rows_read' => 0,
            'farmacy_rows_read' => 0,
            'farmacy_new_rows_read' => 0,
            'ethnobot_taxon_name_matches' => 0,
            'farmacy_fnfnum_matches' => 0,
            'farmacy_chem_matches' => 0,
            'farmacy_new_fnfnum_matches' => 0,
            'farmacy_new_chem_matches' => 0,
            'farmacy_farmacy_new_merged' => 0,
        ];
        $tableCounts = [
            'FNFTAX' => 0,
            'CHEMICALS' => 0,
            'ETHNOBOT' => 0,
            'FARMACY' => 0,
            'FARMACY_NEW' => 0,
        ];
        $count = 0;

        $buckets = 5;
        $base = (int) max(1, (int) floor($maxRecords / $buckets));
        $taxonBudget = $base;
        $chemBudget = $base;
        $ethnoBudget = $base;
        $farmacyBudget = $base;
        $farmacyNewBudget = max(1, $maxRecords - ($taxonBudget + $chemBudget + $ethnoBudget + $farmacyBudget));

        $taxonByName = [];
        foreach ($fnftax as $row) {
            $name = strtolower(trim((string) ($row['TAXON'] ?? '')));
            if ($name !== '') {
                $taxonByName[$name] = true;
            }
        }

        $writtenTaxa = 0;
        foreach ($fnftax as $id => $row) {
            if ($writtenTaxa >= $taxonBudget || $count >= $maxRecords) {
                break;
            }
            $taxon = trim((string) ($row['TAXON'] ?? ''));
            if ($taxon === '') {
                continue;
            }
            $this->writeJsonl($out, [
                'id' => 'fnftax:'.$id,
                'title' => $taxon,
                'abstract' => trim(($row['FAMILY'] ?? '').' '.($row['GENUS'] ?? '').' '.($row['SPECIES'] ?? '')),
                'url' => 'https://phytochem.nal.usda.gov/',
                'source_table' => 'FNFTAX',
                'source_key_field' => 'FNFNUM',
                'projector' => self::CONTRACT_VERSION,
            ]);
            $writtenTaxa++;
            $tableCounts['FNFTAX']++;
            $count++;
        }

        $writtenChem = 0;
        foreach ($chemicals as $chem => $row) {
            if ($writtenChem >= $chemBudget || $count >= $maxRecords) {
                break;
            }
            $title = trim((string) $chem);
            if ($title === '') {
                continue;
            }
            $this->writeJsonl($out, [
                'id' => 'chem:'.($row['CHEMID'] ?? $title),
                'title' => $title,
                'abstract' => 'Chemical record from Dr. Duke CHEMICALS.csv'
                    .(isset($row['CASNUM']) && $row['CASNUM'] !== '' ? '; CAS '.$row['CASNUM'] : ''),
                'url' => 'https://phytochem.nal.usda.gov/',
                'source_table' => 'CHEMICALS',
                'source_key_field' => 'CHEM',
                'projector' => self::CONTRACT_VERSION,
            ]);
            $writtenChem++;
            $tableCounts['CHEMICALS']++;
            $count++;
        }

        $writtenEthno = 0;
        foreach ($this->streamRows($csvPaths['ETHNOBOT'] ?? null) as $row) {
            $relationships['ethnobot_rows_read']++;
            $taxon = trim((string) ($row['TAXON'] ?? ''));
            $activity = trim((string) ($row['ACTIVITY'] ?? ''));
            $ethno = trim((string) ($row['ETHNO'] ?? ''));
            $name = strtolower($taxon);
            if ($name !== '' && isset($taxonByName[$name])) {
                $relationships['ethnobot_taxon_name_matches']++;
            }
            if ($writtenEthno >= $ethnoBudget || $count >= $maxRecords) {
                continue;
            }
            if ($taxon === '' || $activity === '' || $ethno === '') {
                continue;
            }
            $this->writeJsonl($out, [
                'id' => 'ethno:'.$ethno,
                'title' => $taxon.' — reported traditional use: '.$activity,
                'abstract' => 'Ethnobotanical report (not a clinical efficacy claim). Country: '
                    .trim((string) ($row['COUNTRY'] ?? '')).'; reference: '.trim((string) ($row['REFERENCE'] ?? '')),
                'url' => 'https://phytochem.nal.usda.gov/',
                'source_table' => 'ETHNOBOT',
                'source_key_field' => 'ETHNO',
                'projector' => self::CONTRACT_VERSION,
            ]);
            $writtenEthno++;
            $tableCounts['ETHNOBOT']++;
            $count++;
        }

        $writtenFarmacy = 0;
        $farmacyIdx = 0;
        foreach ($this->streamRows($csvPaths['FARMACY'] ?? null) as $row) {
            $relationships['farmacy_rows_read']++;
            $fnf = trim((string) ($row['FNFNUM'] ?? ''));
            $chem = trim((string) ($row['CHEM'] ?? ''));
            if ($fnf !== '' && isset($fnftax[$fnf])) {
                $relationships['farmacy_fnfnum_matches']++;
            }
            if ($chem !== '' && isset($chemicals[$chem])) {
                $relationships['farmacy_chem_matches']++;
            }
            if ($writtenFarmacy >= $farmacyBudget || $count >= $maxRecords) {
                $farmacyIdx++;
                continue;
            }
            if ($fnf === '' && $chem === '') {
                $farmacyIdx++;
                continue;
            }
            $taxonLabel = ($fnf !== '' && isset($fnftax[$fnf]))
                ? trim((string) ($fnftax[$fnf]['TAXON'] ?? ''))
                : '';
            $this->writeJsonl($out, [
                'id' => 'farmacy:'.$fnf.':'.$chem.':'.$farmacyIdx,
                'title' => trim(($taxonLabel !== '' ? $taxonLabel.' — ' : '').($chem !== '' ? $chem : 'FARMACY row')),
                'abstract' => 'Plant–chemical association from FARMACY (distinct from FARMACY_NEW).'
                    .' FNFNUM='.$fnf.'; CHEM='.$chem
                    .(isset($row['AMOUNT']) && $row['AMOUNT'] !== '' ? '; amount '.$row['AMOUNT'] : ''),
                'url' => 'https://phytochem.nal.usda.gov/',
                'source_table' => 'FARMACY',
                'source_key_field' => 'FNFNUM+CHEM',
                'projector' => self::CONTRACT_VERSION,
            ]);
            $writtenFarmacy++;
            $tableCounts['FARMACY']++;
            $count++;
            $farmacyIdx++;
        }

        $writtenFarmacyNew = 0;
        $farmacyNewIdx = 0;
        foreach ($this->streamRows($csvPaths['FARMACY_NEW'] ?? null) as $row) {
            $relationships['farmacy_new_rows_read']++;
            $fnf = trim((string) ($row['FNFNUM'] ?? ''));
            $chem = trim((string) ($row['CHEM'] ?? ''));
            if ($fnf !== '' && isset($fnftax[$fnf])) {
                $relationships['farmacy_new_fnfnum_matches']++;
            }
            if ($chem !== '' && isset($chemicals[$chem])) {
                $relationships['farmacy_new_chem_matches']++;
            }
            if ($writtenFarmacyNew >= $farmacyNewBudget || $count >= $maxRecords) {
                $farmacyNewIdx++;
                continue;
            }
            if ($fnf === '' && $chem === '') {
                $farmacyNewIdx++;
                continue;
            }
            $taxonLabel = ($fnf !== '' && isset($fnftax[$fnf]))
                ? trim((string) ($fnftax[$fnf]['TAXON'] ?? ''))
                : '';
            $this->writeJsonl($out, [
                'id' => 'farmacy_new:'.$fnf.':'.$chem.':'.$farmacyNewIdx,
                'title' => trim(($taxonLabel !== '' ? $taxonLabel.' — ' : '').($chem !== '' ? $chem : 'FARMACY_NEW row')),
                'abstract' => 'Plant–chemical association from FARMACY_NEW (distinct from FARMACY; not merged).'
                    .' FNFNUM='.$fnf.'; CHEM='.$chem
                    .(isset($row['AMOUNT']) && $row['AMOUNT'] !== '' ? '; amount '.$row['AMOUNT'] : ''),
                'url' => 'https://phytochem.nal.usda.gov/',
                'source_table' => 'FARMACY_NEW',
                'source_key_field' => 'FNFNUM+CHEM',
                'projector' => self::CONTRACT_VERSION,
            ]);
            $writtenFarmacyNew++;
            $tableCounts['FARMACY_NEW']++;
            $count++;
            $farmacyNewIdx++;
        }

        fclose($out);

        return [
            'status' => $count > 0 ? 'completed' : 'empty',
            'records' => $count,
            'relationships' => $relationships,
            'table_counts' => $tableCounts,
            'failure' => $count > 0 ? null : 'no_projectable_rows',
            'path' => $jsonlPath,
        ];
    }

    /** @param  resource  $out */
    private function writeJsonl($out, array $record): void
    {
        fwrite($out, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function filePresent(?string $path): bool
    {
        return $path !== null && is_file($path) && is_readable($path);
    }

    /** @return array<string, array<string, string>> */
    private function loadMap(?string $path, string $keyField): array
    {
        $map = [];
        foreach ($this->streamRows($path) as $row) {
            $key = trim((string) ($row[$keyField] ?? ''));
            if ($key !== '') {
                $map[$key] = $row;
            }
        }

        return $map;
    }

    /**
     * @return \Generator<int, array<string, string>>
     */
    private function streamRows(?string $path): \Generator
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
