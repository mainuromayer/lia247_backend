<?php

namespace App\Http\Resources\V1\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'min_order_amount' => $this->min_order_amount,
            'valid_from' => $this->valid_from ? Carbon::parse($this->valid_from)->format('Y-m-d h:i A') : null,
            'valid_until' => $this->valid_until ? Carbon::parse($this->valid_until)->format('Y-m-d h:i A') : null,
            'is_active' => $this->is_active,
        ];
    }
}
