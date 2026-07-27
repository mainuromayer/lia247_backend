<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vendorLat = $this->vendor?->business?->latitude;
        $vendorLng = $this->vendor?->business?->longitude;
        $customerLat = $this->latitude;
        $customerLng = $this->longitude;

        $distance = null;
        $duration = null;

        if ($vendorLat && $vendorLng && $customerLat && $customerLng) {
            $distance = $this->calculateDistance(
                (float) $vendorLat,
                (float) $vendorLng,
                (float) $customerLat,
                (float) $customerLng
            );
            if ($distance !== null) {
                // Assuming average speed 25 km/h -> 2.4 mins per km, adding 5 mins buffer for prep/handover
                $duration = (int) round(($distance * 2.4) + 5);
            }
        }

        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'customer' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->receiver_name ?? $this->user->name,
                'phone' => $this->receiver_phone ?? $this->user->phone,
                'avatar' => $this->user->avatar,
            ] : null,
            'vendor' => $this->vendor ? [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
                'business_name' => $this->vendor->business?->business_name,
                'business_description' => $this->vendor->business?->business_description,
                'business_type' => $this->vendor->business?->business_type,
                'cover_photo' => $this->vendor->business?->cover_photo,
                'business_address' => $this->vendor->business?->business_address,
                'latitude' => $vendorLat,
                'longitude' => $vendorLng,
                'phone' => $this->vendor->business?->business_phone ?? $this->vendor->phone,
            ] : null,
            'delivery_man' => $this->deliveryMan ? [
                'id' => $this->deliveryMan->id,
                'name' => $this->deliveryMan->name,
                'phone' => $this->deliveryMan->phone,
                'latitude' => $this->deliveryMan->latitude,
                'longitude' => $this->deliveryMan->longitude,
                'avg_ratings' => (float) $this->deliveryMan->average_rating,
                'total_delivery_complete' => (int) $this->deliveryMan->completed_deliveries_count,
            ] : null,
            'pickup_point' => [
                'address' => $this->vendor?->business?->business_address,
                'latitude' => $vendorLat,
                'longitude' => $vendorLng,
                'business_name' => $this->vendor?->business?->business_name,
                'phone' => $this->vendor?->business?->business_phone ?? $this->vendor?->phone,
                'avatar' => $this->vendor?->business?->cover_photo ?? $this->vendor?->avatar,
            ],
            'destination' => [
                'address' => $this->receiver_address,
                'latitude' => $customerLat,
                'longitude' => $customerLng,
                'receiver_name' => $this->receiver_name,
                'receiver_phone' => $this->receiver_phone,
                'avatar' => $this->user?->avatar,
            ],
            'totals' => [
                'subtotal' => $this->subtotal,
                'delivery_fee' => $this->delivery_fee,
                'vat' => $this->vat_amount,
                'discount' => $this->discount_amount,
                'payable' => $this->total_amount,
            ],
            'earnings' => (float) ($this->delivery_fee + ($this->admin_delivery_contribution ?? 0) + ($this->vendor_delivery_contribution ?? 0)),
            'estimated_distance' => $distance !== null ? $distance . ' km' : null,
            'estimated_duration' => $duration !== null ? $duration . ' min' : null,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'cancel_reason' => $this->cancel_reason,
            'payment_status' => $this->payment_status,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Calculate straight-line distance using the Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }
}
