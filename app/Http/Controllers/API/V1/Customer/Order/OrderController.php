<?php

namespace App\Http\Controllers\API\V1\Customer\Order;

use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Customer\Order\OrderDetailResource;
use App\Http\Resources\API\V1\Customer\Order\OrderListResource;
use App\Http\Resources\API\V1\Customer\Order\OrderResource;
use App\Models\Order;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * Get customer order history grouped by active and previous.
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $status = $request->status;

            $activeOrdersQuery = Order::where('user_id', $user->id)
                ->whereNotIn('status', ['delivered', 'completed', 'canceled', 'cancelled']);
            
            if ($status) {
                $activeOrdersQuery->where('status', $status);
            }

            $activeOrders = $activeOrdersQuery->with(['items.item', 'vendor'])
                ->latest()
                ->get();

            $previousOrdersQuery = Order::where('user_id', $user->id)
                ->whereIn('status', ['delivered', 'completed', 'canceled', 'cancelled']);

            if ($status) {
                $previousOrdersQuery->where('status', $status);
            }

            $previousOrders = $previousOrdersQuery->with(['items.item', 'vendor'])
                ->latest()
                ->get();

            return $this->success([
                'active_order' => OrderListResource::collection($activeOrders),
                'previous_order' => OrderListResource::collection($previousOrders),
            ], 'Order history fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching order history'. $e->getMessage(), 500);
        }
    }

    /**
     * Get single order details.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $order = Order::where('user_id', $user->id)
                ->with(['items.item', 'vendor', 'deliveryMan'])
                ->where('id', $id)
                ->first();

            if (!$order) {
                return $this->error([], 'Order not found', 404);
            }

            return $this->success(new OrderDetailResource($order), 'Order details fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching order details'. $e->getMessage(), 500);
        }
    }

    /**
     * Get the current location of the assigned delivery man.
     */
    public function getLocation($id)
    {
        try {
            $user = Auth::user();
            $order = Order::where('user_id', $user->id)
                ->where('id', $id)
                ->with('deliveryOption')
                ->first();

            if (!$order) {
                return $this->error([], 'Order not found', 404);
            }

            if (!$order->delivery_man_id) {
                return $this->error([], 'No delivery driver assigned to this order yet', 400);
            }

            $deliveryMan = $order->deliveryMan;
            if (!$deliveryMan) {
                return $this->error([], 'Delivery driver details not found', 404);
            }

            return $this->success([
                'id' => $deliveryMan->id,
                'name' => $deliveryMan->name,
                'phone' => $deliveryMan->phone,
                'avatar' => $deliveryMan->avatar,
                'rating' => (float) $deliveryMan->average_rating,
                'reviews_count' => (int) $deliveryMan->reviews()->where('role', 'delivery_man')->count(),
                'latitude' => $deliveryMan->latitude ? (float) $deliveryMan->latitude : null,
                'longitude' => $deliveryMan->longitude ? (float) $deliveryMan->longitude : null,
                'order_status' => $order->status,
                'estimated_delivery_time' => $order->deliveryOption->estimated_time ?? '30 mins',
            ], 'Delivery driver location fetched successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching delivery driver location: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel an order from customer side
     */
    public function cancelOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                DB::rollBack();
                return $this->error([], 'Order not found', 404);
            }

            if ($order->status === 'canceled') {
                DB::rollBack();
                return $this->error([], 'Order is already canceled.', 400);
            }

            // Customer can cancel ONLY before vendor marks order as ready.
            // Allowed statuses to cancel: pending, accepted, preparing.
            // Disallowed: ready, picked, delivered, completed.
            if (in_array($order->status, ['ready', 'picked', 'delivered', 'completed'])) {
                DB::rollBack();
                return $this->error([], 'Order is already prepared and cannot be cancelled.', 400);
            }

            // Prevent duplicate refund
            if ($order->payment_status === 'refunded') {
                DB::rollBack();
                return $this->error([], 'This order has already been refunded.', 400);
            }

            $refund_amount = (float) $order->amount;
            $refund_transaction_id = null;
            $refunded_at = null;

            if ($order->payment_type === 'wallet' && $order->payment_status === 'captured') {
                $walletTx = WalletService::credit(
                    $order->user_id,
                    $refund_amount,
                    'refund',
                    $order,
                    'Refund for canceled order #' . $order->order_no
                );
                $refund_transaction_id = $walletTx->id;
                $refunded_at = now();
                $order->payment_status = 'refunded';
            } elseif ($order->payment_type === 'stripe' && $order->payment_status === 'captured') {
                if ($order->payment_intent_id) {
                    try {
                        Stripe::setApiKey(config('services.stripe.secret'));
                        $refund = \Stripe\Refund::create([
                            'payment_intent' => $order->payment_intent_id,
                        ]);
                        $refund_transaction_id = $refund->id;
                        $refunded_at = now();
                        $order->payment_status = 'refunded';
                    } catch (Exception $e) {
                        Log::error('Stripe Refund Error: ' . $e->getMessage());
                        DB::rollBack();
                        return $this->error([], 'Failed to refund Stripe payment: ' . $e->getMessage(), 500);
                    }
                }
            }

            $order->status = 'canceled';
            $order->cancel_reason = $request->cancel_reason;
            
            if ($order->payment_status === 'refunded') {
                $order->refund_transaction_id = $refund_transaction_id;
                $order->refunded_at = $refunded_at;
                $order->refunded_amount = $refund_amount;
            }

            $order->save();

            broadcast(new OrderStatusUpdated($order));

            DB::commit();

            return $this->success(new OrderResource($order), 'Order canceled successfully');

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to cancel order: ' . $e->getMessage(), 500);
        }
    }
}
