<?php

namespace App\Http\Resources\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopularVendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $distance = (float) ($this->distance ?? 0);
        
        // Simple time estimation: 5 mins base + 2 mins per KM
        $estimatedTime = 5 + round($distance * 2);
        
        return [
            'vendor_id' => $this->id,
            'vendor_name' => $this->name,
            'vendor_image' => $this->avatar,
            'discount_price' => 0, // Placeholder if no global vendor discount exists
            'business_image' => $this->business?->cover_photo,
            'business_name' => $this->business?->business_name,
            'type' => 'Restaurant',
            'estimated_time' => $estimatedTime . ' min',
            'distance_km' => round($distance, 2),
            'rating' => (float) $this->average_rating,
            'free_delivery' => (bool) $this->business?->free_delivery,
            'is_favourite' => $this->is_favourite ?? false,
        ];
    }
}
