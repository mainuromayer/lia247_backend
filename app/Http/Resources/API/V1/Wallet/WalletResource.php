<?php

namespace App\Http\Resources\API\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'balance' => $this->balance,
            'pending_balance' => $this->pending_balance,
            'currency' => 'USD', // Configurable
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
