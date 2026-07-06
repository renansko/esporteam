<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (defined('APP_RUNNING_TESTS') && APP_RUNNING_TESTS) {
            return $next($request);
        }

        $token    = $request->header('X-Service-Token');
        $expected = config('services.internal_token');

        if (!$token || !$expected || !hash_equals($expected, $token)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
