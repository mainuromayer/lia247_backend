<?php

namespace App\Http\Resources\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor' => [
                'id' => $this->vendor_id,
                'name' => $this->vendor?->name,
                'business_name' => $this->vendor?->business?->business_name,
                'business_address' => $this->vendor?->business?->business_address,
                'logo' => $this->vendor?->avatar,
                'free_delivery' => (bool) $this->vendor?->business?->free_delivery,
                'rating' => (float) $this->vendor?->average_rating,
            ],
            'category_name' => $this->category?->name,
            'category_slug' => $this->category?->slug,
            'name' => $this->name,
            'slug' => $this->slug,
            'cooking_time' => $this->cooking_time,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'rating' => (float) $this->average_rating,
            'status' => $this->status,
            'distance' => round($this->distance, 2) . ' km',
            'variants' => $this->whenLoaded('variants'),
            'toppings' => $this->whenLoaded('toppings'),
            'is_favourite' => auth('api')->check() 
                ? \App\Models\Favorite::where([
                    'user_id' => auth('api')->id(),
                    'favouritable_id' => $this->id,
                    'favouritable_type' => \App\Models\Item::class,
                ])->exists()
                : false,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
