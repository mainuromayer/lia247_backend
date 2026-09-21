<?php

namespace App\Http\Resources\V1\Vendor;

use App\Http\Resources\V1\Vendor\BusinessResource;
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
            'user_type' => $this->user_type,
            'status' => $this->status,
            'business' => new BusinessResource($this->whenLoaded('business')),
            'is_favourite' => auth('api')->check() 
                ? Favorite::where([
                    'user_id' => auth('api')->id(),
                    'favouritable_id' => $this->id,
                    'favouritable_type' => User::class,
                ])->exists()
                : false,
            'email_notification' => (bool) $this->email_notification,
            'stripe_account_id' => $this->stripe_account_id,
            'onboard_complete' => (bool) $this->onboard_complete,
        ];
    }
}
