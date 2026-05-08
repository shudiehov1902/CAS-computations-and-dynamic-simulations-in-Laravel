<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnonymousToken
{
    public const COOKIE_NAME = 'anonymous_user_token';

    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        $userToken = $this->resolveToken($request->cookie(self::COOKIE_NAME));

        $request->attributes->set('user_token', $userToken);

        $response = $next($request);

        $response->headers->setCookie(cookie(
            self::COOKIE_NAME,
            $userToken,
            self::COOKIE_LIFETIME_MINUTES,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));

        return $response;
    }

    private function resolveToken(mixed $existingToken): string
    {
        if (is_string($existingToken) && Str::isUuid($existingToken)) {
            return $existingToken;
        }

        return (string) Str::uuid();
    }
}
