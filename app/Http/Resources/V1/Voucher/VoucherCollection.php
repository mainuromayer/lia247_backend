<?php

namespace App\Http\Resources\V1\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VoucherCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => VoucherResource::collection($this->collection),
        ];
    }
}
