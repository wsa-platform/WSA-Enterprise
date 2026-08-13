<?php

namespace App\Console\Commands;

use App\Services\Deployment\ProductionAdminBootstrap;
use Illuminate\Console\Command;

class BootstrapProductionAdmin extends Command
{
    protected $signature = 'deploy:bootstrap-admin';

    protected $description = 'Create or update the production administrator from environment variables';

    public function handle(ProductionAdminBootstrap $bootstrap): int
    {
        if (! $bootstrap->shouldRun()) {
            $this->components->info('Admin bootstrap skipped (disabled or ADMIN_PASSWORD not set).');

            return self::SUCCESS;
        }

        $result = $bootstrap->run();

        $action = $result['created'] ? 'created' : 'updated';
        $this->components->info("Production admin {$action} for {$result['email']} ({$result['organization_slug']}).");

        return self::SUCCESS;
    }
}
