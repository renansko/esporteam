<?php

namespace App\Http\Middleware;

use App\Services\AdultCapability;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdultEligibility
{
    public function __construct(private readonly AdultCapability $capability) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->capability->assertAllowed($request->user());
        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => 'adult_eligibility_required',
            ], 403);
        }

        return $next($request);
    }
}
