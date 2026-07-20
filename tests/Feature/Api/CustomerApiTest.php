<?php

namespace Tests\Feature\Api;

use App\Models\AuthToken;
use App\Models\Commune;
use App\Models\Customer;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeader(): array
    {
        $region = Region::create(['name' => 'RegionAuth']);
        $commune = $region->communes()->create(['name' => 'ComunaAuth']);

        $customer = Customer::create([
            'dni' => 'AUTH'.uniqid(),
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => uniqid().'@example.com',
            'password' => 'password',
            'name' => 'Auth',
            'last_name' => 'Customer',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $token = AuthToken::create([
            'customer_id' => $customer->id,
            'token' => bin2hex(random_bytes(20)),
            'expires_at' => now()->addHour(),
        ]);

        return ['Authorization' => 'Bearer '.$token->token];
    }

    public function test_customer_can_be_registered_with_a_valid_region_and_commune()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/customers', [
            'dni' => '12345678',
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('customers', ['dni' => '12345678', 'status' => 'A']);
    }

    public function test_customer_registration_fails_when_commune_does_not_belong_to_region()
    {
        $regionA = Region::create(['name' => 'Metropolitana']);
        $regionB = Region::create(['name' => 'Valparaíso']);
        $communeOfB = $regionB->communes()->create(['name' => 'Viña del Mar']);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/customers', [
            'dni' => '12345678',
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'region_id' => $regionA->id,
            'commune_id' => $communeOfB->id,
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('customers', ['dni' => '12345678']);
    }

    public function test_active_customer_can_be_queried_by_dni()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'address' => null,
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/customers?dni=12345678');

        $response->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Juan',
                'last_name' => 'Perez',
                'address' => null,
                'region' => 'Metropolitana',
                'commune' => 'Santiago',
            ],
        ]);
    }

    public function test_trashed_customer_is_not_returned_by_query()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'trash',
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/customers?dni=12345678');

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    public function test_active_customer_can_be_deleted_logically()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->deleteJson("/api/customers/{$customer->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('customers', ['dni' => '12345678', 'status' => 'trash']);
    }

    public function test_deleting_an_already_trashed_customer_returns_registro_no_existe()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'trash',
        ]);

        $response = $this->withHeaders($this->authHeader())->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(404)->assertJson(['success' => false, 'message' => 'Registro no existe']);
    }

    public function test_deleting_a_nonexistent_id_returns_registro_no_existe()
    {
        $response = $this->withHeaders($this->authHeader())->deleteJson('/api/customers/999999');

        $response->assertStatus(404)->assertJson(['success' => false, 'message' => 'Registro no existe']);
    }

    public function test_active_customer_can_be_updated()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);
        $otherCommune = $region->communes()->create(['name' => 'Providencia']);

        $customer = Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/update", [
            'address' => 'Nueva direccion 123',
            'commune_id' => $otherCommune->id,
            'region_id' => $region->id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('customers', [
            'dni' => '12345678',
            'address' => 'Nueva direccion 123',
            'commune_id' => $otherCommune->id,
            'name' => 'Juan',
        ]);
    }

    public function test_trashed_customer_cannot_be_updated()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'trash',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/update", [
            'address' => 'No deberia aplicar',
        ]);

        $response->assertStatus(404)->assertJson(['success' => false, 'message' => 'Registro no existe']);
    }

    public function test_trashed_customer_can_be_restored()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'trash',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/restore");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('customers', ['dni' => '12345678', 'status' => 'A']);
    }

    public function test_restoring_a_non_trashed_customer_fails()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = Customer::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/restore");

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_customer_endpoints_require_a_valid_token()
    {
        $response = $this->getJson('/api/customers?dni=12345678');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_expired_token_is_rejected()
    {
        $region = Region::create(['name' => 'RegionExpired']);
        $commune = $region->communes()->create(['name' => 'ComunaExpired']);

        $customer = Customer::create([
            'dni' => 'EXP'.uniqid(),
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => uniqid().'@example.com',
            'password' => 'password',
            'name' => 'Expired',
            'last_name' => 'Customer',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $token = AuthToken::create([
            'customer_id' => $customer->id,
            'token' => bin2hex(random_bytes(20)),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token->token])
            ->getJson('/api/customers?dni=12345678');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }
}
