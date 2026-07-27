<?php

namespace App\Events;

use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ride $ride) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("rider.available")
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.requested';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->ride->id,
            'status' => $this->ride->status,
            'pickup_address' => $this->ride->pickup_address,
            'dropoff_address' => $this->ride->dropoff_address,
            'distance' => $this->ride->distance,
            'estimated_fare' => $this->ride->estimated_fare,
            'customer_name' => $this->ride->user->name,
            'transport_type' => $this->ride->transportType->name ?? null,
            'created_at' => $this->ride->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
