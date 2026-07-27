<?php

namespace App\Http\Resources\Customer\SupportChat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $files = is_string($this->files) ? json_decode($this->files, true) : $this->files;
        if (!is_array($files) && !empty($this->files)) {
            $files = [$this->files];
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'files' => collect($files ?? [])->map(function ($file) {
                return asset($file);
            }),
            'sender_type' => $this->sender_type,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
