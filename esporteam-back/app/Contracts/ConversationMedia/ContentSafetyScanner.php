<?php

namespace App\Contracts\ConversationMedia;

interface ContentSafetyScanner
{
    public function isSafe(string $contents, string $mime): bool;
}
