<?php

namespace App\Http\Resources\V1\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'business_description' => $this->business_description,
            'business_phone' => $this->business_phone,
            'business_address' => $this->business_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'license_number' => $this->license_number,
            'cover_photo' => $this->cover_photo ? url($this->cover_photo) : null,
            'free_delivery' => (bool) $this->free_delivery,
            'free_delivery_type' => $this->free_delivery_type,
            'admin_contribution' => (float) $this->admin_contribution,
            'vendor_contribution' => (float) $this->vendor_contribution,
        ];
    }
}
