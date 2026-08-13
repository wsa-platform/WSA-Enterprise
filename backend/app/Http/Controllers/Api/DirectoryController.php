<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ManagesUserOwnedModules;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{Branch, Company, Employee};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ManagesUserOwnedModules;
    use PaginatesOrganizationRecords;

    private const MODULES = [
        'companies' => [Company::class, ['name' => ['required', 'string', 'max:255'], 'legal_name' => ['nullable', 'string'], 'tax_number' => ['nullable', 'string', 'max:80'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']], []],
        'branches' => [Branch::class, ['company_id' => ['required', 'integer', 'exists:companies,id'], 'name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:32'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'is_active' => ['boolean']], ['company_id' => Company::class]],
        'employees' => [Employee::class, ['company_id' => ['required', 'integer', 'exists:companies,id'], 'branch_id' => ['nullable', 'integer', 'exists:branches,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id'], 'employee_number' => ['required', 'string', 'max:32'], 'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'email' => ['nullable', 'email'], 'title' => ['nullable', 'string', 'max:150'], 'status' => ['sometimes', 'string', 'max:32'], 'hired_at' => ['nullable', 'date']], ['company_id' => Company::class, 'branch_id' => Branch::class]],
    ];

    protected function moduleManagePermission(Request $request, string $module): string
    {
        return 'business.manage';
    }

    protected function moduleViewPermission(Request $request, string $module): string
    {
        return 'business.view';
    }

    private function module(string $module): array
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);

        return self::MODULES[$module];
    }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->module($module);
        $data = $request->validate($rules);
        $data = $this->ownership()->stripOwnerKeys($data);
        OrganizationScopeValidator::assert($this->organization($request), $data, $relations);

        return $data;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        [$class] = $this->module($module);

        return $this->ownedIndex($request, $module, $class);
    }

    public function store(Request $request, string $module): JsonResponse
    {
        [$class] = $this->module($module);

        return $this->ownedStore($request, $module, $class, $this->validatedPayload($request, $module));
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->module($module);

        return $this->ownedUpdate($request, $module, $class, $id, $this->validatedPayload($request, $module));
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->module($module);

        return $this->ownedDestroy($request, $module, $class, $id);
    }
}
