<?php

namespace App\Http\Resources\V1\Rider;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at->format('M d, Y h:iA'),
            'customer' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? 'Anonymous',
                'avatar' => $this->user->avatar ?? null,
            ],
            'ride' => [
                'id' => $this->ride_id,
                'ride_no' => $this->ride->ride_no ?? null,
                'total_amount' => $this->ride->total_amount ?? null,
            ],
        ];
    }
}
