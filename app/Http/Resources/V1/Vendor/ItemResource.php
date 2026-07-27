<?php

namespace App\Http\Resources\V1\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\V1\Vendor\ItemVariantResource;
use App\Http\Resources\V1\Vendor\ItemToppingResource;

class ItemResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', function() { return $this->category?->name; }),
            'category_slug' => $this->whenLoaded('category', function() { return $this->category?->slug; }),
            'name' => $this->name,
            'slug' => $this->slug,
            'cooking_time' => $this->cooking_time,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'status' => $this->status,
            'variants' => ItemVariantResource::collection($this->whenLoaded('variants')),
            'toppings' => ItemToppingResource::collection($this->whenLoaded('toppings')),
        ];
    }
}
