<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexSessionRecommendationRequest;
use App\Http\Requests\IndexSportSessionRequest;
use App\Http\Requests\PublishOneOffSportSessionRequest;
use App\Http\Requests\StoreSessionInvitesRequest;
use App\Http\Requests\StoreSportSessionRequest;
use App\Http\Requests\UpdateSessionInviteRequest;
use App\Http\Requests\UpdateSessionParticipantRequest;
use App\Http\Resources\SessionRecommendationResource;
use App\Http\Resources\SportSessionResource;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Services\DiscoveryCache;
use App\Services\SportSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SportSessionController extends Controller
{
    public function __construct(
        private readonly DiscoveryCache $cache,
        private readonly SportSessionService $sessions,
    ) {}

    public function index(IndexSportSessionRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $filters = $request->validated();
        $sessions = $this->cache->remember(
            'map',
            $userId,
            $filters,
            fn (): array => SportSessionResource::collection(
                $this->sessions->openSessions($userId, $filters),
            )->resolve(),
        );

        return $this->successResponse($sessions, 'Sessions listed.');
    }

    public function store(StoreSportSessionRequest $request): JsonResponse
    {
        $session = $this->sessions->createForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->createdResponse(new SportSessionResource($session), 'Session created.');
    }

    public function publishOneOff(PublishOneOffSportSessionRequest $request): JsonResponse
    {
        $key = (string) $request->header('Idempotency-Key');
        if ($key === '') {
            abort(422, 'Idempotency-Key header is required.');
        }

        $session = $this->sessions->publishOneOff(
            (int) $request->user()->id,
            $request->validated(),
            $key,
        );

        return $this->createdResponse(new SportSessionResource($session), 'Session published.');
    }

    public function show(Request $request, SportSession $session): JsonResponse
    {
        return $this->successResponse(
            new SportSessionResource($this->sessions->detailForUser(
                (int) $request->user()->id,
                $session,
            )),
            'Session detail loaded.',
        );
    }

    public function participantSessions(Request $request): JsonResponse
    {
        return $this->successResponse(
            SportSessionResource::collection($this->sessions->participantSessionsForUser(
                (int) $request->user()->id,
            )),
            'Participant sessions loaded.',
        );
    }

    public function join(Request $request, SportSession $session): JsonResponse
    {
        $session = $this->sessions->join((int) $request->user()->id, $session);

        return $this->createdResponse(new SportSessionResource($session), 'Session joined.');
    }

    public function recommendations(IndexSessionRecommendationRequest $request, SportSession $session): JsonResponse
    {
        return $this->successResponse(
            SessionRecommendationResource::collection($this->sessions->recommendationsForHost(
                (int) $request->user()->id,
                $session,
                $request->validated(),
            )),
            'Session recommendations listed.',
        );
    }

    public function invite(StoreSessionInvitesRequest $request, SportSession $session): JsonResponse
    {
        $session = $this->sessions->inviteProfiles(
            (int) $request->user()->id,
            $session,
            $request->validated('profile_ids'),
        );

        return $this->createdResponse(new SportSessionResource($session), 'Session invites saved.');
    }

    public function respondToInvite(UpdateSessionInviteRequest $request, SportSession $session): JsonResponse
    {
        $session = $this->sessions->respondToInvite(
            (int) $request->user()->id,
            $session,
            $request->validated('action'),
        );

        return $this->successResponse(new SportSessionResource($session), 'Session invite updated.');
    }

    public function updateParticipant(UpdateSessionParticipantRequest $request, SportSession $session, SportProfile $profile): JsonResponse
    {
        $session = $this->sessions->decideParticipant(
            (int) $request->user()->id,
            $session,
            $profile,
            $request->validated('action'),
        );

        return $this->successResponse(new SportSessionResource($session), 'Session participant updated.');
    }
}
