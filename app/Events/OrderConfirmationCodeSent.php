<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationCodeSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order, public int $code) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("order.{$this->order->id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.confirmation_code.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'confirmation_code' => $this->code,
            'message' => "Your confirmation code is {$this->code}",
        ];
    }
}
