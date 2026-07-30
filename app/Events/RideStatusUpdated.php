<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ride $ride) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("ride.{$this->ride->id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->ride->id,
            'ride_no' => $this->ride->ride_no,
            'status' => $this->ride->status,
            'payment_status' => $this->ride->payment_status,
            'rider_id' => $this->ride->rider_id,
            'updated_at' => $this->ride->updated_at ? $this->ride->updated_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
        ];
    }
}
