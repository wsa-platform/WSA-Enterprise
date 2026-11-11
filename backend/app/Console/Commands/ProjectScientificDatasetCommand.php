<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Search\Family\Bulk\AgrisOdsProviderXmlProjector;
use App\Services\Agriculture\Research\Search\Family\Bulk\DrDukeCsvRelationshipProjector;
use Illuminate\Console\Command;

/**
 * Local-only projection helpers for AGRIS ODS provider XML and Dr. Duke CSV tables.
 * Does not download, activate seats, or write Capability Store records.
 */
final class ProjectScientificDatasetCommand extends Command
{
    protected $signature = 'scientific:project-dataset
        {kind : agris_ods_provider_xml|dr_duke_csv}
        {--xml= : Local AGRIS provider XML path}
        {--csv-dir= : Local directory containing Duke CSV extracts}
        {--out= : Output JSONL path}
        {--max-records=2000 : Projection record budget}';

    protected $description = 'Project local AGRIS ODS / Dr. Duke files to JSONL for LocalIndex ingest';

    public function handle(): int
    {
        $kind = strtolower(trim((string) $this->argument('kind')));
        $out = (string) $this->option('out');
        $max = max(1, min(2_000_000, (int) $this->option('max-records')));
        if ($out === '') {
            $this->error('--out is required');

            return self::FAILURE;
        }

        if ($kind === 'agris_ods_provider_xml') {
            $xml = (string) $this->option('xml');
            $report = (new AgrisOdsProviderXmlProjector)->projectToJsonl($xml, $out, $max);
            $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return ($report['status'] === 'completed') ? self::SUCCESS : self::FAILURE;
        }

        if ($kind === 'dr_duke_csv') {
            $dir = rtrim((string) $this->option('csv-dir'), '\\/');
            $paths = [];
            // FARMACY and FARMACY_NEW are distinct; never omit FARMACY_NEW when present.
            foreach (['FNFTAX', 'CHEMICALS', 'ETHNOBOT', 'FARMACY', 'FARMACY_NEW'] as $table) {
                $candidate = $dir.DIRECTORY_SEPARATOR.$table.'.csv';
                if (is_file($candidate)) {
                    $paths[$table] = $candidate;
                }
            }
            $report = (new DrDukeCsvRelationshipProjector)->projectSearchableJsonl($paths, $out, $max);
            $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return ($report['status'] === 'completed') ? self::SUCCESS : self::FAILURE;
        }

        $this->error("Unknown kind '{$kind}'");

        return self::FAILURE;
    }
}
