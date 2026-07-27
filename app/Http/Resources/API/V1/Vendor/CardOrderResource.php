<?php

namespace App\Http\Resources\API\V1\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = [
                'image' => ($item->item && $item->item->image) ? asset($item->item->image) : null,
                'name' => $item->item_name,
                'variant_name' => $item->variant ? $item->variant->name : null,
                'price' => (float)$item->price,
                'quantity' => (int)$item->quantity,
            ];
        }

        return [
            'id' => $this->id,
            'order_id' => $this->order_no ?? '#' . $this->id,
            'created_at' => $this->created_at->format('d M Y, h:i A'),
            'user_image' => $this->user ? $this->user->avatar : null,
            'items' => $items,
            'total_items' => $this->items->count(),
            'total_price' => (float)$this->total_amount,
            'status' => $this->status,
            'is_pickup' => $this->order_type === 'pickup',
        ];
    }
}
