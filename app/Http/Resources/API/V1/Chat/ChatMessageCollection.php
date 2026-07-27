<?php

namespace App\Http\Resources\API\V1\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ChatMessageCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => ChatMessageResource::collection($this->collection),
        ];
    }
}
