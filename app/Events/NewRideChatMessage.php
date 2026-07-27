<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewRideChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {
        Log::info('New Ride Chat Message: ' . $this->message->id);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("ride.{$this->message->ride_id}.chat")
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'ride_id' => $this->message->ride_id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message' => $this->message->message,
            'files' => $this->message->files ? array_map(fn($file) => url($file), $this->message->files) : [],
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at,
            'sender' => $this->message->sender ? [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
                'user_type' => $this->message->sender->user_type,
            ] : null,
            'receiver' => $this->message->receiver ? [
                'id' => $this->message->receiver->id,
                'name' => $this->message->receiver->name,
                'avatar' => $this->message->receiver->avatar,
                'user_type' => $this->message->receiver->user_type,
            ] : null,
        ];
    }
}
