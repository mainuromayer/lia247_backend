<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $rideId,
        public $readerId,
        public $receiverId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("ride.{$this->rideId}.chat")
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->rideId,
            'reader_id' => $this->readerId,
            'receiver_id' => $this->receiverId,
        ];
    }
}
