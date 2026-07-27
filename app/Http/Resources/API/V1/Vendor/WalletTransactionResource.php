<?php

namespace App\Http\Resources\API\V1\Vendor;

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
            $customerName = $this->reference->user ? $this->reference->user->name : $this->reference->receiver_name;
            $quantity = $this->reference->items->sum('quantity');
        }

        return [
            'wallet' => $this->category == 'order_payment' ? 'Stripe' : 'System',
            'transaction_id' => 'Trx' . ($this->reference_id ?? $this->id),
            'date' => $this->created_at->format('M d, Y'),
            'quantity' => (int)$quantity,
            'amount' => (float)$this->amount,
            'customer' => $customerName,
            'status' => ucfirst($this->status),
        ];
    }
}
