<?php

namespace App\Services\Architecture\MissionArtifactArchive;

/**
 * Archives ADR-023 as an original file and extracts fenced ```text``` / ```markdown``` blocks
 * as explicit *excerpts* (never claimed to be standalone originals).
 */
final class AdrEmbeddedExcerptImporter
{
    public function __construct(
        private readonly FilesystemMissionArtifactArchive $archive,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function importAdr(string $adrPath, int $maxExcerpts = 40): array
    {
        $report = [
            'schema_id' => 'wsa.adr_embedded_excerpt_import.v1',
            'adr_path' => $adrPath,
            'adr_archive_id' => null,
            'excerpts_found' => 0,
            'excerpts_archived' => 0,
            'excerpts_unchanged' => 0,
            'excerpts_skipped' => 0,
            'items' => [],
        ];
        if (! is_file($adrPath)) {
            $report['status'] = 'adr_missing';

            return $report;
        }

        $sha = hash_file('sha256', $adrPath) ?: '';
        $adrIngest = $this->archive->findByOriginalPath($adrPath);
        if ($adrIngest === null) {
            $adrIngestResult = $this->archive->ingestFile(
                $adrPath,
                'adr:ADR-023|sha256:'.$sha,
                'adr_original_import',
                'ADR-023',
                null,
                [],
                'ADR-023-scientific-source-expansion.md',
            );
            $report['adr_archive_id'] = $adrIngestResult['archive_id'];
            $report['adr_status'] = $adrIngestResult['status'];
            $adrId = $adrIngestResult['archive_id'];
        } else {
            $report['adr_archive_id'] = $adrIngest->archiveId;
            $report['adr_status'] = 'unchanged';
            $adrId = $adrIngest->archiveId;
        }

        $raw = file_get_contents($adrPath);
        if ($raw === false || $adrId === null) {
            $report['status'] = 'failed_read';

            return $report;
        }

        if (preg_match_all('/```(?:text|markdown)?\r?\n(.*?)```/s', $raw, $matches, PREG_OFFSET_CAPTURE) !== false) {
            $blocks = $matches[1] ?? [];
            $report['excerpts_found'] = count($blocks);
            $taken = 0;
            foreach ($blocks as $idx => $block) {
                $text = (string) ($block[0] ?? '');
                $offset = (int) ($block[1] ?? 0);
                if (strlen($text) < 200) {
                    $report['excerpts_skipped']++;
                    continue;
                }
                // Prefer prompt/report-like blocks
                $looksPrompt = (bool) preg_match('/\b(PROMPT|OBJECTIVE|WSA-Enterprise|GO-\d|STEP-\d|MISSION)\b/i', $text);
                if (! $looksPrompt) {
                    $report['excerpts_skipped']++;
                    continue;
                }
                if ($taken >= $maxExcerpts) {
                    break;
                }
                $taken++;
                $line = substr_count(substr($raw, 0, $offset), "\n") + 1;
                $excerptSha = hash('sha256', $text);
                $title = 'ADR-023 excerpt L'.$line.' (not a standalone original)';
                $ingestion = 'adr023:excerpt:L'.$line.'|sha256:'.$excerptSha;
                $result = $this->archive->ingestBytes(
                    $text,
                    $ingestion,
                    $title,
                    'adr_embedded_excerpt',
                    'text',
                    'adr_excerpt_extraction',
                    $adrPath.'#L'.$line,
                    'ADR-023',
                    null,
                    [['type' => 'excerpt_of', 'target_archive_id' => $adrId]],
                    [
                        'is_excerpt' => true,
                        'is_full_original' => false,
                        'source_line' => $line,
                        'containing_adr_archive_id' => $adrId,
                    ],
                );
                $report['items'][] = [
                    'line' => $line,
                    'status' => $result['status'],
                    'archive_id' => $result['archive_id'] ?? null,
                    'bytes' => strlen($text),
                ];
                if ($result['status'] === 'archived') {
                    $report['excerpts_archived']++;
                    if ($adrId !== null && isset($result['archive_id'])) {
                        $this->archive->link((string) $result['archive_id'], 'excerpt_of', $adrId);
                    }
                } elseif ($result['status'] === 'unchanged') {
                    $report['excerpts_unchanged']++;
                }
            }
        }

        $report['status'] = 'completed';

        return $report;
    }
}
