<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryFileTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_library_files_are_tenant_scoped(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('library/a/a.pdf', '%PDF-1.4 a');
        Storage::disk('local')->put('library/b/b.pdf', '%PDF-1.4 b');

        $orgA = Organization::create(['name' => 'A', 'slug' => 'org-a', 'is_active' => true]);
        $orgB = Organization::create(['name' => 'B', 'slug' => 'org-b', 'is_active' => true]);
        $userA = $this->member($orgA, 'a@wsa.test');
        $userB = $this->member($orgB, 'b@wsa.test');

        $fileA = LibraryItem::create([
            'organization_id' => $orgA->id,
            'owner_user_id' => $userA->id,
            'slug' => 'file-a',
            'title' => 'File A',
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/a/a.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);
        $fileB = LibraryItem::create([
            'organization_id' => $orgB->id,
            'owner_user_id' => $userB->id,
            'slug' => 'file-b',
            'title' => 'File B',
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/b/b.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        Sanctum::actingAs($userA);
        $listed = $this->getJson('/api/v1/library/files?'.http_build_query([
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]), [
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk()->json('data');
        $ids = collect($listed)->pluck('id')->all();
        $this->assertContains($fileA->id, $ids);
        $this->assertNotContains($fileB->id, $ids);

        $this->get('/api/v1/library/files/'.$fileA->id.'/content', [
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk();
        $this->get('/api/v1/library/files/'.$fileB->id.'/content', [
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }

    private function member(Organization $organization, string $email): User
    {
        $user = User::create([
            'name' => 'Member',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);

        return $user;
    }
}
