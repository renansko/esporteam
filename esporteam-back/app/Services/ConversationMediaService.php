<?php

namespace App\Services;

use App\Contracts\ConversationMedia\ContentSafetyScanner;
use App\Contracts\ConversationMedia\ImageNormalizer;
use App\Contracts\ConversationMedia\MalwareScanner;
use App\Contracts\ConversationMedia\MediaStorage;
use App\Jobs\ProcessConversationMedia;
use App\Models\ConversationMedia;
use App\Models\EventConversation;
use App\Models\SportSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** @wiki app/brain/services/ConversationMediaService.md */
class ConversationMediaService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    public function __construct(private readonly EventConversationService $conversations, private readonly MediaStorage $storage, private readonly MalwareScanner $malware, private readonly ContentSafetyScanner $safety, private readonly ImageNormalizer $normalizer) {}

    /** @wiki app/brain/functions/ConversationMediaService.md#prepareUpload */
    public function prepareUpload(int $userId, SportSession $session, string $mime): array
    {
        if (! in_array($mime, self::ALLOWED_MIMES, true)) throw ValidationException::withMessages(['mime' => 'Use JPEG, PNG ou WebP.']);
        $conversation = $this->conversations->authorizedConversation($userId, $session);
        $profile = $this->conversations->profileForUser($userId);
        $pending = ConversationMedia::query()->where('event_conversation_id', $conversation->id)->where('author_profile_id', $profile->id)->where('status', 'processing')->count();
        if ($pending >= 4) throw ValidationException::withMessages(['media' => 'Conclua ou remova as quatro fotos pendentes antes de enviar outras.']);
        $uploadId = (string) Str::uuid(); $expiresAt = now()->addMinutes(10); $key = 'conversation-media/uploads/'.$uploadId;
        $media = ConversationMedia::query()->create(['event_conversation_id' => $conversation->id, 'author_profile_id' => $profile->id, 'upload_id' => $uploadId, 'upload_key' => $key, 'declared_mime' => $mime, 'status' => 'processing', 'upload_expires_at' => $expiresAt]);
        return ['media' => $media, 'upload_url' => $this->storage->temporaryUploadUrl($key, $expiresAt, $mime), 'upload_expires_at' => $expiresAt->toISOString()];
    }

    /** @wiki app/brain/functions/ConversationMediaService.md#processUpload */
    public function completeUpload(int $userId, SportSession $session, string $uploadId): ConversationMedia
    {
        $conversation = $this->conversations->authorizedConversation($userId, $session);
        $media = DB::transaction(function () use ($conversation, $uploadId, $userId) {
            $media = ConversationMedia::query()->lockForUpdate()->where('event_conversation_id', $conversation->id)->where('upload_id', $uploadId)->where('author_profile_id', $this->conversations->profileForUser($userId)->id)->firstOrFail();
            if ($media->status === 'processing' && $media->queued_at === null) { $media->update(['queued_at' => now()]); DB::afterCommit(fn () => ProcessConversationMedia::dispatch($media->id)); }
            return $media;
        });
        return $media;
    }

    public function processUpload(int $mediaId): void
    {
        $media = ConversationMedia::query()->lockForUpdate()->findOrFail($mediaId);
        if ($media->status !== 'processing') return;
        try {
            if (! $this->storage->exists($media->upload_key) || $media->upload_expires_at->isPast()) { $this->reject($media, 'upload_incomplete'); return; }
            $contents = $this->storage->read($media->upload_key); $size = strlen($contents);
            $info = @getimagesizefromstring($contents); $mime = is_array($info) ? ($info['mime'] ?? null) : null;
            if ($size === 0 || $size > 10 * 1024 * 1024 || ! in_array($mime, self::ALLOWED_MIMES, true)) { $this->reject($media, 'invalid_image'); return; }
            if (! $this->malware->isSafe($contents)) { $this->reject($media, 'unsafe_file'); return; }
            if (! $this->safety->isSafe($contents, $mime)) { $this->reject($media, 'unsafe_content'); return; }
            $normalized = $this->normalizer->normalize($contents, $mime); $safeKey = 'conversation-media/approved/'.$media->upload_id.'.jpg'; $thumbKey = 'conversation-media/thumbnails/'.$media->upload_id.'.jpg';
            $this->storage->write($safeKey, $normalized['safe'], $normalized['mime']); $this->storage->write($thumbKey, $normalized['thumbnail'], $normalized['mime']); $this->storage->delete($media->upload_key);
            $media->update(['status' => 'approved', 'safe_key' => $safeKey, 'thumbnail_key' => $thumbKey, 'detected_mime' => $mime, 'byte_size' => $size, 'processed_at' => now()]);
        } catch (\Throwable $exception) {
            report($exception); $this->reject($media, 'processing_failed');
        }
    }

    public function approvedForMessage(EventConversation $conversation, int $profileId, array $mediaIds): array
    {
        if (count($mediaIds) > 4) throw ValidationException::withMessages(['media_ids' => 'Uma mensagem aceita no máximo quatro fotos.']);
        $media = ConversationMedia::query()->whereIn('id', $mediaIds)->where('event_conversation_id', $conversation->id)->where('author_profile_id', $profileId)->where('status', 'approved')->whereDoesntHave('messageLink')->get()->keyBy('id');
        if ($media->count() !== count(array_unique($mediaIds))) throw ValidationException::withMessages(['media_ids' => 'Cada foto deve estar aprovada e disponível para esta mensagem.']);
        return collect($mediaIds)->map(fn ($id) => $media[$id])->all();
    }

    public function readUrl(ConversationMedia $media, int $userId): string
    {
        $this->authorizeRead($media, $userId);
        abort_unless($media->status === 'approved' && $media->safe_key !== null, 404);
        return $this->storage->temporaryReadUrl($media->safe_key, now()->addMinutes(5));
    }
    public function authorizeRead(ConversationMedia $media, int $userId): void { $this->conversations->authorizedConversation($userId, $media->conversation->session); }
    private function reject(ConversationMedia $media, string $code): void { $this->storage->delete($media->upload_key); $media->update(['status' => 'rejected', 'rejection_code' => $code, 'processed_at' => now()]); }
}
