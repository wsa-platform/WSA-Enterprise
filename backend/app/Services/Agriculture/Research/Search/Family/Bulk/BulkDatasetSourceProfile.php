<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceLicensingDeclaration;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;

/**
 * Profile of one source published as a bulk or static dataset file (ADR-023 §8.40, increment R6).
 *
 * The file is read from local storage only; obtaining it is outside this profile. JSON Lines
 * fields are dot paths inside one record; CSV fields are header column names. Every limit is
 * bounded so an oversized or hostile file cannot exhaust memory.
 */
final readonly class BulkDatasetSourceProfile implements ScientificSourceFamilyProfile
{
    public const FORMAT_JSONL = 'jsonl';

    public const FORMAT_CSV = 'csv';

    public const FIELDS = ['identifier', 'title', 'abstract', 'doi', 'url', 'published', 'year', 'authors', 'journal', 'withdrawn'];

    public const REQUIRED_FIELDS = ['identifier', 'title'];

    public const MAX_RECORDS_CEILING = 1_000_000;

    public const MAX_LINE_BYTES_CEILING = 1_048_576;

    public ScientificSourceLicensingDeclaration $licensing;

    /**
     * @param  array<string, string>  $fieldPaths  keyed by FIELDS; identifier and title are required
     * @param  string|null  $authorSeparator  CSV only: separator inside the authors column
     */
    public function __construct(
        public string $sourceKey,
        public string $displayName,
        public ScientificSourceAccessFamily $family,
        public string $format,
        public array $fieldPaths,
        public ?string $authorSeparator = null,
        ?ScientificSourceLicensingDeclaration $licensing = null,
        public int $maxRecords = 100_000,
        public int $maxLineBytes = 65_536,
    ) {
        $this->licensing = $licensing ?? ScientificSourceLicensingDeclaration::unresolved();

        if (preg_match('/^[a-z][a-z0-9_]*$/', $sourceKey) !== 1) {
            throw new ScientificSourceContractViolation("Profile sourceKey must be lower-case snake_case; got '{$sourceKey}'.");
        }
        if (trim($displayName) === '') {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' requires a display name.");
        }
        if (! in_array($family, [ScientificSourceAccessFamily::BULK_DATASET, ScientificSourceAccessFamily::STATIC_DATASET], true)) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' must belong to the bulk or static dataset family.");
        }
        if (! in_array($format, [self::FORMAT_JSONL, self::FORMAT_CSV], true)) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' format must be jsonl or csv; got '{$format}'.");
        }
        $pathPattern = $format === self::FORMAT_JSONL ? '/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/' : '/^[^\x00-\x1F\x7F]{1,128}$/';
        foreach ($fieldPaths as $field => $path) {
            if (! in_array($field, self::FIELDS, true)) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' maps unknown field '{$field}'.");
            }
            if (! is_string($path) || trim($path) === '' || preg_match($pathPattern, $path) !== 1) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' field '{$field}' needs a valid {$format} path.");
            }
        }
        foreach (self::REQUIRED_FIELDS as $field) {
            if (! isset($fieldPaths[$field])) {
                throw new ScientificSourceContractViolation("Profile '{$sourceKey}' must map the '{$field}' field.");
            }
        }
        if ($authorSeparator !== null && ($format !== self::FORMAT_CSV || $authorSeparator === '')) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' author separator applies to CSV only and must be non-empty.");
        }
        if ($maxRecords < 1 || $maxRecords > self::MAX_RECORDS_CEILING) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' maxRecords must be between 1 and ".self::MAX_RECORDS_CEILING.'.');
        }
        if ($maxLineBytes < 64 || $maxLineBytes > self::MAX_LINE_BYTES_CEILING) {
            throw new ScientificSourceContractViolation("Profile '{$sourceKey}' maxLineBytes must be between 64 and ".self::MAX_LINE_BYTES_CEILING.'.');
        }
    }

    public function sourceKey(): string
    {
        return $this->sourceKey;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function accessFamily(): ScientificSourceAccessFamily
    {
        return $this->family;
    }

    public function licensing(): ScientificSourceLicensingDeclaration
    {
        return $this->licensing;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'display_name' => $this->displayName,
            'access_family' => $this->family->value,
            'format' => $this->format,
            'field_paths' => $this->fieldPaths,
            'author_separator' => $this->authorSeparator,
            'max_records' => $this->maxRecords,
            'max_line_bytes' => $this->maxLineBytes,
            'licensing' => $this->licensing->toArray(),
        ];
    }
}
