<?php

namespace App\Http\Resources\V1\Customer\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item_name' => $this->item_name,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant?->name,
            'selected_toppings' => $this->selected_toppings ?? [],
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->price,
            'total_price' => (float) $this->total_price,
            'special_instruction' => $this->special_instruction,
        ];
    }
}
