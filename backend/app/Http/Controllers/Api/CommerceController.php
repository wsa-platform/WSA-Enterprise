<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{AppNotification, Customer, Invoice, Product, SalesOrder, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommerceController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function salesOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');

        return $this->paginateQuery($request, SalesOrder::where('organization_id', $this->organization($request))->with('items')->latest());
    }

    public function invoices(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');

        return $this->paginateQuery($request, Invoice::where('organization_id', $this->organization($request))->with('items')->latest());
    }

    public function storeSalesOrder(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organization = $this->organization($request);
        $data = $request->validate([
            'customer_id'=>['required','integer','exists:customers,id'], 'warehouse_id'=>['nullable','integer','exists:warehouses,id'], 'number'=>['required','string','max:32'],
            'ordered_at'=>['nullable','date'], 'expected_at'=>['nullable','date'], 'currency'=>['sometimes','string','size:3'], 'notes'=>['nullable','string'],
            'items'=>['required','array','min:1'], 'items.*.product_id'=>['required','integer','exists:products,id'], 'items.*.quantity'=>['required','numeric','gt:0'],
            'items.*.unit_price'=>['required','numeric','min:0'], 'items.*.tax_rate'=>['nullable','numeric','min:0','max:100'],
        ]);
        OrganizationScopeValidator::assert($organization, $data, ['customer_id' => Customer::class, 'warehouse_id' => Warehouse::class]);
        abort_unless(
            Product::where('organization_id', $organization)->whereIn('id', collect($data['items'])->pluck('product_id'))->count() === count($data['items']),
            422
        );

        return DB::transaction(function () use ($data, $organization) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $tax = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price'] * (($item['tax_rate'] ?? 0) / 100));
            $order = SalesOrder::create(['organization_id'=>$organization, ...$data, 'subtotal'=>$subtotal, 'tax_total'=>$tax, 'total'=>$subtotal+$tax]);
            foreach ($data['items'] as $item) {
                $order->items()->create([...$item, 'tax_rate'=>$item['tax_rate'] ?? 0, 'line_total'=>$item['quantity']*$item['unit_price']*(1+(($item['tax_rate'] ?? 0)/100))]);
            }

            return response()->json($order->load('items'), 201);
        });
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.manage');
        $organization = $this->organization($request);
        $data = $request->validate([
            'customer_id'=>['required','integer','exists:customers,id'], 'sales_order_id'=>['nullable','integer','exists:sales_orders,id'], 'number'=>['required','string','max:32'],
            'issued_at'=>['nullable','date'], 'due_at'=>['nullable','date'], 'currency'=>['sometimes','string','size:3'],
            'items'=>['required','array','min:1'], 'items.*.product_id'=>['nullable','integer','exists:products,id'], 'items.*.description'=>['required','string','max:255'],
            'items.*.quantity'=>['required','numeric','gt:0'], 'items.*.unit_price'=>['required','numeric','min:0'], 'items.*.tax_rate'=>['nullable','numeric','min:0','max:100'],
        ]);
        OrganizationScopeValidator::assert($organization, $data, ['customer_id' => Customer::class, 'sales_order_id' => SalesOrder::class]);

        return DB::transaction(function () use ($data, $organization) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $tax = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price'] * (($item['tax_rate'] ?? 0) / 100));
            $invoice = Invoice::create(['organization_id'=>$organization, ...$data, 'subtotal'=>$subtotal, 'tax_total'=>$tax, 'total'=>$subtotal+$tax]);
            foreach ($data['items'] as $item) {
                $invoice->items()->create([...$item, 'tax_rate'=>$item['tax_rate'] ?? 0, 'line_total'=>$item['quantity']*$item['unit_price']*(1+(($item['tax_rate'] ?? 0)/100))]);
            }

            return response()->json($invoice->load('items'), 201);
        });
    }

    public function report(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');
        $organization = $this->organization($request);

        return response()->json([
            'sales_total' => SalesOrder::where('organization_id', $organization)->whereNotIn('status', ['draft', 'cancelled'])->sum('total'),
            'invoice_total' => Invoice::where('organization_id', $organization)->sum('total'),
            'outstanding_invoices' => Invoice::where('organization_id', $organization)->where('status', 'sent')->count(),
            'open_orders' => SalesOrder::where('organization_id', $organization)->whereNotIn('status', ['fulfilled', 'cancelled'])->count(),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');

        return $this->paginateQuery(
            $request,
            AppNotification::where('organization_id', $this->organization($request))
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))
                ->latest()
        );
    }

    public function readNotification(Request $request, AppNotification $notification): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($notification->organization_id === $this->organization($request), 404);
        $notification->update(['read_at' => now()]);

        return response()->json($notification);
    }
}
