<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexEventConversationRequest;
use App\Http\Requests\StoreEventMessageRequest;
use App\Http\Resources\EventMessageResource;
use App\Models\SportSession;
use App\Services\EventConversationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventConversationController extends Controller
{
    public function __construct(private readonly EventConversationService $conversations) {}

    public function show(IndexEventConversationRequest $request, SportSession $session): JsonResponse
    {
        $this->assertEnabled();
        $data = $this->conversations->openConversation((int) $request->user()->id, $session, $request->integer('cursor') ?: null, $request->integer('limit', 50));
        return $this->successResponse([
            'conversation' => $data['conversation'],
            'messages' => EventMessageResource::collection($data['messages'])->resolve(),
            'next_cursor' => $data['next_cursor'],
        ], 'Conversation opened.');
    }

    public function store(StoreEventMessageRequest $request, SportSession $session): JsonResponse
    {
        $this->assertEnabled();
        $message = $this->conversations->postMessage((int) $request->user()->id, $session, $request->string('body')->toString(), $request->string('client_message_id')->toString());
        return $this->createdResponse(new EventMessageResource($message), 'Message posted.');
    }

    private function assertEnabled(): void
    {
        if (! config('features.event_social_chat', false)) throw new NotFoundHttpException();
    }
}
