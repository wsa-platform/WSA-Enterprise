<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Search\Family\Bulk\DrDukeDatasetReconciler;
use Illuminate\Console\Command;

/**
 * Read-only quantitative reconciliation of Dr. Duke CSV relationships.
 */
final class ReconcileDrDukeDatasetCommand extends Command
{
    protected $signature = 'scientific:reconcile-dr-duke
        {--csv-dir= : Directory containing Duke CSV extracts}
        {--out= : Output reconciliation JSON path}';

    protected $description = 'Reconcile Dr. Duke CSV keys/relationships; write quantitative report JSON';

    public function handle(): int
    {
        $dir = rtrim((string) $this->option('csv-dir'), '\\/');
        $out = (string) $this->option('out');
        if ($dir === '' || $out === '') {
            $this->error('--csv-dir and --out are required');

            return self::FAILURE;
        }

        $tables = [
            'FNFTAX', 'CHEMICALS', 'ETHNOBOT', 'FARMACY', 'FARMACY_NEW',
            'COMMON_NAMES', 'ACTIVITIES', 'AGGREGAC', 'ASSAY', 'CHEM_MEANS',
            'CODES', 'DOSAGE', 'PARTS', 'REFERENCES', 'SUPERACT', 'YIELDS',
        ];
        $paths = [];
        foreach ($tables as $table) {
            $candidate = $dir.DIRECTORY_SEPARATOR.$table.'.csv';
            if (is_file($candidate)) {
                $paths[$table] = $candidate;
            }
        }

        $report = (new DrDukeDatasetReconciler)->reconcile($paths);
        $dirOut = dirname($out);
        if (! is_dir($dirOut) && ! mkdir($dirOut, 0775, true) && ! is_dir($dirOut)) {
            $this->error('Cannot create output directory');

            return self::FAILURE;
        }
        file_put_contents(
            $out,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        $this->line(json_encode([
            'status' => 'completed',
            'path' => $out,
            'tables' => count($paths),
            'fnftax_keys' => $report['fnftax_keys'] ?? 0,
        ], JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
