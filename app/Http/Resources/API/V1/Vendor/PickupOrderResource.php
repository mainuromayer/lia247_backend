<?php

namespace App\Http\Resources\API\V1\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupOrderResource extends JsonResource
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
            'order_id' => $this->order_no ?? '#' . $this->id,
            'created_at' => $this->created_at->format('d M Y, h:i A'),
            'rider_image' => $this->deliveryMan ? $this->deliveryMan->avatar : null,
            'rider_id' => $this->deliveryMan ? $this->deliveryMan->id : 'N/A',
            'rider_name' => $this->deliveryMan ? $this->deliveryMan->name : 'No Rider Assigned',
            'rider_phone' => $this->deliveryMan ? $this->deliveryMan->phone : null,
            'is_pickup' => $this->delivery_man_id ? true : false,
            'status' => $this->status,
        ];
    }
}
