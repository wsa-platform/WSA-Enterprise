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
            'status' => 'pending',
            'input' => ['content' => 'Sample crop advisory note.'],
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
            'input' => ['content' => 'Sample crop advisory note.'],
        ]);

        (new ProcessAiRequest($record->id))->handle(app(\App\Services\Ai\AiService::class));

        $record->refresh();
        $this->assertSame('completed', $record->status);
        $this->assertNotNull($record->output);
    }

    public function test_ai_service_dispatch_for_processing_queues_job(): void
    {
        Queue::fake();

        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);

        $record = app(\App\Services\Ai\AiService::class)->dispatchForProcessing(
            $organization->id,
            'library_summary',
            ['content' => 'Sample crop advisory note.'],
            $user->id,
        );

        $this->assertSame('pending', $record->status);
        Queue::assertPushed(ProcessAiRequest::class, fn (ProcessAiRequest $job) => $job->aiRequestId === $record->id);
    }

    public function test_process_ai_request_job_failed_marks_record(): void
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);

        $record = AiRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'pending',
            'input' => ['content' => 'Sample crop advisory note.'],
        ]);

        (new ProcessAiRequest($record->id))->failed(new \RuntimeException('Worker exhausted retries'));

        $record->refresh();
        $this->assertSame('failed', $record->status);
        $this->assertStringContainsString('Worker exhausted retries', (string) $record->error_message);
    }
}
