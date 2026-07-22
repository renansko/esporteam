<?php

use App\Models\Sport;
use Database\Seeders\CommonSportsSeeder;

it('provides the common Modalidades as an active base catalog', function () {
    $this->seed(CommonSportsSeeder::class);

    $expectedSports = [
        'futebol' => 'Futebol',
        'futsal' => 'Futsal',
        'corrida' => 'Corrida',
        'caminhada' => 'Caminhada',
        'ciclismo' => 'Ciclismo',
        'musculacao' => 'Musculação',
        'funcional' => 'Treino funcional',
        'natacao' => 'Natação',
        'volei' => 'Vôlei',
        'volei-de-praia' => 'Vôlei de praia',
        'basquete' => 'Basquete',
        'tenis' => 'Tênis',
        'beach-tennis' => 'Beach Tennis',
        'padel' => 'Padel',
        'jiu-jitsu' => 'Jiu-jitsu',
        'muay-thai' => 'Muay Thai',
        'boxe' => 'Boxe',
        'yoga' => 'Yoga',
        'pilates' => 'Pilates',
        'skate' => 'Skate',
    ];

    expect(Sport::query()->whereIn('slug', array_keys($expectedSports))->count())
        ->toBe(count($expectedSports));

    foreach ($expectedSports as $slug => $name) {
        $sport = Sport::query()->where('slug', $slug)->firstOrFail();

        expect($sport->name)->toBe($name)
            ->and($sport->is_active)->toBeTrue();
    }
});
