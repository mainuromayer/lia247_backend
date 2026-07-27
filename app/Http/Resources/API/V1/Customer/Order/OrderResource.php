<?php

namespace App\Http\Resources\API\V1\Customer\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstItem = $this->items->first();
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'order_type' => $this->order_type,
            'total_amount' => $this->total_amount,
            'item_count' => $this->items->sum('quantity'),
            'first_item_name' => $firstItem ? $firstItem->item_name : null,
            'first_item_image' => ($firstItem && $firstItem->item) ? $firstItem->item->image : null,
            'vendor_name' => $this->vendor ? $this->vendor->name : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
