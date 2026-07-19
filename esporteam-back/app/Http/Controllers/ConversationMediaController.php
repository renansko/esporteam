<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteConversationMediaUploadRequest;
use App\Http\Requests\PrepareConversationMediaUploadRequest;
use App\Contracts\ConversationMedia\MediaStorage;
use App\Models\ConversationMedia;
use App\Models\SportSession;
use App\Services\ConversationMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationMediaController extends Controller
{
    public function __construct(private readonly ConversationMediaService $media, private readonly MediaStorage $storage) {}
    public function prepare(PrepareConversationMediaUploadRequest $request, SportSession $session): JsonResponse
    {
        $this->assertEnabled();
        return $this->createdResponse($this->media->prepareUpload((int) $request->user()->id, $session, $request->string('mime')->toString()), 'Private upload prepared.');
    }
    public function complete(CompleteConversationMediaUploadRequest $request, SportSession $session): JsonResponse
    {
        $this->assertEnabled();
        return $this->successResponse(['media' => $this->media->completeUpload((int) $request->user()->id, $session, $request->string('upload_id')->toString())], 'Media processing scheduled.');
    }
    public function show(Request $request, ConversationMedia $media): JsonResponse
    {
        $this->assertEnabled();
        $this->media->authorizeRead($media, (int) $request->user()->id);
        return $this->successResponse(['id' => $media->id, 'status' => $media->status, 'rejection_code' => $media->rejection_code, 'url' => $media->status === 'approved' ? $this->media->readUrl($media, (int) $request->user()->id) : null], 'Conversation media state returned.');
    }
    public function localUpload(Request $request, string $key): JsonResponse
    {
        abort_unless(strlen($request->getContent()) <= 10 * 1024 * 1024, 413);
        abort_unless($this->storage->acceptSignedUpload($key, (int) $request->query('expires'), (string) $request->query('signature'), $request->getContent(), $request->header('Content-Type')), 403);
        return response()->json(['success' => true], 201);
    }
    public function localDownload(Request $request, string $key)
    {
        abort(404); // Local development obtains media through the authenticated JSON endpoint; S3 serves production URLs.
    }
    private function assertEnabled(): void { abort_unless(config('features.event_social_chat', false), 404); }
}
