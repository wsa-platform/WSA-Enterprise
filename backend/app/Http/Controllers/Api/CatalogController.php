<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Category, Customer, Product, Supplier, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private const MODULES = [
        'customers' => [Customer::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'email'=>['nullable','email'], 'phone'=>['nullable','string'], 'tax_number'=>['nullable','string'], 'billing_address'=>['nullable','string'], 'credit_limit'=>['numeric','min:0'], 'is_active'=>['boolean']]],
        'suppliers' => [Supplier::class, ['code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'email'=>['nullable','email'], 'phone'=>['nullable','string'], 'tax_number'=>['nullable','string'], 'address'=>['nullable','string'], 'is_active'=>['boolean']]],
        'categories' => [Category::class, ['parent_id'=>['nullable','integer','exists:categories,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'description'=>['nullable','string']]],
        'products' => [Product::class, ['category_id'=>['nullable','integer','exists:categories,id'], 'sku'=>['required','string','max:64'], 'name'=>['required','string','max:255'], 'description'=>['nullable','string'], 'unit'=>['sometimes','string','max:16'], 'cost_price'=>['numeric','min:0'], 'sale_price'=>['numeric','min:0'], 'reorder_level'=>['numeric','min:0'], 'is_active'=>['boolean']]],
        'warehouses' => [Warehouse::class, ['branch_id'=>['nullable','integer','exists:branches,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'address'=>['nullable','string'], 'is_active'=>['boolean']]],
    ];
    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }
    private function organization(Request $request): int { return $request->user()->organizations()->firstOrFail()->id; }
    public function index(Request $request, string $module): JsonResponse { [$class] = $this->config($module); return response()->json($class::where('organization_id', $this->organization($request))->latest()->get()); }
    public function store(Request $request, string $module): JsonResponse { [$class, $rules] = $this->config($module); return response()->json($class::create(['organization_id'=>$this->organization($request), ...$request->validate($rules)]), 201); }
    public function update(Request $request, string $module, int $id): JsonResponse { [$class, $rules] = $this->config($module); $record=$class::where('organization_id',$this->organization($request))->findOrFail($id); $record->update($request->validate($rules)); return response()->json($record); }
    public function destroy(Request $request, string $module, int $id): JsonResponse { [$class] = $this->config($module); $class::where('organization_id',$this->organization($request))->findOrFail($id)->delete(); return response()->json(status: 204); }
}
