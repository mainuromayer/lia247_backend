<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderId;
    public $readerId;
    public $senderId;

    public function __construct($orderId, $readerId, $senderId)
    {
        $this->orderId = $orderId;
        $this->readerId = $readerId;
        $this->senderId = $senderId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("order.{$this->orderId}.chat")
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => (int) $this->orderId,
            'reader_id' => (int) $this->readerId,
            'sender_id' => (int) $this->senderId,
        ];
    }
}
