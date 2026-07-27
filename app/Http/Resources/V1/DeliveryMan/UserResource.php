<?php

namespace App\Http\Resources\V1\DeliveryMan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\DeliveryMan\DrivingResource;
use App\Http\Resources\V1\DeliveryMan\VehicleResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'avatar' => $this->avatar,
            'cover_photo' => $this->cover_photo,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'nid' => $this->nid,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'terms_and_conditions' => (bool) $this->terms_and_conditions,
            'user_type' => $this->user_type,
            'status' => $this->status,
            'vehicle_information' => new VehicleResource($this->whenLoaded('vehicleInformation')),
            'driving_information' => new DrivingResource($this->whenLoaded('drivingInformation')),
            'push_notification' => (bool) $this->push_notification,
            'order_request_notification' => (bool) $this->order_request_notification,
            'is_online' => (bool) $this->is_online,
            'wallet_balance' => (float) ($this->wallet?->balance ?? 0.00),
        ];
    }
}
