<?php

use App\Models\SportProfile;
use App\Models\SportSession;

it('blocks ineligible sport profiles from hosting and joining sessions with a stable code', function () {
    SportProfile::query()->create(['user_id' => 91, 'display_name' => 'Ineligible host']);

    actingAsWorkspace(1, ['id' => 91, 'is_adult' => false])
        ->postJson('/api/sessions', [
            'title' => 'Treino',
            'type' => 'treino',
            'starts_at' => now()->addDay()->toISOString(),
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'adult_eligibility_required');

    $host = SportProfile::query()->create(['user_id' => 90, 'display_name' => 'Eligible host']);
    $session = SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'title' => 'Existing session',
        'type' => 'treino',
        'starts_at' => now()->addDay(),
    ]);
    SportProfile::query()->create(['user_id' => 92, 'display_name' => 'Ineligible participant']);

    actingAsWorkspace(1, ['id' => 92, 'is_adult' => false])
        ->postJson("/api/sessions/{$session->id}/join")
        ->assertForbidden()
        ->assertJsonPath('code', 'adult_eligibility_required');

    actingAsWorkspace(1, ['id' => 92, 'is_adult' => false])
        ->postJson('/api/post-match-actions/session')
        ->assertForbidden()
        ->assertJsonPath('code', 'adult_eligibility_required');
});
