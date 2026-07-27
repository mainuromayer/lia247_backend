<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->item_name,
            'variant' => $this->variant ? [
                'id' => $this->variant->id,
                'name' => $this->variant->name,
            ] : null,
            'toppings' => is_string($this->selected_toppings) 
                ? json_decode($this->selected_toppings, true) 
                : ($this->selected_toppings ?? []),
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->price,
            'total_price' => (float) $this->total_price,
            'special_instruction' => $this->special_instruction,
        ];
    }
}
