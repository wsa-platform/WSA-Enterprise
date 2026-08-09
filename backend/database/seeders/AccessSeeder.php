<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Database\Seeder;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'wsa-demo')->first();
        if (! $organization) {
            return;
        }

        PermissionService::seedNamesForOrganization($organization->id);

        $adminRole = Role::updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Administrator'],
            ['description' => 'Full workspace access through assigned permissions.']
        );

        $viewerRole = Role::updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Viewer'],
            ['description' => 'Read-only access to business and platform modules.']
        );

        $adminRole->permissions()->sync(
            Permission::where('organization_id', $organization->id)->pluck('id')
        );

        $viewerRole->permissions()->sync(
            Permission::where('organization_id', $organization->id)
                ->whereIn('name', ['platform.view', 'farm.view', 'crop.view', 'soil.view', 'diagnosis.view', 'training.view', 'library.view', 'business.view'])
                ->pluck('id')
        );

        $admin = User::where('email', 'admin@wsa.test')->first();
        if ($admin) {
            $admin->roles()->syncWithoutDetaching([
                $adminRole->id => ['organization_id' => $organization->id],
            ]);
        }
    }
}
