<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'     => $name,
            'slug'     => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'owner_id' => $this->faker->numberBetween(1, 100),
        ];
    }
}
