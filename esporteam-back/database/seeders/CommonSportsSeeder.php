<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommonSportsSeeder extends Seeder
{
    public const SPORTS = [
        ['name' => 'Futebol', 'slug' => 'futebol', 'category' => 'coletivo'],
        ['name' => 'Futsal', 'slug' => 'futsal', 'category' => 'coletivo'],
        ['name' => 'Corrida', 'slug' => 'corrida', 'category' => 'endurance'],
        ['name' => 'Caminhada', 'slug' => 'caminhada', 'category' => 'endurance'],
        ['name' => 'Ciclismo', 'slug' => 'ciclismo', 'category' => 'endurance'],
        ['name' => 'Musculação', 'slug' => 'musculacao', 'category' => 'fitness'],
        ['name' => 'Treino funcional', 'slug' => 'funcional', 'category' => 'fitness'],
        ['name' => 'Natação', 'slug' => 'natacao', 'category' => 'aquatico'],
        ['name' => 'Vôlei', 'slug' => 'volei', 'category' => 'coletivo'],
        ['name' => 'Vôlei de praia', 'slug' => 'volei-de-praia', 'category' => 'coletivo'],
        ['name' => 'Basquete', 'slug' => 'basquete', 'category' => 'coletivo'],
        ['name' => 'Tênis', 'slug' => 'tenis', 'category' => 'raquete'],
        ['name' => 'Beach Tennis', 'slug' => 'beach-tennis', 'category' => 'raquete'],
        ['name' => 'Padel', 'slug' => 'padel', 'category' => 'raquete'],
        ['name' => 'Jiu-jitsu', 'slug' => 'jiu-jitsu', 'category' => 'luta'],
        ['name' => 'Muay Thai', 'slug' => 'muay-thai', 'category' => 'luta'],
        ['name' => 'Boxe', 'slug' => 'boxe', 'category' => 'luta'],
        ['name' => 'Yoga', 'slug' => 'yoga', 'category' => 'bem-estar'],
        ['name' => 'Pilates', 'slug' => 'pilates', 'category' => 'bem-estar'],
        ['name' => 'Skate', 'slug' => 'skate', 'category' => 'urbano'],
    ];

    public function run(): void
    {
        $timestamp = now();
        $sports = array_map(
            fn (array $sport): array => [
                ...$sport,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            self::SPORTS,
        );

        DB::table('sports')->upsert(
            $sports,
            ['slug'],
            ['name', 'category', 'is_active', 'updated_at'],
        );
    }

    public static function slugs(): array
    {
        return array_column(self::SPORTS, 'slug');
    }
}
