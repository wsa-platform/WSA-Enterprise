<?php

namespace Database\Seeders;

use App\Models\CropHarvest;
use App\Models\CropSeason;
use App\Models\CropType;
use App\Models\CropVariety;
use App\Models\CropYield;
use App\Models\Farm;
use App\Models\FarmBlock;
use App\Models\FarmField;
use App\Models\FarmRegion;
use App\Models\GisMap;
use App\Models\GpsCoordinate;
use App\Models\Greenhouse;
use App\Models\GrowthStage;
use App\Models\IrrigationZone;
use App\Models\Organization;
use App\Models\SoilAnalysis;
use App\Models\SoilNutrient;
use App\Models\SoilRecommendation;
use Illuminate\Database\Seeder;

class AgriculturalSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'wsa-demo')->firstOrFail();

        $farm = Farm::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'GVF'],
            [
                'name' => 'Green Valley Farm',
                'owner_name' => 'Avery Morgan',
                'address' => 'Route 12, Al-Kharj, Saudi Arabia',
                'area_hectares' => 120.5,
                'is_active' => true,
            ]
        );

        $northRegion = FarmRegion::updateOrCreate(
            ['farm_id' => $farm->id, 'code' => 'NORTH'],
            [
                'organization_id' => $organization->id,
                'name' => 'North Orchard',
                'description' => 'Stone-fruit and tomato production blocks.',
                'area_hectares' => 45.0,
            ]
        );

        $southRegion = FarmRegion::updateOrCreate(
            ['farm_id' => $farm->id, 'code' => 'SOUTH'],
            [
                'organization_id' => $organization->id,
                'name' => 'South Fields',
                'description' => 'Open-field seasonal rotations.',
                'area_hectares' => 75.5,
            ]
        );

        $fieldA = FarmField::updateOrCreate(
            ['farm_id' => $farm->id, 'code' => 'FLD-A'],
            [
                'organization_id' => $organization->id,
                'region_id' => $northRegion->id,
                'name' => 'Tomato Block A',
                'area_hectares' => 12.5,
                'soil_type' => 'Sandy loam',
                'status' => 'active',
            ]
        );

        $fieldB = FarmField::updateOrCreate(
            ['farm_id' => $farm->id, 'code' => 'FLD-B'],
            [
                'organization_id' => $organization->id,
                'region_id' => $southRegion->id,
                'name' => 'Rotation Field B',
                'area_hectares' => 18.0,
                'soil_type' => 'Clay loam',
                'status' => 'active',
            ]
        );

        $blockA1 = FarmBlock::updateOrCreate(
            ['field_id' => $fieldA->id, 'code' => 'A1'],
            [
                'organization_id' => $organization->id,
                'name' => 'Tomato rows 1–8',
                'area_hectares' => 3.2,
                'crop' => 'Tomato',
                'variety' => 'Roma VF',
                'status' => 'active',
            ]
        );

        $greenhouse = Greenhouse::updateOrCreate(
            ['farm_id' => $farm->id, 'code' => 'GH-01'],
            [
                'organization_id' => $organization->id,
                'field_id' => $fieldA->id,
                'name' => 'Propagation House 1',
                'area_square_meters' => 2400,
                'structure_type' => 'Multi-span',
                'climate_control' => 'Evaporative cooling',
                'status' => 'active',
            ]
        );

        IrrigationZone::updateOrCreate(
            ['farm_id' => $farm->id, 'code' => 'IZ-A1'],
            [
                'organization_id' => $organization->id,
                'field_id' => $fieldA->id,
                'block_id' => $blockA1->id,
                'greenhouse_id' => null,
                'name' => 'Drip zone A1',
                'method' => 'Drip',
                'flow_rate_lph' => 450,
                'status' => 'active',
            ]
        );

        GpsCoordinate::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'coordinateable_type' => FarmField::class,
                'coordinateable_id' => $fieldA->id,
                'sequence' => 0,
            ],
            [
                'latitude' => 24.1558000,
                'longitude' => 47.3342000,
                'altitude_meters' => 612,
            ]
        );

        GisMap::updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Field boundaries'],
            [
                'farm_id' => $farm->id,
                'layer_type' => 'boundary',
                'source_url' => null,
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => [],
                ],
                'metadata' => ['crs' => 'EPSG:4326', 'updated_by' => 'demo-seed'],
            ]
        );

        $tomato = CropType::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'TOM'],
            [
                'name' => 'Tomato',
                'scientific_name' => 'Solanum lycopersicum',
                'description' => 'Primary greenhouse and open-field crop.',
            ]
        );

        $roma = CropVariety::updateOrCreate(
            ['crop_type_id' => $tomato->id, 'code' => 'ROMA-VF'],
            [
                'organization_id' => $organization->id,
                'name' => 'Roma VF',
                'supplier' => 'Regional Seed Co.',
                'maturity_days' => 75,
                'notes' => 'Determinate paste tomato with VF resistance.',
            ]
        );

        foreach ([
            ['name' => 'Seedling', 'sequence' => 1, 'expected_days' => 14],
            ['name' => 'Vegetative', 'sequence' => 2, 'expected_days' => 28],
            ['name' => 'Flowering', 'sequence' => 3, 'expected_days' => 21],
            ['name' => 'Fruit set', 'sequence' => 4, 'expected_days' => 12],
        ] as $stage) {
            GrowthStage::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'crop_type_id' => $tomato->id,
                    'name' => $stage['name'],
                ],
                [
                    'sequence' => $stage['sequence'],
                    'expected_days' => $stage['expected_days'],
                    'description' => "{$stage['name']} stage for {$tomato->name}.",
                ]
            );
        }

        $season = CropSeason::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => '2026-S1'],
            [
                'farm_id' => $farm->id,
                'name' => 'Spring 2026',
                'starts_at' => '2026-02-01',
                'ends_at' => '2026-06-30',
                'status' => 'active',
            ]
        );

        CropHarvest::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'season_id' => $season->id,
                'crop_type_id' => $tomato->id,
                'field_id' => $fieldA->id,
                'block_id' => $blockA1->id,
                'harvested_at' => '2026-05-15',
            ],
            [
                'variety_id' => $roma->id,
                'quantity' => 8420,
                'unit' => 'kg',
                'quality_score' => 92.5,
                'notes' => 'First commercial harvest of the season.',
            ]
        );

        CropYield::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'season_id' => $season->id,
                'crop_type_id' => $tomato->id,
                'field_id' => $fieldA->id,
                'block_id' => $blockA1->id,
                'reported_at' => '2026-05-20',
            ],
            [
                'area_hectares' => 3.2,
                'expected_quantity' => 9000,
                'actual_quantity' => 8420,
                'unit' => 'kg',
                'notes' => 'Yield within 6% of forecast.',
            ]
        );

        $analysis = SoilAnalysis::updateOrCreate(
            ['organization_id' => $organization->id, 'sample_reference' => 'SOIL-2026-001'],
            [
                'farm_id' => $farm->id,
                'field_id' => $fieldA->id,
                'block_id' => $blockA1->id,
                'sampled_at' => '2026-01-20',
                'ph' => 6.4,
                'ec' => 1.2,
                'organic_matter_percent' => 3.8,
                'moisture_percent' => 22.5,
                'laboratory' => 'WSA Soil Lab',
                'notes' => 'Pre-season baseline sample.',
            ]
        );

        foreach ([
            ['nutrient' => 'N', 'value' => 28, 'unit' => 'mg/kg', 'target_min' => 25, 'target_max' => 40, 'status' => 'optimal'],
            ['nutrient' => 'P', 'value' => 18, 'unit' => 'mg/kg', 'target_min' => 15, 'target_max' => 30, 'status' => 'optimal'],
            ['nutrient' => 'K', 'value' => 210, 'unit' => 'mg/kg', 'target_min' => 180, 'target_max' => 250, 'status' => 'optimal'],
        ] as $nutrient) {
            SoilNutrient::updateOrCreate(
                [
                    'soil_analysis_id' => $analysis->id,
                    'nutrient' => $nutrient['nutrient'],
                ],
                [
                    'organization_id' => $organization->id,
                    ...$nutrient,
                ]
            );
        }

        SoilRecommendation::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'soil_analysis_id' => $analysis->id,
                'title' => 'Apply balanced NPK before transplant',
            ],
            [
                'field_id' => $fieldA->id,
                'block_id' => $blockA1->id,
                'recommendation' => 'Apply 120 kg/ha of 15-15-15 two weeks before transplanting Roma VF seedlings.',
                'category' => 'fertilization',
                'priority' => 'high',
                'status' => 'open',
                'due_at' => '2026-02-10',
            ]
        );
    }
}
