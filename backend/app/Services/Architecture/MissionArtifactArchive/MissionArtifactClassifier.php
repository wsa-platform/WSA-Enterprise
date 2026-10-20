<?php

namespace App\Services\Architecture\MissionArtifactArchive;

/**
 * Classifies mission/engineering files for controlled archival. Does not invent AHO schemas.
 */
final class MissionArtifactClassifier
{
    /**
     * @return array{artifact_type: string, format: string, title: string, mission_id: ?string, related_seat: ?string, related_source_key: ?string, aho_supported: bool}
     */
    public function classify(string $path, string $relativeHint = ''): array
    {
        $base = basename($path);
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        $hay = strtolower($base.' '.$relativeHint);

        $format = match ($ext) {
            'json' => 'json',
            'md', 'markdown' => 'markdown',
            'txt' => 'text',
            'aho' => 'aho',
            'xml' => 'xml',
            'py' => 'python',
            default => $ext !== '' ? $ext : 'unknown',
        };

        $type = 'other';
        if (str_contains($hay, 'prompt')) {
            $type = 'cursor_prompt';
        } elseif (str_contains($hay, 'report') || str_contains($hay, 'audit')) {
            $type = 'engineering_report';
        } elseif (str_contains($hay, 'owner-decision') || str_contains($hay, 'owner_decision')) {
            $type = 'owner_decision';
        } elseif (str_contains($hay, 'manifest') || str_contains($hay, 'checkpoint') || str_contains($hay, 'stopping')) {
            $type = 'checkpoint_or_manifest';
        } elseif (str_contains($hay, 'readiness') || str_contains($hay, 'activation')) {
            $type = 'readiness_matrix';
        } elseif (str_contains($hay, 'reconciliation')) {
            $type = 'reconciliation_report';
        } elseif ($format === 'json') {
            $type = 'json_artifact';
        } elseif ($format === 'aho') {
            $type = 'aho_artifact';
        } elseif (str_contains($hay, 'mission') || str_contains($hay, 'execution')) {
            $type = 'mission_spec';
        }

        $missionId = null;
        if (preg_match('/(2026-\d{2}-\d{2}[^\\\\\/]*SOURCE-EXPANSION[^\\\\\/]*|2026-10-17-SOURCE-EXPANSION-MISSION)/i', $relativeHint.' '.$path, $m) === 1) {
            $missionId = '2026-10-17-SOURCE-EXPANSION-MISSION';
        } elseif (preg_match('/(2026-10-14-D-08[^\\\\\/]*)/i', $relativeHint.' '.$path, $m) === 1) {
            $missionId = $m[1];
        }

        $seat = null;
        $source = null;
        if (preg_match('/\b(G[1-6]-\d{2})\b/i', $hay, $m) === 1) {
            $seat = strtoupper($m[1]);
        }
        foreach (['organic_eprints', 'gbif', 'bhl', 'agris', 'dr_duke'] as $sk) {
            if (str_contains($hay, $sk) || str_contains($hay, str_replace('_', '-', $sk))) {
                $source = $sk;
                break;
            }
        }

        return [
            'artifact_type' => $type,
            'format' => $format,
            'title' => $base,
            'mission_id' => $missionId,
            'related_seat' => $seat,
            'related_source_key' => $source,
            // No AHO schema discovered in repository; .aho files would be retained but not schema-extracted.
            'aho_supported' => false,
        ];
    }
}
