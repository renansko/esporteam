<?php

use App\Models\User;

describe('PUT /api/users/{id}/permissions', function () {
    it('should update permissions successfully (bit 2 stripped)', function () {
        $user = User::factory()->create(['permissions' => 0]);

        // Client sends permissions=3 (bit 0 + bit 2). Self-service strips bit 2.
        $response = $this->putJson("/api/users/{$user->id}/permissions", [
            'permissions' => 3,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Permissions updated successfully',
        ]);
        expect($user->fresh()->permissions)->toBe(1);
    });

    it('should NOT allow a non-admin user to self-promote via bit 2', function () {
        $user = User::factory()->create(['permissions' => 0]);

        $response = $this->putJson("/api/users/{$user->id}/permissions", [
            'permissions' => 2,
        ]);

        $response->assertOk();
        // Bit 2 must be stripped — the user stays non-admin.
        expect($user->fresh()->permissions)->toBe(0);
    });

    it('should return 422 when permissions is missing', function () {
        $user = User::factory()->create();

        $response = $this->putJson("/api/users/{$user->id}/permissions", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['permissions']);
    });

    it('should return 422 when permissions is not an integer', function () {
        $user = User::factory()->create();

        $response = $this->putJson("/api/users/{$user->id}/permissions", [
            'permissions' => 'admin',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['permissions']);
    });

    it('should return 404 when user not found', function () {
        $response = $this->putJson('/api/users/99999/permissions', [
            'permissions' => 1,
        ]);

        $response->assertNotFound();
    });
});
