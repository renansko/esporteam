<?php

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates the workspace creator as owner', function () {
    $response = $this->withHeader('X-Test-User-Id', '77')
        ->postJson('/api/workspaces', ['name' => 'Nova Escola']);

    $response->assertCreated();

    $workspaceId = $response->json('data.id');

    expect(WorkspaceMember::where('workspace_id', $workspaceId)->where('user_id', 77)->value('role'))
        ->toBe('owner');
});

it('accepts teacher helper and member roles for invites', function (string $role) {
    $workspace = Workspace::factory()->create();
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => 1,
        'role' => 'owner',
    ]);

    $response = $this->postJson("/api/workspaces/{$workspace->id}/invites", [
        'email' => "{$role}@example.com",
        'role' => $role,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.role', $role);
})->with(['teacher', 'helper', 'member']);

it('does not allow assigning owner through member APIs', function () {
    $workspace = Workspace::factory()->create();
    WorkspaceMember::create([
        'workspace_id' => $workspace->id,
        'user_id' => 1,
        'role' => 'owner',
    ]);

    $response = $this->postJson("/api/workspaces/{$workspace->id}/members", [
        'user_id' => 2,
        'role' => 'owner',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});
