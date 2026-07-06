<?php

use App\Models\SportProfile;

it('filters discovered sport profiles by overlapping availability windows', function () {
    SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
    ]);

    $overlapping = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Overlapping profile',
    ]);
    $overlapping->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '18:00',
        'ends_at' => '20:00',
    ]);

    $touchingButNotOverlapping = SportProfile::query()->create([
        'user_id' => 99,
        'display_name' => 'Later profile',
    ]);
    $touchingButNotOverlapping->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '20:00',
        'ends_at' => '21:00',
    ]);

    $differentWeekday = SportProfile::query()->create([
        'user_id' => 100,
        'display_name' => 'Different weekday profile',
    ]);
    $differentWeekday->availabilityWindows()->create([
        'weekday' => 3,
        'starts_at' => '18:00',
        'ends_at' => '20:00',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?weekday=2&starts_at=19:00&ends_at=20:00')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $overlapping->id)
        ->assertJsonPath('data.0.availability.0.weekday', 2);
});
