<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CropLibraryPublicFilesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->organization = Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
    }

    public function test_crop_files_are_filtered_by_category_crop_and_section(): void
    {
        $wheatFarming = $this->createCropFile('wheat-farming.pdf', 'grains', 'wheat', 'farming-needs', 'دليل زراعة القمح');
        $this->createCropFile('wheat-research.pdf', 'grains', 'wheat', 'scientific-research', 'أبحاث القمح');
        $this->createCropFile('oats-farming.pdf', 'grains', 'oats', 'farming-needs', 'دليل زراعة الشوفان');

        $response = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => $this->organization->slug,
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $wheatFarming->id);
        $response->assertJsonPath('data.0.extension', 'pdf');
        $response->assertJsonPath('data.0.preview_mode', 'inline_browser');
    }

    public function test_crop_files_do_not_mix_between_crops(): void
    {
        $this->createCropFile('corn-industries.docx', 'grains', 'corn', 'industries', 'صناعات الذرة');
        $this->createCropFile('sorghum-other.zip', 'grains', 'sorghum', 'other', 'ملفات أخرى للذرة الرفيعة');

        $cornResponse = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => $this->organization->slug,
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'corn',
            'library_file_section' => 'industries',
        ]));
        $cornResponse->assertOk();
        $cornResponse->assertJsonCount(1, 'data');
        $cornResponse->assertJsonPath('data.0.extension', 'docx');
        $cornResponse->assertJsonPath('data.0.preview_mode', 'download_only');

        $sorghumResponse = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => $this->organization->slug,
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'sorghum',
            'library_file_section' => 'other',
        ]));
        $sorghumResponse->assertOk();
        $sorghumResponse->assertJsonCount(1, 'data');
        $sorghumResponse->assertJsonPath('data.0.extension', 'zip');
    }

    public function test_pdf_content_is_served_inline_with_original_extension(): void
    {
        $item = $this->createCropFile('wheat-guide.pdf', 'grains', 'wheat', 'farming-needs', 'دليل القمح');
        Storage::disk('local')->put((string) $item->file_path, '%PDF-1.4 test');

        $response = $this->get('/api/v1/public/library/crop-files/'.$item->id.'/content?organization='.$this->organization->slug);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('wheat-guide.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_docx_content_is_served_as_attachment(): void
    {
        $item = $this->createCropFile('corn-report.docx', 'grains', 'corn', 'scientific-research', 'تقرير الذرة');
        Storage::disk('local')->put((string) $item->file_path, 'docx-bytes');

        $response = $this->get('/api/v1/public/library/crop-files/'.$item->id.'/content?organization='.$this->organization->slug);

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('corn-report.docx', (string) $response->headers->get('content-disposition'));
    }

    private function createCropFile(
        string $fileName,
        string $categoryId,
        string $cropId,
        string $sectionId,
        string $titleAr,
    ): LibraryItem {
        $path = 'library/crop-files/'.$fileName;
        Storage::disk('local')->put($path, 'seed');

        return LibraryItem::create([
            'organization_id' => $this->organization->id,
            'slug' => 'crop-file-'.$categoryId.'-'.$cropId.'-'.$sectionId.'-'.md5($fileName),
            'title' => $titleAr,
            'title_ar' => $titleAr,
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_disk' => 'local',
            'file_path' => $path,
            'metadata' => [
                'plant_production_category_id' => $categoryId,
                'field_crop_id' => $cropId,
                'library_file_section' => $sectionId,
            ],
        ]);
    }
}
