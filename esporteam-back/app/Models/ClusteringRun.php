<?php

namespace App\Models;

use App\Enums\ClusteringRunStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @wiki app/brain/entities/ClusteringRun.md
 */
class ClusteringRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'status',
        'started_at',
        'completed_at',
        'ideas_processed',
        'items_created',
        'items_assigned',
        'llm_model',
        'prompt_version',
        'token_usage_in',
        'token_usage_out',
        'cache_hit_rate',
        'pre_cluster_bundles_count',
        'fallback_used',
        'failure_reason',
        'summary',
    ];

    protected $casts = [
        'status'                    => ClusteringRunStatus::class,
        'started_at'                => 'datetime',
        'completed_at'              => 'datetime',
        'ideas_processed'           => 'integer',
        'items_created'             => 'integer',
        'items_assigned'            => 'integer',
        'token_usage_in'            => 'integer',
        'token_usage_out'           => 'integer',
        'cache_hit_rate'            => 'decimal:2',
        'pre_cluster_bundles_count' => 'integer',
        'fallback_used'             => 'boolean',
    ];

    public function decisions(): HasMany
    {
        return $this->hasMany(ClusteringDecision::class, 'run_id');
    }
}
