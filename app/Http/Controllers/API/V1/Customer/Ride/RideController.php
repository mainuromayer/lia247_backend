<?php

namespace App\Http\Controllers\API\V1\Customer\Ride;

use \Stripe\PaymentIntent;
use App\Events\RideRequested;
use App\Events\RideStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Customer\Ride\RideCancelRequest;
use App\Http\Requests\API\V1\Customer\Ride\RideEstimateRequest;
use App\Http\Requests\API\V1\Customer\Ride\RiderReviewRequest;
use App\Http\Requests\API\V1\Customer\Ride\RideStoreRequest;
use App\Http\Resources\API\V1\Customer\Ride\RideResource;
use App\Http\Resources\API\V1\Customer\Ride\RiderProfileResource;
use App\Models\Review;
use App\Models\Ride;
use App\Models\SystemSetting;
use App\Models\TransportType;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommissionService;
use App\Services\NotificationService;

use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;


class RideController extends Controller
{
    use ApiResponse;

    /**
     * Get Ride Fare Estimate (Category-wise)
     */
    public function estimate(RideEstimateRequest $request)
    {
        try {
            $distance         = $request->distance;
            $transportTypeId  = $request->transport_type_id;

            $settings      = SystemSetting::first();
            $vatPercentage = (float) ($settings->vat_percentage ?? 10.00);

            // Load all active transport types with their category
            $query = TransportType::with('category')->where('is_active', true);

            if ($transportTypeId) {
                $query->where('id', $transportTypeId);
            }

            $transportTypes = $query->get();

            if ($transportTypes->isEmpty()) {
                return $this->error([], 'No transport types available');
            }

            // Calculate fare for each transport type
            $withEstimates = $transportTypes->map(function ($tType) use ($distance, $vatPercentage) {
                $pricePerKM    = (float) ($tType->price_per_km ?? 0.00);
                $subtotal      = $distance * $pricePerKM;
                $vatAmount     = ($subtotal * $vatPercentage) / 100;
                $estimatedFare = $subtotal + $vatAmount;

                return [
                    'id'             => $tType->id,
                    'name'           => $tType->name,
                    'transport_type' => $tType->transport_type,
                    'icon'           => $tType->icon ? asset($tType->icon) : null,
                    'price_per_km'   => $pricePerKM,
                    'vat_percentage' => $vatPercentage,
                    'subtotal'       => round($subtotal, 2),
                    'vat_amount'     => round($vatAmount, 2),
                    'estimated_fare' => round($estimatedFare, 2),
                    // keep category info for grouping
                    '_category_id'   => $tType->transport_type_category_id,
                    '_category_name' => optional($tType->category)->name ?? 'Uncategorized',
                ];
            });

            // Group by category
            $grouped = $withEstimates
                ->groupBy('_category_id')
                ->map(function ($items, $categoryId) {
                    $categoryName = $items->first()['_category_name'];

                    // Strip internal grouping keys before returning
                    $transports = $items->map(function ($item) {
                        unset($item['_category_id'], $item['_category_name']);
                        return $item;
                    })->values();

                    return [
                        'category_id'    => $categoryId,
                        'category_name'  => $categoryName,
                        'transport_types' => $transports,
                    ];
                })
                ->values();

            return $this->success([
                'distance'   => $distance,
                'currency'   => 'USD',
                'categories' => $grouped,
            ], 'Ride estimates calculated');

        } catch (Exception $e) {
            return $this->error([], 'Failed to calculate estimate: ' . $e->getMessage());
        }
    }

    /**
     * Request a Ride
     */
    /**
     * Summary of requestRide
     * @param RideStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestRide(RideStoreRequest $request)
    {
        // ── 1. Validation & Payload Check ─────────────────────────────────────
        if ($request->payment_type === 'wallet' && $request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id must not be provided for wallet payments.', 422);
        }

        if ($request->payment_type === 'stripe' && !$request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id is required for Stripe card payments.', 422);
        }

        DB::beginTransaction();

        try {
            /** @var \App\Models\User $user */
            $user = auth('api')->user();

            $hasPendingRide = Ride::where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingRide) {
                DB::rollBack();
                return $this->error([], 'You already have a pending ride request.', 400);
            }

            $payment_type = $request->payment_type;
            $payment_status = 'pending';
            $payment_intent_id = null;
            $transaction_id = null;
            $clientSecret = null;

            if ($payment_type === 'wallet') {
                $wallet = Wallet::where('user_id', $user->id)->where('user_type', 'customer')->first();
                if (!$wallet) {
                    $existingWallet = Wallet::where('user_id', $user->id)->first();
                    if ($existingWallet) {
                        $existingWallet->user_type = 'customer';
                        $existingWallet->save();
                        $wallet = $existingWallet;
                    }
                }
                $balance = $wallet ? (float)$wallet->balance : 0.00;
                if ($balance < $request->estimated_fare) {
                    DB::rollBack();
                    return $this->error([], 'Insufficient wallet balance.', 400);
                }

                $walletTx = WalletService::debit(
                    $user->id,
                    $request->estimated_fare,
                    'ride_payment',
                    null,
                    'Payment for ride request'
                );
                $payment_status = 'captured';
                $transaction_id = $walletTx->id;
            } elseif ($payment_type === 'stripe') {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = PaymentIntent::create([
                        'amount' => (int) round($request->estimated_fare * 100),
                        'currency' => 'usd',
                        'customer' => $user->createOrGetStripeCustomer()->id,
                        'payment_method' => $request->payment_method_id,
                        'confirm' => true,
                        'off_session' => true,
                        'capture_method' => 'automatic',
                        'description' => 'Payment for ride request',
                    ]);

                    if (!in_array($intent->status, ['succeeded', 'processing'])) {
                        DB::rollBack();
                        return $this->error([], 'Payment failed. Stripe status: ' . $intent->status, 400);
                    }

                    $payment_intent_id = $intent->id;
                    $transaction_id = $intent->latest_charge ?? $intent->id;
                    $payment_status = 'captured';
                    $clientSecret = $intent->client_secret;
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Stripe immediate ride charge failed: ' . $e->getMessage());
                    return $this->error([], 'Payment failed: ' . $e->getMessage(), 400);
                }
            }

            $ride = new Ride();
            $ride->user_id = $user->id;
            $ride->customer_address = $user->address;
            $ride->customer_lat = $user->latitude;
            $ride->customer_lng = $user->longitude;
            $ride->pickup_address = $request->pickup_address;
            $ride->pickup_lat = $request->pickup_lat;
            $ride->pickup_lng = $request->pickup_lng;
            $ride->dropoff_address = $request->dropoff_address;
            $ride->dropoff_lat = $request->dropoff_lat;
            $ride->dropoff_lng = $request->dropoff_lng;
            $ride->distance = $request->distance;
            $ride->transport_type_id = $request->transport_type_id;
            $ride->estimated_fare = $request->estimated_fare;
            $ride->total_amount = $request->estimated_fare;
            
            // Save payment details
            $ride->payment_type = $payment_type;
            $ride->payment_status = $payment_status;
            $ride->amount = $request->estimated_fare;
            $ride->transaction_id = $transaction_id;
            $ride->payment_intent_id = $payment_intent_id;
            $ride->status = 'pending';
            
            $ride->save();

            // Broadcast RideRequested event to available riders in real-time
            broadcast(new RideRequested($ride));

            // Notify Admin
            NotificationService::sendToRole('admin', 'New Ride Requested', "A new ride request has been placed by {$user->name}.", 'ride', ['ride_id' => $ride->id]);

            // Notify Riders
            NotificationService::sendToRole('rider', 'New Ride Request Nearby', "A new ride request is available nearby. Estimated fare: {$ride->estimated_fare}.", 'ride', ['ride_id' => $ride->id, 'is_request' => true]);

            DB::commit();

            return $this->success([
                'client_secret' => $clientSecret,
                'ride' => new RideResource($ride)
            ], 'Ride request placed successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Ride Request Error: ' . $e->getMessage());
            return $this->error([], 'Failed to request ride: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel Ride
     */
    public function cancelRide(RideCancelRequest $request)
    {
        DB::beginTransaction();
        try {
            $ride = Ride::where('id', $request->id)->where('user_id', auth('api')->id())->firstOrFail();

            if ($ride->status === 'canceled') {
                DB::rollBack();
                return $this->error([], 'This ride is already canceled.', 400);
            }

            if (in_array($ride->status, ['started', 'in_progress', 'ongoing'])) {
                DB::rollBack();
                return $this->error([], 'Ride has already started and cannot be cancelled.', 400);
            }

            if ($ride->status === 'completed') {
                DB::rollBack();
                return $this->error([], 'You cannot cancel a ride that is already completed.', 400);
            }

            // Check if already refunded to prevent duplicate refunds
            if ($ride->payment_status === 'refunded') {
                DB::rollBack();
                return $this->error([], 'This ride has already been refunded.', 400);
            }

            // Refund logic
            $refund_amount = (float) $ride->amount;
            $refund_transaction_id = null;
            $refunded_at = null;

            if ($ride->payment_type === 'wallet' && $ride->payment_status === 'captured') {
                // Refund to wallet
                $walletTx = WalletService::credit(
                    $ride->user_id,
                    $refund_amount,
                    'refund',
                    $ride,
                    'Refund for canceled ride #' . $ride->ride_no
                );
                $refund_transaction_id = $walletTx->id;
                $refunded_at = now();
                $ride->payment_status = 'refunded';
            } elseif ($ride->payment_type === 'stripe' && $ride->payment_status === 'captured') {
                // Refund Stripe card charge
                if ($ride->payment_intent_id) {
                    try {
                        Stripe::setApiKey(config('services.stripe.secret'));
                        $refund = \Stripe\Refund::create([
                            'payment_intent' => $ride->payment_intent_id,
                        ]);
                        $refund_transaction_id = $refund->id;
                        $refunded_at = now();
                        $ride->payment_status = 'refunded';
                    } catch (Exception $e) {
                        Log::error('Stripe Refund Error: ' . $e->getMessage());
                        DB::rollBack();
                        return $this->error([], 'Failed to refund Stripe payment: ' . $e->getMessage(), 500);
                    }
                }
            }

            $ride->status = 'canceled';
            $ride->cancel_reason = $request->cancel_reason;
            $ride->rider_id = null; // Clear rider if they were already assigned
            
            // Save refund details
            if ($ride->payment_status === 'refunded') {
                $ride->refund_transaction_id = $refund_transaction_id;
                $ride->refunded_at = $refunded_at;
                $ride->refunded_amount = $refund_amount;
            }
            
            $ride->save();

            DB::commit();

            broadcast(new RideStatusUpdated($ride));

            return $this->success(new RideResource($ride), 'Ride canceled successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to cancel ride: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Review Rider
     */
    public function reviewRider(RiderReviewRequest $request)
    {
        try {
            $ride = Ride::where('id', $request->id)->where('user_id', auth('api')->id())->firstOrFail();

            if ($ride->status !== 'completed') {
                return $this->error([], 'You can only review a completed ride.', 400);
            }

            if (!$ride->rider_id) {
                return $this->error([], 'No Rideshare Driver associated with this ride.', 400);
            }

            $review = Review::updateOrCreate(
                [
                    'ride_id' => $ride->id,
                    'user_id' => auth('api')->id(),
                    'target_id' => $ride->rider_id,
                    'role' => 'rider'
                ],
                [
                    'rating' => $request->rating,
                    'comment' => $request->comment
                ]
            );

            return $this->success($review, 'Review submitted successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * Get Ride Details
     */
    public function show($id)
    {
        $ride = Ride::with(['user', 'rider'])->find($id);
        if (!$ride) return $this->error([], 'Ride not found', 404);

        return $this->success(new RideResource($ride), 'Ride details retrieved');
    }

    /**
     * Ride History
     */
    public function history(Request $request)
    {
        $query = Ride::where('user_id', auth('api')->id());
        
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $rides = $query->latest()->get();
        return $this->success(RideResource::collection($rides), 'Ride history retrieved');
    }

    /**
     * Get Rider Profile and Reviews
     */
    public function riderProfile($id)
    {
        try {
            // 1. Try to find the rider directly by user ID
            $rider = User::where('id', $id)->where('user_type', 'rider')->first();

            // 2. If not found, check if the ID refers to a Ride, and get that ride's rider
            if (!$rider) {
                $ride = Ride::find($id);
                if ($ride && $ride->rider_id) {
                    $rider = User::where('id', $ride->rider_id)->where('user_type', 'rider')->first();
                }
            }

            if (!$rider) {
                return $this->error([], 'Rideshare Driver not found', 404);
            }

            return $this->success(new RiderProfileResource($rider), 'Rideshare Driver profile retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve Rideshare Driver profile: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Get the current location of the assigned rider.
     */
    public function getLocation($id)
    {
        try {
            $userId = auth('api')->id();
            $ride = Ride::where('user_id', $userId)
                ->where('id', $id)
                ->first();

            if (!$ride) {
                return $this->error([], 'Ride not found', 404);
            }

            if (!$ride->rider_id) {
                return $this->error([], 'No Rideshare Driver assigned to this ride yet', 400);
            }

            $rider = $ride->rider;
            if (!$rider) {
                return $this->error([], 'Rideshare Driver details not found', 404);
            }

            return $this->success([
                'id' => $rider->id,
                'name' => $rider->name,
                'phone' => $rider->phone,
                'latitude' => $rider->latitude ? (float) $rider->latitude : null,
                'longitude' => $rider->longitude ? (float) $rider->longitude : null,
                'ride_status' => $ride->status,
            ], 'Rideshare driver location fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve Rideshare driver location: ' . $e->getMessage(), 500);
        }
    }
}
