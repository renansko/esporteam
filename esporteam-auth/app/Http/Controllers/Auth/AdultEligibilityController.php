<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteAdultEligibilityRequest;
use App\Http\Resources\UserResource;
use App\Services\AdultEligibilityService;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;

class AdultEligibilityController extends Controller
{
    public function __construct(private readonly AdultEligibilityService $eligibility, private readonly JwtService $jwt) {}

    public function store(CompleteAdultEligibilityRequest $request): JsonResponse
    {
        $user = $this->eligibility->declare($request->user(), $request->validated());

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $this->jwt->encode($user->toArray()),
        ], 'Adult eligibility declared.');
    }
}
