<?php

namespace App\Http\Resources\API\V1\Customer\Ride;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = SystemSetting::first();
        $vatPercentage = (float) ($settings->vat_percentage ?? 10.00);

        $baseFare = ($this->status === 'completed' && $this->actual_fare) ? (float)$this->actual_fare : (float)$this->estimated_fare;
        $subtotal = $baseFare / (1 + $vatPercentage / 100);
        $vatAmount = $baseFare - $subtotal;

        return [
            'id' => $this->id,
            'ride_no' => $this->ride_no,
            'customer' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ] : null,
            'transport_type' => $this->transportType ? [
                'id' => $this->transportType->id,
                'name' => $this->transportType->name,
                'icon' => $this->transportType->icon,
            ] : null,
            'rider' => $this->rider ? [
                'id' => $this->rider->id,
                'name' => $this->rider->name,
                'phone' => $this->rider->phone,
                'avatar' => $this->rider->avatar,
                'rating' => $this->rider->average_rating,
                'reviews_count' => $this->rider->reviews()->where('role', 'rider')->count(),
                'latitude' => $this->rider->latitude ? (float) $this->rider->latitude : null,
                'longitude' => $this->rider->longitude ? (float) $this->rider->longitude : null,
                'address' => $this->rider->address,
                'vehicle' => $this->rider->vehicleInformation,
            ] : null,
            'pickup' => [
                'address' => $this->pickup_address,
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
            ],
            'dropoff' => [
                'address' => $this->dropoff_address,
                'lat' => $this->dropoff_lat,
                'lng' => $this->dropoff_lng,
            ],
            'distance' => $this->distance,
            'fare' => [
                'estimated' => $this->estimated_fare,
                'subtotal' => (float) round($subtotal, 2),
                'vat_amount' => (float) round($vatAmount, 2),
                'total' => $this->total_amount,
            ],
            'status' => $this->status,
            'cancel_reason' => $this->cancel_reason,
            'payment_status' => $this->payment_status,
            'start_time' => $this->start_time ? $this->start_time->format('Y-m-d H:i:s') : null,
            'end_time' => $this->end_time ? $this->end_time->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
