<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedApiKey = (string) config('cas.api_key');
        $providedApiKey = $request->header('X-CAS-API-Key');

        if (! is_string($providedApiKey) || ! hash_equals($expectedApiKey, $providedApiKey)) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Invalid or missing API key.',
        ], 401);
    }
}
