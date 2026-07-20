<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommuneResource extends JsonResource
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
                'description' => $this->description,
                'status' => $this->status,
                'region_id' => $this->region_id,
            ],
            'relationships' => [
                'region' => $this->whenLoaded('region', fn () => [
                    'id' => $this->region->id,
                    'name' => $this->region->name,
                ]),
            ],
        ];
    }
}
