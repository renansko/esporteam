<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvailabilityRequest;
use App\Http\Requests\UpdateProfileSportsRequest;
use App\Http\Requests\UpsertSportProfileRequest;
use App\Http\Resources\SportProfileResource;
use App\Services\SportProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SportProfileController extends Controller
{
    public function __construct(
        private readonly SportProfileService $profiles,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->profiles->findForUser((int) $request->user()->id);

        if (!$profile) {
            return $this->successResponse(null, 'Sport profile not created.');
        }

        return $this->successResponse(new SportProfileResource($profile));
    }

    public function upsert(UpsertSportProfileRequest $request): JsonResponse
    {
        $profile = $this->profiles->upsertForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->successResponse(new SportProfileResource($profile), 'Sport profile saved.');
    }

    public function sports(UpdateProfileSportsRequest $request): JsonResponse
    {
        $profile = $this->profiles->replaceSports(
            (int) $request->user()->id,
            $request->validated('sports'),
        );

        return $this->successResponse(new SportProfileResource($profile), 'Sport preferences saved.');
    }

    public function availability(UpdateAvailabilityRequest $request): JsonResponse
    {
        $profile = $this->profiles->replaceAvailability(
            (int) $request->user()->id,
            $request->validated('windows'),
        );

        return $this->successResponse(new SportProfileResource($profile), 'Availability saved.');
    }
}
