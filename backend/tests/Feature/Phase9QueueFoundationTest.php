<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiRequest;
use App\Models\AiRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Phase9QueueFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_ai_request_job_can_be_dispatched(): void
    {
        Queue::fake();

        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);

        $record = AiRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'processing',
            'input' => ['text' => 'Sample crop advisory note.'],
        ]);

        ProcessAiRequest::dispatch($record->id);

        Queue::assertPushed(ProcessAiRequest::class, fn (ProcessAiRequest $job) => $job->aiRequestId === $record->id);
    }

    public function test_process_ai_request_job_completes_pending_record(): void
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);

        $record = AiRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'processing',
            'input' => ['text' => 'Sample crop advisory note.'],
        ]);

        (new ProcessAiRequest($record->id))->handle(app(\App\Services\Ai\AiService::class));

        $record->refresh();
        $this->assertSame('completed', $record->status);
        $this->assertNotNull($record->output);
    }
}
