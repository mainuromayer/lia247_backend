<?php

namespace App\Http\Resources\V1\Customer\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item_name' => $this->item?->name ?? null,
            'image' => $this->item?->image ?? null,
            'variant' => $this->variant ? [
                'id' => $this->variant->id,
                'name' => $this->variant->name,
                'price' => $this->variant->price,
            ] : null,
            'toppings' => (function($toppings) {
                if (empty($toppings)) return [];
                if (is_string($toppings)) {
                    $decoded = json_decode($toppings, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return $toppings;
            })($this->selected_toppings),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) ($this->unit_price * $this->quantity),
            'special_instruction' => $this->special_instruction,
        ];
    }
}
