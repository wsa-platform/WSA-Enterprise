<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\TrainingCourse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelBPublicTenantAuthorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_library_and_crop_files_use_server_public_tenant_not_client_org(): void
    {
        $public = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo', 'is_active' => true]);
        $other = Organization::create(['name' => 'Org Alpha', 'slug' => 'org-alpha', 'is_active' => true]);
        config(['wsa.public_organization_slug' => 'wsa-demo']);

        LibraryItem::create([
            'organization_id' => $public->id,
            'slug' => 'public-ok',
            'title' => 'Public Demo Item',
            'publication_status' => 'published',
        ]);
        LibraryItem::create([
            'organization_id' => $other->id,
            'slug' => 'alpha-secret',
            'title' => 'Alpha Item',
            'publication_status' => 'published',
        ]);
        TrainingCourse::create([
            'organization_id' => $public->id,
            'code' => 'demo-1',
            'title' => 'Demo Course',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        TrainingCourse::create([
            'organization_id' => $other->id,
            'code' => 'alpha-1',
            'title' => 'Alpha Course',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $items = $this->getJson('/api/v1/public/library/items?organization=org-alpha')
            ->assertOk()
            ->json();
        $this->assertSame($public->id, $items['organization_id']);
        $this->assertSame('wsa-demo', $items['organization_slug']);
        $titles = collect($items['data'])->pluck('title')->all();
        $this->assertContains('Public Demo Item', $titles);
        $this->assertNotContains('Alpha Item', $titles);

        $courses = $this->getJson('/api/v1/public/training/courses?organization_id='.$other->id)
            ->assertOk()
            ->json();
        $this->assertSame($public->id, $courses['organization_id']);
        $this->assertNotContains('Alpha Course', collect($courses['data'])->pluck('title')->all());

        $files = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => 'org-alpha',
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]))->assertOk()->json();
        $this->assertSame($public->id, $files['organization_id']);

        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('library/alpha/secret.pdf', '%PDF-1.4 secret');
        $secret = LibraryItem::create([
            'organization_id' => $other->id,
            'slug' => 'alpha-file',
            'title' => 'Alpha File',
            'publication_status' => 'published',
            'file_disk' => 'local',
            'file_path' => 'library/alpha/secret.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);
        $this->getJson('/api/v1/public/library/crop-files/'.$secret->id.'/content?organization=org-alpha')
            ->assertNotFound();
    }
}
