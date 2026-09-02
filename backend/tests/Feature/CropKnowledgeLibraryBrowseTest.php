<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\FieldCropCultivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropKnowledgeLibraryBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_crop_knowledge_tree_lists_seeded_profiles(): void
    {
        $organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        $user = User::factory()->create();
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $this->seed(FieldCropCultivationSeeder::class);

        $response = $this->actingAs($user)
            ->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/library/crop-knowledge/tree');

        $response->assertOk();
        $response->assertJsonStructure(['categories', 'knowledge_options']);
        $categories = $response->json('categories');
        $this->assertNotEmpty($categories);
    }
}
