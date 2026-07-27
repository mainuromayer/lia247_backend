<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RideLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rideId;
    public $orderId; // For compatibility with frontend if it expects orderId
    public $latitude;
    public $longitude;

    public function __construct($rideId, $latitude, $longitude)
    {
        $this->rideId = $rideId;
        $this->orderId = $rideId; // Set orderId to rideId for compatibility
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        Log::info('Ride Location Updated: ' . $this->rideId . ' ' . $this->latitude . ' ' . $this->longitude);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('ride.' . $this->rideId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ride.location.updated';
    }
}
