<?php

namespace App\Contracts\ConversationMedia;

interface ImageNormalizer
{
    /** Returns a metadata-free safe image and thumbnail. */
    public function normalize(string $contents, string $mime): array;
}
