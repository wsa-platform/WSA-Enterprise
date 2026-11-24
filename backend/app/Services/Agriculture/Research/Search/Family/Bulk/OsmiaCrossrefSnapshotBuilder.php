<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use Illuminate\Support\Facades\Http;

/**
 * Builds a bounded OSMIA (G3-05) JSONL snapshot from the free Crossref Works API.
 *
 * Crossref is used as a machine metadata gateway because OSMIA has no native OAI/API.
 * Only works whose Crossref license URL is CC BY 4.0 (non-NC) are retained.
 * No API key; polite User-Agent + mailto query per Crossref etiquette.
 */
final class OsmiaCrossrefSnapshotBuilder
{
    public const ISSN = '2727-3806';

    public const SOURCE_KEY = 'osmia';

    public const CROSSREF_JOURNAL_WORKS = 'https://api.crossref.org/journals/2727-3806/works';

    public const CC_BY_4 = 'creativecommons.org/licenses/by/4.0';

    /**
     * @return array{
     *     path: string,
     *     written: int,
     *     skipped_license: int,
     *     skipped_incomplete: int,
     *     total_reported: int|null,
     *     pages: int
     * }
     */
    public function writeJsonl(string $path, int $maxRecords = 125, int $rowsPerPage = 50): array
    {
        $maxRecords = max(1, min(500, $maxRecords));
        $rowsPerPage = max(1, min(100, $rowsPerPage));
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Unable to create snapshot directory.');
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open snapshot file for write.');
        }

        $written = 0;
        $skippedLicense = 0;
        $skippedIncomplete = 0;
        $pages = 0;
        $offset = 0;
        $totalReported = null;

        try {
            while ($written < $maxRecords) {
                $pageSize = min($rowsPerPage, $maxRecords - $written);
                $response = Http::timeout(45)
                    ->withHeaders([
                        'User-Agent' => 'WSA-Enterprise-ScientificOnboarding/1.0 (mailto:scientific-onboarding@wsa.local)',
                        'Accept' => 'application/json',
                    ])
                    ->get(self::CROSSREF_JOURNAL_WORKS, [
                        'rows' => $pageSize,
                        'offset' => $offset,
                        'mailto' => 'scientific-onboarding@wsa.local',
                    ]);
                $pages++;
                if (! $response->successful()) {
                    throw new \RuntimeException('Crossref HTTP '.$response->status());
                }
                $message = $response->json('message');
                if (! is_array($message)) {
                    throw new \RuntimeException('Crossref response missing message object.');
                }
                if ($totalReported === null) {
                    $totalReported = isset($message['total-results']) ? (int) $message['total-results'] : null;
                }
                $items = $message['items'] ?? [];
                if (! is_array($items) || $items === []) {
                    break;
                }
                foreach ($items as $item) {
                    if ($written >= $maxRecords) {
                        break;
                    }
                    if (! is_array($item)) {
                        $skippedIncomplete++;
                        continue;
                    }
                    if (! $this->hasCcBy40License($item)) {
                        $skippedLicense++;
                        continue;
                    }
                    $row = $this->mapItem($item);
                    if ($row === null) {
                        $skippedIncomplete++;
                        continue;
                    }
                    fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
                    $written++;
                }
                $offset += count($items);
                if (count($items) < $pageSize) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return [
            'path' => $path,
            'written' => $written,
            'skipped_license' => $skippedLicense,
            'skipped_incomplete' => $skippedIncomplete,
            'total_reported' => $totalReported,
            'pages' => $pages,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hasCcBy40License(array $item): bool
    {
        $licenses = $item['license'] ?? null;
        if (! is_array($licenses)) {
            return false;
        }
        foreach ($licenses as $license) {
            if (! is_array($license)) {
                continue;
            }
            $url = mb_strtolower(trim((string) ($license['URL'] ?? '')));
            if ($url === '') {
                continue;
            }
            if (str_contains($url, 'by-nc') || str_contains($url, 'by-nd')) {
                continue;
            }
            if (str_contains($url, self::CC_BY_4)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapItem(array $item): ?array
    {
        $doi = trim((string) ($item['DOI'] ?? ''));
        $titles = $item['title'] ?? [];
        $title = is_array($titles) ? trim((string) ($titles[0] ?? '')) : trim((string) $titles);
        if ($doi === '' || $title === '') {
            return null;
        }
        $abstract = isset($item['abstract']) ? trim(strip_tags((string) $item['abstract'])) : '';
        $url = trim((string) (($item['resource']['primary']['URL'] ?? null) ?: ($item['URL'] ?? '')));
        if ($url === '') {
            $url = 'https://doi.org/'.$doi;
        }
        $published = null;
        $year = null;
        $parts = $item['published']['date-parts'][0] ?? $item['issued']['date-parts'][0] ?? null;
        if (is_array($parts) && isset($parts[0])) {
            $year = (int) $parts[0];
            $published = sprintf('%04d', $year);
            if (isset($parts[1])) {
                $published .= sprintf('-%02d', (int) $parts[1]);
            }
            if (isset($parts[2])) {
                $published .= sprintf('-%02d', (int) $parts[2]);
            }
        }
        $authors = [];
        foreach ($item['author'] ?? [] as $author) {
            if (! is_array($author)) {
                continue;
            }
            $name = trim(((string) ($author['given'] ?? '')).' '.((string) ($author['family'] ?? '')));
            if ($name !== '') {
                $authors[] = $name;
            }
        }

        return [
            'id' => $doi,
            'title' => $title,
            'abstract' => $abstract !== '' ? $abstract : null,
            'doi' => $doi,
            'url' => $url,
            'published' => $published,
            'year' => $year,
            'authors' => $authors !== [] ? implode('; ', $authors) : null,
            'journal' => 'Osmia',
            'license' => 'CC BY 4.0',
        ];
    }
}
