<?php

namespace App\Http\Controllers;

use App\Services\ConversationPushService;
use Illuminate\Http\Request;

class ConversationPushController extends Controller
{
    public function __construct(private readonly ConversationPushService $push) {}

    public function show(Request $request)
    {
        $this->enabled();
        return $this->successResponse(['enabled' => $this->push->preference((int) $request->user()->id), 'public_key' => config('services.webpush.public_key')]);
    }

    public function subscribe(Request $request)
    {
        $this->enabled();
        $data = $request->validate(['device_id' => ['required', 'string', 'max:128'], 'endpoint' => ['required', 'url', 'max:2048'], 'keys' => ['required', 'array'], 'keys.p256dh' => ['required', 'string'], 'keys.auth' => ['required', 'string']]);
        return $this->successResponse(['subscription' => $this->push->register((int) $request->user()->id, $data)], 'Push subscription saved.');
    }

    public function preference(Request $request)
    {
        $this->enabled();
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        return $this->successResponse(['enabled' => $this->push->setPreference((int) $request->user()->id, (bool) $data['enabled'])]);
    }

    public function unsubscribe(Request $request)
    {
        $this->enabled();
        $data = $request->validate(['device_id' => ['nullable', 'string', 'max:128']]);
        $this->push->remove((int) $request->user()->id, $data['device_id'] ?? null);
        return $this->successResponse(['removed' => true]);
    }

    private function enabled(): void
    {
        abort_unless(config('features.event_push_notifications', false), 404);
    }
}
