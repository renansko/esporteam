<?php

namespace App\Models;

use App\Enums\ClusteringDecisionAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @wiki app/brain/entities/ClusteringDecision.md
 */
class ClusteringDecision extends Model
{
    use HasFactory;

    // Tabela tem só created_at, sem updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'run_id',
        'idea_id',
        'roadmap_item_id',
        'action',
        'rationale',
    ];

    protected $casts = [
        'action' => ClusteringDecisionAction::class,
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ClusteringRun::class, 'run_id');
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function roadmapItem(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class);
    }
}
