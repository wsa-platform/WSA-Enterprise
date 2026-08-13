<?php

namespace App\Console\Commands;

use App\Services\Deployment\ProductionAdminBootstrap;
use Illuminate\Console\Command;

class VerifyProductionAdmin extends Command
{
    protected $signature = 'deploy:verify-admin';

    protected $description = 'Verify that the configured production administrator exists';

    public function handle(ProductionAdminBootstrap $bootstrap): int
    {
        $email = $bootstrap->adminEmail();

        if ($email === '') {
            $this->components->error('ADMIN_EMAIL is not configured.');

            return self::FAILURE;
        }

        if (! $bootstrap->adminExists()) {
            $this->components->error("No user found for {$email}. Run deploy:bootstrap-admin after setting ADMIN_PASSWORD.");

            return self::FAILURE;
        }

        $this->components->info("Production admin account exists for {$email}.");

        return self::SUCCESS;
    }
}
