<?php

namespace App\Http\Resources\V1\Customer\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += ($item->unit_price * $item->quantity);
        }

        return [
            'cart_id' => $this->id,
            'items_count' => (int) $this->items->sum('quantity'),
            'subtotal' => (float) $subtotal,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
