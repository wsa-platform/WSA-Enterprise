<?php

namespace Database\Seeders;

use App\Models\LibraryCategory;
use App\Models\Organization;
use App\Services\Agriculture\FieldCropLibraryRepository;
use App\Services\Agriculture\FieldCropLibrarySeedData;
use Illuminate\Database\Seeder;

class FieldCropCultivationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'wsa-demo')->first();
        if ($organization === null) {
            return;
        }

        $libraryCategory = LibraryCategory::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'FIELD-CROP-GUIDES'],
            ['name' => 'Field crop guides', 'name_ar' => 'أدلة المحاصيل الحقلية']
        );

        $repository = app(FieldCropLibraryRepository::class);

        foreach (FieldCropLibrarySeedData::profiles() as $cropId => $sections) {
            $cropName = match ($cropId) {
                'wheat' => 'القمح',
                'corn' => 'الذرة',
                default => $cropId,
            };

            $item = $repository->mergeSections($organization->id, [
                'selected_crop_id' => $cropId,
                'selected_crop_name' => $cropName,
                'selected_category_id' => $cropId === 'wheat' || $cropId === 'corn' ? 'grains' : '',
                'selected_category_name' => $cropId === 'wheat' || $cropId === 'corn' ? 'محاصيل الحبوب' : '',
            ], $sections);

            if ($libraryCategory->id !== null) {
                $item->category_id = $libraryCategory->id;
                $item->save();
            }
        }
    }
}
