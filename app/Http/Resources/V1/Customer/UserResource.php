<?php

namespace App\Http\Resources\V1\Customer;

use App\Models\Favorite;
use App\Models\User;
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
            'email_verified_at' => $this->email_verified_at,
            'is_favourite' => auth('api')->check() 
                ? Favorite::where([
                    'user_id' => auth('api')->id(),
                    'favouritable_id' => $this->id,
                    'favouritable_type' => User::class,
                ])->exists()
                : false,
            'push_notification' => (bool) $this->push_notification,
            'email_notification' => (bool) $this->email_notification,
            'rating' => (float) $this->average_rating,
        ];
    }
}
