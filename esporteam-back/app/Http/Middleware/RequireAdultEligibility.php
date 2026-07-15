<?php

namespace App\Http\Middleware;

use App\Services\AdultCapability;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdultEligibility
{
    public function __construct(private readonly AdultCapability $capability) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->capability->assertAllowed($request->user());

        return $next($request);
    }
}
