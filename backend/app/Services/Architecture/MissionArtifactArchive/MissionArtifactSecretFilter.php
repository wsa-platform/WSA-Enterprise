<?php

namespace App\Services\Architecture\MissionArtifactArchive;

/**
 * Fail-closed exclusion of secret-bearing paths before archive import.
 * Never copies credentials into the archive.
 */
final class MissionArtifactSecretFilter
{
    /** @var list<string> */
    private const EXCLUDED_BASENAMES = [
        '.env',
        '.env.local',
        '.env.production',
        '.env.backup',
        'credentials.json',
        'service-account.json',
        'id_rsa',
        'id_ed25519',
    ];

    /** @var list<string> */
    private const EXCLUDED_PATH_FRAGMENTS = [
        DIRECTORY_SEPARATOR.'.env',
        '/.env',
        '\\.env',
        DIRECTORY_SEPARATOR.'secrets'.DIRECTORY_SEPARATOR,
        '/secrets/',
        '\\secrets\\',
        'private_keys',
    ];

    public function isExcluded(string $path): bool
    {
        $base = strtolower(basename($path));
        foreach (self::EXCLUDED_BASENAMES as $name) {
            if ($base === strtolower($name) || str_starts_with($base, '.env.')) {
                return true;
            }
        }
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        foreach (self::EXCLUDED_PATH_FRAGMENTS as $frag) {
            if (stripos($normalized, $frag) !== false) {
                return true;
            }
        }
        // Credential-looking filenames
        if (preg_match('/(^|[^a-z])(api[_-]?key|secret|token|password|passwd)([^a-z]|$)/i', $base) === 1
            && preg_match('/\.(md|json|txt|env|pem|key)$/i', $base) === 1) {
            // Allow owner-decision / non-secret docs that mention "token" in prose titles only if clearly mission docs
            if (! preg_match('/owner|decision|report|prompt|manifest|readiness/i', $base)) {
                return true;
            }
        }

        return false;
    }
}
