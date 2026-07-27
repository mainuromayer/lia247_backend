<?php

namespace App\Http\Resources\API\V1\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->when($this->order_id !== null, (int) $this->order_id),
            'ride_id' => $this->when($this->ride_id !== null, (int) $this->ride_id),
            'sender_id' => (int) $this->sender_id,
            'receiver_id' => (int) $this->receiver_id,
            'message' => $this->message,
            'files' => $this->files ? array_map(fn($file) => url($file), $this->files) : [],
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at,
            'sender' => $this->sender ? [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar,
                'user_type' => $this->sender->user_type,
            ] : null,
            'receiver' => $this->receiver ? [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'avatar' => $this->receiver->avatar,
                'user_type' => $this->receiver->user_type,
            ] : null,
        ];
    }
}
