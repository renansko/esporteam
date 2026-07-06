<?php

namespace Tests\Feature;

use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkspacePublicApiTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_returns_public_info_for_existing_workspace(): void
    {
        // Arrange
        $workspace = Workspace::factory()->create([
            'name' => 'Creche Girassol',
            'slug' => 'creche-girassol',
        ]);

        // Act
        $response = $this->getJson("/api/workspaces/{$workspace->id}/public");

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id'   => $workspace->id,
                    'name' => 'Creche Girassol',
                    'slug' => 'creche-girassol',
                ],
            ]);
    }

    public function test_does_not_expose_sensitive_fields(): void
    {
        // Arrange
        $workspace = Workspace::factory()->create();

        // Act
        $response = $this->getJson("/api/workspaces/{$workspace->id}/public");

        // Assert
        $response->assertOk();

        $data = $response->json('data');
        $this->assertArrayNotHasKey('owner_id', $data);
        $this->assertArrayNotHasKey('created_at', $data);
        $this->assertArrayNotHasKey('updated_at', $data);
    }

    public function test_returns_404_for_non_existent_workspace(): void
    {
        $response = $this->getJson('/api/workspaces/999/public');

        $response->assertNotFound();
    }

    public function test_does_not_require_authentication(): void
    {
        // Arrange
        $workspace = Workspace::factory()->create();

        // Act — sem nenhum header de auth
        $response = $this->getJson("/api/workspaces/{$workspace->id}/public");

        // Assert
        $response->assertOk()->assertJson(['success' => true]);
    }
}
