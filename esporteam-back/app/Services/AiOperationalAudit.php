<?php

namespace App\Services;

use App\Models\AiAuditEvent;
use App\Models\SportProfile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Stores one safe, idempotent operational trace for each AI outcome.
 *
 * @wiki app/brain/services/AiOperationalAudit.md
 */
class AiOperationalAudit
{
    /**
     * @param  array<string, bool|int|string|null>  $metadata
     *
     * @wiki app/brain/functions/AiOperationalAudit.md#record
     */
    public function record(
        string $operation,
        string $outcome,
        ?int $sportProfileId,
        string $idempotencyKey,
        array $metadata = [],
    ): AiAuditEvent {
        if (! in_array($operation, ['bio_generation', 'bio_embedding'], true)
            || ! in_array($outcome, ['succeeded', 'failed', 'rate_limited'], true)
        ) {
            throw new InvalidArgumentException('Unsupported AI audit event.');
        }

        $event = AiAuditEvent::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'sport_profile_id' => $sportProfileId,
                'operation' => $operation,
                'outcome' => $outcome,
                'metadata' => $this->safeMetadata($metadata),
            ],
        );

        if ($event->wasRecentlyCreated) {
            Log::info('ai.operational_audit', [
                'event_id' => $event->id,
                'operation' => $operation,
                'outcome' => $outcome,
                'sport_profile_id' => $sportProfileId,
                'metadata' => $event->metadata,
            ]);
        }

        return $event;
    }

    /**
     * @wiki app/brain/functions/AiOperationalAudit.md#recordBioGenerationRateLimit
     */
    public function recordBioGenerationRateLimit(
        int $userId,
        int $maxAttempts,
        int $decaySeconds,
        int $retryAfterSeconds,
    ): AiAuditEvent {
        $profileId = SportProfile::query()->where('user_id', $userId)->value('id');
        $windowEndsAt = now()->addSeconds($retryAfterSeconds)->timestamp;

        return $this->record('bio_generation', 'rate_limited', $profileId, "bio-generation:rate-limit:{$userId}:{$windowEndsAt}", [
            'provider' => config('bio_assisted.provider', 'openai'),
            'model' => config('bio_assisted.model', 'gpt-4o-mini'),
            'prompt_version' => config('bio_assisted.prompt_version', 'bio_v1'),
            'rate_limit_max_attempts' => $maxAttempts,
            'rate_limit_decay_seconds' => $decaySeconds,
            'rate_limit_retry_after_seconds' => $retryAfterSeconds,
            'fallback_used' => false,
        ]);
    }

    /** @param array<string, bool|int|string|null> $metadata */
    private function safeMetadata(array $metadata): array
    {
        $allowed = [
            'provider' => 'string',
            'model' => 'string',
            'prompt_version' => 'string',
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'tokens_total' => 'integer',
            'duration_ms' => 'integer',
            'failure_category' => 'string',
            'retry_attempt' => 'integer',
            'retry_scheduled' => 'boolean',
            'fallback_used' => 'boolean',
            'rate_limit_max_attempts' => 'integer',
            'rate_limit_decay_seconds' => 'integer',
            'rate_limit_retry_after_seconds' => 'integer',
        ];

        $safe = [];
        foreach ($allowed as $key => $type) {
            if (! array_key_exists($key, $metadata) || $metadata[$key] === null) {
                continue;
            }

            $safe[$key] = match ($type) {
                'boolean' => (bool) $metadata[$key],
                'integer' => max(0, (int) $metadata[$key]),
                default => mb_substr((string) $metadata[$key], 0, 120),
            };
        }

        return $safe;
    }
}
