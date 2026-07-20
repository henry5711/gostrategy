<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'last_name' => $this->last_name,
                'address' => $this->address,
            ],
            'relationships' => [
                'region' => $this->whenLoaded('region', fn () => [
                    'id' => $this->region->id,
                    'name' => $this->region->name,
                ]),
                'commune' => $this->whenLoaded('commune', fn () => [
                    'id' => $this->commune->id,
                    'name' => $this->commune->name,
                ]),
            ],
        ];
    }
}
