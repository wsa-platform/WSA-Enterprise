<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Deployment\ProductionAdminBootstrap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BootstrapProductionAdmin extends Command
{
    protected $signature = 'deploy:bootstrap-admin';

    protected $description = 'Create or update the production administrator from environment variables';

    public function handle(ProductionAdminBootstrap $bootstrap): int
    {
        if (! $bootstrap->shouldRun()) {
            $this->components->warn('Admin bootstrap skipped (disabled or ADMIN_PASSWORD not set).');

            return self::SUCCESS;
        }

        try {
            $result = $bootstrap->run();
        } catch (\Throwable $exception) {
            $this->components->error('Admin bootstrap failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $user = User::query()->where('email', $result['email'])->first();
        $passwordValid = $user !== null
            && Hash::check($bootstrap->adminPassword(), (string) $user->password);

        if (! $passwordValid) {
            $this->components->error('Admin bootstrap completed but password verification failed.');

            return self::FAILURE;
        }

        $action = $result['created'] ? 'created' : 'updated';
        $this->components->info("Production admin {$action} for {$result['email']} ({$result['organization_slug']}).");

        return self::SUCCESS;
    }
}
