<?php

it('returns profile and permissions from authenticated claims', function () {
    actingAsWorkspace(42, ['id' => 9, 'permissions' => 3, 'profile' => 'teacher'])
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.user.id', 9)
        ->assertJsonPath('data.user.profile', 'teacher')
        ->assertJsonPath('data.user.permissions', 3);
});
