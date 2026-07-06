<?php

namespace App\Models;

use App\Enums\IdeaSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @wiki app/brain/entities/Idea.md
 */
class Idea extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'source',
        'title',
        'description',
        'author_email',
        'roadmap_item_id',
        'source_file_id',
    ];

    protected $casts = [
        'source' => IdeaSource::class,
    ];

    public function setAuthorEmailAttribute(?string $value): void
    {
        $this->attributes['author_email'] = $value === null ? null : strtolower(trim($value));
    }

    public function roadmapItem(): BelongsTo
    {
        return $this->belongsTo(RoadmapItem::class);
    }

    public function clusterDecision(): HasOne
    {
        return $this->hasOne(ClusteringDecision::class)->latestOfMany('id');
    }
}
