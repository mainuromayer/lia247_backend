<?php

namespace App\Http\Controllers\API\V1\Rider\Ride;

use App\Actions\GeoLocation;
use App\Events\RideLocationUpdated;
use App\Events\RiderStatusUpdated;
use App\Events\RideStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Rider\Ride\RideCompleteRequest;
use App\Http\Requests\API\V1\Rider\Ride\RideStatusUpdateRequest;
use App\Http\Resources\API\V1\Customer\Ride\RideResource;
use App\Mail\RideConfirmationCodeMail;
use App\Models\Ride;
use App\Models\RiderRideCancellation;
use App\Models\SystemSetting;
use App\Models\TransportType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommissionService;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Balance;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class RideController extends Controller
{
    use ApiResponse;

    /**
     * List Pending Ride Requests (Nearby Rides)
     */
    public function nearbyRides()
    {
        $rider = auth('api')->user() ?? Auth::user();
        if (!$rider || !$rider->is_online) {
            return $this->error([], 'You are currently offline or unauthorized. Please go online to view nearby rides.', 403);
        }

        $balance = $this->getRiderBalance($rider);

        $cancelledRideIds = $rider->riderRideCancellations()->pluck('ride_id')->toArray();

        $rides = Ride::where('status', 'pending')
            ->whereNotIn('id', $cancelledRideIds)
            ->with(['user', 'transportType'])
            ->latest()
            ->get();

        // Filter rides by balance (Stripe + Local)
        $rides = $rides->filter(function ($ride) use ($balance) {
            return $balance >= $ride->estimated_fare;
        });

        return $this->success(RideResource::collection($rides), 'Nearby rides retrieved');
    }

    /**
     * Accept a Ride
     */
    public function acceptRide($id)
    {
        try {
            $rider = auth('api')->user() ?? Auth::user();
            if (!$rider || !$rider->is_online) {
                return $this->error([], 'You are currently offline or unauthorized. Please go online to accept rides.', 403);
            }

            // Check if rider already has an active ride
            $hasActiveRide = Ride::where('rider_id', $rider->id)
                ->whereIn('status', ['accepted', 'on_the_way', 'arrived', 'started'])
                ->exists();

            if ($hasActiveRide) {
                return $this->error([], 'You already have an active ride. Please complete it before accepting another.', 400);
            }

            $ride = Ride::findOrFail($id);

            $balance = $this->getRiderBalance($rider);

            if ($balance < $ride->estimated_fare) {
                return $this->error([], 'Insufficient balance in your wallet to accept this ride. Estimated fare: ' . $ride->estimated_fare, 400);
            }

            $ride->rider_id = $rider->id;
            $ride->rider_address = $rider->address;
            $ride->rider_lat = $rider->latitude;
            $ride->rider_lng = $rider->longitude;
            $ride->status = 'accepted';

            // Stripe Authorization Hold at Acceptance (if not already captured/paid)
            if ($ride->payment_type === 'wallet' || $ride->payment_status === 'captured') {
                // Payment already captured/processed via Wallet or Stripe. No auth hold needed.
            } else {
                try {
                    $customer = $ride->user;
                    if (!$customer || !$customer->hasDefaultPaymentMethod()) {
                        return $this->error([], 'Customer does not have a valid payment method.', 400);
                    }

                    $amount = (int) ($ride->estimated_fare * 100);
                    $paymentMethod = $customer->defaultPaymentMethod();
                    
                    $paymentIntent = $customer->createPaymentIntent($amount, [
                        'customer' => $customer->stripe_id,
                        'payment_method' => $paymentMethod->id,
                        'capture_method' => 'manual',
                        'confirm' => true,
                        'off_session' => true,
                    ]);

                    $ride->payment_intent_id = $paymentIntent->id;
                    $ride->payment_status = 'authorized';
                } catch (Exception $e) {
                    Log::error('Stripe Auth Hold (Acceptance) Error: ' . $e->getMessage());
                    return $this->error([], 'Payment authorization failed for this ride. Customer might have insufficient funds.', 400);
                }
            }

            $ride->save();

            broadcast(new RideStatusUpdated($ride));
            broadcast(new \App\Events\RiderStatusUpdated($rider));

            // Notify Customer
            NotificationService::send(
                $ride->user_id,
                "Ride Accepted",
                "A rider ({$rider->name}) has accepted your ride request.",
                'ride',
                'customer',
                ['ride_id' => $ride->id]
            );

            return $this->success(new RideResource($ride), 'Ride accepted successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to accept ride: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a Ride (Before Starting)
     */
    public function cancelRide($id)
    {
        try {
            $riderId = auth('api')->id() ?? Auth::id();
            
            $ride = Ride::find($id);
            if (!$ride) {
                return $this->error([], 'Ride not found.', 404);
            }

            if ($ride->status === 'pending' && is_null($ride->rider_id)) {
                // The rider is rejecting a pending ride request
                RiderRideCancellation::firstOrCreate([
                    'rider_id' => $riderId,
                    'ride_id' => $ride->id,
                ]);
                return $this->success(null, 'Ride request rejected successfully');
            }

            if ((int)$ride->rider_id !== (int)$riderId) {
                return $this->error([], 'This ride is not assigned to you.', 403);
            }

            if (!in_array($ride->status, ['accepted', 'on_the_way', 'arrived'])) {
                return $this->error([], 'You cannot cancel the ride at this stage.', 400);
            }

            // Create cancellation record so they don't see it again
            RiderRideCancellation::firstOrCreate([
                'rider_id' => $riderId,
                'ride_id' => $ride->id,
            ]);

            $ride->status = 'pending';
            $ride->rider_id = null;
            $ride->rider_address = null;
            $ride->rider_lat = null;
            $ride->rider_lng = null;
            $ride->save();

            broadcast(new RideStatusUpdated($ride));

            return $this->success(new RideResource($ride), 'Ride canceled and put back to pending');
        } catch (Exception $e) {
            return $this->error([], 'Failed to cancel ride: ' . $e->getMessage());
        }
    }

    /**
     * Start the Ride
     */
    public function startRide($id)
    {
        try {
            $riderId = auth('api')->id() ?? Auth::id();
            Log::info("startRide hit: ride_id=" . $id . ", authenticated rider_id=" . ($riderId ?? 'NULL'));

            $ride = Ride::find($id);

            // 1. Check if the ride exists
            if (!$ride) {
                return $this->error([], 'Ride not found.', 404);
            }

            // 2. Check if the ride is assigned to the authenticated rider
            if ((int)$ride->rider_id !== (int)$riderId) {
                return $this->error([], 'This ride is not assigned to you.', 403);
            }

            // 3. Check if the ride status is valid for starting
            if ($ride->status !== 'arrived' && $ride->status !== 'accepted' && $ride->status !== 'ready') {
                return $this->error([], 'Invalid ride status to start. Current status: ' . $ride->status, 400);
            }

            $ride->status = 'started';
            $ride->start_time = now();
            $ride->save();

            broadcast(new RideStatusUpdated($ride));

            // Notify Customer
            NotificationService::send(
                $ride->user_id,
                "Ride Started",
                "Your ride has started. Have a safe journey!",
                'ride',
                'customer',
                ['ride_id' => $ride->id]
            );

            return $this->success(new RideResource($ride), 'Ride started');
        } catch (Exception $e) {
            Log::error('Failed to start ride ID ' . $id . ': ' . $e->getMessage());
            return $this->error([], 'Failed to start ride: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Complete the Ride and Calculate Fare
     */
    public function completeRide($id)
    {
        DB::beginTransaction();
        try {
            $riderId = auth('api')->id() ?? Auth::id();
            
            $ride = Ride::where('id', $id)->with('transportType')->first();
            if (!$ride) {
                return $this->error([], 'Ride not found.', 404);
            }

            if ((int)$ride->rider_id !== (int)$riderId) {
                return $this->error([], 'This ride is not assigned to you.', 403);
            }

            if ($ride->status !== 'started') {
                return $this->error([], 'Ride must be started before completing.', 400);
            }

            $ride->status = 'completed';
            $ride->end_time = now();
            
            // Calculate final amount based on Transport Type's Price Per KM
            $settings = SystemSetting::first();
            $vatPercentage = (float) ($settings->vat_percentage ?? 10.00);

            $distance = (float) $ride->distance;
            $pricePerKM = $ride->transportType ? (float) $ride->transportType->price_per_km : 0.00;
            
            $subtotal = $distance * $pricePerKM;
            $vatAmount = ($subtotal * $vatPercentage) / 100;
            $finalFare = $subtotal + $vatAmount;
            
            $ride->actual_fare = $finalFare;
            $ride->total_amount = $finalFare;
            
            // Stripe Capture Logic
            if ($ride->payment_intent_id && $ride->payment_status === 'authorized') {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = PaymentIntent::retrieve($ride->payment_intent_id);
                    // Capture actual amount (or up to authorized amount)
                    $captureAmount = (int) ($ride->total_amount * 100);
                    if ($captureAmount > $intent->amount) {
                        $captureAmount = $intent->amount; // Can't capture more than authorized
                    }
                    $intent->capture(['amount_to_capture' => $captureAmount]);
                    $ride->payment_status = 'captured';
                } catch (Exception $e) {
                    Log::error('Stripe Capture Error: ' . $e->getMessage());
                    // We might still want to proceed with wallet distribution if capture fails but we want to track it
                }
            }

            // Calculate Split for wallet distribution
            $rideCommission = (float) ($settings->ride_commission ?? 15.00);
            $driverPayoutPercent = 100 - $rideCommission;
            
            $split = CommissionService::calculateRideSplit($subtotal, $driverPayoutPercent);
            
            $ride->commission_amount = $split['admin_amount'] + $vatAmount;
            $ride->save();

            broadcast(new RideStatusUpdated($ride));
            $rider = auth('api')->user() ?? Auth::user();
            if ($rider) {
                broadcast(new RiderStatusUpdated($rider));
            }

            // Credit rider's wallet and debit admin's wallet
            if ($split['rider_amount'] > 0) {
                $admin = User::where('user_type', 'admin')->first();
                if ($admin) {
                    try {
                        WalletService::debit(
                            $admin->id,
                            $split['rider_amount'],
                            'ride_payout_rider',
                            $ride,
                            'Payout to Rider for ride #' . $ride->ride_no
                        );
                    } catch (Exception $ex) {
                        Log::error('Admin Wallet Debit (Ride Completion) Error: ' . $ex->getMessage());
                    }
                }

                WalletService::credit(
                    $ride->rider_id, 
                    $split['rider_amount'], 
                    'ride_earning', 
                    $ride, 
                    'Earning for ride #' . $ride->ride_no
                );
            }

            // Notify Customer
            NotificationService::send(
                $ride->user_id,
                "Ride Completed",
                "Your ride is complete. Total fare: {$ride->total_amount}.",
                'ride',
                'customer',
                ['ride_id' => $ride->id, 'fare' => $ride->total_amount]
            );

            DB::commit();

            return $this->success(new RideResource($ride), 'Ride completed and earnings added to wallet');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to complete ride: ' . $e->getMessage());
        }
    }

    /**
     * Update Ride Status (On the way, Arrived, Started)
     */
    public function updateStatus(RideStatusUpdateRequest $request, $id)
    {
        $status = $request->status;

        // Map status aliases to standard names
        if (in_array($status, ['accept', 'accepted'])) {
            $mappedStatus = 'accepted';
        } elseif ($status === 'on_the_way') {
            $mappedStatus = 'on_the_way';
        } elseif ($status === 'arrived') {
            $mappedStatus = 'arrived';
        } elseif ($status === 'ready') {
            $mappedStatus = 'ready';
        } elseif (in_array($status, ['start', 'started'])) {
            $mappedStatus = 'started';
        } elseif (in_array($status, ['complete', 'completed'])) {
            $mappedStatus = 'completed';
        } elseif (in_array($status, ['cancel', 'canceled', 'cancelled'])) {
            $mappedStatus = 'canceled';
        } else {
            return $this->error([], 'Invalid status input: ' . $status, 400);
        }

        try {
            $ride = Ride::find($id);
            if (!$ride) {
                return $this->error([], 'Ride not found.', 404);
            }

            $currentStatus = $ride->status;

            // Sequential transitions: accepted -> on_the_way -> arrived -> ready -> started -> completed
            $statusOrder = ['accepted', 'on_the_way', 'arrived', 'ready', 'started', 'completed'];
            $currentIndex = array_search($currentStatus, $statusOrder);
            $inputIndex = array_search($mappedStatus, $statusOrder);

            if ($inputIndex !== false) {
                if ($currentIndex !== false) {
                    if ($inputIndex <= $currentIndex) {
                        return $this->error([], "The rider is already " . $currentStatus . ".", 400);
                    }
                    if ($inputIndex > $currentIndex + 1) {
                        $msgs = [
                            'on_the_way' => 'Ride must be accepted before going on the way.',
                            'arrived' => 'Ride must be on the way before arriving.',
                            'ready' => 'Ride must be arrived before marking as ready.',
                            'started' => 'Ride must be ready before starting.',
                            'completed' => 'Ride must be started before completing.',
                        ];
                        $msg = $msgs[$mappedStatus] ?? "Invalid status transition.";
                        return $this->error([], $msg, 400);
                    }
                } else {
                    // Current status is pending (or other)
                    if ($mappedStatus !== 'accepted') {
                        return $this->error([], 'Ride must be accepted first.', 400);
                    }
                }
            }

            // Forward to helper methods if applicable
            if ($mappedStatus === 'accepted') {
                return $this->acceptRide($id);
            }

            if ($mappedStatus === 'canceled') {
                return $this->cancelRide($id);
            }

            if ($mappedStatus === 'started') {
                return $this->startRide($id);
            }

            if ($mappedStatus === 'completed') {
                return $this->completeRide($id);
            }

            // Otherwise, update inline status for on_the_way, arrived, ready
            $rider = auth('api')->user() ?? Auth::user();
            if ((int)$ride->rider_id !== (int)$rider->id) {
                return $this->error([], 'This ride is not assigned to you.', 403);
            }

            // GPS Verification for 'arrived' or 'ready'
            if (in_array($mappedStatus, ['arrived', 'ready'])) {
                $distanceToPickup = $this->calculateDistance(
                    $rider->latitude, $rider->longitude,
                    $ride->pickup_lat, $ride->pickup_lng
                );

                // Allow if within 200 meters
                // if ($distanceToPickup > 0.2) { 
                //     return $this->error([], 'You must be at the pickup location to update status to ' . $mappedStatus . '.', 400);
                // }
            }

            $ride->status = $mappedStatus;
            
            if ($mappedStatus === 'arrived') {
                $ride->arrived_at = now();
            } elseif ($mappedStatus === 'ready') {
                $ride->ready_at = now();
            }

            $ride->save();

            broadcast(new RideStatusUpdated($ride));

            // Notify Customer
            $statusMessages = [
                'on_the_way' => "Your rider is on the way.",
                'arrived' => "Your rider has arrived at the pickup location.",
                'ready' => "Your rider is ready and waiting for you. Please arrive within 5 minutes.",
            ];

            if (isset($statusMessages[$mappedStatus])) {
                NotificationService::send(
                    $ride->user_id,
                    "Ride Update",
                    $statusMessages[$mappedStatus],
                    'ride',
                    'customer',
                    ['ride_id' => $ride->id, 'status' => $mappedStatus]
                );
            }

            return $this->success(new RideResource($ride), 'Ride status updated to ' . $mappedStatus);
        } catch (Exception $e) {
            return $this->error([], 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Driver No-Show Cancel (After 5 mins of ready)
     */
    public function noShowCancel($id)
    {
        DB::beginTransaction();
        try {
            $riderId = auth('api')->id() ?? Auth::id();
            
            $ride = Ride::find($id);
            if (!$ride) {
                return $this->error([], 'Ride not found.', 404);
            }

            if ((int)$ride->rider_id !== (int)$riderId) {
                return $this->error([], 'This ride is not assigned to you.', 403);
            }

            if ($ride->status !== 'ready') {
                return $this->error([], 'Ride must be in "ready" status to report no-show.', 400);
            }

            $readyAt = Carbon::parse($ride->ready_at);
            if (now()->diffInMinutes($readyAt) < 5) {
                return $this->error([], 'You must wait at least 5 minutes after being ready.', 400);
            }

            // Report No-Show: No fees charged as per new policy, release hold
            if ($ride->payment_intent_id && $ride->payment_status === 'authorized') {
                try {
                    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = \Stripe\PaymentIntent::retrieve($ride->payment_intent_id);
                    $intent->cancel();
                    $ride->payment_status = 'canceled';
                } catch (Exception $e) {
                    Log::error('Stripe Release Hold (No-Show) Error: ' . $e->getMessage());
                }
            }

            $ride->status = 'no_show';
            $ride->actual_fare = 0;
            $ride->total_amount = 0;
            $ride->commission_amount = 0;
            $ride->save();

            broadcast(new RideStatusUpdated($ride));
            
            // Notify Customer
            NotificationService::send(
                $ride->user_id,
                "Ride Canceled (No-Show)",
                "You didn't show up for your ride.",
                'ride',
                'customer',
                ['ride_id' => $ride->id]
            );

            DB::commit();
            return $this->success(new RideResource($ride), 'Ride canceled as no-show. Fee applied.');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to cancel as no-show: ' . $e->getMessage());
        }
    }

    /**
     * Calculate Distance between two coordinates (Haversine formula) in KM
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }



    /**
     * Update Location of the Rider for an Active Ride
     */
    public function updateLocation(Request $request, GeoLocation $geoLocation)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $user = auth('api')->user() ?? Auth::user();
            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            
            // Resolve and update address
            $address = $geoLocation->getAddress($request->latitude, $request->longitude);
            if ($address) {
                $user->address = $address;
            }
            $user->save();

            Log::info("updateLocation hit for rider ID: " . $user->id);

            $rides = Ride::where('rider_id', $user->id)
                ->whereIn('status', ['accepted', 'on_the_way', 'arrived', 'ready', 'started'])
                ->get();

            Log::info("Active rides found for rider ID " . $user->id . ": " . $rides->count() . " rides. Ride IDs: " . $rides->pluck('id')->join(', '));

            foreach ($rides as $ride) {
                // Broadcast the location update for each active ride
                broadcast(new RideLocationUpdated($ride->id, $request->latitude, $request->longitude));
            }

            return $this->success([
                'latitude' => (string) $request->latitude,
                'longitude' => (string) $request->longitude,
            ], 'Location updated successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to update location: ' . $e->getMessage());
        }
    }

    /**
     * Get Rider's Effective Balance (Stripe + Local Wallet)
     */
    private function getRiderBalance($rider)
    {
        $balance = 0;
        
        // 1. Check Stripe Connect balance if available
        if ($rider->stripe_account_id) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $stripeBalance = Balance::retrieve(null, ['stripe_account' => $rider->stripe_account_id]);
                $balance = collect($stripeBalance->available)->sum('amount') / 100;
            } catch (Exception $e) {
                Log::error('Stripe Balance Error: ' . $e->getMessage());
            }
        }

        // 2. Add local wallet balance if any
        $wallet = Wallet::where('user_id', $rider->id)->where('user_type', 'rider')->first();
        if (!$wallet) {
            $existingWallet = Wallet::where('user_id', $rider->id)->first();
            if ($existingWallet) {
                $existingWallet->user_type = 'rider';
                $existingWallet->save();
                $wallet = $existingWallet;
            }
        }
        if ($wallet) {
            $balance += $wallet->balance;
        }

        return $balance;
    }

    /**
     * Ride History for Rider
     */
    public function history(Request $request)
    {
        try {
            $riderId = auth('api')->id() ?? Auth::id();
            $query = Ride::where('rider_id', $riderId);
            
            if ($request->has('tab')) {
                $tab = $request->tab;
                if ($tab === 'active') {
                    $query->whereIn('status', ['accepted', 'on_the_way', 'arrived', 'started']);
                } elseif ($tab === 'completed') {
                    $query->where('status', 'completed');
                } elseif ($tab === 'cancelled') {
                    $query->whereIn('status', ['canceled', 'cancelled', 'no_show']);
                }
            } elseif ($request->status) {
                $status = $request->status;
                if ($status === 'canceled' || $status === 'cancelled') {
                    $query->whereIn('status', ['canceled', 'cancelled']);
                } else {
                    $query->where('status', $status);
                }
            }

            $rides = $query->with(['user', 'transportType'])
                ->latest()
                ->get();
                
            return $this->success(RideResource::collection($rides), 'Ride history retrieved');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve ride history: ' . $e->getMessage());
        }
    }

    /**
     * View Details of a Single Ride (Order Summary / Order View)
     */
    public function show($id)
    {
        try {
            $riderId = auth('api')->id() ?? Auth::id();
            
            $ride = Ride::with(['user', 'transportType'])->find($id);
            if (!$ride) {
                return $this->error([], 'Ride not found.', 404);
            }

            if ((int)$ride->rider_id !== (int)$riderId) {
                return $this->error([], 'This ride is not assigned to you.', 403);
            }

            return $this->success(new RideResource($ride), 'Ride details retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve ride details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the current realtime ride request offered to the rider (30 seconds queue window)
     */
    public function currentRequest()
    {
        try {
            $rider = auth('api')->user() ?? Auth::user();
            if (!$rider || !$rider->is_online) {
                return $this->error([], 'You are currently offline or unauthorized. Please go online to view nearby rides.', 403);
            }

            $cancelledRideIds = $rider->riderRideCancellations()->pluck('ride_id')->toArray();

            // Find the latest pending ride
            $ride = Ride::where('status', 'pending')
                ->whereNotIn('id', $cancelledRideIds)
                ->whereNull('rider_id')
                ->with(['user', 'transportType'])
                ->latest()
                ->first();

            if (!$ride) {
                return $this->success(null, 'No new ride requests');
            }

            // Calculate when the ride will expire (30 seconds from creation)
            $expiresInSeconds = max(0, 30 - now()->diffInSeconds($ride->created_at));

            return $this->success([
                'ride' => new RideResource($ride),
                'expires_in_seconds' => $expiresInSeconds,
            ], 'Current ride request retrieved');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve current request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the current location of the authenticated rider.
     */
    public function getLocation()
    {
        try {
            $user = auth('api')->user() ?? Auth::user();
            return $this->success([
                'latitude' => $user ? ($user->latitude ? (float) $user->latitude : null) : null,
                'longitude' => $user ? ($user->longitude ? (float) $user->longitude : null) : null,
            ], 'Current location retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve location: ' . $e->getMessage(), 500);
        }
    }
}
