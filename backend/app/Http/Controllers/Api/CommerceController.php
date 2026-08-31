<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Concerns\ScopesOwnedServices;
use App\Http\Controllers\Controller;
use App\Models\{AppNotification, Customer, Invoice, Product, SalesOrder, Warehouse};
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommerceController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;
    use ScopesOwnedServices;

    public function salesOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');

        return $this->paginateQuery(
            $request,
            $this->scopedOwnedQuery($request, SalesOrder::query())->with('items')->latest(),
        );
    }

    public function invoices(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'business.view');

        return $this->paginateQuery(
            $request,
            $this->scopedOwnedQuery($request, Invoice::query())->with('items')->latest(),
        );
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
        OrganizationScopeValidator::assertProductIds($organization, collect($data['items'])->pluck('product_id')->all());

        return DB::transaction(function () use ($data, $organization, $request) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $tax = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price'] * (($item['tax_rate'] ?? 0) / 100));
            $order = SalesOrder::unguarded(fn () => SalesOrder::create([
                ...$this->assignOwnedPayload($request, $data),
                'organization_id'=>$organization,
                'subtotal'=>$subtotal,
                'tax_total'=>$tax,
                'total'=>$subtotal+$tax,
            ]));
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
        OrganizationScopeValidator::assertProductIds($organization, collect($data['items'])->pluck('product_id')->all());

        return DB::transaction(function () use ($data, $organization, $request) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $tax = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price'] * (($item['tax_rate'] ?? 0) / 100));
            $invoice = Invoice::unguarded(fn () => Invoice::create([
                ...$this->assignOwnedPayload($request, $data),
                'organization_id'=>$organization,
                'subtotal'=>$subtotal,
                'tax_total'=>$tax,
                'total'=>$subtotal+$tax,
            ]));
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
        $orders = $this->scopedOwnedQuery($request, SalesOrder::query());
        $invoices = $this->scopedOwnedQuery($request, Invoice::query());

        return response()->json([
            'sales_total' => (clone $orders)->whereNotIn('status', ['draft', 'cancelled'])->sum('total'),
            'invoice_total' => (clone $invoices)->sum('total'),
            'outstanding_invoices' => (clone $invoices)->where('status', 'sent')->count(),
            'open_orders' => (clone $orders)->whereNotIn('status', ['fulfilled', 'cancelled'])->count(),
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
        abort_unless($notification->user_id === null || $notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return response()->json($notification);
    }

    public function storeNotification(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['sometimes', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($data['user_id'] ?? null) {
            abort_unless(
                \App\Models\User::query()
                    ->whereKey($data['user_id'])
                    ->whereHas('organizations', fn ($q) => $q->whereKey($organizationId))
                    ->exists(),
                422,
                'User is not a member of this organization.',
            );
        }

        $notification = app(NotificationService::class)->notify(
            organizationId: $organizationId,
            userId: $data['user_id'] ?? null,
            type: $data['type'] ?? 'admin.broadcast',
            title: $data['title'],
            body: $data['body'],
        );

        return response()->json($notification, 201);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $userId = $request->user()->id;

        $updated = AppNotification::where('organization_id', $organizationId)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['updated' => $updated]);
    }
}
