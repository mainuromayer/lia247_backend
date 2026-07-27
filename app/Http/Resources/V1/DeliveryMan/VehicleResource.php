<?php

namespace App\Http\Resources\V1\DeliveryMan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photos = [];
        foreach ($this->vehicle_photos ?? [] as $photo) {
            $photos[] = asset($photo);
        }

        return [
            'id' => $this->id,
            'vehicle_photos' => $photos,
            'license_front' => $this->license_front ? asset($this->license_front) : '',
            'license_back' => $this->license_back ? asset($this->license_back) : '',
            'vehicle_type' => $this->vehicle_type ?? '',
            'vehicle_model' => $this->vehicle_model ?? '',
            'vehicle_color' => $this->vehicle_color ?? '',
            'insurance_number' => $this->insurance_number ?? '',
            'manufacture_year' => $this->manufacture_year ?? '',
            'max_occupancy' => $this->max_occupancy ?? '',
            'license_number' => $this->license_number ?? '',
        ];
    }
}
