<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Farm, FarmBlock, FarmField, FarmRegion, GisMap, GpsCoordinate, Greenhouse, IrrigationZone};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    private const MODULES = [
        'farms' => [Farm::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'owner_name'=>['nullable','string'], 'address'=>['nullable','string'], 'area_hectares'=>['numeric','min:0'], 'is_active'=>['boolean']], []],
        'regions' => [FarmRegion::class, ['farm_id'=>['required','integer','exists:farms,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'description'=>['nullable','string'], 'area_hectares'=>['numeric','min:0']], ['farm_id'=>Farm::class]],
        'fields' => [FarmField::class, ['farm_id'=>['required','integer','exists:farms,id'], 'region_id'=>['nullable','integer','exists:farm_regions,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'area_hectares'=>['numeric','min:0'], 'soil_type'=>['nullable','string'], 'status'=>['sometimes','string']], ['farm_id'=>Farm::class, 'region_id'=>FarmRegion::class]],
        'blocks' => [FarmBlock::class, ['field_id'=>['required','integer','exists:farm_fields,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'area_hectares'=>['numeric','min:0'], 'crop'=>['nullable','string'], 'variety'=>['nullable','string'], 'status'=>['sometimes','string']], ['field_id'=>FarmField::class]],
        'greenhouses' => [Greenhouse::class, ['farm_id'=>['required','integer','exists:farms,id'], 'field_id'=>['nullable','integer','exists:farm_fields,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'area_square_meters'=>['numeric','min:0'], 'structure_type'=>['nullable','string'], 'climate_control'=>['nullable','string'], 'status'=>['sometimes','string']], ['farm_id'=>Farm::class, 'field_id'=>FarmField::class]],
        'irrigation-zones' => [IrrigationZone::class, ['farm_id'=>['required','integer','exists:farms,id'], 'field_id'=>['nullable','integer','exists:farm_fields,id'], 'block_id'=>['nullable','integer','exists:farm_blocks,id'], 'greenhouse_id'=>['nullable','integer','exists:greenhouses,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'method'=>['nullable','string'], 'flow_rate_lph'=>['nullable','numeric','min:0'], 'status'=>['sometimes','string']], ['farm_id'=>Farm::class, 'field_id'=>FarmField::class, 'block_id'=>FarmBlock::class, 'greenhouse_id'=>Greenhouse::class]],
        'gps-coordinates' => [GpsCoordinate::class, ['coordinateable_type'=>['required','string','max:255'], 'coordinateable_id'=>['required','integer'], 'latitude'=>['required','numeric','between:-90,90'], 'longitude'=>['required','numeric','between:-180,180'], 'altitude_meters'=>['nullable','numeric'], 'sequence'=>['sometimes','integer','min:0']], []],
        'gis-maps' => [GisMap::class, ['farm_id'=>['nullable','integer','exists:farms,id'], 'name'=>['required','string','max:255'], 'layer_type'=>['required','string','max:64'], 'source_url'=>['nullable','url'], 'geojson'=>['nullable','array'], 'metadata'=>['nullable','array']], ['farm_id'=>Farm::class]],
    ];

    private const COORDINATEABLE = [
        Farm::class,
        FarmField::class,
        FarmBlock::class,
        Greenhouse::class,
    ];

    private function config(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }

    private function organization(Request $request): int
    {
        return $request->user()->organizations()->firstOrFail()->id;
    }

    private function validatedPayload(Request $request, string $module): array
    {
        [$class, $rules, $relations] = $this->config($module);
        $organizationId = $this->organization($request);
        $data = $request->validate($rules);

        AgriculturalScopeValidator::assert($organizationId, $data, $relations);

        if ($module === 'gps-coordinates') {
            abort_unless(in_array($data['coordinateable_type'], self::COORDINATEABLE, true), 422);
            AgriculturalScopeValidator::assert($organizationId, $data, [
                'coordinateable_id' => $data['coordinateable_type'],
            ]);
        }

        return $data;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);

        return response()->json($class::where('organization_id', $this->organization($request))->latest()->get());
    }

    public function store(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);

        return response()->json($class::create([
            'organization_id' => $this->organization($request),
            ...$this->validatedPayload($request, $module),
        ]), 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);
        $record = $class::where('organization_id', $this->organization($request))->findOrFail($id);
        $record->update($this->validatedPayload($request, $module));

        return response()->json($record);
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);
        $class::where('organization_id', $this->organization($request))->findOrFail($id)->delete();

        return response()->json(status: 204);
    }
}
