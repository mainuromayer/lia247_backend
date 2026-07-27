<?php

namespace App\Http\Controllers\API\V1\DeliveryMan;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\OrderResource;
use App\Models\Order;
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
            $user = Auth::user();

            // 1. Calculate Earnings (This Month)

            $thisMonthEarnings = Order::where('delivery_man_id', $user->id)
                ->whereIn('status', ['completed', 'delivered'])
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->get()
                ->sum(function ($order) {
                    return $order->delivery_fee + ($order->admin_delivery_contribution ?? 0) + ($order->vendor_delivery_contribution ?? 0);
                });

            // 2. Active Order (Accepted Order)
            $activeOrder = Order::where('delivery_man_id', $user->id)
                ->whereIn('status', ['accepted', 'preparing', 'ready', 'picked'])
                ->with(['user', 'vendor.business', 'deliveryMan'])
                ->latest()
                ->first();

            // 3. Current Running Trip Details
            $currentRunningTrip = null;
            if ($activeOrder) {
                $vendorLat = $activeOrder->vendor?->business?->latitude;
                $vendorLng = $activeOrder->vendor?->business?->longitude;
                $customerLat = $activeOrder->latitude;
                $customerLng = $activeOrder->longitude;

                $remainingDistance = null;
                $duration = null;

                if ($vendorLat !== null && $vendorLng !== null && $customerLat !== null && $customerLng !== null) {
                    $distVendorToCustomer = $this->calculateDistance(
                        (float)$vendorLat,
                        (float)$vendorLng,
                        (float)$customerLat,
                        (float)$customerLng
                    );

                    if ($user->latitude !== null && $user->longitude !== null) {
                        if ($activeOrder->status === 'picked') {
                            // Already picked up, going to customer
                            $remainingDistance = $this->calculateDistance(
                                (float)$user->latitude,
                                (float)$user->longitude,
                                (float)$customerLat,
                                (float)$customerLng
                            );
                            $duration = (int)round($remainingDistance * 2.4);
                        } else {
                            // Going to vendor first, then customer
                            $distToVendor = $this->calculateDistance(
                                (float)$user->latitude,
                                (float)$user->longitude,
                                (float)$vendorLat,
                                (float)$vendorLng
                            );
                            $remainingDistance = $distToVendor + $distVendorToCustomer;
                            $duration = (int)round(($remainingDistance * 2.4) + 5);
                        }
                    } else {
                        // Fallback if rider coords not available
                        $remainingDistance = $distVendorToCustomer;
                        $duration = (int)round(($remainingDistance * 2.4) + 5);
                    }
                }

                $currentRunningTrip = [
                    'date' => $activeOrder->created_at->format('Y-m-d'),
                    'time' => $activeOrder->created_at->format('H:i:s'),
                    'pickup_location' => [
                        'address' => $activeOrder->vendor?->business?->business_address,
                        'latitude' => $vendorLat !== null ? (float)$vendorLat : null,
                        'longitude' => $vendorLng !== null ? (float)$vendorLng : null,
                    ],
                    'dropoff_location' => [
                        'address' => $activeOrder->receiver_address,
                        'latitude' => $customerLat !== null ? (float)$customerLat : null,
                        'longitude' => $customerLng !== null ? (float)$customerLng : null,
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
