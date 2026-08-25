<?php

namespace App\Support;

final class InternationalPhone
{
    public const PATTERN = '/^\+[1-9]\d{7,14}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN, trim($value)) === 1;
    }

    public static function normalize(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        $normalized = '+'.$digits;

        return self::isValid($normalized) ? $normalized : null;
    }
}
