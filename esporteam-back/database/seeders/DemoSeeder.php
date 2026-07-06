<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Usuários e workspaces vivem em esporteam-auth/esporteam-workspace; este
        // seeder cuida só de entidades de domínio do app.
        foreach ($this->sports() as $sport) {
            Sport::query()->updateOrCreate(
                ['slug' => $sport['slug']],
                $sport,
            );
        }
    }

    private function sports(): array
    {
        return [
            ['name' => 'Futebol', 'slug' => 'futebol', 'category' => 'coletivo', 'is_active' => true],
            ['name' => 'Corrida', 'slug' => 'corrida', 'category' => 'endurance', 'is_active' => true],
            ['name' => 'Tenis', 'slug' => 'tenis', 'category' => 'raquete', 'is_active' => true],
            ['name' => 'Beach Tennis', 'slug' => 'beach-tennis', 'category' => 'raquete', 'is_active' => true],
            ['name' => 'Volei', 'slug' => 'volei', 'category' => 'coletivo', 'is_active' => true],
            ['name' => 'Basquete', 'slug' => 'basquete', 'category' => 'coletivo', 'is_active' => true],
            ['name' => 'Ciclismo', 'slug' => 'ciclismo', 'category' => 'endurance', 'is_active' => true],
            ['name' => 'Musculacao', 'slug' => 'musculacao', 'category' => 'fitness', 'is_active' => true],
            ['name' => 'Jiu-jitsu', 'slug' => 'jiu-jitsu', 'category' => 'luta', 'is_active' => true],
            ['name' => 'Natacao', 'slug' => 'natacao', 'category' => 'aquatico', 'is_active' => true],
            ['name' => 'Yoga', 'slug' => 'yoga', 'category' => 'bem-estar', 'is_active' => true],
            ['name' => 'Funcional', 'slug' => 'funcional', 'category' => 'fitness', 'is_active' => true],
        ];
    }
}
