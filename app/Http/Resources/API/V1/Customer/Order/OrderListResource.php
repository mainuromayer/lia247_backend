<?php

namespace App\Http\Resources\API\V1\Customer\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstItem = $this->items->first();
        
        // Format subtitle: "Estimated delivery 03.34 p.m" or "Delivered on 2 May 17.03"
        // Wait, 'status' determines this.
        $subtitle = '';
        if (in_array($this->status, ['completed', 'delivered'])) {
            $subtitle = 'Delivered on ' . $this->updated_at->format('j M H:i');
        } else {
            $subtitle = 'Estimated delivery ' . $this->created_at->addMinutes(30)->format('h:i A');
        }

        return [
            'id' => $this->id,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'vendor_name' => $this->vendor ? $this->vendor->name : 'Unknown Vendor',
            'subtitle' => $subtitle,
            'item_summary' => $firstItem ? $firstItem->item_name : 'No items',
            'image' => ($firstItem && $firstItem->item) ? $firstItem->item->image : null,
            'created_at' => $this->created_at->format('j M H:i'),
        ];
    }

}
