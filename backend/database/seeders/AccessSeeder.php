<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Illuminate\Database\Seeder;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'wsa-demo')->first();
        if (! $organization) {
            return;
        }

        EnterpriseRoleService::seedForOrganization($organization->id);

        $admin = User::where('email', 'admin@wsa.test')->first();
        if ($admin) {
            app(EnterpriseRoleService::class)->assignDefaultOwner($admin, $organization);
        }
    }
}
