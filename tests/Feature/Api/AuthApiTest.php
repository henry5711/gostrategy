<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createCustomer(array $overrides = []): Customer
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        return Customer::create(array_merge([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ], $overrides));
    }

    public function test_customer_can_login_and_receive_a_token()
    {
        $customer = $this->createCustomer();

        $response = $this->postJson('/api/auth/login', [
            'email' => $customer->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'expires_at']]);

        $this->assertDatabaseHas('auth_tokens', [
            'customer_id' => $customer->id,
            'token' => $response->json('data.token'),
        ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $customer = $this->createCustomer();

        $response = $this->postJson('/api/auth/login', [
            'email' => $customer->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_login_fails_for_a_trashed_customer()
    {
        $customer = $this->createCustomer(['status' => 'trash']);

        $response = $this->postJson('/api/auth/login', [
            'email' => $customer->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
