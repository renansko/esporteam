<?php

namespace App\Services\ConversationMedia;

use App\Contracts\ConversationMedia\ContentSafetyScanner;

class AwsContentSafetyScanner implements ContentSafetyScanner
{
    public function isSafe(string $contents, string $mime): bool
    {
        try {
            $client = new \Aws\Rekognition\RekognitionClient([
                'version' => 'latest', 'region' => config('conversation-media.aws_region'),
                'credentials' => ['key' => config('conversation-media.aws_key'), 'secret' => config('conversation-media.aws_secret')],
            ]);
            $result = $client->detectModerationLabels(['Image' => ['Bytes' => $contents], 'MinConfidence' => (float) config('conversation-media.moderation_min_confidence', 80)]);
            return count($result['ModerationLabels'] ?? []) === 0;
        } catch (\Throwable) { return false; }
    }
}
