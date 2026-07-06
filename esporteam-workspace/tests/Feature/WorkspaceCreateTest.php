<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkspaceCreateTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_create_workspace(): void
    {
        $response = $this->withHeader('X-Test-Permissions', '0')
            ->postJson('/api/workspaces', ['name' => 'Nova Escola']);

        $response->assertForbidden()
            ->assertJson(['success' => false]);
    }

    public function test_user_with_permission_can_create_workspace(): void
    {
        $response = $this->withHeader('X-Test-Permissions', '1')
            ->postJson('/api/workspaces', ['name' => 'Nova Escola']);

        $response->assertCreated()
            ->assertJson(['success' => true]);
    }

    public function test_user_defaults_to_can_create(): void
    {
        $response = $this->postJson('/api/workspaces', ['name' => 'Nova Escola']);

        $response->assertCreated()
            ->assertJson(['success' => true]);
    }
}
