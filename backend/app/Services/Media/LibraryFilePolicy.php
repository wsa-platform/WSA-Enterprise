<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generic Library upload + serve policy.
 * Active content (SVG/HTML/JS) is never stored or inlined.
 */
final class LibraryFilePolicy
{
    public const MAX_UPLOAD_KILOBYTES = 20480;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
        'txt', 'csv',
        'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
    ];

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'text/plain',
        'text/csv',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /** @var list<string> */
    private const INLINE_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt', 'csv',
    ];

    /** @var list<string> */
    private const ACTIVE_CONTENT_EXTENSIONS = [
        'svg', 'html', 'htm', 'js', 'mjs', 'xhtml', 'xml',
    ];

    public function assertSafeUpload(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_UPLOAD_KILOBYTES * 1024) {
            throw ValidationException::withMessages(['file' => ['File exceeds maximum upload size.']]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '' || in_array($extension, self::ACTIVE_CONTENT_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => ['Active content uploads are not allowed.']]);
        }

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => ['Unsupported file type.']]);
        }

        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        if ($mime === '' || ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages(['file' => ['Unsupported media type.']]);
        }
    }

    public function isInlinePreviewable(string $extension): bool
    {
        $extension = strtolower($extension);

        if (in_array($extension, self::ACTIVE_CONTENT_EXTENSIONS, true)) {
            return false;
        }

        return in_array($extension, self::INLINE_EXTENSIONS, true);
    }

    public function mimeTypeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    public function stream(string $disk, string $path, string $fileName): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $disposition = $this->isInlinePreviewable($extension) ? 'inline' : 'attachment';
        $safeName = str_replace(['"', "\r", "\n"], '', $fileName);

        return Storage::disk($disk)->response($path, $safeName, [
            'Content-Type' => $this->mimeTypeForExtension($extension),
            'Content-Disposition' => $disposition.'; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
