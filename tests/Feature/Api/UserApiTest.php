<?php

namespace Tests\Feature\Api;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeader(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
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
        $this->assertDatabaseHas('users', ['dni' => '12345678', 'status' => 'A']);
    }

    public function test_customer_password_is_stored_hashed()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $this->withHeaders($this->authHeader())->postJson('/api/customers', [
            'dni' => '12345678',
            'email' => 'customer@example.com',
            'password' => 'password123',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
        ]);

        $customer = User::where('dni', '12345678')->first();
        $this->assertNotEquals('password123', $customer->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $customer->password));
    }

    public function test_customer_registration_requires_a_password()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $response = $this->withHeaders($this->authHeader())->postJson('/api/customers', [
            'dni' => '12345678',
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
        ]);

        $response->assertStatus(422);
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

        $response->assertStatus(422)->assertJsonValidationErrors(['commune_id']);
        $this->assertDatabaseMissing('users', ['dni' => '12345678']);
    }

    public function test_customer_can_be_queried_by_id()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'address' => null,
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson("/api/customers/{$customer->id}");

        $response->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'attributes' => [
                    'name' => 'Juan',
                    'last_name' => 'Perez',
                    'address' => null,
                ],
                'relationships' => [
                    'region' => ['id' => $region->id, 'name' => 'Metropolitana'],
                    'commune' => ['id' => $commune->id, 'name' => 'Santiago'],
                ],
            ],
        ]);
    }

    public function test_trashed_customer_is_not_returned_by_query()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);
        $customer->delete();

        $response = $this->withHeaders($this->authHeader())->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    public function test_index_lists_customers_without_forcing_any_filter()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        User::create([
            'dni' => '11111111', 'region_id' => $region->id, 'commune_id' => $commune->id,
            'email' => 'activo@example.com', 'name' => 'Activo', 'last_name' => 'Uno',
            'date_reg' => now(), 'status' => 'A',
        ]);
        User::create([
            'dni' => '22222222', 'region_id' => $region->id, 'commune_id' => $commune->id,
            'email' => 'inactivo@example.com', 'name' => 'Inactivo', 'last_name' => 'Dos',
            'date_reg' => now(), 'status' => 'I',
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/customers?region_id='.$region->id);

        $response->assertOk()->assertJson(['success' => true]);
        // Sin filtro de status, aparecen tanto A como I (no se fuerza ningún estado).
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_can_filter_by_status()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        User::create([
            'dni' => '11111111', 'region_id' => $region->id, 'commune_id' => $commune->id,
            'email' => 'activo@example.com', 'name' => 'Activo', 'last_name' => 'Uno',
            'date_reg' => now(), 'status' => 'A',
        ]);
        User::create([
            'dni' => '22222222', 'region_id' => $region->id, 'commune_id' => $commune->id,
            'email' => 'inactivo@example.com', 'name' => 'Inactivo', 'last_name' => 'Dos',
            'date_reg' => now(), 'status' => 'I',
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/customers?status=A');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Activo', $response->json('data.0.attributes.name'));
    }

    public function test_index_can_filter_by_dni_or_email()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        User::create([
            'dni' => '11111111', 'region_id' => $region->id, 'commune_id' => $commune->id,
            'email' => 'uno@example.com', 'name' => 'Uno', 'last_name' => 'Uno',
            'date_reg' => now(), 'status' => 'A',
        ]);
        User::create([
            'dni' => '22222222', 'region_id' => $region->id, 'commune_id' => $commune->id,
            'email' => 'dos@example.com', 'name' => 'Dos', 'last_name' => 'Dos',
            'date_reg' => now(), 'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->getJson('/api/customers?dni=11111111');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Uno', $response->json('data.0.attributes.name'));
    }

    public function test_active_customer_can_be_deleted_logically()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->deleteJson("/api/customers/{$customer->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('users', ['id' => $customer->id]);
    }

    public function test_deleting_an_already_trashed_customer_returns_registro_no_existe()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);
        $customer->delete();

        $response = $this->withHeaders($this->authHeader())->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(404)->assertJson(['success' => false, 'message' => 'Registro no existe']);
    }

    public function test_deleting_a_nonexistent_id_returns_registro_no_existe()
    {
        $response = $this->withHeaders($this->authHeader())->deleteJson('/api/customers/999999');

        $response->assertStatus(404)->assertJson(['success' => false, 'message' => 'Registro no existe']);
    }

    public function test_active_customer_can_be_edited()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);
        $otherCommune = $region->communes()->create(['name' => 'Providencia']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/edit", [
            'address' => 'Nueva direccion 123',
            'commune_id' => $otherCommune->id,
            'region_id' => $region->id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', [
            'dni' => '12345678',
            'address' => 'Nueva direccion 123',
            'commune_id' => $otherCommune->id,
            'name' => 'Juan',
        ]);
    }

    public function test_trashed_customer_cannot_be_edited()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);
        $customer->delete();

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/edit", [
            'address' => 'No deberia aplicar',
        ]);

        $response->assertStatus(404)->assertJson(['success' => false, 'message' => 'Registro no existe']);
    }

    public function test_trashed_customer_can_be_restored()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
            'name' => 'Juan',
            'last_name' => 'Perez',
            'date_reg' => now(),
            'status' => 'A',
        ]);
        $customer->delete();

        $response = $this->withHeaders($this->authHeader())->postJson("/api/customers/{$customer->id}/restore");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_restoring_a_non_trashed_customer_fails()
    {
        $region = Region::create(['name' => 'Metropolitana']);
        $commune = $region->communes()->create(['name' => 'Santiago']);

        $customer = User::create([
            'dni' => '12345678',
            'region_id' => $region->id,
            'commune_id' => $commune->id,
            'email' => 'customer@example.com',
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
        $response = $this->getJson('/api/customers/1');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_expired_token_is_rejected()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', ['*'], now()->subMinute());

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token->plainTextToken])
            ->getJson('/api/customers/1');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }
}
