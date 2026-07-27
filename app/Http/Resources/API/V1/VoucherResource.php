<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
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
            'code' => $this->code,
            'type' => $this->type,
            'app_type' => $this->app_type,
            'discount' => [
                'type' => $this->discount_type,
                'amount' => $this->discount_amount,
            ],
            'min_order_amount' => $this->min_order_amount,
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'is_active' => $this->is_active,
            'owner_role' => $this->owner_role,
        ];
    }
}
