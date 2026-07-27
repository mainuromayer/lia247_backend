<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $rider) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("rider.{$this->rider->id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'rider.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->rider->id,
            'name' => $this->rider->name,
            'status' => $this->rider->status,
            'is_online' => (bool)$this->rider->is_online,
            'updated_at' => $this->rider->updated_at ? $this->rider->updated_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
        ];
    }
}
