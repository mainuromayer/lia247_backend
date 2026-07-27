<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $vendor) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("vendor.{$this->vendor->id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'vendor.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->vendor->id,
            'name' => $this->vendor->name,
            'status' => $this->vendor->status,
            'is_online' => (bool)$this->vendor->is_online,
            'updated_at' => $this->vendor->updated_at ? $this->vendor->updated_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
        ];
    }
}
