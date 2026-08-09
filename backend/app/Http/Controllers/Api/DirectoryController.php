<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\{Branch, Company, Employee};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    use AuthorizesOrganizationAccess;

    private const MODULES = [
        'companies' => [Company::class, ['name' => ['required', 'string', 'max:255'], 'legal_name' => ['nullable', 'string'], 'tax_number' => ['nullable', 'string', 'max:80'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']], []],
        'branches' => [Branch::class, ['company_id' => ['required', 'integer', 'exists:companies,id'], 'name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:32'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']], ['company_id' => Company::class]],
        'employees' => [Employee::class, ['company_id' => ['required', 'integer', 'exists:companies,id'], 'branch_id' => ['nullable', 'integer', 'exists:branches,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id'], 'employee_number' => ['required', 'string', 'max:32'], 'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'email' => ['nullable', 'email'], 'title' => ['nullable', 'string', 'max:150'], 'status' => ['sometimes', 'string', 'max:32'], 'hired_at' => ['nullable', 'date']], ['company_id' => Company::class, 'branch_id' => Branch::class]],
    ];

    private function module(string $module): array
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);

        return self::MODULES[$module];
    }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->module($module);
        $data = $request->validate($rules);
        OrganizationScopeValidator::assert($this->organization($request), $data, $relations);

        return $data;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');
        [$class] = $this->module($module);

        return response()->json($class::where('organization_id', $this->organization($request))->latest()->get());
    }

    public function store(Request $request, string $module): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        [$class] = $this->module($module);
        $record = $class::create(['organization_id' => $this->organization($request), ...$this->validatedPayload($request, $module)]);

        return response()->json($record, 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        [$class] = $this->module($module);
        /** @var Model $record */
        $record = $class::where('organization_id', $this->organization($request))->findOrFail($id);
        $record->update($this->validatedPayload($request, $module));

        return response()->json($record);
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        [$class] = $this->module($module);
        $class::where('organization_id', $this->organization($request))->findOrFail($id)->delete();

        return response()->json(status: 204);
    }
}
