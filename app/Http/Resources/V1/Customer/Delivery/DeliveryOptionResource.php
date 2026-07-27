<?php

namespace App\Http\Resources\V1\Customer\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sub_text' => $this->sub_text,
            'image' => $this->image ? asset($this->image) : null,
            'price' => (float) $this->price,
            'estimated_time' => $this->estimated_time,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
