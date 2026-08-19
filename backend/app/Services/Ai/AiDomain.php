<?php

namespace App\Services\Ai;

use Illuminate\Validation\ValidationException;

class AiDomain
{
    /** @return list<string> */
    public static function allowed(): array
    {
        $configured = config('ai.domains', []);

        return is_array($configured) ? array_values(array_map('strval', $configured)) : [];
    }

    public static function normalize(string $domain): string
    {
        return strtolower(trim($domain));
    }

    public static function isAllowed(string $domain): bool
    {
        return in_array(self::normalize($domain), self::allowed(), true);
    }

    public static function assert(string $domain): string
    {
        $normalized = self::normalize($domain);

        if (! self::isAllowed($normalized)) {
            throw ValidationException::withMessages([
                'domain' => ['Unsupported assistant domain.'],
            ]);
        }

        return $normalized;
    }
}
