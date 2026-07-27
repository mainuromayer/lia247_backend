<?php

namespace App\Http\Resources\V1\Customer\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'user_id' => $this->user_id,
            'amount' => (float) $this->amount,
            'subtotal' => (float) $this->subtotal,
            'vat_amount' => (float) $this->vat_amount,
            'order_type' => $this->order_type,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'delivery_option_id' => $this->delivery_option_id,
            'delivery_fee' => (float) $this->delivery_fee,
            'voucher_id' => $this->voucher_id,
            'discount_amount' => (float) $this->discount_amount,
            'discount_type' => $this->discount_type,
            'total_amount' => (float) $this->total_amount,
            'payment_gateway' => $this->payment_gateway,
            'notes' => $this->notes,
            'status' => $this->status,
            'payment_intent_id' => $this->payment_intent_id,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
