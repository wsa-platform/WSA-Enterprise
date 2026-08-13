<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ManagesUserOwnedModules;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{CropType, DiagnosisCategory, DiagnosisDisease, DiagnosisRecommendation, DiagnosisSubject, DiagnosisSymptom};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ManagesUserOwnedModules;
    use PaginatesOrganizationRecords;

    private const MODULES = [
        'categories' => [DiagnosisCategory::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'description'=>['nullable','string'], 'is_active'=>['boolean']], []],
        'subjects' => [DiagnosisSubject::class, ['category_id'=>['nullable','integer','exists:diagnosis_categories,id'], 'crop_type_id'=>['nullable','integer','exists:crop_types,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'subject_type'=>['sometimes','string','max:32'], 'description'=>['nullable','string'], 'is_active'=>['boolean']], ['category_id'=>DiagnosisCategory::class, 'crop_type_id'=>CropType::class]],
        'symptoms' => [DiagnosisSymptom::class, ['subject_id'=>['nullable','integer','exists:diagnosis_subjects,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'description'=>['nullable','string']], ['subject_id'=>DiagnosisSubject::class]],
        'diseases' => [DiagnosisDisease::class, ['subject_id'=>['nullable','integer','exists:diagnosis_subjects,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'scientific_name'=>['nullable','string'], 'description'=>['nullable','string'], 'default_severity'=>['sometimes','string','max:32']], ['subject_id'=>DiagnosisSubject::class]],
        'recommendations' => [DiagnosisRecommendation::class, ['diagnosis_result_id'=>['required','integer','exists:diagnosis_results,id'], 'title'=>['required','string','max:255'], 'recommendation'=>['required','string'], 'category'=>['nullable','string','max:64'], 'priority'=>['sometimes','string','max:32'], 'status'=>['sometimes','string','max:32']], ['diagnosis_result_id'=>\App\Models\DiagnosisResult::class]],
    ];

    protected function moduleManagePermission(Request $request, string $module): string
    {
        return 'diagnosis.manage';
    }

    protected function moduleViewPermission(Request $request, string $module): string
    {
        return 'diagnosis.view';
    }

    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }

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
