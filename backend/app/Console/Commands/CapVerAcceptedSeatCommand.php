<?php

namespace App\Console\Commands;

use App\Services\Agriculture\Research\Capability\CapVer\CapVerSeatVerificationService;
use Illuminate\Console\Command;

/**
 * Execute CapVer for the seats returned by AcceptedOwnerDecisionCatalog::capVerAuthorizedSeats().
 *
 * A positional adr_id runs that seat only. Omitting it runs the authorized batch.
 * Does not invent VERIFIED without prerequisites. Writes outcome JSON when --write-report.
 */
final class CapVerAcceptedSeatCommand extends Command
{
    protected $signature = 'scientific:capver-accepted
        {adr_id? : Authorized seat id; omit to run every CapVer-authorized seat}
        {--write-report= : Optional absolute/relative path for CapVer outcome JSON}';

    protected $description = 'Execute CapVer workflow for owner-accepted near-ready seats (does not fabricate Cap Store success)';

    public function handle(CapVerSeatVerificationService $capVer): int
    {
        $adrId = $this->argument('adr_id');
        $outcomes = is_string($adrId) && trim($adrId) !== ''
            ? [$capVer->execute(trim($adrId))]
            : $capVer->executeAcceptedSeats();

        $payload = [
            'schema_id' => 'wsa.capver.accepted_seat_outcomes.v1',
            'kind' => 'CAPVER_EXECUTION_RECORD',
            'mission_date' => '2026-10-18',
            'note' => 'Owner CapVer AUTHORIZE ≠ CapVer pass. capability_store_status is authoritative for Cap Store writes.',
            'outcomes' => array_map(static fn ($o) => $o->toArray(), $outcomes),
        ];

        foreach ($outcomes as $outcome) {
            $this->line(sprintf(
                '%s %s prereq=%s contract_verified=%s store=%s activation_pass=%s',
                $outcome->adrId,
                $outcome->sourceKey,
                $outcome->prerequisitesSatisfied ? 'ok' : 'fail',
                $outcome->contractVerifiedAuthored ? 'yes' : 'no',
                $outcome->capabilityStoreStatus,
                $outcome->capVerPassedForActivation ? 'yes' : 'no',
            ));
            if ($outcome->prerequisiteFailures !== []) {
                $this->warn('  failures: '.implode(', ', $outcome->prerequisiteFailures));
            }
            if ($outcome->capabilityStoreDetail !== null) {
                $this->line('  store_detail: '.$outcome->capabilityStoreDetail);
            }
        }

        $writeReport = $this->option('write-report');
        if (is_string($writeReport) && trim($writeReport) !== '') {
            $path = trim($writeReport);
            if (! preg_match('/^(?:[A-Za-z]:\\\\|\\\\|\\/)/', $path)) {
                $candidate = dirname(base_path()).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
                $path = $candidate;
            }
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents(
                $path,
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
            );
            $this->info('Wrote CapVer report: '.$path);
        }

        $failed = array_filter($outcomes, static fn ($o) => ! $o->capVerPassedForActivation);

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
