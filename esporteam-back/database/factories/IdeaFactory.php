<?php

namespace Database\Factories;

use App\Enums\IdeaSource;
use App\Models\Idea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Idea>
 */
class IdeaFactory extends Factory
{
    protected $model = Idea::class;

    public function definition(): array
    {
        return [
            'workspace_id'    => 1,
            'source'          => IdeaSource::Manual->value,
            'title'           => $this->faker->sentence(4),
            'description'     => $this->faker->paragraph(),
            'author_email'    => $this->faker->safeEmail(),
            'roadmap_item_id' => null,
        ];
    }
}
