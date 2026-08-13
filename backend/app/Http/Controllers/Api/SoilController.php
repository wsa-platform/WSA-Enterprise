<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ManagesUserOwnedModules;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{Farm, FarmBlock, FarmField, SoilAnalysis, SoilNutrient, SoilRecommendation};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoilController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ManagesUserOwnedModules;
    use PaginatesOrganizationRecords;

    private const MODULES = [
        'analyses' => [SoilAnalysis::class, ['farm_id'=>['nullable','integer','exists:farms,id'], 'field_id'=>['nullable','integer','exists:farm_fields,id'], 'block_id'=>['nullable','integer','exists:farm_blocks,id'], 'sample_reference'=>['required','string','max:64'], 'sampled_at'=>['required','date'], 'ph'=>['nullable','numeric','between:0,14'], 'ec'=>['nullable','numeric','min:0'], 'organic_matter_percent'=>['nullable','numeric','min:0','max:100'], 'moisture_percent'=>['nullable','numeric','min:0','max:100'], 'laboratory'=>['nullable','string'], 'notes'=>['nullable','string']], ['farm_id'=>Farm::class, 'field_id'=>FarmField::class, 'block_id'=>FarmBlock::class]],
        'nutrients' => [SoilNutrient::class, ['soil_analysis_id'=>['required','integer','exists:soil_analyses,id'], 'nutrient'=>['required','string','max:32'], 'value'=>['required','numeric'], 'unit'=>['required','string','max:32'], 'target_min'=>['nullable','numeric'], 'target_max'=>['nullable','numeric'], 'status'=>['nullable','string','max:32']], ['soil_analysis_id'=>SoilAnalysis::class]],
        'recommendations' => [SoilRecommendation::class, ['soil_analysis_id'=>['nullable','integer','exists:soil_analyses,id'], 'field_id'=>['nullable','integer','exists:farm_fields,id'], 'block_id'=>['nullable','integer','exists:farm_blocks,id'], 'title'=>['required','string','max:255'], 'recommendation'=>['required','string'], 'category'=>['nullable','string','max:64'], 'priority'=>['sometimes','string','max:32'], 'status'=>['sometimes','string','max:32'], 'due_at'=>['nullable','date']], ['soil_analysis_id'=>SoilAnalysis::class, 'field_id'=>FarmField::class, 'block_id'=>FarmBlock::class]],
    ];

    protected function moduleManagePermission(Request $request, string $module): string
    {
        return 'soil.manage';
    }

    protected function moduleViewPermission(Request $request, string $module): string
    {
        return 'soil.view';
    }

    private function config(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->config($module);
        $data = $request->validate($rules);
        $data = $this->ownership()->stripOwnerKeys($data);
        OrganizationScopeValidator::assert($this->organization($request), $data, $relations);

        return $data;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);

        return $this->ownedIndex($request, $module, $class);
    }

    public function store(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);

        return $this->ownedStore($request, $module, $class, $this->validatedPayload($request, $module));
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);

        return $this->ownedUpdate($request, $module, $class, $id, $this->validatedPayload($request, $module));
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);

        return $this->ownedDestroy($request, $module, $class, $id);
    }
}
