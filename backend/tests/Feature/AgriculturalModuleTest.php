<?php

namespace Tests\Feature;

use App\Models\CropType;
use App\Models\Farm;
use App\Models\FarmBlock;
use App\Models\FarmField;
use App\Models\Organization;
use App\Models\SoilAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgriculturalModuleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTenantMember(): array
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $organization->members()->attach($user->id, ['role' => 'admin']);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_farm_modules_require_authentication(): void
    {
        $this->getJson('/api/v1/farm/farms')->assertUnauthorized();
    }

    public function test_farm_crud_is_scoped_to_the_authenticated_organization(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $this->postJson('/api/v1/farm/farms', [
            'code' => 'F1',
            'name' => 'Demo Farm',
            'area_hectares' => 10,
            'is_active' => true,
        ])->assertCreated()
            ->assertJsonPath('organization_id', $organization->id)
            ->assertJsonPath('code', 'F1');

        $this->getJson('/api/v1/farm/farms')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Demo Farm');

        $farmId = Farm::firstOrFail()->id;

        $this->putJson("/api/v1/farm/farms/{$farmId}", [
            'code' => 'F1',
            'name' => 'Updated Farm',
            'area_hectares' => 12,
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('name', 'Updated Farm');

        $this->deleteJson("/api/v1/farm/farms/{$farmId}")->assertNoContent();
        $this->getJson('/api/v1/farm/farms')->assertOk()->assertJsonCount(0);
    }

    public function test_farm_records_from_other_organizations_are_not_accessible(): void
    {
        [$organizationA] = $this->actingAsTenantMember();

        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $foreignFarm = Farm::create([
            'organization_id' => $organizationB->id,
            'code' => 'X1',
            'name' => 'Foreign Farm',
            'area_hectares' => 5,
            'is_active' => true,
        ]);

        Farm::create([
            'organization_id' => $organizationA->id,
            'code' => 'A1',
            'name' => 'Local Farm',
            'area_hectares' => 8,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/farm/farms')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'A1');

        $this->putJson("/api/v1/farm/farms/{$foreignFarm->id}", [
            'code' => 'X1',
            'name' => 'Blocked',
            'area_hectares' => 5,
            'is_active' => true,
        ])->assertNotFound();

        $this->deleteJson("/api/v1/farm/farms/{$foreignFarm->id}")->assertNotFound();
    }

    public function test_farm_module_rejects_foreign_organization_relations(): void
    {
        $this->actingAsTenantMember();

        $foreignOrganization = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $foreignFarm = Farm::create([
            'organization_id' => $foreignOrganization->id,
            'code' => 'X1',
            'name' => 'Foreign Farm',
            'area_hectares' => 5,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/farm/regions', [
            'farm_id' => $foreignFarm->id,
            'code' => 'R1',
            'name' => 'Blocked Region',
            'area_hectares' => 1,
        ])->assertStatus(422);
    }

    public function test_crop_modules_validate_and_persist_records(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $this->postJson('/api/v1/crop/types', [
            'code' => 'TOM',
            'name' => 'Tomato',
            'scientific_name' => 'Solanum lycopersicum',
        ])->assertCreated()
            ->assertJsonPath('organization_id', $organization->id);

        $this->getJson('/api/v1/crop/types')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'TOM');
    }

    public function test_crop_records_from_other_organizations_are_not_listed(): void
    {
        [$organizationA] = $this->actingAsTenantMember();

        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        CropType::create([
            'organization_id' => $organizationB->id,
            'code' => 'FOR',
            'name' => 'Foreign Crop',
        ]);

        CropType::create([
            'organization_id' => $organizationA->id,
            'code' => 'TOM',
            'name' => 'Tomato',
        ]);

        $this->getJson('/api/v1/crop/types')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'TOM');
    }

    public function test_soil_modules_validate_and_persist_records(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $this->postJson('/api/v1/soil/analyses', [
            'sample_reference' => 'SOIL-001',
            'sampled_at' => '2026-01-15',
            'ph' => 6.5,
            'laboratory' => 'Demo Lab',
        ])->assertCreated()
            ->assertJsonPath('organization_id', $organization->id)
            ->assertJsonPath('sample_reference', 'SOIL-001');

        $this->getJson('/api/v1/soil/analyses')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_soil_records_from_other_organizations_are_not_listed(): void
    {
        [$organizationA] = $this->actingAsTenantMember();

        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        SoilAnalysis::create([
            'organization_id' => $organizationB->id,
            'sample_reference' => 'FOREIGN-001',
            'sampled_at' => '2026-01-10',
        ]);

        SoilAnalysis::create([
            'organization_id' => $organizationA->id,
            'sample_reference' => 'LOCAL-001',
            'sampled_at' => '2026-01-11',
        ]);

        $this->getJson('/api/v1/soil/analyses')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.sample_reference', 'LOCAL-001');
    }

    public function test_integrated_agricultural_workflow(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $farm = $this->postJson('/api/v1/farm/farms', [
            'code' => 'GVF',
            'name' => 'Green Valley Farm',
            'area_hectares' => 20,
            'is_active' => true,
        ])->assertCreated()->json();

        $field = $this->postJson('/api/v1/farm/fields', [
            'farm_id' => $farm['id'],
            'code' => 'FLD-A',
            'name' => 'Tomato Block A',
            'area_hectares' => 4,
            'status' => 'active',
        ])->assertCreated()->json();

        $block = $this->postJson('/api/v1/farm/blocks', [
            'field_id' => $field['id'],
            'code' => 'A1',
            'name' => 'Rows 1-8',
            'area_hectares' => 1.2,
            'status' => 'active',
        ])->assertCreated()->json();

        $analysis = $this->postJson('/api/v1/soil/analyses', [
            'farm_id' => $farm['id'],
            'field_id' => $field['id'],
            'block_id' => $block['id'],
            'sample_reference' => 'SOIL-2026-001',
            'sampled_at' => '2026-01-20',
            'ph' => 6.4,
            'laboratory' => 'WSA Soil Lab',
        ])->assertCreated()
            ->assertJsonPath('organization_id', $organization->id)
            ->json();

        $this->postJson('/api/v1/soil/nutrients', [
            'soil_analysis_id' => $analysis['id'],
            'nutrient' => 'N',
            'value' => 28,
            'unit' => 'mg/kg',
            'status' => 'optimal',
        ])->assertCreated();

        $this->postJson('/api/v1/soil/recommendations', [
            'soil_analysis_id' => $analysis['id'],
            'field_id' => $field['id'],
            'block_id' => $block['id'],
            'title' => 'Apply balanced NPK before transplant',
            'recommendation' => 'Apply 120 kg/ha of 15-15-15 before transplanting.',
            'category' => 'fertilization',
            'priority' => 'high',
            'status' => 'open',
            'due_at' => '2026-02-10',
        ])->assertCreated();

        $this->getJson('/api/v1/soil/nutrients')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/v1/soil/recommendations')->assertOk()->assertJsonCount(1);
    }

    public function test_unknown_agricultural_module_returns_not_found(): void
    {
        $this->actingAsTenantMember();

        $this->getJson('/api/v1/farm/unknown-module')->assertNotFound();
        $this->getJson('/api/v1/crop/unknown-module')->assertNotFound();
        $this->getJson('/api/v1/soil/unknown-module')->assertNotFound();
    }
}
