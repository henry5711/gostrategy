<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_sanctum_token()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'expires_at']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_login_requires_email_and_password()
    {
        // Validación de FormRequest: shape default de Laravel (message/errors),
        // no el envelope success/message/data (no se usa ningún trait para forzarlo).
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_a_customer_without_password_cannot_login()
    {
        // Los customers registrados por POST /api/customers no tienen password.
        $customer = User::factory()->create(['password' => null]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $customer->email,
            'password' => 'anything',
        ]);

        $response->assertStatus(401)->assertJson(['success' => false]);
    }
}
