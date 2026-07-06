<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireEsporteamAdmin
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (((int) ($user->permissions ?? 0)) & 2) !== 2) {
            return $this->errorResponse(__('messages.auth.admin_required'), null, 403);
        }

        return $next($request);
    }
}
