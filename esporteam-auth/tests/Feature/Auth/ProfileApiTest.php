<?php

use App\Models\User;

describe('profiles', function () {
    it('register creates user with user profile and returns it on /me', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.profile', 'user');

        expect(User::where('email', 'jane@example.com')->first()->profile)->toBe('user');
    });

    it('register creates a teacher profile when the teacher intent is selected', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Marina Doe',
            'email' => 'marina@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'registration_intent' => 'teacher',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.profile', 'teacher');

        expect(User::where('email', 'marina@example.com')->first()->profile)->toBe('teacher');
    });

    it('require profile middleware allows matching profile and blocks common user', function () {
        $admin = User::factory()->create(['profile' => 'admin']);
        $user = User::factory()->create(['profile' => 'user']);

        expect($admin->hasProfile('admin'))->toBeTrue()
            ->and($admin->hasAnyProfile(['admin', 'teacher']))->toBeTrue()
            ->and($admin->isAdmin())->toBeTrue()
            ->and($user->hasAnyProfile(['admin', 'teacher']))->toBeFalse();
    });
});
