<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Search\Family\Bulk\AgrisOdsBoundedHarvester;
use Illuminate\Console\Command;

/**
 * Bounded resumable AGRIS ODS provider harvest. Never activates seats.
 */
final class HarvestAgrisOdsBoundedCommand extends Command
{
    protected $signature = 'scientific:harvest-agris-ods
        {--root-xml= : Local AGRIS.ODS.xml catalog path}
        {--cache-dir= : Local cache directory for provider XML/JSONL}
        {--manifest= : Output harvest manifest JSON path}
        {--max-providers=5 : Provider download/projection budget (max 2000; resume via checkpoint)}
        {--max-records-per-provider=200 : Projection budget per provider (max 500000 for full-body)}
        {--timeout=60 : HTTPS download timeout seconds}';

    protected $description = 'Bounded resumable harvest of AGRIS ODS provider XMLs into local cache + manifest';

    public function handle(): int
    {
        $root = (string) $this->option('root-xml');
        $cache = (string) $this->option('cache-dir');
        $manifest = (string) $this->option('manifest');
        if ($root === '' || $cache === '' || $manifest === '') {
            $this->error('--root-xml, --cache-dir, and --manifest are required');

            return self::FAILURE;
        }

        // Full legally accessible AGRIS body may require all ODS providers (~1244).
        // Hard ceiling protects accidental unbounded CLI misuse; resume remains checkpointed.
        $report = (new AgrisOdsBoundedHarvester)->harvest(
            $root,
            $cache,
            $manifest,
            max(1, min(2_000, (int) $this->option('max-providers'))),
            max(1, min(500_000, (int) $this->option('max-records-per-provider'))),
            max(10, min(300, (int) $this->option('timeout'))),
        );

        $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return isset($report['totals']['projected']) && (int) $report['totals']['projected'] > 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
