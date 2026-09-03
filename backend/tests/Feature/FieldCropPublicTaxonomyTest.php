<?php

namespace Tests\Feature;

use App\Services\Agriculture\FieldCropCategoryCatalog;
use App\Services\Agriculture\FieldCropTaxonomyCatalog;
use Tests\TestCase;

class FieldCropPublicTaxonomyTest extends TestCase
{
    public function test_public_taxonomy_endpoint_exposes_backend_catalog(): void
    {
        $response = $this->getJson('/api/v1/public/field-crops/taxonomy');

        $response->assertOk();
        $response->assertJsonPath('source', 'field_crop_category_catalog');
        $response->assertJsonStructure([
            'source',
            'plant_production_sections' => [['id', 'name', 'library_category_ids']],
            'library_categories' => [['id', 'name']],
            'categories' => [['id', 'name', 'crops']],
        ]);

        $wheat = collect($response->json('categories'))
            ->firstWhere('id', 'grains')['crops'] ?? [];
        $wheatIds = collect($wheat)->pluck('id')->all();
        $this->assertContains('wheat', $wheatIds);

        $wheatRow = collect($wheat)->firstWhere('id', 'wheat');
        $this->assertSame(
            FieldCropTaxonomyCatalog::scientificNameFor('wheat'),
            $wheatRow['scientific_name'] ?? null,
        );
        $this->assertSame(FieldCropCategoryCatalog::toArray()['source'], 'field_crop_category_catalog');
    }
}
