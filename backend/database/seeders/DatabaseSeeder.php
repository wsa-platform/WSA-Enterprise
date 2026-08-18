<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@wsa.test'],
            ['name' => 'Avery Morgan', 'password' => Hash::make('password')]
        );
        $member = User::updateOrCreate(
            ['email' => 'member@wsa.test'],
            ['name' => 'Jordan Lee', 'password' => Hash::make('password')]
        );

        $organization = Organization::updateOrCreate(
            ['slug' => 'wsa-demo'],
            ['name' => 'WSA Demo Workspace']
        );
        $organization->members()->syncWithoutDetaching([
            $admin->id => ['role' => 'admin'],
            $member->id => ['role' => 'member'],
        ]);

        $platform = Project::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'PLATFORM'],
            [
                'manager_id' => $admin->id,
                'name' => 'Enterprise Platform',
                'description' => 'Core platform delivery for WSA Enterprise.',
                'status' => 'active',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->addMonths(3),
                'budget' => 120000,
            ]
        );
        $analytics = Project::updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'ANALYTICS'],
            [
                'manager_id' => $member->id,
                'name' => 'Operations Analytics',
                'description' => 'Reporting and operational intelligence.',
                'status' => 'active',
                'starts_at' => now()->subWeeks(2),
                'ends_at' => now()->addMonths(2),
                'budget' => 48000,
            ]
        );

        foreach ([
            [$platform, $admin, 'Configure production environment', 'in_progress', 'high', now()->addDays(2)],
            [$platform, $member, 'Review API access controls', 'todo', 'high', now()->addDays(4)],
            [$platform, $admin, 'Create release checklist', 'done', 'medium', now()->subDay()],
            [$analytics, $member, 'Define dashboard metrics', 'in_progress', 'medium', now()->addDays(1)],
            [$analytics, $admin, 'Validate source data', 'todo', 'low', now()->addDays(6)],
        ] as [$project, $assignee, $title, $status, $priority, $dueAt]) {
            Task::updateOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'assignee_id' => $assignee->id,
                    'status' => $status,
                    'priority' => $priority,
                    'due_at' => $dueAt,
                    'completed_at' => $status === 'done' ? now()->subDay() : null,
                ]
            );
        }

        $this->call(AgriculturalSeeder::class);
        $this->call(Phase5Seeder::class);
        $this->call(BillingSeeder::class);
        $this->call(AccessSeeder::class);
        $this->call(JobSeekerSeeder::class);
        $this->call(MarketplaceSeeder::class);
    }
}
