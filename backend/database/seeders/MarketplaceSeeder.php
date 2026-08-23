<?php

namespace Database\Seeders;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'produce', 'name' => 'Fresh Produce', 'name_ar' => 'منتجات طازجة', 'sort_order' => 1],
            ['slug' => 'equipment', 'name' => 'Farm Equipment', 'name_ar' => 'معدات زراعية', 'sort_order' => 2],
            ['slug' => 'seeds', 'name' => 'Seeds & Inputs', 'name_ar' => 'بذور ومدخلات', 'sort_order' => 3],
        ];

        foreach ($categories as $cat) {
            MarketplaceCategory::updateOrCreate(['slug' => $cat['slug']], $cat + ['is_active' => true]);
        }

        $this->call(MarketplaceProductFoundationSeeder::class);

        $org = Organization::where('slug', 'wsa-demo')->first();
        $seller = User::firstOrCreate(
            ['email' => 'seller@wsa.test'],
            ['name' => 'مزارع WSA', 'password' => bcrypt('password')]
        );

        if ($org) {
            $org->members()->syncWithoutDetaching([$seller->id => ['role' => 'member']]);
        }

        $produce = MarketplaceCategory::where('slug', 'produce')->first();

        MarketplaceListing::updateOrCreate(
            ['seller_user_id' => $seller->id, 'title' => 'طماطم عضوية — دفعة تصدير'],
            [
                'organization_id' => $org?->id,
                'category_id' => $produce?->id,
                'description' => 'طماطم عضوية طازجة من مزارع الرياض.',
                'seller_type' => MarketplaceListing::SELLER_LOCAL,
                'status' => MarketplaceListing::STATUS_PUBLISHED,
                'price' => 1500,
                'currency' => 'SAR',
                'country' => 'SA',
                'origin_country' => 'EG',
                'city' => 'الرياض',
                'seller_region' => 'منطقة الرياض',
                'availability' => MarketplaceListing::AVAILABILITY_AVAILABLE_NOW,
                'seller_display_name' => 'مزارع WSA',
                'seller_email' => 'seller@wsa.test',
                'seller_phone' => '+966511111111',
                'seller_verified' => true,
                'export_ready' => true,
                'export_destination' => 'GCC',
                'contact_access_price' => 49,
                'published_at' => now()->subDays(2),
            ]
        );

        MarketplaceListing::updateOrCreate(
            ['seller_user_id' => $seller->id, 'title' => 'معدات ري — مسودة'],
            [
                'organization_id' => $org?->id,
                'category_id' => MarketplaceCategory::where('slug', 'equipment')->value('id'),
                'description' => 'نظام ري بالتنقيط — مسودة.',
                'seller_type' => MarketplaceListing::SELLER_LOCAL,
                'status' => MarketplaceListing::STATUS_DRAFT,
                'price' => 8000,
                'country' => 'SA',
                'city' => 'جدة',
                'seller_display_name' => 'مزارع WSA',
                'seller_email' => 'seller@wsa.test',
                'seller_phone' => '+966511111111',
                'contact_access_price' => 49,
            ]
        );
    }
}
