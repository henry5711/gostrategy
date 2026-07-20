<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RegionSeeder::class);

        // Customer de prueba para el login de la API (POST /api/auth/login).
        Customer::create([
            'dni' => 'V00000000',
            'region_id' => 1,
            'commune_id' => 1,
            'email' => 'test@example.com',
            'password' => 'password',
            'name' => 'Test',
            'last_name' => 'Customer',
            'address' => 'Dirección de prueba',
            'date_reg' => now(),
            'status' => Status::Active->value,
        ]);
    }
}
