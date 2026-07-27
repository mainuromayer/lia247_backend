<?php

namespace App\Http\Resources\V1\Customer\Transport;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price_per_km' => (float) $this->price_per_km,
            'min_balance' => (float) $this->min_balance,
            'transport_type_category_id' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'transport_type' => $this->transport_type,
            'icon' => $this->icon ? asset($this->icon) : null,
        ];
    }
}
