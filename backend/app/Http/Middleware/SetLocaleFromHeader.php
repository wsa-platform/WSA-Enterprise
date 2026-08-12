<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    /** @var list<string> */
    public const SUPPORTED_LOCALES = ['en', 'ar', 'tr', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request->header('Accept-Language'));
        app()->setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(?string $header): string
    {
        if ($header === null || $header === '') {
            return 'en';
        }

        foreach (explode(',', $header) as $part) {
            $code = strtolower(trim(explode(';', $part)[0]));
            $primary = substr($code, 0, 2);

            if (in_array($primary, self::SUPPORTED_LOCALES, true)) {
                return $primary;
            }
        }

        return 'en';
    }
}
