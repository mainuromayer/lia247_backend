<?php

namespace App\Http\Controllers\API\V1\DeliveryMan;

use App\Actions\GeoLocation;
use App\Events\DeliveryManStatusUpdated;
use App\Events\OrderLocationUpdated;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\OrderResource;
use App\Mail\OrderConfirmationCodeMail;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;

use App\Services\CommissionService;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * List Available Orders (Ready for Pickup)
     */
    public function availableOrders()
    {
        try {
            $deliveryMan = Auth::user();
            if (!$deliveryMan->is_online) {
                return $this->error([], 'You are currently offline. Please go online to view available orders.', 403);
            }

            $cancelledOrderIds = $deliveryMan->deliveryManOrderCancellations()->pluck('order_id')->toArray();

            $orders = Order::whereIn('status', ['preparing', 'ready'])
                ->whereNull('delivery_man_id')
                ->whereNotIn('order_type', ['pickup'])
                ->whereNotIn('id', $cancelledOrderIds)
                ->with(['user', 'vendor.business', 'deliveryMan', 'items.item'])
                ->latest()
                ->get();
            if ($orders->isEmpty()) {
                return $this->error([], 'No available orders', 200);
            }

            return $this->success(OrderResource::collection($orders), 'Available orders retrieved');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve available orders' . $e->getMessage());
        }
    }

    /**
     * Accept/Assign Order to Delivery Man
     */
    public function acceptOrder($id)
    {
        try {
            $deliveryMan = Auth::user();
            if (!$deliveryMan->is_online) {
                return $this->error([], 'You are currently offline. Please go online to accept orders.', 403);
            }

            $order = Order::find($id);

            if (!$order) {
                return $this->error([], 'Order not found', 404);
            }

            if (!in_array($order->status, ['preparing', 'ready'])) {
                return $this->error([], 'Order is not available for acceptance. It must be in preparing or ready stage.', 400);
            }

            if ($order->order_type === 'pickup') {
                return $this->error([], 'Pickup orders cannot be accepted by a delivery driver.', 400);
            }

            if ($order->delivery_man_id) {
                return $this->error([], 'Order already assigned to another delivery driver', 400);
            }

            $deliveryMan = Auth::user();
            $order->delivery_man_id = $deliveryMan->id;
            $order->delivery_man_address = $deliveryMan->address;
            $order->delivery_man_lat = $deliveryMan->latitude;
            $order->delivery_man_lng = $deliveryMan->longitude;

            $order->save();

            broadcast(new OrderStatusUpdated($order));
            broadcast(new DeliveryManStatusUpdated($deliveryMan));

            $order->load(['user', 'vendor.business', 'deliveryMan']);

            // Notify Customer
            NotificationService::send(
                $order->user_id,
                "Delivery Driver Assigned",
                "A delivery driver has been assigned to your order #{$order->order_no}.",
                'order',
                'customer',
                ['order_id' => $order->id]
            );

            return $this->success(new OrderResource($order), 'Order accepted successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to accept order' . $e->getMessage());
        }
    }

    /**
     * Mark Order as Picked Up (From Ready to Picked)
     */
    public function pickupOrder($id)
    {
        try {
            $deliveryMan = Auth::user();
            if (!$deliveryMan->is_online) {
                return $this->error([], 'You are currently offline. Please go online to pickup orders.', 403);
            }

            $order = Order::where('id', $id)->where('delivery_man_id', Auth::id())->first();

            if (!$order) {
                return $this->error([], 'Order not found or not assigned to you', 404);
            }

            if ($order->status !== 'ready') {
                return $this->error([], 'Order is not ready for pickup yet', 400);
            }

            $order->status = 'picked';
            $order->save();

            broadcast(new OrderStatusUpdated($order));

            $order->load(['user', 'vendor.business', 'deliveryMan']);

            // Notify Customer
            NotificationService::send(
                $order->user_id,
                "Order Picked Up",
                "Your order #{$order->order_no} has been picked up and is on the way.",
                'order',
                'customer',
                ['order_id' => $order->id]
            );

            return $this->success(new OrderResource($order), 'Order picked up successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to pickup order' . $e->getMessage());
        }
    }

    /**
     * Mark Order as Delivered and Process Payment Split
     */
    public function deliverOrder(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $order = Order::where('id', $id)->where('delivery_man_id', Auth::id())->first();

            if (!$order) {
                return $this->error([], 'Order not found or not assigned to you', 404);
            }

            if ($order->status !== 'picked') {
                return $this->error([], 'Order must be picked up before it can be delivered.', 400);
            }

            $foodPrice = $order->subtotal;
            $deliveryFee = $order->delivery_fee;
            $discount = $order->discount_amount ?? 0;

            $voucherOwner = 'admin';
            if ($order->voucher_id) {
                $voucher = Voucher::find($order->voucher_id);
                $voucherOwner = $voucher ? $voucher->owner_role : 'admin';
            }

            $commissionPercent = SystemSetting::first()?->food_commission ?? 10;

            // Calculate Split
            $split = CommissionService::calculateFoodSplit(
                $foodPrice,
                $deliveryFee,
                $discount,
                $voucherOwner,
                $commissionPercent,
                $order->free_delivery_covered_by,
                $order->admin_delivery_contribution,
                $order->vendor_delivery_contribution
            );

            // Stripe Capture Logic
            if ($order->payment_intent_id) {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = PaymentIntent::retrieve($order->payment_intent_id);
                    if ($intent->status === 'requires_capture') {
                        $intent->capture(['amount_to_capture' => (int) ($order->total_amount * 100)]);
                    }
                } catch (Exception $e) {
                    Log::error('Stripe Capture (Food Delivery) Error: ' . $e->getMessage());
                }
            }

            $order->commission_amount = $split['admin_amount'] + ($order->vat_amount ?? 0);
            $order->status = 'delivered';
            $order->save();

            broadcast(new OrderStatusUpdated($order));
            broadcast(new DeliveryManStatusUpdated(Auth::user()));

            $order->load(['user', 'vendor.business', 'deliveryMan']);

            // Debit Admin Wallet for Vendor and Delivery Man share
            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                if ($split['vendor_amount'] > 0) {
                    try {
                        WalletService::debit(
                            $admin->id,
                            $split['vendor_amount'],
                            'order_payout_vendor',
                            $order,
                            'Vendor payout for order #' . $order->order_no
                        );
                    } catch (Exception $ex) {
                        Log::error('Admin Wallet Debit (Food Vendor Payout) Error: ' . $ex->getMessage());
                    }
                }
                if ($split['delivery_man_amount'] > 0) {
                    try {
                        WalletService::debit(
                            $admin->id,
                            $split['delivery_man_amount'],
                            'order_payout_delivery_man',
                            $order,
                            'Delivery driver payout for order #' . $order->order_no
                        );
                    } catch (Exception $ex) {
                        Log::error('Admin Wallet Debit (Food Delivery Payout) Error: ' . $ex->getMessage());
                    }
                }
            }

            // Wallet Distribution (Auto Distribution)
            WalletService::credit($order->vendor_id, $split['vendor_amount'], 'order_payment', $order, 'Payment for order #' . $order->order_no);
            WalletService::credit($order->delivery_man_id, $split['delivery_man_amount'], 'delivery_fee', $order, 'Delivery fee for order #' . $order->order_no);

            // Notify Customer
            NotificationService::send(
                $order->user_id,
                "Order Delivered",
                "Your order #{$order->order_no} has been delivered. Enjoy!",
                'order',
                'customer',
                ['order_id' => $order->id]
            );

            DB::commit();

            return $this->success(new OrderResource($order), 'Order delivered and earnings distributed to pending balance');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to deliver order' . $e->getMessage());
        }
    }


    /**
     * Update Location of the Delivery Man for an Order
     */
    public function updateLocation(Request $request, GeoLocation $geoLocation)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $user = Auth::user();
            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            
            // Resolve and update address
            $address = $geoLocation->getAddress($request->latitude, $request->longitude);
            if ($address) {
                $user->address = $address;
            }
            $user->save();


            Log::info("updateLocation hit for delivery driver ID: " . $user->id);

            // Find all active orders for this delivery driver
            $orders = Order::where('delivery_man_id', $user->id)
                ->whereIn('status', ['ready', 'picked', 'accepted'])
                ->get();

            Log::info("Active orders found for delivery driver ID " . $user->id . ": " . $orders->count() . " orders. Order IDs: " . $orders->pluck('id')->join(', '));
DB::commit();
            foreach ($orders as $order) {
                log::info($order->id);
                // Broadcast the location update for each active order
                broadcast(new OrderLocationUpdated($order->id, $request->latitude, $request->longitude))->toOthers();
            }

            return $this->success([
                'latitude' => (string) $request->latitude,
                'longitude' => (string) $request->longitude,
            ], 'Location updated successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to update location: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        try {
            $query = Order::where('delivery_man_id', Auth::id());

            if ($request->has('tab')) {
                $tab = $request->tab;
                if ($tab === 'active') {
                    $query->whereIn('status', ['accepted', 'preparing', 'ready', 'picked']);
                } elseif ($tab === 'completed') {
                    $query->whereIn('status', ['completed', 'delivered']);
                } elseif ($tab === 'cancelled') {
                    $query->whereIn('status', ['canceled', 'cancelled']);
                }
            } elseif ($request->status) {
                $status = $request->status;
                if ($status === 'canceled' || $status === 'cancelled') {
                    $query->whereIn('status', ['canceled', 'cancelled']);
                } else {
                    $query->where('status', $status);
                }
            }

            $orders = $query->with(['user', 'vendor.business', 'deliveryMan'])
                ->latest()
                ->get();

            return $this->success(OrderResource::collection($orders), 'Delivery history retrieved');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve delivery history' . $e->getMessage());
        }
    }

    /**
     * Send Confirmation Code to User's Email
     */
    public function sendConfirmationCode($id)
    {
        try {
            $order = Order::where('id', $id)->where('delivery_man_id', Auth::id())->with('user')->first();

            if (!$order) {
                return $this->error([], 'Order not found or not assigned to you', 404);
            }

            if (!$order->user || !$order->user->email) {
                return $this->error([], 'User email not found', 404);
            }

            $code = rand(1000, 9999);
            $order->confirmation_code = $code;
            $order->confirmation_code_expires_at = now()->addMinutes(30);
            $order->save();

            // Send email
            Mail::to($order->user->email)->queue(new OrderConfirmationCodeMail($order, $code));
            return $this->success([
                'code' => $code,
                'order_id' => $order->id
            ], 'Confirmation code sent to customer email');
        } catch (Exception $e) {
            return $this->error([], 'Failed to send confirmation code: ' . $e->getMessage());
        }
    }

    /**
     * Verify Confirmation Code and Mark Order as Delivered
     */
    public function verifyConfirmationCode(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:4',
        ]);

        try {
            $order = Order::where('confirmation_code', $request->code)->where('delivery_man_id', Auth::id())->first();

            if (!$order) {
                return $this->error([], 'Order not found or invalid code', 404);
            }

            if ($order->confirmation_code_expires_at && now()->gt($order->confirmation_code_expires_at)) {
                return $this->error([], 'Confirmation code has expired', 400);
            }

            // Clear code after successful verification
            $order->confirmation_code = null;
            $order->confirmation_code_expires_at = null;

            // Stripe Capture Logic
            if ($order->payment_intent_id) {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = PaymentIntent::retrieve($order->payment_intent_id);
                    if ($intent->status === 'requires_capture') {
                        $intent->capture(['amount_to_capture' => (int) ($order->total_amount * 100)]);
                    }
                } catch (Exception $e) {
                    Log::error('Stripe Capture (Food Verify) Error: ' . $e->getMessage());
                }
            }

            // Calculate money distribution
            $commissionPercent = SystemSetting::first()?->food_commission ?? 10;
            $split = CommissionService::calculateFoodSplit(
                $order->subtotal ?? 0,
                $order->delivery_fee ?? 0,
                $order->discount_amount ?? 0,
                $order->voucher_id ? (Voucher::find($order->voucher_id)?->owner_role ?? 'admin') : 'admin',
                $commissionPercent,
                $order->free_delivery_covered_by,
                $order->admin_delivery_contribution,
                $order->vendor_delivery_contribution
            );

            $order->commission_amount = $split['admin_amount'] + ($order->vat_amount ?? 0);
            $order->status = 'delivered';
            $order->save();

            broadcast(new OrderStatusUpdated($order));
            $deliveryMan = Auth::user();
            if ($deliveryMan) {
                broadcast(new \App\Events\DeliveryManStatusUpdated($deliveryMan));
            }

            // Debit Admin Wallet for Delivery Man and Vendor share
            $admin = \App\Models\User::where('user_type', 'admin')->first();
            if ($admin) {
                if ($split['vendor_amount'] > 0) {
                    try {
                        WalletService::debit(
                            $admin->id,
                            $split['vendor_amount'],
                            'order_payout_vendor',
                            $order,
                            'Vendor payout for order #' . ($order->order_id ?? $order->id)
                        );
                    } catch (Exception $ex) {
                        Log::error('Admin Wallet Debit (Food Vendor Verify) Error: ' . $ex->getMessage());
                    }
                }
                if ($split['delivery_man_amount'] > 0) {
                    try {
                        WalletService::debit(
                            $admin->id,
                            $split['delivery_man_amount'],
                            'order_payout_delivery_man',
                            $order,
                            'Delivery driver payout for order #' . ($order->order_id ?? $order->id)
                        );
                    } catch (Exception $ex) {
                        Log::error('Admin Wallet Debit (Food Delivery Verify) Error: ' . $ex->getMessage());
                    }
                }
            }

            // Distribute to Delivery Man Wallet
            if ($split['delivery_man_amount'] > 0) {
                WalletService::credit(
                    $order->delivery_man_id,
                    $split['delivery_man_amount'],
                    'order_earning',
                    $order,
                    'Earning for delivering order #' . ($order->order_id ?? $order->id)
                );
            }

            // Distribute to Vendor Wallet
            if ($split['vendor_amount'] > 0) {
                WalletService::credit(
                    $order->vendor_id,
                    $split['vendor_amount'],
                    'order_earning',
                    $order,
                    'Earning for order #' . ($order->order_id ?? $order->id)
                );
            }

            return $this->success([
                'order_id' => $order->id,
                'status' => $order->status,
            ], 'Order verified successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to verify order: ' . $e->getMessage());
        }
    }

    /**
     * View Details of a Single Order (Order Summary / Order View)
     */
    public function show($id)
    {
        try {
            $order = Order::where('id', $id)
                ->where('delivery_man_id', Auth::id())
                ->with(['user', 'vendor.business', 'deliveryMan', 'items.item'])
                ->first();

            if (!$order) {
                return $this->error([], 'Order not found or not assigned to you', 404);
            }

            return $this->success(new OrderResource($order), 'Order details retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve order details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the current realtime order request offered to the delivery man (30 seconds queue window)
     */
    public function currentRequest()
    {
        try {
            $deliveryMan = Auth::user();
            if (!$deliveryMan->is_online) {
                return $this->error([], 'You are currently offline. Please go online to view available orders.', 403);
            }

            $cancelledOrderIds = $deliveryMan->deliveryManOrderCancellations()->pluck('order_id')->toArray();

            // Find the latest available order ready for assignment
            $order = Order::whereIn('status', ['preparing', 'ready'])
                ->whereNull('delivery_man_id')
                ->whereNotIn('order_type', ['pickup'])
                ->whereNotIn('id', $cancelledOrderIds)
                ->with(['user', 'vendor.business'])
                ->latest()
                ->first();

            if (!$order) {
                return $this->success(null, 'No new order requests');
            }

            // Estimate time left (30-second window from order creation/ready status)
            $expiresInSeconds = max(0, 30 - now()->diffInSeconds($order->updated_at));

            return $this->success([
                'order' => new OrderResource($order),
                'expires_in_seconds' => $expiresInSeconds,
            ], 'Current order request retrieved');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve current request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel/Unassign an Order (Before Picked Up)
     */
    public function cancelOrder($id)
    {
        try {
            $deliveryManId = Auth::id();
            // Find the order. It could either be assigned to them, OR be available (assigned to null, and in status accepted/preparing/ready).
            $order = Order::where('id', $id)
                ->where(function ($query) use ($deliveryManId) {
                    $query->where('delivery_man_id', $deliveryManId)
                        ->orWhere(function ($q) {
                            $q->whereNull('delivery_man_id')
                                ->whereIn('status', ['preparing', 'ready']);
                        });
                })
                ->firstOrFail();

            if (is_null($order->delivery_man_id)) {
                // The delivery man is rejecting an available order request
                \App\Models\DeliveryManOrderCancellation::firstOrCreate([
                    'delivery_man_id' => $deliveryManId,
                    'order_id' => $order->id,
                ]);
                return $this->success(null, 'Order request rejected successfully');
            }

            if (in_array($order->status, ['picked', 'delivered'])) {
                return $this->error([], 'You cannot cancel the order at this stage.', 400);
            }

            // Create cancellation record so they don't see it again
            \App\Models\DeliveryManOrderCancellation::firstOrCreate([
                'delivery_man_id' => $deliveryManId,
                'order_id' => $order->id,
            ]);

            $order->delivery_man_id = null;
            $order->delivery_man_address = null;
            $order->delivery_man_lat = null;
            $order->delivery_man_lng = null;
            $order->save();

            broadcast(new OrderStatusUpdated($order));

            return $this->success(new OrderResource($order), 'Order assignment canceled successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Update Order Status (Delivery Man Action)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accept,accepted,picked,picked_up,deliver,delivered,complete,completed,cancel,canceled,cancelled',
        ]);

        $status = $request->status;

        // Map status aliases to standard actions
        if (in_array($status, ['accept', 'accepted'])) {
            $mappedAction = 'accept';
        } elseif (in_array($status, ['picked', 'picked_up'])) {
            $mappedAction = 'picked';
        } elseif (in_array($status, ['deliver', 'delivered', 'complete', 'completed'])) {
            $mappedAction = 'deliver';
        } elseif (in_array($status, ['cancel', 'canceled', 'cancelled'])) {
            $mappedAction = 'cancel';
        }

        $order = Order::find($id);
        if (!$order) {
            return $this->error([], 'Order not found', 404);
        }

        $currentStatus = $order->status;

        if ($mappedAction === 'accept') {
            if ($order->delivery_man_id) {
                if ($currentStatus === 'picked') {
                    return $this->error([], 'The order is already picked.', 400);
                }
                if ($currentStatus === 'delivered' || $currentStatus === 'completed') {
                    return $this->error([], 'The order is already delivered.', 400);
                }
                return $this->error([], 'The order is already accepted.', 400);
            }
            return $this->acceptOrder($id);
        }

        if ($mappedAction === 'picked') {
            if ($order->delivery_man_id !== Auth::id()) {
                return $this->error([], 'This order is not assigned to you.', 403);
            }
            if ($currentStatus === 'picked') {
                return $this->error([], 'The order is already picked.', 400);
            }
            if ($currentStatus === 'delivered' || $currentStatus === 'completed') {
                return $this->error([], 'The order is already delivered.', 400);
            }
            return $this->pickupOrder($id);
        }

        if ($mappedAction === 'deliver') {
            if ($order->delivery_man_id !== Auth::id()) {
                return $this->error([], 'This order is not assigned to you.', 403);
            }
            if ($currentStatus === 'delivered' || $currentStatus === 'completed') {
                return $this->error([], 'The order is already delivered.', 400);
            }
            if ($currentStatus !== 'picked') {
                return $this->error([], 'Order must be picked up before it can be delivered.', 400);
            }
            return $this->deliverOrder($request, $id);
        }

        if ($mappedAction === 'cancel') {
            return $this->cancelOrder($id);
        }

        return $this->error([], 'Delivery driver cannot update status to: ' . $status, 400);
    }

    /**
     * Get the current location of the authenticated delivery driver.
     */
    public function getLocation()
    {
        try {
            $user = Auth::user();
            return $this->success([
                'latitude' => $user->latitude ? (float) $user->latitude : null,
                'longitude' => $user->longitude ? (float) $user->longitude : null,
            ], 'Current location retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve location: ' . $e->getMessage(), 500);
        }
    }
}
