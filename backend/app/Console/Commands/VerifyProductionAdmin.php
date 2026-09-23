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

        $user = \App\Models\User::query()->where('email', $email)->first();
        if ($user === null || ! $user->isPlatformAdministrator()) {
            $this->components->error("User {$email} exists but is not a Platform Administrator. Re-run deploy:bootstrap-admin.");

            return self::FAILURE;
        }

        $this->components->info("Production Platform Administrator exists for {$email}.");

        return self::SUCCESS;
    }
}
