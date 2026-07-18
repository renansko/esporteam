<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexSessionRecommendationRequest;
use App\Http\Requests\IndexSportSessionRequest;
use App\Http\Requests\PublishOneOffSportSessionRequest;
use App\Http\Requests\PublishSportSessionSeriesRequest;
use App\Http\Requests\StoreSessionInvitesRequest;
use App\Http\Requests\StoreSportSessionRequest;
use App\Http\Requests\UpdateSessionInviteRequest;
use App\Http\Requests\UpdateSessionParticipantRequest;
use App\Http\Resources\SessionRecommendationResource;
use App\Http\Resources\SportSessionResource;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\SportSessionSeries;
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
        $filters['_recurring_events'] = config('features.recurring_events', false);
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

    public function publishSeries(PublishSportSessionSeriesRequest $request): JsonResponse
    {
        $published = $this->sessions->publishSeries(
            (int) $request->user()->id,
            $request->validated(),
            $request->validated('idempotency_key'),
        );

        return $this->createdResponse([
            'series' => [
                'id' => $published['series']->id, 'timezone' => $published['series']->timezone,
                'interval_weeks' => $published['series']->interval_weeks, 'weekdays' => $published['series']->weekdays,
                'ends_type' => $published['series']->ends_type,
            ],
            'occurrences' => SportSessionResource::collection($published['occurrences'])->resolve(),
        ], 'Session series published.');
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
        $session = $this->sessions->joinOccurrence((int) $request->user()->id, $session);

        return $this->createdResponse(new SportSessionResource($session), 'Session joined.');
    }

    public function followSeries(Request $request, SportSessionSeries $series): JsonResponse
    {
        $series = $this->sessions->followSeries((int) $request->user()->id, $series);

        return $this->createdResponse(['id' => $series->id, 'following' => true], 'Session series followed.');
    }

    public function unfollowSeries(Request $request, SportSessionSeries $series): JsonResponse
    {
        $this->sessions->unfollowSeries((int) $request->user()->id, $series);

        return $this->successResponse(['id' => $series->id, 'following' => false], 'Session series unfollowed.');
    }

    public function events(Request $request): JsonResponse
    {
        $events = $this->sessions->eventsForUser((int) $request->user()->id);

        return $this->successResponse([
            'upcoming_confirmed' => SportSessionResource::collection($events['upcoming_confirmed'])->resolve(),
            'pending_approval' => SportSessionResource::collection($events['pending_approval'])->resolve(),
            'followed_series' => $events['followed_series']->map(fn (SportSessionSeries $series) => ['id' => $series->id, 'title' => $series->title, 'next_occurrences' => SportSessionResource::collection($series->occurrences)->resolve()])->values(),
            'hosted' => SportSessionResource::collection($events['hosted'])->resolve(),
        ], 'Events loaded.');
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
