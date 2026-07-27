<?php

namespace App\Http\Resources\API\V1\Customer\Checkout;

use App\Http\Resources\V1\Customer\Cart\CartItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSummaryResource extends JsonResource
{
    /**
     * The summary data to include.
     */
    protected $summary;

    public function __construct($resource, $summary)
    {
        parent::__construct($resource);
        $this->summary = $summary;
    }

    public function toArray(Request $request): array
    {
        return [
            'cart_id' => $this->id,
            'items_count' => (int) $this->items->sum('quantity'),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'summary' => [
                'subtotal' => (float) $this->summary['subtotal'],
                'discount' => (float) $this->summary['discount'],
                'voucher_code' => $this->summary['voucher_code'],
                'vat_percentage' => (float) $this->summary['vat_percentage'],
                'vat_amount' => (float) $this->summary['vat_amount'],
                'delivery_fee' => (float) $this->summary['delivery_fee'],
                'total' => (float) $this->summary['total'],
            ]
        ];
    }
}
