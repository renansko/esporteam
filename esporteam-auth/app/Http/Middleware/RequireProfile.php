<?php

namespace App\Http\Middleware;

use App\Enums\UserProfile;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireProfile
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$profiles): Response
    {
        $allowed = array_values(array_intersect($profiles, UserProfile::values()));
        $user = $request->user();

        if (!$user || !$user->hasAnyProfile($allowed)) {
            return $this->errorResponse(__('messages.auth.profile_required'), null, 403);
        }

        return $next($request);
    }
}
