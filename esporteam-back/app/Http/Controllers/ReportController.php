<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $report = $this->reports->createForUser(
            (int) $request->user()->id,
            (int) $data['reported_profile_id'],
            $data['reason'],
            $data['details'] ?? null,
            array_filter([
                'event_conversation_id' => $data['event_conversation_id'] ?? null,
                'event_message_id' => $data['event_message_id'] ?? null,
                'sport_session_id' => $data['sport_session_id'] ?? null,
            ]),
        );

        return $this->createdResponse(new ReportResource($report), 'Report created.');
    }
}
