<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireEsporteamOwner
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (((int) ($user->permissions ?? 0)) & 4) !== 4) {
            return $this->errorResponse(__('messages.auth.owner_required'), null, 403);
        }

        return $next($request);
    }
}
