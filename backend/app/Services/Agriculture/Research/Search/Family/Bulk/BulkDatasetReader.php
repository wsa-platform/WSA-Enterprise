<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyRecord;

/**
 * Reads a bulk or static dataset file that is already on local storage (ADR-023 §8.40).
 *
 * Never downloads: a path with a URL scheme or stream wrapper is refused. Lines are read one
 * at a time within the profile's byte and record limits; the file content is untrusted data and
 * a malformed line is counted, never executed or followed.
 */
final class BulkDatasetReader
{
    public const CONTRACT_VERSION = 'scientific_source_bulk_reader.v1';

    private const WITHDRAWN_VALUES = ['1', 'true', 'yes', 'withdrawn', 'deleted'];

    public function read(BulkDatasetSourceProfile $profile, string $path, ?string $retrievedAt = null): BulkDatasetSnapshot
    {
        if (preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $path) === 1 || str_contains($path, "\0")) {
            throw new ScientificSourceContractViolation('A dataset is read from a local file path; URLs and stream wrappers are refused.');
        }
        if (! is_file($path) || ! is_readable($path)) {
            return new BulkDatasetSnapshot($profile->sourceKey, BulkDatasetSnapshot::STATUS_FAILED, failure: 'file_unreadable');
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return new BulkDatasetSnapshot($profile->sourceKey, BulkDatasetSnapshot::STATUS_FAILED, failure: 'file_unreadable');
        }

        $retrievedAt ??= now()->utc()->toIso8601String();
        $hash = hash_init('sha256');
        $records = [];
        $seen = [];
        $withdrawn = [];
        $linesRead = 0;
        $recordLines = 0;
        $unusable = 0;
        $malformed = 0;
        $duplicates = 0;
        $failure = null;
        $header = null;

        try {
            while (($line = fgets($handle, $profile->maxLineBytes + 1)) !== false) {
                hash_update($hash, $line);
                if (strlen($line) === $profile->maxLineBytes && ! str_ends_with($line, "\n") && ! feof($handle)) {
                    $failure = 'line_too_long';

                    break;
                }
                $linesRead++;
                if ($linesRead === 1) {
                    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
                }
                $line = rtrim($line, "\r\n");
                if (trim($line) === '') {
                    continue;
                }
                if (! mb_check_encoding($line, 'UTF-8')) {
                    $malformed++;

                    continue;
                }
                if ($profile->format === BulkDatasetSourceProfile::FORMAT_CSV && $header === null) {
                    $header = str_getcsv($line, ',', '"', '');
                    if (in_array(null, $header, true) || in_array('', $header, true) || count($header) !== count(array_unique($header))) {
                        $failure = 'malformed_header';

                        break;
                    }

                    continue;
                }
                if (++$recordLines > $profile->maxRecords) {
                    $failure = 'max_records_exceeded';

                    break;
                }
                $row = $this->row($profile, $line, $header);
                if ($row === null) {
                    $malformed++;

                    continue;
                }
                $value = fn (string $field): mixed => $this->field($profile, $row, $field);
                $identifier = ScientificSourceFamilyRecord::scalar($value('identifier'));
                if ($identifier !== null) {
                    $identifier = trim($identifier);
                }
                if ($identifier === null || $identifier === '') {
                    $unusable++;

                    continue;
                }
                if (isset($seen[$identifier])) {
                    $duplicates++;

                    continue;
                }
                $seen[$identifier] = true;
                $flag = $value('withdrawn');
                if ($flag === true || in_array(strtolower(trim((string) (is_scalar($flag) ? $flag : ''))), self::WITHDRAWN_VALUES, true)) {
                    $withdrawn[] = $identifier;

                    continue;
                }
                $year = $value('year');
                $result = ScientificSourceFamilyRecord::toResult(
                    profile: $profile,
                    rawTitle: ScientificSourceFamilyRecord::scalar($value('title')),
                    originalIdentifier: $identifier,
                    originalIdentifierField: $profile->fieldPaths['identifier'],
                    rawDoi: ScientificSourceFamilyRecord::scalar($value('doi')),
                    rawUrl: ScientificSourceFamilyRecord::scalar($value('url')),
                    rawAbstract: ScientificSourceFamilyRecord::scalar($value('abstract')),
                    publishedAt: ScientificSourceFamilyRecord::scalar($value('published')),
                    year: is_int($year) || (is_string($year) && preg_match('/^\d{4}$/', trim($year)) === 1) ? (int) $year : null,
                    authors: $this->authors($profile, $value('authors')),
                    journal: ScientificSourceFamilyRecord::scalar($value('journal')),
                    raw: $row,
                    retrievedAt: $retrievedAt,
                    adapterClass: self::class,
                    contractVersion: self::CONTRACT_VERSION,
                );
                if ($result === null) {
                    $unusable++;

                    continue;
                }
                $records[] = $result;
            }
        } finally {
            fclose($handle);
        }

        if ($failure === null && $malformed > 0) {
            $failure = 'malformed_lines';
        }
        if ($failure === null && $profile->format === BulkDatasetSourceProfile::FORMAT_CSV && $header === null) {
            $failure = 'missing_header';
        }

        return new BulkDatasetSnapshot(
            sourceKey: $profile->sourceKey,
            status: $failure === null ? BulkDatasetSnapshot::STATUS_COMPLETE : BulkDatasetSnapshot::STATUS_INCOMPLETE,
            records: $records,
            withdrawnIdentifiers: $withdrawn,
            linesRead: $linesRead,
            unusableRecords: $unusable,
            malformedLines: $malformed,
            duplicateIdentifiers: $duplicates,
            failure: $failure,
            contentSha256: $failure === null ? hash_final($hash) : null,
        );
    }

    /**
     * @param  list<string|null>|null  $header
     * @return array<string, mixed>|null
     */
    private function row(BulkDatasetSourceProfile $profile, string $line, ?array $header): ?array
    {
        if ($profile->format === BulkDatasetSourceProfile::FORMAT_JSONL) {
            try {
                $row = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }

            return is_array($row) && ! array_is_list($row) ? $row : null;
        }
        $cells = str_getcsv($line, ',', '"', '');
        if ($header === null || count($cells) !== count($header)) {
            return null;
        }

        return array_combine($header, $cells);
    }

    /** @param  array<string, mixed>  $row */
    private function field(BulkDatasetSourceProfile $profile, array $row, string $field): mixed
    {
        $path = $profile->fieldPaths[$field] ?? null;
        if ($path === null) {
            return null;
        }

        return $profile->format === BulkDatasetSourceProfile::FORMAT_JSONL ? data_get($row, $path) : ($row[$path] ?? null);
    }

    /** @return list<string> */
    private function authors(BulkDatasetSourceProfile $profile, mixed $value): array
    {
        if (is_string($value) && $profile->authorSeparator !== null) {
            $value = explode($profile->authorSeparator, $value);
        }
        if (! is_array($value)) {
            $name = ScientificSourceFamilyRecord::scalar($value);

            return $name !== null ? [$name] : [];
        }
        $authors = [];
        foreach ($value as $entry) {
            $name = is_array($entry) && ! array_is_list($entry)
                ? ScientificSourceFamilyRecord::scalar($entry['name'] ?? null)
                : ScientificSourceFamilyRecord::scalar($entry);
            if ($name !== null) {
                $authors[] = $name;
            }
        }

        return $authors;
    }
}
