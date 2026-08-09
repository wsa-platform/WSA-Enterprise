<?php

namespace App\Services\Media;

class MediaReferenceService
{
    /** @return array<string, mixed> */
    public function validateAndSanitize(array $data, string $diskField = 'file_disk', string $pathField = 'file_path'): array
    {
        $allowedDisks = config('filesystems.allowed_disks', ['local', 'public']);

        if (isset($data[$diskField]) && ! in_array($data[$diskField], $allowedDisks, true)) {
            abort(422, 'Invalid storage disk.');
        }

        if (isset($data[$pathField])) {
            $data[$pathField] = $this->sanitizePath($data[$pathField]);
        }

        return $data;
    }

    /** @return array{disk: string, reference: string}|null */
    public function toPublicMetadata(?string $disk, ?string $path): ?array
    {
        if (! $disk || ! $path) {
            return null;
        }

        return [
            'disk' => $disk,
            'reference' => basename($this->sanitizePath($path)),
        ];
    }

    private function sanitizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        abort_if(
            str_contains($normalized, '..') || str_starts_with($normalized, '/'),
            422,
            'Invalid file reference.'
        );

        return ltrim($normalized, '/');
    }
}
