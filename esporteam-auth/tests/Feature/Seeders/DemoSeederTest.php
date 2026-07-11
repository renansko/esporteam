<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds sport demo auth users that match backend sport profile ids', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    $practitioner = User::query()->where('email', 'ana.praticante@esporteam.test')->firstOrFail();

    expect($practitioner->id)->toBe(8001)
        ->and($practitioner->name)->toBe('Ana Martins')
        ->and($practitioner->profile)->toBe('user')
        ->and(Hash::check('demo1234', $practitioner->password))->toBeTrue()
        ->and(User::query()->whereBetween('id', [8001, 8040])->count())->toBe(40)
        ->and(User::query()->whereBetween('id', [8025, 8032])->where('profile', 'teacher')->count())->toBe(8)
        ->and(User::query()->where('email', 'eduardo@mesa.app')->where('profile', 'admin')->exists())->toBeTrue();
});
