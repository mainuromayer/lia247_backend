<?php

namespace App\Http\Resources\API\V1\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstItem = $this->items->first();

        return [
            'id' => $this->id,
            'order_id' => $this->order_no ?? '#' . $this->id,
            'item_image' => ($firstItem && $firstItem->item && $firstItem->item->image) ? asset($firstItem->item->image) : null,
            'item_name' => $firstItem ? $firstItem->item_name : 'No items',
            'quantity' => (int) $this->items->sum('quantity'),
            'price' => (float) $this->items->sum('price'),
            'total_price' => (float) $this->items->sum('total_price'),
            'customer_name' => $this->user ? $this->user->name : $this->receiver_name,
            'status' => $this->status,
            'created_at' => $this->created_at->format('d M Y, h:i A'),
        ];
    }
}
