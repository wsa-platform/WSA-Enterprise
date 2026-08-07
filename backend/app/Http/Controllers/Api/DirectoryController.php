<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    private const MODULES = [
        'companies' => [Company::class, ['name' => ['required', 'string', 'max:255'], 'legal_name' => ['nullable', 'string'], 'tax_number' => ['nullable', 'string', 'max:80'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']]],
        'branches' => [Branch::class, ['company_id' => ['required', 'integer', 'exists:companies,id'], 'name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:32'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']]],
        'employees' => [Employee::class, ['company_id' => ['required', 'integer', 'exists:companies,id'], 'branch_id' => ['nullable', 'integer', 'exists:branches,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id'], 'employee_number' => ['required', 'string', 'max:32'], 'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'email' => ['nullable', 'email'], 'title' => ['nullable', 'string', 'max:150'], 'status' => ['sometimes', 'string', 'max:32'], 'hired_at' => ['nullable', 'date']]],
    ];

    private function module(string $module): array
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);
        return self::MODULES[$module];
    }

    private function organization(Request $request): int
    {
        return $request->user()->organizations()->firstOrFail()->id;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        [$class] = $this->module($module);
        return response()->json($class::where('organization_id', $this->organization($request))->latest()->get());
    }

    public function store(Request $request, string $module): JsonResponse
    {
        [$class, $rules] = $this->module($module);
        $record = $class::create(['organization_id' => $this->organization($request), ...$request->validate($rules)]);
        return response()->json($record, 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        [$class, $rules] = $this->module($module);
        /** @var Model $record */
        $record = $class::where('organization_id', $this->organization($request))->findOrFail($id);
        $record->update($request->validate($rules));
        return response()->json($record);
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->module($module);
        $class::where('organization_id', $this->organization($request))->findOrFail($id)->delete();
        return response()->json(status: 204);
    }
}
