<?php

namespace App\Http\Controllers\API\V1\Customer\Tip;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Customer\Tip\TipOrderRequest;
use App\Http\Requests\API\V1\Customer\Tip\TipRideRequest;
use App\Models\Order;
use App\Models\Ride;
use App\Models\Tip;
use App\Models\User;
use App\Models\Wallet;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\API\V1\Customer\Tip\TipOrderResource;
use App\Http\Resources\API\V1\Customer\Tip\TipRideResource;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class TipController extends Controller
{
    use ApiResponse;

    /**
     * Store a tip for a delivery man (Order)
     */
    public function tipOrder(TipOrderRequest $request)
    {
        // ── 1. Validation & Payload Check ─────────────────────────────────────
        if ($request->payment_type === 'wallet' && $request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id must not be provided for wallet payments.', 422);
        }

        if ($request->payment_type === 'stripe' && !$request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id is required for Stripe card payments.', 422);
        }

        $user = Auth::user();
        $amount = (float) $request->amount;
        $order_id = $request->order_id;
        $recipient_id = (int) $request->user_id;

        DB::beginTransaction();

        try {
            $order = Order::find($order_id);

            if (!$order) {
                DB::rollBack();
                return $this->error([], 'Order not found.', 404);
            }

            if ($order->user_id !== $user->id) {
                DB::rollBack();
                return $this->error([], 'You can only tip for your own orders.', 403);
            }

            if (!in_array($order->status, ['delivered', 'completed'])) {
                DB::rollBack();
                return $this->error([], 'You can only tip for completed or delivered orders.', 403);
            }

            if (!$order->delivery_man_id) {
                DB::rollBack();
                return $this->error([], 'No delivery man is assigned to this order.', 400);
            }

            // Verify that the requested user_id matches the order's delivery man ID
            if ($order->delivery_man_id !== $recipient_id) {
                DB::rollBack();
                return $this->error([], 'The selected recipient does not match the delivery man assigned to this order.', 400);
            }



            $referenceNo = $order->order_no;
            $referenceModel = $order;

            $recipient = User::find($recipient_id);
            if (!$recipient) {
                DB::rollBack();
                return $this->error([], 'Recipient not found.', 404);
            }

            $paymentType = $request->payment_type;
            $paymentStatus = 'pending';
            $transactionId = null;
            $paymentIntentId = null;

            if ($paymentType === 'wallet') {
                // Fetch customer wallet
                $wallet = Wallet::where('user_id', $user->id)->where('user_type', 'customer')->first();
                if (!$wallet) {
                    $existingWallet = Wallet::where('user_id', $user->id)->first();
                    if ($existingWallet) {
                        $existingWallet->user_type = 'customer';
                        $existingWallet->save();
                        $wallet = $existingWallet;
                    } else {
                        $wallet = Wallet::create([
                            'user_id' => $user->id,
                            'user_type' => 'customer',
                            'balance' => 0.00,
                            'pending_balance' => 0.00,
                        ]);
                    }
                }

                $walletBalance = (float) $wallet->balance;
                if ($walletBalance < $amount) {
                    DB::rollBack();
                    return $this->error([], 'Insufficient wallet balance.', 400);
                }

                // Debit customer
                $debitTx = WalletService::debit(
                    $user->id,
                    $amount,
                    'tip',
                    $referenceModel,
                    "Tip for order " . $referenceNo
                );

                // Credit recipient
                $creditTx = WalletService::credit(
                    $recipient_id,
                    $amount,
                    'tip',
                    $referenceModel,
                    "Tip received from customer for order " . $referenceNo
                );

                $paymentStatus = 'captured';
                $transactionId = $debitTx->id;
            } elseif ($paymentType === 'stripe') {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $stripeCustomer = $user->createOrGetStripeCustomer();
                    $paymentMethodId = $request->payment_method_id;

                    // Charge card off-session using resolved payment method ID
                    $intent = PaymentIntent::create([
                        'amount' => (int) round($amount * 100),
                        'currency' => env('STRIPE_CURRENCY', 'usd'),
                        'customer' => $stripeCustomer->id,
                        'payment_method' => $paymentMethodId,
                        'confirm' => true,
                        'off_session' => true,
                        'capture_method' => 'automatic',
                        'description' => "Tip for order " . $referenceNo,
                    ]);

                    if (!in_array($intent->status, ['succeeded', 'processing'])) {
                        DB::rollBack();
                        return $this->error([], 'Card payment failed. Stripe status: ' . $intent->status, 400);
                    }

                    // Card payment succeeded: credit recipient wallet
                    $creditTx = WalletService::credit(
                        $recipient_id,
                        $amount,
                        'tip',
                        $referenceModel,
                        "Tip received from customer for order " . $referenceNo
                    );

                    $paymentStatus = 'captured';
                    $transactionId = $intent->latest_charge ?? $intent->id;
                    $paymentIntentId = $intent->id;
                } catch (Exception $stripeEx) {
                    Log::error('Stripe order tipping charge failed: ' . $stripeEx->getMessage());
                    
                    DB::rollBack();

                    // Log failed attempt to DB outside transaction
                    try {
                        Tip::create([
                            'user_id' => $user->id,
                            'recipient_id' => $recipient_id,
                            'order_id' => $order_id,
                            'ride_id' => null,
                            'amount' => $amount,
                            'payment_type' => 'stripe',
                            'payment_status' => 'failed',
                        ]);
                    } catch (Exception $logEx) {
                        Log::error('Failed to log failed tip to database: ' . $logEx->getMessage());
                    }

                    return $this->error([], 'Payment failed: ' . $stripeEx->getMessage(), 400);
                }
            }

            // Create tip record in DB
            $tip = Tip::create([
                'user_id' => $user->id,
                'recipient_id' => $recipient_id,
                'order_id' => $order_id,
                'ride_id' => null,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_status' => $paymentStatus,
                'transaction_id' => $transactionId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            DB::commit();

            // Send notification to the delivery man
            try {
                NotificationService::send(
                    $recipient_id,
                    "Tip Received",
                    "You have received a tip of {$amount} from a customer for order #{$referenceNo}.",
                    'payment',
                    $recipient->user_type,
                    [
                        'amount' => $amount,
                        'order_id' => $order_id,
                        'tip_id' => $tip->id
                    ]
                );
            } catch (Exception $notifEx) {
                Log::error('Failed to send tip notification: ' . $notifEx->getMessage());
            }

            $clientSecret = $paymentIntentId ? (PaymentIntent::retrieve($paymentIntentId)->client_secret ?? null) : null;
            return $this->success(new TipOrderResource($tip, $clientSecret), 'Tip processed successfully.', 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Tip transaction failed: ' . $e->getMessage());
            return $this->error([], 'Failed to process tip: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a tip for a rider (Ride)
     */
    public function tipRide(TipRideRequest $request)
    {
        // ── 1. Validation & Payload Check ─────────────────────────────────────
        if ($request->payment_type === 'wallet' && $request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id must not be provided for wallet payments.', 422);
        }

        if ($request->payment_type === 'stripe' && !$request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id is required for Stripe card payments.', 422);
        }

        $user = Auth::user();
        $amount = (float) $request->amount;
        $ride_id = $request->ride_id;
        $recipient_id = (int) $request->user_id;

        DB::beginTransaction();

        try {
            $ride = Ride::find($ride_id);

            if (!$ride) {
                DB::rollBack();
                return $this->error([], 'Ride not found.', 404);
            }

            if ($ride->user_id !== $user->id) {
                DB::rollBack();
                return $this->error([], 'You can only tip for your own rides.', 403);
            }

            if ($ride->status !== 'completed') {
                DB::rollBack();
                return $this->error([], 'You can only tip for completed rides.', 403);
            }

            if (!$ride->rider_id) {
                DB::rollBack();
                return $this->error([], 'No driver is assigned to this ride.', 400);
            }

            // Verify that the requested user_id matches the ride's rider ID
            if ($ride->rider_id !== $recipient_id) {
                DB::rollBack();
                return $this->error([], 'The selected recipient does not match the driver/rider assigned to this ride.', 400);
            }



            $referenceNo = $ride->ride_no;
            $referenceModel = $ride;

            $recipient = User::find($recipient_id);
            if (!$recipient) {
                DB::rollBack();
                return $this->error([], 'Recipient not found.', 404);
            }

            $paymentType = $request->payment_type;
            $paymentStatus = 'pending';
            $transactionId = null;
            $paymentIntentId = null;

            if ($paymentType === 'wallet') {
                // Fetch customer wallet
                $wallet = Wallet::where('user_id', $user->id)->where('user_type', 'customer')->first();
                if (!$wallet) {
                    $existingWallet = Wallet::where('user_id', $user->id)->first();
                    if ($existingWallet) {
                        $existingWallet->user_type = 'customer';
                        $existingWallet->save();
                        $wallet = $existingWallet;
                    } else {
                        $wallet = Wallet::create([
                            'user_id' => $user->id,
                            'user_type' => 'customer',
                            'balance' => 0.00,
                            'pending_balance' => 0.00,
                        ]);
                    }
                }

                $walletBalance = (float) $wallet->balance;
                if ($walletBalance < $amount) {
                    DB::rollBack();
                    return $this->error([], 'Insufficient wallet balance.', 400);
                }

                // Debit customer
                $debitTx = WalletService::debit(
                    $user->id,
                    $amount,
                    'tip',
                    $referenceModel,
                    "Tip for ride " . $referenceNo
                );

                // Credit recipient
                $creditTx = WalletService::credit(
                    $recipient_id,
                    $amount,
                    'tip',
                    $referenceModel,
                    "Tip received from customer for ride " . $referenceNo
                );

                $paymentStatus = 'captured';
                $transactionId = $debitTx->id;
            } elseif ($paymentType === 'stripe') {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $stripeCustomer = $user->createOrGetStripeCustomer();
                    $paymentMethodId = $request->payment_method_id;

                    // Charge card off-session using resolved payment method ID
                    $intent = PaymentIntent::create([
                        'amount' => (int) round($amount * 100),
                        'currency' => env('STRIPE_CURRENCY', 'usd'),
                        'customer' => $stripeCustomer->id,
                        'payment_method' => $paymentMethodId,
                        'confirm' => true,
                        'off_session' => true,
                        'capture_method' => 'automatic',
                        'description' => "Tip for ride " . $referenceNo,
                    ]);

                    if (!in_array($intent->status, ['succeeded', 'processing'])) {
                        DB::rollBack();
                        return $this->error([], 'Card payment failed. Stripe status: ' . $intent->status, 400);
                    }

                    // Card payment succeeded: credit recipient wallet
                    $creditTx = WalletService::credit(
                        $recipient_id,
                        $amount,
                        'tip',
                        $referenceModel,
                        "Tip received from customer for ride " . $referenceNo
                    );

                    $paymentStatus = 'captured';
                    $transactionId = $intent->latest_charge ?? $intent->id;
                    $paymentIntentId = $intent->id;
                } catch (Exception $stripeEx) {
                    Log::error('Stripe ride tipping charge failed: ' . $stripeEx->getMessage());
                    
                    DB::rollBack();

                    // Log failed attempt to DB outside transaction
                    try {
                        Tip::create([
                            'user_id' => $user->id,
                            'recipient_id' => $recipient_id,
                            'order_id' => null,
                            'ride_id' => $ride_id,
                            'amount' => $amount,
                            'payment_type' => 'stripe',
                            'payment_status' => 'failed',
                        ]);
                    } catch (Exception $logEx) {
                        Log::error('Failed to log failed tip to database: ' . $logEx->getMessage());
                    }

                    return $this->error([], 'Payment failed: ' . $stripeEx->getMessage(), 400);
                }
            }

            // Create tip record in DB
            $tip = Tip::create([
                'user_id' => $user->id,
                'recipient_id' => $recipient_id,
                'order_id' => null,
                'ride_id' => $ride_id,
                'amount' => $amount,
                'payment_type' => $paymentType,
                'payment_status' => $paymentStatus,
                'transaction_id' => $transactionId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            DB::commit();

            // Send notification to the rider
            try {
                NotificationService::send(
                    $recipient_id,
                    "Tip Received",
                    "You have received a tip of {$amount} from a customer for ride #{$referenceNo}.",
                    'payment',
                    $recipient->user_type,
                    [
                        'amount' => $amount,
                        'ride_id' => $ride_id,
                        'tip_id' => $tip->id
                    ]
                );
            } catch (Exception $notifEx) {
                Log::error('Failed to send tip notification: ' . $notifEx->getMessage());
            }

            $clientSecret = $paymentIntentId ? (PaymentIntent::retrieve($paymentIntentId)->client_secret ?? null) : null;
            return $this->success(new TipRideResource($tip, $clientSecret), 'Tip processed successfully.', 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Tip transaction failed: ' . $e->getMessage());
            return $this->error([], 'Failed to process tip: ' . $e->getMessage(), 500);
        }
    }
}
