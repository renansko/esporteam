<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSportGroupMemberRequest;
use App\Http\Requests\StoreSportGroupRequest;
use App\Http\Resources\SportGroupResource;
use App\Models\SportGroup;
use App\Models\SportProfile;
use App\Services\SportGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SportGroupController extends Controller
{
    public function __construct(
        private readonly SportGroupService $groups,
    ) {}

    public function index(): JsonResponse
    {
        $groups = SportGroup::query()
            ->with(['creator', 'sport'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->successResponse(SportGroupResource::collection($groups), 'Groups listed.');
    }

    public function store(StoreSportGroupRequest $request): JsonResponse
    {
        $group = $this->groups->createForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->createdResponse(new SportGroupResource($group), 'Group created.');
    }

    public function show(SportGroup $group): JsonResponse
    {
        return $this->successResponse(
            new SportGroupResource($group->load(['creator', 'sport', 'members'])),
            'Group shown.',
        );
    }

    public function addMember(StoreSportGroupMemberRequest $request, SportGroup $group): JsonResponse
    {
        $group = $this->groups->addMember(
            (int) $request->user()->id,
            $group,
            (int) $request->validated('sport_profile_id'),
            $request->validated('role') ?? 'member',
            $request->validated('status') ?? 'active',
        );

        return $this->createdResponse(new SportGroupResource($group), 'Group member saved.');
    }

    public function removeMember(Request $request, SportGroup $group, SportProfile $profile): JsonResponse
    {
        $this->groups->removeMember((int) $request->user()->id, $group, $profile);

        return $this->deletedResponse();
    }
}
