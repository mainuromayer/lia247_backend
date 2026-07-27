<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReady implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        if ($this->order->delivery_man_id) {
            return [
                new Channel("delivery_man.{$this->order->delivery_man_id}")
            ];
        }

        return [
            new Channel("delivery_man.available")
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.ready';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'status' => $this->order->status,
            'delivery_fee' => $this->order->delivery_fee,
            'total_amount' => $this->order->total_amount,
            'vendor_name' => $this->order->vendor->business?->business_name ?? $this->order->vendor->name,
            'vendor_address' => $this->order->vendor->business?->business_address ?? $this->order->vendor->address,
            'delivery_address' => $this->order->receiver_address,
            'created_at' => $this->order->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
