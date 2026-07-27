<?php

namespace App\Http\Controllers\API\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Customer\Ride\RideResource;
use App\Models\Ride;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Retrieve dashboard stats and upcoming trip
     */
    public function index()
    {
        try {
            $user = auth('api')->user() ?? Auth::user();

            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }

            // 1. Calculate Earnings (This Month)

            $thisMonthEarnings = Ride::where('rider_id', $user->id)
                ->where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->get()
                ->sum(function ($ride) {
                    return $ride->actual_fare;
                });

            // 2. Active Ride (Accepted Ride)
            $activeRide = Ride::where('rider_id', $user->id)
                ->whereIn('status', ['accepted', 'on_the_way', 'arrived', 'started'])
                ->with(['user', 'transportType'])
                ->latest()
                ->first();

            // 3. Current Running Trip Details
            $currentRunningTrip = null;
            if ($activeRide) {
                $pickupLat = $activeRide->pickup_lat;
                $pickupLng = $activeRide->pickup_lng;
                $dropoffLat = $activeRide->dropoff_lat;
                $dropoffLng = $activeRide->dropoff_lng;

                $remainingDistance = null;
                $duration = null;

                if ($pickupLat !== null && $pickupLng !== null && $dropoffLat !== null && $dropoffLng !== null) {
                    $distPickupToDropoff = (float)$activeRide->distance;
                    if ($distPickupToDropoff <= 0) {
                        $distPickupToDropoff = $this->calculateDistance(
                            (float)$pickupLat,
                            (float)$pickupLng,
                            (float)$dropoffLat,
                            (float)$dropoffLng
                        );
                    }

                    if ($user->latitude !== null && $user->longitude !== null) {
                        if ($activeRide->status === 'started') {
                            // Ride started, going to dropoff
                            $remainingDistance = $this->calculateDistance(
                                (float)$user->latitude,
                                (float)$user->longitude,
                                (float)$dropoffLat,
                                (float)$dropoffLng
                            );
                            $duration = (int)round($remainingDistance * 2.0);
                        } else {
                            // Going to pickup location first
                            $distToPickup = $this->calculateDistance(
                                (float)$user->latitude,
                                (float)$user->longitude,
                                (float)$pickupLat,
                                (float)$pickupLng
                            );
                            $remainingDistance = $distToPickup + $distPickupToDropoff;
                            $duration = (int)round(($remainingDistance * 2.0) + 3);
                        }
                    } else {
                        // Fallback
                        $remainingDistance = $distPickupToDropoff;
                        $duration = (int)round(($remainingDistance * 2.0) + 3);
                    }
                }

                $currentRunningTrip = [
                    'date' => $activeRide->created_at->format('Y-m-d'),
                    'time' => $activeRide->created_at->format('H:i:s'),
                    'pickup_location' => [
                        'address' => $activeRide->pickup_address,
                        'latitude' => $pickupLat !== null ? (float)$pickupLat : null,
                        'longitude' => $pickupLng !== null ? (float)$pickupLng : null,
                    ],
                    'dropoff_location' => [
                        'address' => $activeRide->dropoff_address,
                        'latitude' => $dropoffLat !== null ? (float)$dropoffLat : null,
                        'longitude' => $dropoffLng !== null ? (float)$dropoffLng : null,
                    ],
                    'remaining_duration' => $duration,
                    'remaining_distance' => $remainingDistance !== null ? round($remainingDistance, 2) : null,
                ];
            }

            return $this->success([
                'earning_of_this_month' => [
                    'this_month' => Carbon::now()->format('F Y'),
                    'this_month_earning' => (float)number_format($thisMonthEarnings, 2, '.', ''),
                ],
                'current_running_trip' => $currentRunningTrip,
                'current_location' => [
                    'address' => $user->address,
                    'latitude' => $user->latitude !== null ? (float)$user->latitude : null,
                    'longitude' => $user->longitude !== null ? (float)$user->longitude : null,
                ],
                'is_online' => (bool)$user->is_online,
            ], 'Dashboard stats retrieved successfully');

        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve dashboard stats: ' . $e->getMessage(), 500);
        }
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
