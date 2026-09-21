<?php

namespace App\Http\Resources\V1\DeliveryMan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrivingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reports = [];
        foreach ($this->police_reports ?? [] as $report) {
            $reports[] = asset($report);
        }

        return [
            'id' => $this->id,
            'driving_license' => $this->driving_license ?? '',
            'driving_experience' => $this->driving_experience ?? '',
            'police_reports' => $reports,
            'front_prfile_selfie' => $this->front_prfile_selfie ? asset($this->front_prfile_selfie) : '',
            'side_profile_selfie' => $this->side_profile_selfie ? asset($this->side_profile_selfie) : '',
        ];
    }
}
