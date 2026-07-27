<?php

namespace App\Http\Resources\API\V1\Customer\Ride;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Ride;
use App\Models\Review;

class RiderProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get Completed Rides Count
        $completedRidesCount = Ride::where('rider_id', $this->id)
            ->where('status', 'completed')
            ->count();

        // Fetch reviews list
        $reviews = Review::where('target_id', $this->id)
            ->where('role', 'rider')
            ->with('user:id,name,avatar')
            ->latest()
            ->get();

        $reviewsArray = [];
        foreach ($reviews as $review) {
            $reviewsArray[] = [
                'id' => $review->id,
                'reviewer_name' => $review->user?->name ?? 'Anonymous',
                'reviewer_avatar' => $review->user?->avatar,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->diffForHumans(),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'description' => $this->description ?? 'No description available',
            'average_rating' => $this->average_rating,
            'total_reviews' => count($reviewsArray),
            'total_rides_shared' => $completedRidesCount,
            'vehicle' => $this->vehicleInformation ? [
                'vehicle_model' => $this->vehicleInformation->vehicle_model,
                'vehicle_color' => $this->vehicleInformation->vehicle_color,
                'license_number' => $this->vehicleInformation->license_number,
                'manufacture_year' => $this->vehicleInformation->manufacture_year,
            ] : null,
            'reviews' => $reviewsArray
        ];
    }
}
