<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{InventoryBalance, InventoryMovement, Product, PurchaseOrder, Supplier, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function inventory(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');

        return $this->paginateQuery(
            $request,
            InventoryBalance::where('organization_id', $this->organization($request))->with(['warehouse:id,name,code', 'product:id,name,sku'])
        );
    }

    public function adjustInventory(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organization = $this->organization($request);
        $data = $request->validate(['warehouse_id'=>['required','integer'], 'product_id'=>['required','integer'], 'quantity'=>['required','numeric','not_in:0'], 'unit_cost'=>['nullable','numeric','min:0'], 'notes'=>['nullable','string']]);
        OrganizationScopeValidator::assert($organization, $data, ['warehouse_id' => Warehouse::class, 'product_id' => Product::class]);

        return DB::transaction(function () use ($data, $organization) {
            $balance = InventoryBalance::firstOrCreate(['warehouse_id'=>$data['warehouse_id'],'product_id'=>$data['product_id']], ['organization_id'=>$organization]);
            $balance->increment('quantity', $data['quantity']);
            $movement = InventoryMovement::create(['organization_id'=>$organization, ...$data, 'type'=>'adjustment', 'unit_cost'=>$data['unit_cost'] ?? 0]);

            return response()->json($movement, 201);
        });
    }

    public function purchaseOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');

        return $this->paginateQuery($request, PurchaseOrder::where('organization_id', $this->organization($request))->with('items')->latest());
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organization = $this->organization($request);
        $data = $request->validate([
            'supplier_id'=>['required','integer','exists:suppliers,id'], 'warehouse_id'=>['required','integer','exists:warehouses,id'], 'number'=>['required','string','max:32'],
            'ordered_at'=>['nullable','date'], 'expected_at'=>['nullable','date'], 'currency'=>['sometimes','string','size:3'], 'notes'=>['nullable','string'],
            'items'=>['required','array','min:1'], 'items.*.product_id'=>['required','integer','exists:products,id'], 'items.*.quantity'=>['required','numeric','gt:0'], 'items.*.unit_cost'=>['required','numeric','min:0'], 'items.*.tax_rate'=>['nullable','numeric','min:0','max:100'],
        ]);
        OrganizationScopeValidator::assert($organization, $data, ['supplier_id' => Supplier::class, 'warehouse_id' => Warehouse::class]);
        abort_unless(
            Product::where('organization_id', $organization)->whereIn('id', collect($data['items'])->pluck('product_id'))->count() === count($data['items']),
            422,
            'One or more products are invalid for this organization.'
        );

        return DB::transaction(function () use ($data, $organization) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);
            $tax = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_cost'] * (($item['tax_rate'] ?? 0) / 100));
            $order = PurchaseOrder::create(['organization_id'=>$organization, ...$data, 'subtotal'=>$subtotal, 'tax_total'=>$tax, 'total'=>$subtotal+$tax]);
            foreach ($data['items'] as $item) {
                $order->items()->create([...$item, 'tax_rate'=>$item['tax_rate'] ?? 0, 'line_total'=>$item['quantity']*$item['unit_cost']*(1+(($item['tax_rate'] ?? 0)/100))]);
            }

            return response()->json($order->load('items'), 201);
        });
    }

    public function receivePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organization = $this->organization($request);
        abort_unless($purchaseOrder->organization_id === $organization, 404);
        $data = $request->validate(['items'=>['required','array','min:1'], 'items.*.id'=>['required','integer'], 'items.*.quantity'=>['required','numeric','gt:0']]);
        DB::transaction(function () use ($purchaseOrder, $data, $organization) {
            foreach ($data['items'] as $receipt) {
                $item = $purchaseOrder->items()->findOrFail($receipt['id']);
                $item->increment('received_quantity', $receipt['quantity']);
                $balance = InventoryBalance::firstOrCreate(['warehouse_id'=>$purchaseOrder->warehouse_id,'product_id'=>$item->product_id], ['organization_id'=>$organization,'average_cost'=>$item->unit_cost]);
                $balance->increment('quantity', $receipt['quantity']);
                InventoryMovement::create(['organization_id'=>$organization,'warehouse_id'=>$purchaseOrder->warehouse_id,'product_id'=>$item->product_id,'type'=>'purchase_receipt','quantity'=>$receipt['quantity'],'unit_cost'=>$item->unit_cost,'reference_type'=>PurchaseOrder::class,'reference_id'=>$purchaseOrder->id]);
            }
            $purchaseOrder->update(['status'=>'received']);
        });

        return response()->json($purchaseOrder->fresh('items'));
    }
}
