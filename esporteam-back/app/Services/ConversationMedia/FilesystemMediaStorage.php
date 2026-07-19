<?php

namespace App\Services\ConversationMedia;

use App\Contracts\ConversationMedia\MediaStorage;
use Illuminate\Support\Facades\Storage;

class FilesystemMediaStorage implements MediaStorage
{
    private string $disk;
    public function __construct() { $this->disk = (string) config('conversation-media.disk', 'local'); }
    public function temporaryUploadUrl(string $key, \DateTimeInterface $expiresAt, string $mime): string
    {
        // S3-compatible disks create a presigned PUT URL. Local uses the signed API endpoint.
        if (config('filesystems.disks.'.$this->disk.'.driver') === 's3') return Storage::disk($this->disk)->temporaryUploadUrl($key, $expiresAt, ['ContentType' => $mime])['url'];
        return route('conversation-media.upload', ['key' => $key, 'expires' => $expiresAt->getTimestamp(), 'signature' => hash_hmac('sha256', 'upload|'.$key.'|'.$expiresAt->getTimestamp(), (string) config('app.key'))]);
    }
    public function exists(string $key): bool { return Storage::disk($this->disk)->exists($key); }
    public function read(string $key): string { return Storage::disk($this->disk)->get($key); }
    public function write(string $key, string $contents, string $mime): void { Storage::disk($this->disk)->put($key, $contents, ['visibility' => 'private', 'ContentType' => $mime]); }
    public function delete(string $key): void { Storage::disk($this->disk)->delete($key); }
    public function temporaryReadUrl(string $key, \DateTimeInterface $expiresAt): string
    {
        if (config('filesystems.disks.'.$this->disk.'.driver') === 's3') return Storage::disk($this->disk)->temporaryUrl($key, $expiresAt);
        return route('conversation-media.download', ['key' => $key, 'expires' => $expiresAt->getTimestamp(), 'signature' => hash_hmac('sha256', 'read|'.$key.'|'.$expiresAt->getTimestamp(), (string) config('app.key'))]);
    }
    public function acceptSignedUpload(string $key, int $expires, string $signature, string $contents, ?string $mime): bool
    {
        if ($expires < now()->timestamp || ! hash_equals(hash_hmac('sha256', 'upload|'.$key.'|'.$expires, (string) config('app.key')), $signature)) return false;
        Storage::disk($this->disk)->put($key, $contents, ['visibility' => 'private', 'ContentType' => $mime]);
        return true;
    }
}
