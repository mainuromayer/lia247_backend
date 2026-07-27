<?php

namespace App\Http\Controllers\API\V1\Customer\Location;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\UserLocation;
use App\Actions\GeoLocation;

class LocationController extends Controller
{
    use ApiResponse;

    protected $geoLocation;

    public function __construct(GeoLocation $geoLocation)
    {
        $this->geoLocation = $geoLocation;
    }

    public function updateCurrentLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        try {
            $user = Auth::user();

            $address = $request->address;

            // If address is not provided in request, try to fetch from Google Maps
            if (empty($address)) {
                $address = $this->geoLocation->getAddress($request->latitude, $request->longitude);
            }

            $user->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'address' => $address ?? $user->address,
            ]);

            return $this->success([
                'address' => $address,
                'latitude' => (string) $request->latitude,
                'longitude' => (string) $request->longitude,
            ], "Current location updated successfully", 200);

        }
        catch (Exception $e) {
            Log::error('Current Location update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update current location: ' . $e->getMessage(), 500);
        }
    }

    public function setLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:home,office,others',
            'location' => 'required|string',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        try {
            $user = Auth::user();

            $userLocation = UserLocation::create([
                'user_id' => $user->id,
                'type' => $request->type,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            return $this->success($userLocation, "Location added successfully", 200);

        }
        catch (Exception $e) {
            Log::error('Location set failed: ' . $e->getMessage());
            return $this->error([], 'Failed to add location: ' . $e->getMessage(), 500);
        }
    }

    public function getLocationList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:home,office,others',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        try {
            $user = Auth::user();
            $query = UserLocation::where('user_id', $user->id);

            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            $locations = $query->latest()->get();

            return $this->success($locations, "Locations retrieved successfully", 200);
        }
        catch (Exception $e) {
            Log::error('Location list fetch failed: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve locations: ' . $e->getMessage(), 500);
        }
    }

    public function deleteLocation($id)
    {
        try {
            $user = Auth::user();
            $location = UserLocation::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$location) {
                return $this->error([], 'Location not found or unauthorized', 404);
            }

            $location->delete();

            return $this->success([], "Location deleted successfully", 200);

        }
        catch (Exception $e) {
            Log::error('Location delete failed: ' . $e->getMessage());
            return $this->error([], 'Failed to delete location: ' . $e->getMessage(), 500);
        }
    }
}