<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryManStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $deliveryMan) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("delivery_man.{$this->deliveryMan->id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'delivery_man.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->deliveryMan->id,
            'name' => $this->deliveryMan->name,
            'status' => $this->deliveryMan->status,
            'is_online' => (bool)$this->deliveryMan->is_online,
            'updated_at' => $this->deliveryMan->updated_at ? $this->deliveryMan->updated_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
        ];
    }
}
