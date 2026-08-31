<?php



namespace App\Http\Controllers\Api;



use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;

use App\Http\Controllers\Concerns\ScopesOwnedServices;

use App\Http\Controllers\Controller;

use App\Models\AuditLog;

use App\Models\CropType;

use App\Models\Farm;

use App\Models\Invoice;

use App\Models\JobSeekerProfile;

use App\Models\MarketplaceListing;

use App\Models\ContactAccessOrder;

use App\Models\MarketplaceEntitlement;

use App\Models\MarketingCampaign;

use App\Models\Product;

use App\Models\SalesOrder;

use App\Models\User;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Symfony\Component\HttpFoundation\StreamedResponse;



class ReportsController extends Controller

{

    use AuthorizesOrganizationAccess;

    use ScopesOwnedServices;



    public function overview(Request $request): JsonResponse

    {

        $this->authorizePermission($request, 'platform.view');

        $organizationId = $this->organization($request);

        $days = min(max((int) $request->query('days', 30), 1), 365);

        $since = now()->subDays($days);



        $orders = $this->scopedOwnedQuery($request, SalesOrder::query());

        $invoices = $this->scopedOwnedQuery($request, Invoice::query());

        $products = $this->scopedOwnedQuery($request, Product::query());

        $farms = $this->scopedOwnedQuery($request, Farm::query());

        $crops = $this->scopedOwnedQuery($request, CropType::query());



        return response()->json([

            'period_days' => $days,

            'generated_at' => now()->toIso8601String(),

            'commerce' => [

                'sales_total' => (clone $orders)->whereNotIn('status', ['draft', 'cancelled'])->sum('total'),

                'invoice_total' => (clone $invoices)->sum('total'),

                'outstanding_invoices' => (clone $invoices)->where('status', 'sent')->count(),

                'open_orders' => (clone $orders)->whereNotIn('status', ['fulfilled', 'cancelled'])->count(),

            ],

            'catalog' => [

                'products_total' => (clone $products)->count(),

                'products_active' => (clone $products)->where('is_active', true)->count(),

            ],

            'agriculture' => [

                'farms_total' => (clone $farms)->count(),

                'crop_types_total' => (clone $crops)->count(),

            ],

            'marketing' => [

                'campaigns_total' => MarketingCampaign::where('organization_id', $organizationId)->count(),

                'campaigns_active' => MarketingCampaign::where('organization_id', $organizationId)

                    ->whereIn('status', ['scheduled', 'processing', 'sent'])

                    ->count(),

            ],

            'access' => [

                'users_total' => User::query()

                    ->whereHas('organizations', fn ($query) => $query->whereKey($organizationId))

                    ->count(),

                'audit_events' => AuditLog::where('organization_id', $organizationId)

                    ->where('created_at', '>=', $since)

                    ->count(),

            ],

            'charts' => [

                'users_over_time' => $this->dailySeries(

                    User::query()

                        ->whereHas('organizations', fn ($query) => $query->whereKey($organizationId))

                        ->where('created_at', '>=', $since),

                    $days,

                ),

                'products_over_time' => $this->dailySeries(

                    (clone $products)->where('created_at', '>=', $since),

                    $days,

                ),

                'audit_over_time' => $this->dailySeries(

                    AuditLog::where('organization_id', $organizationId)->where('created_at', '>=', $since),

                    $days,

                ),

                'campaigns_over_time' => $this->dailySeries(

                    MarketingCampaign::where('organization_id', $organizationId)->where('created_at', '>=', $since),

                    $days,

                ),

            ],

        ]);

    }



    public function recruitment(Request $request): JsonResponse

    {

        $this->authorizePermission($request, 'reports.recruitment');

        $days = min(max((int) $request->query('days', 30), 1), 365);

        $since = now()->subDays($days);



        $profiles = JobSeekerProfile::query();

        $statusCounts = (clone $profiles)

            ->selectRaw('recruitment_status, count(*) as total')

            ->groupBy('recruitment_status')

            ->pluck('total', 'recruitment_status');



        return response()->json([

            'period_days' => $days,

            'generated_at' => now()->toIso8601String(),

            'summary' => [

                'total_profiles' => (clone $profiles)->count(),

                'active_profiles' => (clone $profiles)->where('is_active', true)->count(),

                'new_in_period' => (clone $profiles)->where('created_at', '>=', $since)->count(),

                'hired_total' => (clone $profiles)->where('recruitment_status', JobSeekerProfile::STATUS_HIRED)->count(),

            ],

            'by_status' => $statusCounts,

            'charts' => [

                'profiles_over_time' => $this->dailySeries(

                    (clone $profiles)->where('created_at', '>=', $since),

                    $days,

                ),

            ],

        ]);

    }



    public function marketplace(Request $request): JsonResponse

    {

        $this->authorizePermission($request, 'reports.marketplace');

        $days = min(max((int) $request->query('days', 30), 1), 365);

        $since = now()->subDays($days);



        $listings = MarketplaceListing::query();

        $orders = ContactAccessOrder::query()->where('created_at', '>=', $since);



        return response()->json([

            'period_days' => $days,

            'generated_at' => now()->toIso8601String(),

            'summary' => [

                'listings_total' => (clone $listings)->count(),

                'published' => (clone $listings)->where('status', MarketplaceListing::STATUS_PUBLISHED)->count(),

                'pending_review' => (clone $listings)->where('status', MarketplaceListing::STATUS_PENDING_REVIEW)->count(),

                'contact_orders' => (clone $orders)->count(),

                'contact_orders_paid' => (clone $orders)->where('payment_status', ContactAccessOrder::PAYMENT_PAID)->count(),

                'active_entitlements' => MarketplaceEntitlement::whereNull('revoked_at')->count(),

            ],

            'by_status' => (clone $listings)

                ->selectRaw('status, count(*) as total')

                ->groupBy('status')

                ->pluck('total', 'status'),

            'charts' => [

                'listings_over_time' => $this->dailySeries(

                    (clone $listings)->where('created_at', '>=', $since),

                    $days,

                ),

            ],

        ]);

    }



    public function export(Request $request): StreamedResponse

    {

        $this->authorizePermission($request, 'platform.view');

        $organizationId = $this->organization($request);

        $days = min(max((int) $request->query('days', 30), 1), 365);

        $since = now()->subDays($days);



        $filename = sprintf('wsa-report-%s-%dd.csv', now()->format('Y-m-d'), $days);



        return response()->streamDownload(function () use ($organizationId, $since, $days): void {

            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel / Arabic compatibility

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['section', 'metric', 'value']);



            $orders = SalesOrder::where('organization_id', $organizationId);

            $invoices = Invoice::where('organization_id', $organizationId);



            fputcsv($handle, ['commerce', 'sales_total', (clone $orders)->whereNotIn('status', ['draft', 'cancelled'])->sum('total')]);

            fputcsv($handle, ['commerce', 'invoice_total', (clone $invoices)->sum('total')]);

            fputcsv($handle, ['commerce', 'outstanding_invoices', (clone $invoices)->where('status', 'sent')->count()]);

            fputcsv($handle, ['catalog', 'products_total', Product::where('organization_id', $organizationId)->count()]);

            fputcsv($handle, ['agriculture', 'farms_total', Farm::where('organization_id', $organizationId)->count()]);

            fputcsv($handle, ['agriculture', 'crop_types_total', CropType::where('organization_id', $organizationId)->count()]);

            fputcsv($handle, ['marketing', 'campaigns_total', MarketingCampaign::where('organization_id', $organizationId)->count()]);

            fputcsv($handle, ['recruitment', 'profiles_total', JobSeekerProfile::count()]);

            fputcsv($handle, ['recruitment', 'hired_total', JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_HIRED)->count()]);

            fputcsv($handle, ['marketplace', 'listings_total', MarketplaceListing::count()]);

            fputcsv($handle, ['marketplace', 'published_listings', MarketplaceListing::where('status', MarketplaceListing::STATUS_PUBLISHED)->count()]);

            fputcsv($handle, ['marketplace', 'contact_orders_paid', ContactAccessOrder::where('payment_status', ContactAccessOrder::PAYMENT_PAID)->count()]);

            fputcsv($handle, ['access', 'users_total', User::whereHas('organizations', fn ($q) => $q->whereKey($organizationId))->count()]);

            fputcsv($handle, ['audit', 'events_in_period', AuditLog::where('organization_id', $organizationId)->where('created_at', '>=', $since)->count()]);

            fputcsv($handle, ['meta', 'period_days', $days]);



            fclose($handle);

        }, $filename, [

            'Content-Type' => 'text/csv; charset=UTF-8',

            'Content-Disposition' => 'attachment; filename="'.$filename.'"',

        ]);

    }



    /** @return list<array{date: string, count: int}> */

    private function dailySeries($query, int $days): array

    {

        $driver = DB::connection()->getDriverName();

        $dateExpr = $driver === 'pgsql'

            ? "to_char(created_at, 'YYYY-MM-DD')"

            : "date(created_at)";



        $rows = (clone $query)

            ->selectRaw("{$dateExpr} as day, count(*) as total")

            ->groupBy('day')

            ->orderBy('day')

            ->pluck('total', 'day');



        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {

            $day = now()->subDays($i)->toDateString();

            $series[] = ['date' => $day, 'count' => (int) ($rows[$day] ?? 0)];

        }



        return $series;

    }

}

