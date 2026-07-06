<?php

use App\Models\User;
use App\Services\UserService;

describe('UserService', function () {
    describe('updatePermissions', function () {
        it('should update non-admin bits on a non-admin user', function () {
            $user = User::factory()->create(['permissions' => 0]);
            $service = new UserService();

            $result = $service->updatePermissions($user, 1);

            expect($result->permissions)->toBe(1);
            expect($user->fresh()->permissions)->toBe(1);
        });

        it('should strip bit 2 when a non-admin user tries to self-promote', function () {
            $user = User::factory()->create(['permissions' => 0]);
            $service = new UserService();

            // User sends permissions=2 (is_esporteam_admin) — must be stripped.
            $result = $service->updatePermissions($user, 2);

            expect($result->permissions)->toBe(0);
            expect($user->fresh()->permissions)->toBe(0);
        });

        it('should strip bit 2 but keep other bits when mixed', function () {
            $user = User::factory()->create(['permissions' => 0]);
            $service = new UserService();

            // permissions=3 means bit 0 (can_create_workspace) + bit 2 (admin).
            // Only bit 0 should survive.
            $result = $service->updatePermissions($user, 3);

            expect($result->permissions)->toBe(1);
        });

        it('should preserve bit 2 when an admin user sets permissions to zero', function () {
            $user = User::factory()->create(['permissions' => 3]); // admin + create_workspace
            $service = new UserService();

            // Admin tries to zero their permissions via self-service —
            // bit 2 must be preserved (only admin endpoint can revoke admin).
            $result = $service->updatePermissions($user, 0);

            expect($result->permissions)->toBe(2);
            expect($user->fresh()->permissions)->toBe(2);
        });

        it('should preserve bit 2 regardless of input value', function () {
            $user = User::factory()->create(['permissions' => 2]); // admin only
            $service = new UserService();

            $result = $service->updatePermissions($user, 1);

            // Expected: bit 0 from input + bit 2 preserved = 3
            expect($result->permissions)->toBe(3);
        });
    });
});
