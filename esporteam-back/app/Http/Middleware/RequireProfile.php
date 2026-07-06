<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireProfile
{
    public function handle(Request $request, Closure $next, string ...$profiles): Response
    {
        $profile = (string) ($request->user()->profile ?? 'user');

        if (!in_array($profile, $profiles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Profile is not allowed for this resource.',
            ], 403);
        }

        return $next($request);
    }
}
