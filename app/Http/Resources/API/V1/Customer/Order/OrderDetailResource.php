<?php

namespace App\Http\Resources\API\V1\Customer\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtitle = '';
        if (in_array($this->status, ['completed', 'delivered'])) {
            $subtitle = 'Delivered on ' . $this->updated_at->format('j M H:i');
        } else {
            $subtitle = 'Estimated delivery ' . $this->created_at->addMinutes(30)->format('h:i A');
        }

        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'subtitle' => $subtitle,
            'delivery_man' => $this->deliveryMan ? [
                'id' => $this->deliveryMan->id,
                'name' => $this->deliveryMan->name,
                'phone' => $this->deliveryMan->phone,
                'avatar' => $this->deliveryMan->avatar,
                'rating' => $this->deliveryMan->average_rating,
                'reviews_count' => $this->deliveryMan->reviews()->where('role', 'delivery_man')->count(),
                'latitude' => $this->deliveryMan->latitude ? (float) $this->deliveryMan->latitude : null,
                'longitude' => $this->deliveryMan->longitude ? (float) $this->deliveryMan->longitude : null,
                'address' => $this->deliveryMan->address,
            ] : null,
            'sender' => [
                'name' => $this->vendor->business->business_name ?? $this->vendor->name ?? 'Unknown',
                'phone' => $this->vendor->business->business_phone ?? $this->vendor->phone ?? 'N/A',
                'address' => $this->vendor->business->business_address ?? $this->vendor->address ?? 'N/A',
                'latitude' => $this->vendor->business->latitude ?? $this->vendor->latitude ?? null,
                'longitude' => $this->vendor->business->longitude ?? $this->vendor->longitude ?? null,
            ],
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $this->receiver_phone,
                'address' => $this->receiver_address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'delivery_address' => $this->receiver_address ?? 'Pickup Order', // Legacy support
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'total_price' => $item->total_price,
                ];
            }),
            'item_subtotal_amount' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'vat_and_fees' => $this->vat_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'payment_method' => ucfirst($this->payment_gateway ?? 'Unknown'),
            'status' => $this->status,
        ];
    }

}
