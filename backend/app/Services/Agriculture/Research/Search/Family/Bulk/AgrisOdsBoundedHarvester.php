<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;

/**
 * Bounded, resumable harvest of AGRIS ODS provider XMLs from a local root catalog copy.
 *
 * Never activates seats. Downloads only over HTTPS into a local cache directory.
 * License is read per provider artifact; unverified licenses are skipped (fail-closed for publish).
 */
final class AgrisOdsBoundedHarvester
{
    public const CONTRACT_VERSION = 'agris_ods_bounded_harvester.v1';

    public function __construct(
        private readonly AgrisOdsProviderXmlProjector $projector = new AgrisOdsProviderXmlProjector(),
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function harvest(
        string $rootXmlPath,
        string $cacheDir,
        string $manifestPath,
        int $maxProviders = 5,
        int $maxRecordsPerProvider = 200,
        int $timeoutSeconds = 60,
    ): array {
        if (preg_match('#^[A-Za-z][A-Za-z0-9+.-]*://#', $rootXmlPath) === 1) {
            throw new ScientificSourceContractViolation('Root catalog must be a local file path.');
        }
        if (! is_file($rootXmlPath)) {
            return $this->failManifest($manifestPath, 'root_missing');
        }
        if (! is_dir($cacheDir) && ! mkdir($cacheDir, 0775, true) && ! is_dir($cacheDir)) {
            return $this->failManifest($manifestPath, 'cache_dir');
        }

        $checkpointPath = $cacheDir.DIRECTORY_SEPARATOR.'harvest_checkpoint.v1.json';
        $checkpoint = $this->readJson($checkpointPath) ?? [
            'completed_providers' => [],
            'cursor' => 0,
        ];

        $urls = $this->extractDownloadUrls($rootXmlPath);
        $completed = is_array($checkpoint['completed_providers'] ?? null)
            ? $checkpoint['completed_providers']
            : [];
        $resumedHint = (int) ($checkpoint['cursor'] ?? 0);
        $manifest = [
            'schema_id' => 'wsa.agris_ods_harvest_manifest.v1',
            'contract_version' => self::CONTRACT_VERSION,
            'root_xml' => $rootXmlPath,
            'root_sha256' => hash_file('sha256', $rootXmlPath) ?: null,
            'download_url_total' => count($urls),
            'max_providers' => $maxProviders,
            'providers' => [],
            'totals' => [
                'attempted' => 0,
                'downloaded' => 0,
                'projected' => 0,
                'skipped_unverified_license' => 0,
                'failed' => 0,
                'records' => 0,
            ],
            'resumed_from_cursor' => $resumedHint,
            'scan_mode' => 'full_skip_completed',
            'completed' => false,
        ];

        $attempted = 0;
        // Always scan from index 0; completed_providers is the durable skip set.
        // Cursor alone must not permanently skip download_failed (e.g. HTTP 429) URLs.
        $i = 0;

        while ($attempted < $maxProviders && $i < count($urls)) {
            $url = $urls[$i];
            $i++;
            if ($url === '' || in_array($url, $completed, true)) {
                continue;
            }
            if (! str_starts_with($url, 'https://agris.fao.org/ods/')) {
                $manifest['providers'][] = [
                    'url' => $url,
                    'status' => 'skipped_non_allowlisted_host',
                ];
                continue;
            }

            $attempted++;
            $basename = basename(parse_url($url, PHP_URL_PATH) ?: ('provider_'.$attempted.'.xml'));
            $localXml = $cacheDir.DIRECTORY_SEPARATOR.$basename;
            $entry = [
                'url' => $url,
                'local_xml' => $localXml,
                'status' => 'pending',
            ];

            if (! is_file($localXml)) {
                $download = $this->downloadHttps($url, $localXml, $timeoutSeconds);
                if ($download['ok'] !== true) {
                    $entry['status'] = 'download_failed';
                    $entry['error'] = $download['error'];
                    $manifest['totals']['failed']++;
                    $manifest['providers'][] = $entry;
                    $checkpoint['cursor'] = $i;
                    $this->writeJson($checkpointPath, $checkpoint);
                    continue;
                }
                $entry['bytes'] = $download['bytes'];
                $entry['sha256'] = hash_file('sha256', $localXml) ?: null;
                $manifest['totals']['downloaded']++;
            } else {
                $entry['bytes'] = filesize($localXml) ?: 0;
                $entry['sha256'] = hash_file('sha256', $localXml) ?: null;
                $entry['reused_local'] = true;
                $manifest['totals']['downloaded']++;
            }

            $jsonl = $cacheDir.DIRECTORY_SEPARATOR.pathinfo($basename, PATHINFO_FILENAME).'.jsonl';
            $projection = $this->projector->projectToJsonl($localXml, $jsonl, $maxRecordsPerProvider);
            $entry['license'] = $projection['license'];
            $entry['records'] = $projection['records'];
            $entry['projection_status'] = $projection['status'];
            $entry['jsonl'] = $jsonl;

            $licenseOk = is_string($projection['license'])
                && preg_match('/CC-?BY-?4\.0/i', $projection['license']) === 1;
            if (! $licenseOk) {
                $entry['status'] = 'skipped_unverified_license';
                $manifest['totals']['skipped_unverified_license']++;
            } elseif ($projection['status'] !== 'completed') {
                $entry['status'] = 'projection_failed';
                $entry['failure'] = $projection['failure'];
                $manifest['totals']['failed']++;
            } else {
                $entry['status'] = 'projected';
                $manifest['totals']['projected']++;
                $manifest['totals']['records'] += (int) $projection['records'];
                $completed[] = $url;
            }

            $manifest['providers'][] = $entry;
            $manifest['totals']['attempted'] = $attempted;
            $checkpoint['cursor'] = $i;
            $checkpoint['completed_providers'] = array_values(array_unique($completed));
            $this->writeJson($checkpointPath, $checkpoint);
        }

        $manifest['completed'] = $i >= count($urls);
        $manifest['next_cursor'] = $i;
        $this->writeJson($manifestPath, $manifest);

        return $manifest;
    }

    /** @return list<string> */
    private function extractDownloadUrls(string $rootXmlPath): array
    {
        $xml = file_get_contents($rootXmlPath);
        if ($xml === false) {
            return [];
        }
        preg_match_all('#dcat:downloadURL[^>]*>([^<]+)#', $xml, $m);
        $urls = [];
        foreach ($m[1] ?? [] as $raw) {
            $u = trim(html_entity_decode($raw, ENT_QUOTES | ENT_XML1));
            if ($u !== '' && str_starts_with($u, 'https://')) {
                $urls[$u] = true;
            }
        }

        return array_keys($urls);
    }

    /** @return array{ok: bool, bytes?: int, error?: string} */
    private function downloadHttps(string $url, string $dest, int $timeoutSeconds): array
    {
        $attempts = 4;
        $lastError = 'unknown';
        for ($try = 1; $try <= $attempts; $try++) {
            $fh = fopen($dest, 'wb');
            if ($fh === false) {
                return ['ok' => false, 'error' => 'dest_unwritable'];
            }
            $ch = curl_init($url);
            if ($ch === false) {
                fclose($fh);

                return ['ok' => false, 'error' => 'curl_init_failed'];
            }
            $responseHeaders = [];
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fh,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'WSA-Enterprise-AgrisOdsHarvester/1.0',
                CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                    $len = strlen($headerLine);
                    $parts = explode(':', $headerLine, 2);
                    if (count($parts) === 2) {
                        $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }

                    return $len;
                },
            ]);
            $ok = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            fclose($fh);
            if ($ok === true && $code >= 200 && $code < 300) {
                $bytes = filesize($dest) ?: 0;
                if ($bytes < 64) {
                    @unlink($dest);

                    return ['ok' => false, 'error' => 'too_small'];
                }

                return ['ok' => true, 'bytes' => $bytes];
            }
            @unlink($dest);
            $lastError = $err !== '' ? $err : 'http_'.$code;
            // Respect provider rate limits; honour Retry-After when present.
            if ($code === 429 && $try < $attempts) {
                $retryAfter = $responseHeaders['retry-after'] ?? null;
                $wait = is_numeric($retryAfter)
                    ? min(120, max(1, (int) $retryAfter))
                    : min(60, 5 * $try);
                sleep($wait);
                continue;
            }
            if (in_array($code, [500, 502, 503, 504], true) && $try < $attempts) {
                sleep(min(30, 2 * $try));
                continue;
            }

            break;
        }

        return ['ok' => false, 'error' => $lastError];
    }

    /** @return array<string, mixed>|null */
    private function readJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param  array<string, mixed>  $data */
    private function writeJson(string $path, array $data): void
    {
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return array<string, mixed> */
    private function failManifest(string $manifestPath, string $failure): array
    {
        $manifest = [
            'schema_id' => 'wsa.agris_ods_harvest_manifest.v1',
            'status' => 'failed',
            'failure' => $failure,
            'completed' => false,
        ];
        $this->writeJson($manifestPath, $manifest);

        return $manifest;
    }
}
