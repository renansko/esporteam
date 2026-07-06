<?php

namespace App\Models;

use App\Enums\RoadmapItemOrigin;
use App\Enums\RoadmapItemStatus;
use App\Enums\RoadmapItemVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @wiki app/brain/entities/RoadmapItem.md
 */
class RoadmapItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'status',
        'visibility',
        'origin',
        'score',
        'score_breakdown',
        'votes_count',
    ];

    protected $casts = [
        'status'          => RoadmapItemStatus::class,
        'visibility'      => RoadmapItemVisibility::class,
        'origin'          => RoadmapItemOrigin::class,
        'score'           => 'decimal:4',
        'score_breakdown' => 'array',
        'votes_count'     => 'integer',
    ];

    protected $attributes = [
        'status'      => 'em_analise',
        'visibility'  => 'internal',
        'origin'      => 'manual',
        'votes_count' => 0,
    ];

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function clusteringDecisions(): HasMany
    {
        return $this->hasMany(ClusteringDecision::class);
    }

    /**
     * Recalcula `score` a partir do RICE breakdown e `votes_count`.
     *
     * Fórmula: (impact * reach) / effort.
     * Escala esperada: 1..5 para cada fator. `effort = 0` é coerced para 1.
     *
     * @wiki app/brain/entities/RoadmapItem.md#recomputeScore
     */
    public function recomputeScore(): void
    {
        $breakdown = $this->score_breakdown ?? [];
        $impact = (int) ($breakdown['impact'] ?? 0);
        $reach  = (int) ($breakdown['reach'] ?? 0);
        $effort = (int) ($breakdown['effort'] ?? 0);

        if ($effort <= 0) {
            $effort = 1;
        }

        $base = ($impact * $reach) / $effort;

        // Tie-breaker leve por votes_count (não muda o cálculo bruto).
        $this->score = round($base, 4);
    }
}
