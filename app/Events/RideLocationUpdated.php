<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RideLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rideId;
    public $latitude;
    public $longitude;

    public function __construct($rideId, $latitude, $longitude)
    {
        $this->rideId = $rideId;
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
