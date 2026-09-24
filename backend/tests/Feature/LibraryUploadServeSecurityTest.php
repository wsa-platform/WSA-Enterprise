<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Media\LibraryFilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LibraryUploadServeSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_rejects_svg_and_html_and_disallows_inline_svg(): void
    {
        $policy = new LibraryFilePolicy;

        $this->assertFalse($policy->isInlinePreviewable('svg'));
        $this->assertFalse($policy->isInlinePreviewable('html'));
        $this->assertTrue($policy->isInlinePreviewable('pdf'));

        Storage::fake('local');
        $svg = UploadedFile::fake()->create('xss.svg', 10, 'image/svg+xml');
        $this->expectException(ValidationException::class);
        $policy->assertSafeUpload($svg);
    }

    public function test_policy_rejects_html(): void
    {
        $policy = new LibraryFilePolicy;
        $html = UploadedFile::fake()->create('page.html', 10, 'text/html');
        $this->expectException(ValidationException::class);
        $policy->assertSafeUpload($html);
    }

    public function test_policy_accepts_pdf(): void
    {
        $policy = new LibraryFilePolicy;
        $pdf = UploadedFile::fake()->create('guide.pdf', 20, 'application/pdf');
        $policy->assertSafeUpload($pdf);
        $this->assertTrue($policy->isInlinePreviewable('pdf'));
        $this->assertSame('application/pdf', $policy->mimeTypeForExtension('pdf'));
    }
}
