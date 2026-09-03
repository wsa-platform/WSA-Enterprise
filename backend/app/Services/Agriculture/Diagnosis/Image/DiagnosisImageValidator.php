<?php

namespace App\Services\Agriculture\Diagnosis\Image;

use App\Services\Agriculture\Diagnosis\PlantImageMetadata;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Server-side image validation for Plant AI Diagnosis.
 * Does not trust client MIME; never exposes filesystem paths.
 */
class DiagnosisImageValidator
{
    /** @var list<string> */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private readonly int $maxBytes = 5_242_880,
        private readonly int $minWidth = 64,
        private readonly int $minHeight = 64,
    ) {}

    /**
     * @return array{binary: string, metadata: PlantImageMetadata}
     */
    public function validateUploadedFile(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'image' => ['Uploaded image is invalid or incomplete.'],
            ]);
        }

        $path = $file->getRealPath();
        if ($path === false || $path === '' || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'image' => ['Uploaded image could not be read.'],
            ]);
        }

        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'image' => ['Uploaded image content is empty or unreadable.'],
            ]);
        }

        return $this->validateBinary(
            $binary,
            originalClientName: (string) $file->getClientOriginalName(),
            clientClaimedMime: (string) ($file->getClientMimeType() ?: ''),
        );
    }

    /**
     * @return array{binary: string, metadata: PlantImageMetadata}
     */
    public function validateBase64(string $base64, string $originalClientName = 'upload.bin', string $clientClaimedMime = ''): array
    {
        $normalized = preg_replace('#^data:image/[a-zA-Z0-9.+-]+;base64,#', '', trim($base64)) ?? '';
        $binary = base64_decode($normalized, true);

        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages([
                'image_base64' => ['Image payload is not valid base64 image data.'],
            ]);
        }

        return $this->validateBinary($binary, $originalClientName, $clientClaimedMime);
    }

    /**
     * @return array{binary: string, metadata: PlantImageMetadata}
     */
    public function validateBinary(string $binary, string $originalClientName = '', string $clientClaimedMime = ''): array
    {
        $size = strlen($binary);
        if ($size === 0) {
            throw ValidationException::withMessages([
                'image' => ['Image content is empty.'],
            ]);
        }

        if ($size > $this->maxBytes) {
            throw ValidationException::withMessages([
                'image' => ['Image exceeds maximum allowed size.'],
            ]);
        }

        $detectedMime = $this->detectMime($binary);
        if (! in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'image' => ['Unsupported or unrecognized image type. Allowed: JPEG, PNG, WebP.'],
            ]);
        }

        $dimensions = @getimagesizefromstring($binary);
        if ($dimensions === false || ! isset($dimensions[0], $dimensions[1])) {
            throw ValidationException::withMessages([
                'image' => ['Image appears corrupted or is not a readable raster image.'],
            ]);
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        if ($width < $this->minWidth || $height < $this->minHeight) {
            throw ValidationException::withMessages([
                'image' => ['Image resolution is too low for diagnosis analysis.'],
            ]);
        }

        $metadata = new PlantImageMetadata(
            contentHash: hash('sha256', $binary),
            detectedMime: $detectedMime,
            sizeBytes: $size,
            width: $width,
            height: $height,
            originalClientName: $originalClientName,
            clientClaimedMime: $clientClaimedMime,
        );

        return [
            'binary' => $binary,
            'metadata' => $metadata,
        ];
    }

    private function detectMime(string $binary): string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($binary);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }

        $info = @getimagesizefromstring($binary);
        if (is_array($info) && isset($info['mime']) && is_string($info['mime'])) {
            return strtolower($info['mime']);
        }

        return 'application/octet-stream';
    }
}
