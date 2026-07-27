<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("vendor.{$this->order->vendor_id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'status' => $this->order->status,
            'total_amount' => $this->order->total_amount,
            'created_at' => $this->order->created_at->format('Y-m-d H:i:s'),
            'customer_name' => $this->order->user->name ?? $this->order->receiver_name,
            'items_count' => $this->order->items()->count(),
        ];
    }
}
