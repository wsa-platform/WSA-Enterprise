<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CropHarvest, CropSeason, CropType, CropVariety, CropYield, GrowthStage};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CropController extends Controller
{
    private const MODULES = [
        'types' => [CropType::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'scientific_name'=>['nullable','string'], 'description'=>['nullable','string']]],
        'varieties' => [CropVariety::class, ['crop_type_id'=>['required','integer','exists:crop_types,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'supplier'=>['nullable','string'], 'maturity_days'=>['nullable','integer','min:1'], 'notes'=>['nullable','string']]],
        'seasons' => [CropSeason::class, ['farm_id'=>['nullable','integer','exists:farms,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'starts_at'=>['required','date'], 'ends_at'=>['nullable','date','after_or_equal:starts_at'], 'status'=>['sometimes','string']]],
        'growth-stages' => [GrowthStage::class, ['crop_type_id'=>['nullable','integer','exists:crop_types,id'], 'name'=>['required','string','max:255'], 'sequence'=>['sometimes','integer','min:0'], 'expected_days'=>['nullable','integer','min:0'], 'description'=>['nullable','string']]],
        'harvests' => [CropHarvest::class, ['season_id'=>['nullable','integer','exists:crop_seasons,id'], 'crop_type_id'=>['required','integer','exists:crop_types,id'], 'variety_id'=>['nullable','integer','exists:crop_varieties,id'], 'field_id'=>['nullable','integer','exists:farm_fields,id'], 'block_id'=>['nullable','integer','exists:farm_blocks,id'], 'harvested_at'=>['required','date'], 'quantity'=>['required','numeric','gt:0'], 'unit'=>['sometimes','string','max:16'], 'quality_score'=>['nullable','numeric','between:0,100'], 'notes'=>['nullable','string']]],
        'yields' => [CropYield::class, ['season_id'=>['nullable','integer','exists:crop_seasons,id'], 'crop_type_id'=>['required','integer','exists:crop_types,id'], 'field_id'=>['nullable','integer','exists:farm_fields,id'], 'block_id'=>['nullable','integer','exists:farm_blocks,id'], 'area_hectares'=>['numeric','min:0'], 'expected_quantity'=>['nullable','numeric','min:0'], 'actual_quantity'=>['sometimes','numeric','min:0'], 'unit'=>['sometimes','string','max:16'], 'reported_at'=>['required','date'], 'notes'=>['nullable','string']]],
    ];
    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]),404); return self::MODULES[$module]; }
    private function organization(Request $request): int { return $request->user()->organizations()->firstOrFail()->id; }
    public function index(Request $request,string $module): JsonResponse { [$class]=$this->config($module); return response()->json($class::where('organization_id',$this->organization($request))->latest()->get()); }
    public function store(Request $request,string $module): JsonResponse { [$class,$rules]=$this->config($module); return response()->json($class::create(['organization_id'=>$this->organization($request),...$request->validate($rules)]),201); }
    public function update(Request $request,string $module,int $id): JsonResponse { [$class,$rules]=$this->config($module); $record=$class::where('organization_id',$this->organization($request))->findOrFail($id); $record->update($request->validate($rules)); return response()->json($record); }
    public function destroy(Request $request,string $module,int $id): JsonResponse { [$class]=$this->config($module); $class::where('organization_id',$this->organization($request))->findOrFail($id)->delete(); return response()->json(status:204); }
}
