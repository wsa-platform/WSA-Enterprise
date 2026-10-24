<?php

namespace App\Services\Architecture\MissionArtifactArchive;

use PDO;
use RuntimeException;

/**
 * Durable mission-artifact archive: SQLite metadata + content-addressed blob files.
 * Architecturally distinct from scientific LocalIndex / Capability Store.
 */
final class FilesystemMissionArtifactArchive
{
    public const CONTRACT_VERSION = 'mission_artifact_archive.v1';

    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $sqlitePath,
        private readonly string $blobRoot,
        private readonly MissionArtifactSecretFilter $secrets = new MissionArtifactSecretFilter(),
        private readonly MissionArtifactClassifier $classifier = new MissionArtifactClassifier(),
    ) {}

    public function ensureSchema(): void
    {
        $dir = dirname($this->sqlitePath);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('Cannot create mission artifact DB directory.');
        }
        if (! is_dir($this->blobRoot) && ! mkdir($this->blobRoot, 0775, true) && ! is_dir($this->blobRoot)) {
            throw new RuntimeException('Cannot create mission artifact blob root.');
        }
        $pdo = $this->pdo();
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS mission_artifacts (
  archive_id TEXT PRIMARY KEY,
  ingestion_identity TEXT NOT NULL UNIQUE,
  artifact_type TEXT NOT NULL,
  format TEXT NOT NULL,
  title TEXT NOT NULL,
  original_path TEXT,
  mission_id TEXT,
  related_seat TEXT,
  related_source_key TEXT,
  content_sha256 TEXT NOT NULL,
  byte_length INTEGER NOT NULL,
  capture_method TEXT NOT NULL,
  processing_status TEXT NOT NULL,
  supersedes_archive_id TEXT,
  is_superseded INTEGER NOT NULL DEFAULT 0,
  extracted_metadata_json TEXT NOT NULL,
  blob_relative_path TEXT NOT NULL,
  search_text TEXT,
  created_at TEXT NOT NULL
);
SQL);
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS mission_artifact_relationships (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  from_archive_id TEXT NOT NULL,
  relationship_type TEXT NOT NULL,
  to_archive_id TEXT NOT NULL,
  UNIQUE(from_archive_id, relationship_type, to_archive_id)
);
SQL);
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ma_mission ON mission_artifacts(mission_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ma_type ON mission_artifacts(artifact_type)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ma_sha ON mission_artifacts(content_sha256)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ma_search ON mission_artifacts(search_text)');
    }

    /**
     * Import a local file by exact bytes. Idempotent on ingestion_identity.
     *
     * @param  list<array{type: string, target_archive_id: string}>  $relationships
     * @return array{status: string, archive_id: ?string, reason?: string, record?: MissionArtifactRecord}
     */
    public function ingestFile(
        string $absolutePath,
        string $ingestionIdentity,
        string $captureMethod = 'controlled_import',
        ?string $missionId = null,
        ?string $supersedesArchiveId = null,
        array $relationships = [],
        ?string $relativeHint = null,
    ): array {
        $this->ensureSchema();
        if ($this->secrets->isExcluded($absolutePath)) {
            return ['status' => 'excluded_secret', 'archive_id' => null, 'reason' => 'secret_filter'];
        }
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return ['status' => 'failed', 'archive_id' => null, 'reason' => 'unreadable'];
        }

        $existing = $this->findByIngestionIdentity($ingestionIdentity);
        if ($existing !== null) {
            return ['status' => 'unchanged', 'archive_id' => $existing->archiveId, 'record' => $existing];
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false) {
            return ['status' => 'failed', 'archive_id' => null, 'reason' => 'read_failed'];
        }

        return $this->commitBytes(
            $bytes,
            $ingestionIdentity,
            $absolutePath,
            $captureMethod,
            $missionId,
            $supersedesArchiveId,
            $relationships,
            $relativeHint ?? $absolutePath,
        );
    }

    /**
     * @param  list<array{type: string, target_archive_id: string}>  $relationships
     * @return array{status: string, archive_id: ?string, reason?: string, record?: MissionArtifactRecord}
     */
    public function ingestBytes(
        string $bytes,
        string $ingestionIdentity,
        string $title,
        string $artifactType,
        string $format,
        string $captureMethod = 'programmatic',
        ?string $originalPath = null,
        ?string $missionId = null,
        ?string $supersedesArchiveId = null,
        array $relationships = [],
        array $extraMetadata = [],
    ): array {
        $this->ensureSchema();
        $existing = $this->findByIngestionIdentity($ingestionIdentity);
        if ($existing !== null) {
            return ['status' => 'unchanged', 'archive_id' => $existing->archiveId, 'record' => $existing];
        }

        return $this->commitBytes(
            $bytes,
            $ingestionIdentity,
            $originalPath,
            $captureMethod,
            $missionId,
            $supersedesArchiveId,
            $relationships,
            $title,
            [
                'artifact_type' => $artifactType,
                'format' => $format,
                'title' => $title,
                'mission_id' => $missionId,
                'related_seat' => null,
                'related_source_key' => null,
                'aho_supported' => false,
            ],
            $extraMetadata,
        );
    }

    public function get(string $archiveId): ?MissionArtifactRecord
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare('SELECT * FROM mission_artifacts WHERE archive_id = ?');
        $stmt->execute([$archiveId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByIngestionIdentity(string $ingestionIdentity): ?MissionArtifactRecord
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare('SELECT * FROM mission_artifacts WHERE ingestion_identity = ?');
        $stmt->execute([$ingestionIdentity]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByOriginalPath(string $absolutePath): ?MissionArtifactRecord
    {
        $this->ensureSchema();
        $norm = str_replace('\\', '/', $absolutePath);
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM mission_artifacts WHERE replace(original_path, \'\\\', \'/\') = ? OR original_path = ? LIMIT 1'
        );
        $stmt->execute([$norm, $absolutePath]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $this->hydrate($row);
        }
        // Case-insensitive fallback for Windows paths already stored with mixed separators.
        $stmt = $this->pdo()->prepare('SELECT * FROM mission_artifacts WHERE original_path IS NOT NULL');
        $stmt->execute();
        $target = strtolower($norm);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $op = strtolower(str_replace('\\', '/', (string) $row['original_path']));
            if ($op === $target) {
                return $this->hydrate($row);
            }
        }

        return null;
    }

    /** @return list<MissionArtifactRecord> */
    public function listByMission(string $missionId): array
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare('SELECT * FROM mission_artifacts WHERE mission_id = ? ORDER BY created_at ASC, archive_id ASC');
        $stmt->execute([$missionId]);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    public function latestByType(string $missionId, string $artifactType): ?MissionArtifactRecord
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM mission_artifacts WHERE mission_id = ? AND artifact_type = ? AND is_superseded = 0 ORDER BY created_at DESC, archive_id DESC LIMIT 1'
        );
        $stmt->execute([$missionId, $artifactType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    /** @return list<MissionArtifactRecord> */
    public function search(string $query, int $limit = 20): array
    {
        $this->ensureSchema();
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_strtolower($query)).'%';
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM mission_artifacts WHERE lower(search_text) LIKE ? OR lower(title) LIKE ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $like);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /** @return array{ok: bool, expected: string, actual: ?string, tampered: bool} */
    public function verifyIntegrity(string $archiveId): array
    {
        $record = $this->get($archiveId);
        if ($record === null) {
            return ['ok' => false, 'expected' => '', 'actual' => null, 'tampered' => false];
        }
        $path = $this->containedBlobPath($record->blobRelativePath);
        if ($path === null || ! is_file($path)) {
            return ['ok' => false, 'expected' => $record->contentSha256, 'actual' => null, 'tampered' => true];
        }
        $actual = hash_file('sha256', $path) ?: null;

        return [
            'ok' => $actual === $record->contentSha256,
            'expected' => $record->contentSha256,
            'actual' => $actual,
            'tampered' => $actual !== $record->contentSha256,
        ];
    }

    public function readOriginalBytes(string $archiveId): ?string
    {
        $record = $this->get($archiveId);
        if ($record === null) {
            return null;
        }
        $path = $this->containedBlobPath($record->blobRelativePath);
        if ($path === null || ! is_file($path)) {
            return null;
        }
        $bytes = file_get_contents($path);

        return $bytes === false ? null : $bytes;
    }

    /**
     * Safe export under an allowlisted destination directory (no path traversal).
     *
     * @return array{status: string, path?: string, reason?: string}
     */
    public function exportOriginal(string $archiveId, string $destinationDir, string $fileName): array
    {
        $record = $this->get($archiveId);
        if ($record === null) {
            return ['status' => 'not_found'];
        }
        $realBase = realpath($destinationDir);
        if ($realBase === false || ! is_dir($realBase)) {
            return ['status' => 'failed', 'reason' => 'dest_missing'];
        }
        if (! $this->isSingleExportFileName($fileName)) {
            return ['status' => 'failed', 'reason' => 'unsafe_name'];
        }
        $target = $realBase.DIRECTORY_SEPARATOR.$fileName;
        if (is_link($target)) {
            return ['status' => 'failed', 'reason' => 'unsafe_name'];
        }
        $parent = realpath(dirname($target));
        if ($parent === false || $parent !== $realBase) {
            return ['status' => 'failed', 'reason' => 'unsafe_name'];
        }
        $bytes = $this->readOriginalBytes($archiveId);
        if ($bytes === null) {
            return ['status' => 'failed', 'reason' => 'blob_missing'];
        }
        if (file_put_contents($target, $bytes) === false) {
            return ['status' => 'failed', 'reason' => 'write_failed'];
        }

        return ['status' => 'exported', 'path' => $target];
    }

    public function markSuperseded(string $archiveId): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare('UPDATE mission_artifacts SET is_superseded = 1 WHERE archive_id = ?');
        $stmt->execute([$archiveId]);
    }

    public function link(string $fromArchiveId, string $relationshipType, string $toArchiveId): void
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare(
            'INSERT OR IGNORE INTO mission_artifact_relationships (from_archive_id, relationship_type, to_archive_id) VALUES (?,?,?)'
        );
        $stmt->execute([$fromArchiveId, $relationshipType, $toArchiveId]);
    }

    /** @return list<array{type: string, target_archive_id: string}> */
    public function relationshipsOf(string $archiveId): array
    {
        $this->ensureSchema();
        $stmt = $this->pdo()->prepare(
            'SELECT relationship_type, to_archive_id FROM mission_artifact_relationships WHERE from_archive_id = ?'
        );
        $stmt->execute([$archiveId]);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'type' => (string) $row['relationship_type'],
                'target_archive_id' => (string) $row['to_archive_id'],
            ];
        }

        return $out;
    }

    public function countAll(): int
    {
        $this->ensureSchema();

        return (int) $this->pdo()->query('SELECT COUNT(*) FROM mission_artifacts')->fetchColumn();
    }

    /**
     * Export accepts one file name. Directory separators and parent segments are rejected,
     * not stripped, so a caller cannot overwrite a different name inside the destination.
     */
    private function isSingleExportFileName(string $fileName): bool
    {
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return false;
        }
        if (str_contains($fileName, '/') || str_contains($fileName, '\\') || str_contains($fileName, "\0") || str_contains($fileName, ':')) {
            return false;
        }
        if (basename($fileName) !== $fileName) {
            return false;
        }
        if (str_ends_with($fileName, '.') || str_ends_with($fileName, ' ')) {
            return false;
        }

        $stem = strtolower((string) pathinfo($fileName, PATHINFO_FILENAME));
        $reserved = [
            'con', 'prn', 'aux', 'nul',
            'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9',
            'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9',
        ];

        return ! in_array($stem, $reserved, true);
    }

    /**
     * Blob paths stored by this archive are hash segments. A row that points outside
     * the blob root is not readable.
     */
    private function containedBlobPath(string $relative): ?string
    {
        $normalized = str_replace('\\', '/', $relative);
        if ($normalized === '' || str_contains($normalized, "\0") || str_starts_with($normalized, '/')) {
            return null;
        }
        if (preg_match('/^[A-Za-z]:/', $normalized) === 1) {
            return null;
        }

        $segments = explode('/', $normalized);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        $root = realpath($this->blobRoot);
        if ($root === false) {
            return null;
        }

        $absolute = $root.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);
        $rootNorm = strtolower(rtrim(str_replace('\\', '/', $root), '/'));
        $absoluteNorm = strtolower(str_replace('\\', '/', $absolute));
        if ($absoluteNorm !== $rootNorm && ! str_starts_with($absoluteNorm, $rootNorm.'/')) {
            return null;
        }

        return $absolute;
    }

    public function sqlitePath(): string
    {
        return $this->sqlitePath;
    }

    public function blobRoot(): string
    {
        return $this->blobRoot;
    }

    /**
     * @param  list<array{type: string, target_archive_id: string}>  $relationships
     * @param  array<string, mixed>|null  $classification
     * @param  array<string, mixed>  $extraMetadata
     * @return array{status: string, archive_id: ?string, reason?: string, record?: MissionArtifactRecord}
     */
    private function commitBytes(
        string $bytes,
        string $ingestionIdentity,
        ?string $originalPath,
        string $captureMethod,
        ?string $missionId,
        ?string $supersedesArchiveId,
        array $relationships,
        string $relativeHint,
        ?array $classification = null,
        array $extraMetadata = [],
    ): array {
        $sha = hash('sha256', $bytes);
        $archiveId = 'maa_'.substr($sha, 0, 16).'_'.substr(hash('sha256', $ingestionIdentity), 0, 12);
        $class = $classification ?? $this->classifier->classify((string) $originalPath, $relativeHint);
        if ($missionId !== null) {
            $class['mission_id'] = $missionId;
        }

        $blobRel = substr($sha, 0, 2).DIRECTORY_SEPARATOR.substr($sha, 2, 2).DIRECTORY_SEPARATOR.$sha.'.bin';
        $blobAbs = $this->blobRoot.DIRECTORY_SEPARATOR.$blobRel;
        $blobDir = dirname($blobAbs);
        if (! is_dir($blobDir) && ! @mkdir($blobDir, 0775, true) && ! is_dir($blobDir)) {
            return ['status' => 'failed', 'archive_id' => null, 'reason' => 'blob_dir'];
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            // Stage blob then metadata; incomplete ingest never marked completed.
            $tmp = $blobAbs.'.partial';
            if (file_put_contents($tmp, $bytes) === false) {
                throw new RuntimeException('blob_write');
            }
            if (! rename($tmp, $blobAbs) && ! is_file($blobAbs)) {
                @unlink($tmp);
                throw new RuntimeException('blob_rename');
            }

            $extracted = $this->extractMetadata($bytes, (string) $class['format'], $extraMetadata);
            $search = mb_strtolower(
                ($class['title'] ?? '').' '.($class['artifact_type'] ?? '').' '.($missionId ?? '').' '
                .($class['related_source_key'] ?? '').' '.substr($this->searchableSnippet($bytes, (string) $class['format']), 0, 4000)
            );

            $stmt = $pdo->prepare(<<<'SQL'
INSERT INTO mission_artifacts (
  archive_id, ingestion_identity, artifact_type, format, title, original_path, mission_id,
  related_seat, related_source_key, content_sha256, byte_length, capture_method, processing_status,
  supersedes_archive_id, is_superseded, extracted_metadata_json, blob_relative_path, search_text, created_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
SQL);
            $stmt->execute([
                $archiveId,
                $ingestionIdentity,
                (string) $class['artifact_type'],
                (string) $class['format'],
                (string) $class['title'],
                $originalPath,
                $class['mission_id'] ?? $missionId,
                $class['related_seat'] ?? null,
                $class['related_source_key'] ?? null,
                $sha,
                strlen($bytes),
                $captureMethod,
                'completed',
                $supersedesArchiveId,
                0,
                json_encode($extracted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                str_replace('\\', '/', $blobRel),
                $search,
                gmdate('c'),
            ]);

            if ($supersedesArchiveId !== null) {
                $pdo->prepare('UPDATE mission_artifacts SET is_superseded = 1 WHERE archive_id = ?')
                    ->execute([$supersedesArchiveId]);
                $pdo->prepare(
                    'INSERT OR IGNORE INTO mission_artifact_relationships (from_archive_id, relationship_type, to_archive_id) VALUES (?,?,?)'
                )->execute([$archiveId, 'supersedes', $supersedesArchiveId]);
            }
            foreach ($relationships as $rel) {
                $pdo->prepare(
                    'INSERT OR IGNORE INTO mission_artifact_relationships (from_archive_id, relationship_type, to_archive_id) VALUES (?,?,?)'
                )->execute([$archiveId, $rel['type'], $rel['target_archive_id']]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            @unlink($blobAbs.'.partial');

            return ['status' => 'failed', 'archive_id' => null, 'reason' => 'transaction:'.$e->getMessage()];
        }

        $record = $this->get($archiveId);

        return ['status' => 'archived', 'archive_id' => $archiveId, 'record' => $record];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function extractMetadata(string $bytes, string $format, array $extra): array
    {
        $meta = [
            'contract_version' => self::CONTRACT_VERSION,
            'format' => $format,
            'json_valid' => null,
            'schema_id' => null,
            'aho_extraction' => 'unsupported_no_schema_discovered',
        ];
        if ($format === 'json') {
            $decoded = json_decode($bytes, true);
            $meta['json_valid'] = json_last_error() === JSON_ERROR_NONE;
            if (is_array($decoded)) {
                foreach (['schema_id', 'mission_date', 'membership_revision', 'head', 'status'] as $k) {
                    if (isset($decoded[$k]) && (is_string($decoded[$k]) || is_int($decoded[$k]) || is_bool($decoded[$k]))) {
                        $meta[$k] = $decoded[$k];
                    }
                }
                if (isset($decoded['schema_id']) && is_string($decoded['schema_id'])) {
                    $meta['schema_id'] = $decoded['schema_id'];
                }
            } else {
                $meta['json_parse_error'] = json_last_error_msg();
            }
        }
        if ($format === 'aho') {
            $meta['aho_extraction'] = 'retained_original_only_schema_unknown';
        }

        return array_merge($meta, $extra);
    }

    private function searchableSnippet(string $bytes, string $format): string
    {
        if (in_array($format, ['json', 'markdown', 'text', 'xml', 'python'], true)) {
            return $bytes;
        }

        return '';
    }

    /** @param  array<string, mixed>  $row */
    private function hydrate(array $row): MissionArtifactRecord
    {
        $extracted = json_decode((string) $row['extracted_metadata_json'], true);
        if (! is_array($extracted)) {
            $extracted = [];
        }

        return new MissionArtifactRecord(
            archiveId: (string) $row['archive_id'],
            ingestionIdentity: (string) $row['ingestion_identity'],
            artifactType: (string) $row['artifact_type'],
            format: (string) $row['format'],
            title: (string) $row['title'],
            originalPath: $row['original_path'] !== null ? (string) $row['original_path'] : null,
            missionId: $row['mission_id'] !== null ? (string) $row['mission_id'] : null,
            relatedSeat: $row['related_seat'] !== null ? (string) $row['related_seat'] : null,
            relatedSourceKey: $row['related_source_key'] !== null ? (string) $row['related_source_key'] : null,
            contentSha256: (string) $row['content_sha256'],
            byteLength: (int) $row['byte_length'],
            captureMethod: (string) $row['capture_method'],
            processingStatus: (string) $row['processing_status'],
            supersedesArchiveId: $row['supersedes_archive_id'] !== null ? (string) $row['supersedes_archive_id'] : null,
            isSuperseded: (int) $row['is_superseded'] === 1,
            extractedMetadata: $extracted,
            relationships: $this->relationshipsOf((string) $row['archive_id']),
            createdAt: (string) $row['created_at'],
            blobRelativePath: (string) $row['blob_relative_path'],
        );
    }

    private function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }
        $this->pdo = new PDO('sqlite:'.$this->sqlitePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $this->pdo;
    }
}
