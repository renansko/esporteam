<?php

namespace App\Contracts\ConversationMedia;

interface MediaStorage
{
    /** A short-lived, private upload target. */
    public function temporaryUploadUrl(string $key, \DateTimeInterface $expiresAt, string $mime): string;
    public function exists(string $key): bool;
    public function read(string $key): string;
    public function write(string $key, string $contents, string $mime): void;
    public function delete(string $key): void;
    public function temporaryReadUrl(string $key, \DateTimeInterface $expiresAt): string;
    public function acceptSignedUpload(string $key, int $expires, string $signature, string $contents, ?string $mime): bool;
}
