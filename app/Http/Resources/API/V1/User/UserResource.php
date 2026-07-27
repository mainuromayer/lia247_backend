<?php

namespace App\Http\Resources\API\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'avatar' => $this->avatar,
            'cover_photo' => $this->cover_photo,
            'email_verified_at' => $this->email_verified_at,
            'push_notification' => (bool) $this->push_notification,
            'order_request_notification' => (bool) $this->order_request_notification,
            'rating' => (float) $this->average_rating,
        ];
    }
}
