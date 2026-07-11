<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    public function test_duplicate_email_validation_is_translated_to_portuguese(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Novo usuário',
            'email' => 'existente@example.com',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'O valor para email já existe.');
    }
}
