<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            'Andes' => ['Tachira', 'Merida', 'Trujillo'],
            'Central' => ['Caracas', 'Miranda'],
            'Orinete' => ['Anzoategui', 'Sucre'],
        ];

        foreach ($regions as $regionName => $communes) {
            $region = Region::create(['name' => $regionName]);

            foreach ($communes as $communeName) {
                $region->communes()->create(['name' => $communeName]);
            }
        }
    }
}
