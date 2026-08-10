<?php

namespace Tests\Unit;

use App\Services\Audit\AuditService;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    public function test_sensitive_values_are_removed_from_audit_metadata(): void
    {
        $service = new AuditService;

        $sanitized = $service->sanitize([
            'email' => 'user@example.com',
            'password' => 'secret',
            'token' => 'abc123',
            'name' => 'Example',
        ]);

        $this->assertSame([
            'email' => 'user@example.com',
            'name' => 'Example',
        ], $sanitized);
    }
}
