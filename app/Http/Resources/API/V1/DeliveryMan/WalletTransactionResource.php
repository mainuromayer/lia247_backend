<?php

namespace App\Http\Resources\API\V1\DeliveryMan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customerName = 'N/A';
        $quantity = 1;
        
        if ($this->reference_type == 'App\Models\Order' && $this->reference) {
            $customerName = $this->reference->vendor ? $this->reference->vendor->name : 'N/A';
            $quantity = $this->reference->items->sum('quantity');
        }

        return [
            'wallet' => $this->category == 'delivery_fee' ? 'Stripe' : 'System',
            'transaction_id' => 'Trx' . ($this->reference_id ?? $this->id),
            'date' => $this->created_at->format('M d, Y'),
            'quantity' => (int)$quantity,
            'amount' => (float)$this->amount,
            'customer' => $customerName,
            'status' => ucfirst($this->status),
        ];
    }
}
