<?php

namespace Database\Factories;

use App\Enums\ClusteringDecisionAction;
use App\Models\ClusteringDecision;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClusteringDecision>
 */
class ClusteringDecisionFactory extends Factory
{
    protected $model = ClusteringDecision::class;

    public function definition(): array
    {
        return [
            'run_id'          => ClusteringRun::factory(),
            'idea_id'         => Idea::factory(),
            'roadmap_item_id' => RoadmapItem::factory(),
            'action'          => ClusteringDecisionAction::Create->value,
            'rationale'       => $this->faker->sentence(),
        ];
    }
}
