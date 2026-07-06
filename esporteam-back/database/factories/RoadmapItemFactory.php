<?php

namespace Database\Factories;

use App\Enums\RoadmapItemOrigin;
use App\Enums\RoadmapItemStatus;
use App\Enums\RoadmapItemVisibility;
use App\Models\RoadmapItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoadmapItem>
 */
class RoadmapItemFactory extends Factory
{
    protected $model = RoadmapItem::class;

    public function definition(): array
    {
        $impact = $this->faker->numberBetween(1, 5);
        $reach  = $this->faker->numberBetween(1, 5);
        $effort = $this->faker->numberBetween(1, 5);

        return [
            'workspace_id'    => 1,
            'title'           => $this->faker->sentence(4),
            'description'     => $this->faker->paragraph(),
            'status'          => RoadmapItemStatus::EmAnalise->value,
            'visibility'      => RoadmapItemVisibility::Internal->value,
            'origin'          => RoadmapItemOrigin::Manual->value,
            'score'           => round(($impact * $reach) / max($effort, 1), 4),
            'score_breakdown' => ['impact' => $impact, 'reach' => $reach, 'effort' => $effort],
            'votes_count'     => 0,
        ];
    }
}
