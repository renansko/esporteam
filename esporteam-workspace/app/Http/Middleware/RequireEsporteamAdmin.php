<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireEsporteamAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!($request->user()->is_esporteam_admin ?? false)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.auth.no_permission'),
            ], 403);
        }

        return $next($request);
    }
}
