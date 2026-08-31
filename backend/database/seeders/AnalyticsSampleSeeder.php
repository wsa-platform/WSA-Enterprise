<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Organization;
use App\Models\PageView;
use App\Models\VisitorLocation;
use App\Models\VisitorSession;
use Illuminate\Database\Seeder;

class AnalyticsSampleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('slug', 'wsa-demo')->first();
        if ($organization === null) {
            return;
        }

        if (AnalyticsEvent::where('organization_id', $organization->id)->exists()) {
            return;
        }

        $sources = ['google', 'direct', 'facebook', 'newsletter', 'referral'];
        $pages = ['/', '/products', '/library', '/training', '/about'];
        $countries = [
            ['country' => 'SA', 'city' => 'Riyadh'],
            ['country' => 'SA', 'city' => 'Jeddah'],
            ['country' => 'AE', 'city' => 'Dubai'],
            ['country' => 'EG', 'city' => 'Cairo'],
        ];

        for ($day = 29; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $sessionsToday = random_int(3, 12);

            for ($s = 0; $s < $sessionsToday; $s++) {
                $source = $sources[array_rand($sources)];
                $session = VisitorSession::create([
                    'organization_id' => $organization->id,
                    'session_id' => 'demo-'.uniqid(),
                    'ip_address' => '127.0.0.1',
                    'referrer' => "https://{$source}.example.com",
                    'user_agent' => 'Mozilla/5.0 Demo',
                    'started_at' => $date->copy()->addMinutes(random_int(0, 1439)),
                ]);

                $geo = $countries[array_rand($countries)];
                VisitorLocation::create([
                    'visitor_session_id' => $session->id,
                    'country' => $geo['country'],
                    'city' => $geo['city'],
                ]);

                $viewCount = random_int(1, min(3, count($pages)));
                $selectedKeys = (array) array_rand($pages, $viewCount);
                foreach ($selectedKeys as $key) {
                    PageView::create([
                        'organization_id' => $organization->id,
                        'visitor_session_id' => $session->id,
                        'path' => $pages[$key],
                        'referrer' => $session->referrer,
                        'viewed_at' => $session->started_at->copy()->addSeconds(random_int(1, 300)),
                    ]);
                }
            }

            AnalyticsEvent::create([
                'organization_id' => $organization->id,
                'event_type' => 'page_view',
                'source' => $sources[array_rand($sources)],
                'payload' => ['demo' => true],
                'occurred_at' => $date->copy()->addHours(random_int(8, 20)),
            ]);
        }
    }
}
