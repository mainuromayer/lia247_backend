<?php

namespace App\Http\Resources\API\V1\Customer\Tip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipOrderResource extends JsonResource
{
    protected $clientSecret;

    public function __construct($resource, $clientSecret = null)
    {
        parent::__construct($resource);
        $this->clientSecret = $clientSecret;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->user_id,
            'user_id' => $this->recipient_id,
            'order_id' => $this->order_id,
            'amount' => (float) $this->amount,
            'payment_type' => $this->payment_type,
            'payment_status' => $this->payment_status,
            'transaction_id' => $this->transaction_id,
            'payment_intent_id' => $this->payment_intent_id,
            'client_secret' => $this->clientSecret,
        ];
    }
}
