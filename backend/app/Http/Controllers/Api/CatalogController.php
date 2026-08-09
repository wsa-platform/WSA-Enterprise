<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{Branch, Category, Customer, Product, Supplier, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    private const MODULES = [
        'customers' => [Customer::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'email'=>['nullable','email'], 'phone'=>['nullable','string'], 'tax_number'=>['nullable','string'], 'billing_address'=>['nullable','string'], 'credit_limit'=>['numeric','min:0'], 'is_active'=>['boolean']], []],
        'suppliers' => [Supplier::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'email'=>['nullable','email'], 'phone'=>['nullable','string'], 'tax_number'=>['nullable','string'], 'address'=>['nullable','string'], 'is_active'=>['boolean']], []],
        'categories' => [Category::class, ['parent_id'=>['nullable','integer','exists:categories,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'description'=>['nullable','string']], ['parent_id'=>Category::class]],
        'products' => [Product::class, ['category_id'=>['nullable','integer','exists:categories,id'], 'sku'=>['required','string','max:64'], 'name'=>['required','string','max:255'], 'description'=>['nullable','string'], 'unit'=>['sometimes','string','max:16'], 'cost_price'=>['numeric','min:0'], 'sale_price'=>['numeric','min:0'], 'reorder_level'=>['numeric','min:0'], 'is_active'=>['boolean']], ['category_id'=>Category::class]],
        'warehouses' => [Warehouse::class, ['branch_id'=>['nullable','integer','exists:branches,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'address'=>['nullable','string'], 'is_active'=>['boolean']], ['branch_id'=>Branch::class]],
    ];

    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->config($module);
        $data = $request->validate($rules);
        OrganizationScopeValidator::assert($this->organization($request), $data, $relations);

        return $data;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');
        [$class] = $this->config($module);

        return $this->paginateQuery($request, $class::where('organization_id', $this->organization($request))->latest());
    }

    public function store(Request $request, string $module): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        [$class] = $this->config($module);

        return response()->json($class::create(['organization_id'=>$this->organization($request), ...$this->validatedPayload($request, $module)]), 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        [$class] = $this->config($module);
        $record = $class::where('organization_id', $this->organization($request))->findOrFail($id);
        $record->update($this->validatedPayload($request, $module));

        return response()->json($record);
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        [$class] = $this->config($module);
        $class::where('organization_id', $this->organization($request))->findOrFail($id)->delete();

        return response()->json(status: 204);
    }
}
