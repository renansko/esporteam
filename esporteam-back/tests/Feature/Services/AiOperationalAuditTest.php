<?php

use App\Models\AiAuditEvent;
use App\Models\SportProfile;
use App\Services\AiOperationalAudit;

it('persists only the safe audit metadata contract and deduplicates by idempotency key', function () {
    $profile = SportProfile::query()->create(['user_id' => 991, 'display_name' => 'Auditoria']);

    $audit = app(AiOperationalAudit::class);
    $audit->record('bio_generation', 'failed', $profile->id, 'bio-suggestion:991:failed', [
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'prompt_version' => 'bio_v1',
        'tokens_input' => 42,
        'duration_ms' => 120,
        'failure_category' => 'provider_unavailable',
        'retry_attempt' => 1,
        'fallback_used' => false,
        'raw_error' => 'Bearer sk-secret',
        'email' => 'private@example.com',
        'latitude' => '-23.5505',
    ]);
    $audit->record('bio_generation', 'failed', $profile->id, 'bio-suggestion:991:failed', [
        'raw_error' => 'must not replace the first event',
    ]);

    $event = AiAuditEvent::query()->sole();
    expect($event->operation)->toBe('bio_generation')
        ->and($event->outcome)->toBe('failed')
        ->and($event->sport_profile_id)->toBe($profile->id)
        ->and($event->metadata)->toMatchArray([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'prompt_version' => 'bio_v1',
            'tokens_input' => 42,
            'duration_ms' => 120,
            'failure_category' => 'provider_unavailable',
            'retry_attempt' => 1,
            'fallback_used' => false,
        ])
        ->and($event->metadata)->not->toHaveKeys(['raw_error', 'email', 'latitude']);
});
