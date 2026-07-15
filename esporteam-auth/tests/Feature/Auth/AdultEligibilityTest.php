<?php

namespace Tests\Feature\Auth;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\JwtService;
use Tests\TestCase;

class AdultEligibilityTest extends TestCase
{
    public function test_it_declares_adult_eligibility_without_exposing_birth_date_in_the_token(): void
    {
        $user = User::factory()->create(['is_adult' => false]);
        $token = app(JwtService::class)->encode($user->toArray());

        $response = $this->withToken($token)->postJson('/api/adult-eligibility', [
            'birth_date' => now()->subYears(20)->toDateString(),
            'adult_attestation' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.user.is_adult', true);
        $this->assertTrue($user->fresh()->is_adult);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'declare_adult_eligibility', 'target_id' => $user->id]);

        $payload = app(JwtService::class)->decode($response->json('data.token'));
        $this->assertTrue($payload->is_adult);
        $this->assertObjectNotHasProperty('birth_date', $payload);
        $this->assertObjectNotHasProperty('birth_date', $payload->user);
    }

    public function test_it_rejects_a_minor_declaration(): void
    {
        $user = User::factory()->create();
        $token = app(JwtService::class)->encode($user->toArray());

        $this->withToken($token)->postJson('/api/adult-eligibility', [
            'birth_date' => now()->subYears(17)->toDateString(),
            'adult_attestation' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['birth_date']);
    }
}
