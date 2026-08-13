<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ManagesUserOwnedModules;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{TrainingCertificate, TrainingCourse, TrainingEnrollment, TrainingLesson, TrainingObjective, TrainingProgress, TrainingQuestion, TrainingQuiz};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ManagesUserOwnedModules;
    use PaginatesOrganizationRecords;

    private const MODULES = [
        'courses' => [TrainingCourse::class, ['code'=>['required','string','max:32'], 'title'=>['required','string','max:255'], 'title_ar'=>['nullable','string','max:255'], 'description'=>['nullable','string'], 'description_ar'=>['nullable','string'], 'locale'=>['sometimes','string','max:8'], 'status'=>['sometimes','string','max:32'], 'sort_order'=>['sometimes','integer','min:0']], []],
        'lessons' => [TrainingLesson::class, ['course_id'=>['required','integer','exists:training_courses,id'], 'code'=>['required','string','max:32'], 'title'=>['required','string','max:255'], 'title_ar'=>['nullable','string','max:255'], 'content'=>['nullable','string'], 'content_ar'=>['nullable','string'], 'sort_order'=>['sometimes','integer','min:0'], 'status'=>['sometimes','string','max:32']], ['course_id'=>TrainingCourse::class]],
        'objectives' => [TrainingObjective::class, ['lesson_id'=>['required','integer','exists:training_lessons,id'], 'objective'=>['required','string','max:255'], 'objective_ar'=>['nullable','string','max:255'], 'sort_order'=>['sometimes','integer','min:0']], ['lesson_id'=>TrainingLesson::class]],
        'quizzes' => [TrainingQuiz::class, ['lesson_id'=>['required','integer','exists:training_lessons,id'], 'title'=>['required','string','max:255'], 'title_ar'=>['nullable','string','max:255'], 'passing_score'=>['sometimes','integer','min:0','max:100']], ['lesson_id'=>TrainingLesson::class]],
        'questions' => [TrainingQuestion::class, ['quiz_id'=>['required','integer','exists:training_quizzes,id'], 'question'=>['required','string','max:255'], 'question_ar'=>['nullable','string','max:255'], 'question_type'=>['sometimes','string','max:32'], 'options'=>['nullable','array'], 'correct_answer'=>['required','string','max:255'], 'sort_order'=>['sometimes','integer','min:0']], ['quiz_id'=>TrainingQuiz::class]],
    ];

    protected function moduleManagePermission(Request $request, string $module): string
    {
        return 'training.manage';
    }

    protected function moduleViewPermission(Request $request, string $module): string
    {
        return 'training.view';
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
        $this->authorizePermission($request, $this->moduleViewPermission($request, $module));
        [$class] = $this->config($module);
        $query = $this->ownership()->scopeAccessibleServices(
            $class::query()->where('organization_id', $this->organization($request)),
            $request->user(),
            $this->organization($request),
        )->latest();

        if ($module === 'courses' && $request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        return $this->paginateQuery($request, $query);
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

    public function enrollments(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'training.view');

        return $this->paginateQuery(
            $request,
            $this->ownership()->scopeAccessibleServices(
                TrainingEnrollment::query()->where('organization_id', $this->organization($request)),
                $request->user(),
                $this->organization($request),
            )->with('course')->latest()
        );
    }

    public function enroll(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'training.view');
        $organizationId = $this->organization($request);
        $data = $request->validate(['course_id' => ['required', 'integer', 'exists:training_courses,id']]);
        OrganizationScopeValidator::assert($organizationId, $data, ['course_id' => TrainingCourse::class]);

        $enrollment = TrainingEnrollment::firstOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $data['course_id']],
            [
                'organization_id' => $organizationId,
                'owner_user_id' => $request->user()->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]
        );

        return response()->json($enrollment->load('course'), 201);
    }

    public function completeLesson(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'training.view');
        $organizationId = $this->organization($request);
        $data = $request->validate([
            'enrollment_id' => ['required', 'integer', 'exists:training_enrollments,id'],
            'lesson_id' => ['required', 'integer', 'exists:training_lessons,id'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $enrollment = $this->ownership()->scopeAccessibleServices(
            TrainingEnrollment::query()->where('organization_id', $organizationId),
            $request->user(),
            $organizationId,
        )->findOrFail($data['enrollment_id']);

        OrganizationScopeValidator::assert($organizationId, $data, ['lesson_id' => TrainingLesson::class]);

        $progress = TrainingProgress::updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'lesson_id' => $data['lesson_id']],
            [
                'organization_id' => $organizationId,
                'user_id' => $request->user()->id,
                'owner_user_id' => $request->user()->id,
                'status' => 'completed',
                'score' => $data['score'] ?? null,
                'completed_at' => now(),
            ]
        );

        $totalLessons = TrainingLesson::where('course_id', $enrollment->course_id)->count();
        $completedLessons = TrainingProgress::where('enrollment_id', $enrollment->id)->where('status', 'completed')->count();

        if ($totalLessons > 0 && $completedLessons >= $totalLessons) {
            $enrollment->update(['status' => 'completed', 'completed_at' => now()]);
            TrainingCertificate::firstOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'organization_id' => $organizationId,
                    'user_id' => $request->user()->id,
                    'owner_user_id' => $request->user()->id,
                    'certificate_code' => 'CERT-'.$enrollment->id.'-'.now()->format('Ymd'),
                    'issued_at' => now(),
                    'metadata' => ['course_id' => $enrollment->course_id],
                ]
            );
        }

        return response()->json($progress);
    }
}
