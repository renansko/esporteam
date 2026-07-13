<?php

namespace App\Models;

use App\Enums\BioSuggestionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @wiki app/brain/entities/BioSuggestion.md
 */
class BioSuggestion extends Model
{
    /** True only when a request is served from its previous idempotent result. */
    public bool $wasReplayed = false;

    protected $fillable = [
        'sport_profile_id',
        'status',
        'generated_bio',
        'structured_output',
        'prompt_version',
        'provider',
        'model',
        'tokens_input',
        'tokens_output',
        'failure_code',
        'context_fingerprint',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'status' => BioSuggestionStatus::class,
        'structured_output' => 'array',
        'metadata' => 'array',
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
