<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CropType, DiagnosisCategory, DiagnosisDisease, DiagnosisRecommendation, DiagnosisSubject, DiagnosisSymptom};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisController extends Controller
{
    private const MODULES = [
        'categories' => [DiagnosisCategory::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'description'=>['nullable','string'], 'is_active'=>['boolean']], []],
        'subjects' => [DiagnosisSubject::class, ['category_id'=>['nullable','integer','exists:diagnosis_categories,id'], 'crop_type_id'=>['nullable','integer','exists:crop_types,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'subject_type'=>['sometimes','string','max:32'], 'description'=>['nullable','string'], 'is_active'=>['boolean']], ['category_id'=>DiagnosisCategory::class, 'crop_type_id'=>CropType::class]],
        'symptoms' => [DiagnosisSymptom::class, ['subject_id'=>['nullable','integer','exists:diagnosis_subjects,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'description'=>['nullable','string']], ['subject_id'=>DiagnosisSubject::class]],
        'diseases' => [DiagnosisDisease::class, ['subject_id'=>['nullable','integer','exists:diagnosis_subjects,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255'], 'scientific_name'=>['nullable','string'], 'description'=>['nullable','string'], 'default_severity'=>['sometimes','string','max:32']], ['subject_id'=>DiagnosisSubject::class]],
        'recommendations' => [DiagnosisRecommendation::class, ['diagnosis_result_id'=>['required','integer','exists:diagnosis_results,id'], 'title'=>['required','string','max:255'], 'recommendation'=>['required','string'], 'category'=>['nullable','string','max:64'], 'priority'=>['sometimes','string','max:32'], 'status'=>['sometimes','string','max:32']], ['diagnosis_result_id'=>\App\Models\DiagnosisResult::class]],
    ];

    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }
    private function organization(Request $request): int { return $request->user()->organizations()->firstOrFail()->id; }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->config($module);
        $data = $request->validate($rules);
        AgriculturalScopeValidator::assert($this->organization($request), $data, $relations);
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
        return response()->json($class::create(['organization_id'=>$this->organization($request), ...$this->validatedPayload($request, $module)]), 201);
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
