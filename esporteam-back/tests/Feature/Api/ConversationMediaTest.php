<?php

use App\Contracts\ConversationMedia\ContentSafetyScanner;
use App\Contracts\ConversationMedia\ImageNormalizer;
use App\Contracts\ConversationMedia\MalwareScanner;
use App\Contracts\ConversationMedia\MediaStorage;
use App\Models\ConversationMedia;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Services\ConversationMediaService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('features.event_social_chat', true);
    Queue::fake();
    $storage = new class implements MediaStorage {
        public array $files = [];
        public function temporaryUploadUrl(string $key, \DateTimeInterface $expiresAt, string $mime): string { return 'https://uploads.test/'.$key; }
        public function exists(string $key): bool { return isset($this->files[$key]); }
        public function read(string $key): string { return $this->files[$key]; }
        public function write(string $key, string $contents, string $mime): void { $this->files[$key] = $contents; }
        public function delete(string $key): void { unset($this->files[$key]); }
        public function temporaryReadUrl(string $key, \DateTimeInterface $expiresAt): string { return 'https://media.test/'.$key; }
        public function acceptSignedUpload(string $key, int $expires, string $signature, string $contents, ?string $mime): bool { $this->files[$key] = $contents; return true; }
    };
    app()->instance(MediaStorage::class, $storage);
    app()->instance(MalwareScanner::class, new class implements MalwareScanner { public function isSafe(string $contents): bool { return true; } });
    app()->instance(ContentSafetyScanner::class, new class implements ContentSafetyScanner { public function isSafe(string $contents, string $mime): bool { return true; } });
    app()->instance(ImageNormalizer::class, new class implements ImageNormalizer { public function normalize(string $contents, string $mime): array { return ['safe' => $contents, 'thumbnail' => $contents, 'mime' => 'image/png']; } });
});

function mediaConversationSession(SportProfile $host): SportSession
{
    return SportSession::query()->create(['creator_profile_id' => $host->id, 'title' => 'Corrida segura', 'type' => 'corrida', 'starts_at' => now()->addDay(), 'visibility' => 'public', 'status' => 'open']);
}

it('processes an approved image and attaches at most four approved photos to a message', function () {
    $host = SportProfile::query()->create(['user_id' => 3101, 'display_name' => 'Lia']);
    $session = mediaConversationSession($host); $headers = ['id' => $host->user_id, 'is_adult' => true];
    $prepared = actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/media/prepare", ['mime' => 'image/png'])->assertCreated()->json('data');
    expect($prepared['upload_url'])->toStartWith('https://uploads.test/');
    $media = ConversationMedia::query()->find($prepared['media']['id']);
    app(MediaStorage::class)->files[$media->upload_key] = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL6aQAAAABJRU5ErkJggg==');
    app(ConversationMediaService::class)->processUpload($media->id);
    expect($media->fresh()->status)->toBe('approved');

    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/messages", ['client_message_id' => '503c2b63-8a64-4e8c-ae0a-591ff3396c2a', 'media_ids' => [$media->id]])
        ->assertCreated()->assertJsonPath('data.body', '')->assertJsonPath('data.media.0.status', 'approved');
});

it('rejects forged image content before it can be attached', function () {
    $host = SportProfile::query()->create(['user_id' => 3111, 'display_name' => 'Noa']);
    $session = mediaConversationSession($host); $headers = ['id' => $host->user_id, 'is_adult' => true];
    $prepared = actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/media/prepare", ['mime' => 'image/png'])->assertCreated()->json('data');
    $media = ConversationMedia::query()->find($prepared['media']['id']); app(MediaStorage::class)->files[$media->upload_key] = 'not an image';
    app(ConversationMediaService::class)->processUpload($media->id);
    expect($media->fresh()->status)->toBe('rejected')->and($media->fresh()->rejection_code)->toBe('invalid_image');
    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/messages", ['client_message_id' => '5d5fb9c8-4fdd-48bd-8a52-3eb13b40958b', 'media_ids' => [$media->id]])->assertUnprocessable();
});
