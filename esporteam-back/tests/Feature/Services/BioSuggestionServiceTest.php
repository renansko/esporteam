<?php

use App\Ai\Agents\BioAssistant;
use App\Enums\BioSuggestionStatus;
use App\Exceptions\BioSuggestionGenerationFailed;
use App\Models\BioSuggestion;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Services\BioSuggestionService;

it('stores provider failures without exposing raw error details', function () {
    $profile = SportProfile::query()->create(['user_id' => 707, 'display_name' => 'Falha']);
    $sport = Sport::query()->create(['name' => 'Vôlei', 'slug' => 'volei']);
    $profile->sports()->create(['sport_id' => $sport->id, 'level' => 'advanced', 'goals' => ['jogar']]);

    BioAssistant::fake(function () {
        throw new RuntimeException('private vendor payload');
    });

    expect(fn () => app(BioSuggestionService::class)->createForUser(707))
        ->toThrow(BioSuggestionGenerationFailed::class);

    $suggestion = BioSuggestion::query()->first();
    expect($suggestion->status)->toBe(BioSuggestionStatus::Failed)
        ->and($suggestion->failure_code)->toBe('provider_unavailable')
        ->and($suggestion->metadata)->not->toHaveKey('raw_error');
});
