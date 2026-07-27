<?php

namespace App\Http\Controllers\API\V1\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Services\WalletService;
use App\Services\NotificationService;
use App\Models\Ride;
use App\Models\User;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle the incoming Stripe Webhook request.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        if (!$endpointSecret) {
            $eventData = $request->all();
            $type = $eventData['type'] ?? '';
            $payloadObj = $eventData;
        } else {
            try {
                $event = Webhook::constructEvent(
                    $payload, $sigHeader, $endpointSecret
                );
                $type = $event->type;
                $payloadObj = $event->jsonSerialize();
            } catch (\UnexpectedValueException $e) {
                Log::error('Stripe Webhook invalid payload: ' . $e->getMessage());
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (SignatureVerificationException $e) {
                Log::error('Stripe Webhook signature verification failed: ' . $e->getMessage());
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        Log::info('Stripe Webhook event received: ' . $type);

        switch ($type) {
            case 'payment_intent.amount_capturable_updated':
                return $this->handlePaymentIntentAmountCapturableUpdated($payloadObj);
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($payloadObj);
            case 'payment_intent.payment_failed':
                return $this->handlePaymentIntentPaymentFailed($payloadObj);
            case 'checkout.session.completed':
                return $this->handleCheckoutSessionCompleted($payloadObj);
            case 'checkout.session.expired':
                return $this->handleCheckoutSessionExpired($payloadObj);
            default:
                return response()->json(['status' => 'ignored']);
        }
    }

    /**
     * Helper to return standard success response.
     */
    protected function successMethod()
    {
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle payment intent amount capturable updated (Stripe Authorization Hold).
     */
    protected function handlePaymentIntentAmountCapturableUpdated(array $payload)
    {
        Log::info('Webhook: payment_intent.amount_capturable_updated', ['payload' => $payload]);

        $paymentIntent = $payload['data']['object'];
        $paymentIntentId = $paymentIntent['id'];

        // 1. Check if it's a Ride
        $ride = Ride::where('payment_intent_id', $paymentIntentId)->first();
        if ($ride) {
            $ride->update(['payment_status' => 'authorized']);

            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                WalletService::credit(
                    $admin->id,
                    $ride->estimated_fare,
                    'ride_hold',
                    $ride,
                    'Hold for ride #' . $ride->ride_no
                );
                Log::info("Admin wallet credited with hold amount {$ride->estimated_fare} for ride #{$ride->ride_no}.");
            }
            return $this->successMethod();
        }

        // 2. Check if it's a Food Delivery / Pickup Order
        $orderId = $paymentIntent['metadata']['order_id'] ?? null;
        $orderIdsStr = $paymentIntent['metadata']['order_ids'] ?? null;
        
        $orderIds = [];
        if ($orderIdsStr) {
            $orderIds = explode(',', $orderIdsStr);
        } elseif ($orderId) {
            $orderIds = [$orderId];
        } else {
            $orderIds = Order::where('payment_intent_id', $paymentIntentId)->pluck('id')->toArray();
        }

        if (!empty($orderIds)) {
            $orders = Order::whereIn('id', $orderIds)->get();
            $admin = User::where('user_type', 'admin')->first();

            foreach ($orders as $order) {
                if ($order->payment_status !== 'authorized') {
                    $order->update([
                        'payment_status' => 'authorized',
                        'payment_intent_id' => $paymentIntentId
                    ]);

                    if ($admin) {
                        WalletService::credit(
                            $admin->id,
                            $order->total_amount,
                            'order_hold',
                            $order,
                            'Hold for order #' . $order->order_no
                        );
                        Log::info("Admin wallet credited with hold amount {$order->total_amount} for order #{$order->order_no}.");
                    }
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle payment intent succeeded.
     */
    protected function handlePaymentIntentSucceeded(array $payload)
    {
        Log::info('Webhook: payment_intent.succeeded', ['payload' => $payload]);

        $paymentIntent = $payload['data']['object'];

        // Handle Wallet Top-up
        if (isset($paymentIntent['metadata']['type']) && $paymentIntent['metadata']['type'] === 'wallet_topup') {
            $userId = $paymentIntent['metadata']['user_id'];
            $amount = $paymentIntent['amount'] / 100;

            $user = User::find($userId);
            $userType = $user ? $user->user_type : 'customer';
            $wallet = \App\Models\Wallet::where('user_id', $userId)->where('user_type', $userType)->first();

            $txnExists = false;
            if ($wallet) {
                $txnExists = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('description', 'like', "%{$paymentIntent['id']}%")
                    ->exists();
            }

            if (!$txnExists) {
                WalletService::credit($userId, $amount, 'topup', null, "Wallet top-up via Stripe (Intent: {$paymentIntent['id']})");
                Log::info("User {$userId} wallet credited with {$amount} from top-up.");

                NotificationService::send(
                    $userId,
                    "Wallet Credited",
                    "Your wallet has been credited with {$amount} from top-up.",
                    'payment',
                    null,
                    ['amount' => $amount]
                );
            } else {
                Log::info("User {$userId} wallet top-up already credited for intent {$paymentIntent['id']}.");
            }

            return $this->successMethod();
        }
        
        $orderIdsStr = $paymentIntent['metadata']['order_ids'] ?? null;
        
        if ($orderIdsStr) {
            $orderIds = explode(',', $orderIdsStr);
            $orders = Order::whereIn('id', $orderIds)->get();
            
            foreach ($orders as $order) {
                $order->update(['status' => 'paid']);
                
                NotificationService::send(
                    $order->user_id,
                    "Payment Successful",
                    "Payment for your order #{$order->order_no} has been confirmed.",
                    'order',
                    'customer',
                    ['order_id' => $order->id]
                );

                NotificationService::send(
                    $order->vendor_id,
                    "Order Paid",
                    "Order #{$order->order_no} has been paid and is ready for preparation.",
                    'order',
                    'vendor',
                    ['order_id' => $order->id]
                );
            }
            Log::info("Orders [{$orderIdsStr}] marked as paid and users notified.");
        } else {
            $orders = Order::where('payment_intent_id', $paymentIntent['id'])->get();
            foreach ($orders as $order) {
                $order->update(['status' => 'paid']);
                NotificationService::send($order->user_id, "Payment Successful", "Payment for order #{$order->order_no} confirmed.", 'order', 'customer', ['order_id' => $order->id]);
                NotificationService::send($order->vendor_id, "Order Paid", "Order #{$order->order_no} paid.", 'order', 'vendor', ['order_id' => $order->id]);
            }
            Log::info("Orders with payment_intent_id {$paymentIntent['id']} marked as paid.");
        }

        return $this->successMethod();
    }

    /**
     * Handle payment intent payment failed.
     */
    protected function handlePaymentIntentPaymentFailed(array $payload)
    {
        Log::info('Webhook: payment_intent.payment_failed', ['payload' => $payload]);

        $paymentIntent = $payload['data']['object'];
        $orderIdsStr = $paymentIntent['metadata']['order_ids'] ?? null;

        if ($orderIdsStr) {
            $orderIds = explode(',', $orderIdsStr);
            Order::whereIn('id', $orderIds)->update(['status' => 'failed']);
            Log::info("Orders [{$orderIdsStr}] marked as failed from payment_intent.payment_failed.");
        } else {
            Order::where('payment_intent_id', $paymentIntent['id'])->update(['status' => 'failed']);
            Log::info("Orders with payment_intent_id {$paymentIntent['id']} marked as failed.");
        }

        return $this->successMethod();
    }

    /**
     * Handle checkout session completed.
     */
    protected function handleCheckoutSessionCompleted(array $payload)
    {
        Log::info('Webhook: checkout.session.completed', ['payload' => $payload]);

        $session = $payload['data']['object'];
        $sessionId = $session['id'];
        $paymentIntentId = $session['payment_intent'] ?? null;
        
        $orderIdsStr = $session['metadata']['order_ids'] ?? $session['metadata']['order_id'] ?? null;
        
        if ($orderIdsStr) {
            $orderIds = explode(',', $orderIdsStr);
            foreach ($orderIds as $id) {
                $order = Order::find($id);
                if ($order) {
                    $status = ($session['payment_status'] === 'paid') ? 'paid' : 'pending';
                    $order->update([
                        'status' => $status,
                        'stripe_session_id' => $sessionId,
                        'payment_intent_id' => $paymentIntentId,
                    ]);
                    Log::info("Order {$order->id} updated from checkout.session.completed with status {$status}.");
                }
            }
        }

        // Handle Wallet Top-up
        if (isset($session['metadata']['type']) && $session['metadata']['type'] === 'wallet_topup') {
            if ($session['payment_status'] === 'paid') {
                $userId = $session['metadata']['user_id'];
                $amount = $session['amount_total'] / 100;
                WalletService::credit($userId, $amount, 'topup', null, 'Wallet top-up via Stripe Checkout');
                Log::info("User {$userId} wallet credited with {$amount} from checkout.session.completed top-up.");
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle checkout session expired.
     */
    protected function handleCheckoutSessionExpired(array $payload)
    {
        Log::info('Webhook: checkout.session.expired', ['payload' => $payload]);

        $session = $payload['data']['object'];
        $orderIdsStr = $session['metadata']['order_ids'] ?? $session['metadata']['order_id'] ?? null;
        
        if ($orderIdsStr) {
            $orderIds = explode(',', $orderIdsStr);
            foreach ($orderIds as $id) {
                $order = Order::find($id);
                if ($order && $order->status === 'pending') {
                    $order->update(['status' => 'failed', 'notes' => 'Session expired']);
                    Log::info("Order {$order->id} marked as failed from checkout.session.expired.");
                }
            }
        }

        return $this->successMethod();
    }
}
