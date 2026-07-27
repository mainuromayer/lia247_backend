<?php

namespace App\Events;

use App\Models\SupportChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSupportChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportChat $message) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("support.{$this->message->user_id}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'message' => $this->message->message,
            'files' => $this->message->files,
            'sender_type' => $this->message->sender_type,
            'created_at' => $this->message->created_at,
        ];
    }
}