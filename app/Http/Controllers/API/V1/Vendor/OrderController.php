<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Events\OrderReady;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Vendor\Order\OrderStatusUpdateRequest;
use App\Http\Resources\API\V1\Vendor\CardOrderResource;
use App\Http\Resources\API\V1\Vendor\OrderResource;
use App\Http\Resources\API\V1\Vendor\PickupOrderResource;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Models\Voucher;
use App\Services\CommissionService;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $vendorId = auth()->id();
            $status = $request->input('status', 'all');
            $dateRange = $request->input('date_range');
            $search = $request->input('search');

            $query = Order::with([
                'user',
                'items.item'
            ])
                ->whereHas('items.item', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });

            if ($status != 'all') {
                if ($status == 'completed') {
                    $query->whereIn('status', ['delivered', 'completed']);
                } elseif ($status == 'canceled') {
                    $query->whereIn('status', ['canceled', 'cancelled']);
                } else {
                    $query->where('status', $status);
                }
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', '%' . $search . '%')
                        ->orWhere('receiver_name', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('items', function ($iq) use ($search) {
                            $iq->where('item_name', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($dateRange) {
                if ($dateRange == 'last_week') {
                    $query->where('created_at', '>=', now()->subWeek());
                } elseif ($dateRange == 'last_month') {
                    $query->where('created_at', '>=', now()->subMonth());
                } elseif ($dateRange == 'last_year') {
                    $query->where('created_at', '>=', now()->subYear());
                }
            }

            $orders = $query->latest()->paginate($request->limit ?? 10);

            return $this->success([
                'orders' => OrderResource::collection($orders),
                'pagination' => [
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                ]
            ], 'Vendor Orders Data Retrieved Successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve vendor orders: ' . $e->getMessage());
        }
    }


    /**
     * Card View Orders (e.g. New Orders/Pending)
     */
    public function cardOrders(Request $request)
    {
        try {
            $vendorId = auth()->id();
            $query = Order::with(['user', 'items.item', 'items.variant'])
                ->where('vendor_id', $vendorId)
                ->where('status', 'pending');

            $orders = $query->latest()->paginate($request->limit ?? 10);

            return $this->success([
                'orders' => CardOrderResource::collection($orders),
                'pagination' => [
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                ]
            ], 'Card Orders Data Retrieved Successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve card orders: ' . $e->getMessage());
        }
    }


    /**
     * Pickup Request Orders
     */
    public function pickupOrders(Request $request)
    {
        try {
            $vendorId = auth()->id();
            $query = Order::with(['deliveryMan'])
                ->where('vendor_id', $vendorId)
                ->whereIn('status', ['accepted', 'preparing', 'ready', 'picked']);

            $orderId = $request->input('order_id');
            if ($orderId) {
                $query->where(function ($q) use ($orderId) {
                    $q->where('id', $orderId)
                        ->orWhere('order_no', $orderId)
                        ->orWhere('order_no', '#ORD' . $orderId)
                        ->orWhere('order_no', '#' . $orderId);
                });
            }

            $orders = $query->latest()->paginate($request->limit ?? 10);

            return $this->success([
                'orders' => PickupOrderResource::collection($orders),
                'pagination' => [
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                ]
            ], 'Pickup Orders Data Retrieved Successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve pickup orders: ' . $e->getMessage());
        }
    }


    /**
     * Get a minimal list of accepted order IDs
     */
    public function acceptedOrdersIds()
    {
        try {
            $vendorId = auth()->id();
            $orders = Order::where('vendor_id', $vendorId)
                ->whereIn('status', ['accepted', 'preparing', 'ready', 'picked'])
                ->select('id', 'order_no')
                ->latest()
                ->get();

            $data = $orders->map(fn($order) => [
                'id' => $order->id,
                'order_id' => $order->order_no,
            ])->values()->toArray();

            return $this->success($data, 'Accepted Order IDs Retrieved Successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve accepted order IDs: ' . $e->getMessage());
        }
    }


    /**
     * Update Order Status (Vendor Actions)
     *
     * Flow:
     *  pending  → accepted   : Stripe auth hold on customer card
     *  accepted → preparing  : no payment action
     *  preparing→ ready      : no payment action
     *  ready    → (rider picks up for delivery / customer picks up for pickup)
     *  *        → delivered/completed : capture hold + wallet distribution
     *  *        → canceled   : release hold (cancel PaymentIntent)
     */
    public function updateStatus(OrderStatusUpdateRequest $request, $id)
    {
        try {
            $status = $request->status;
            if ($status === 'accept') {
                $status = 'accepted';
            } elseif ($status === 'picked_up') {
                $status = 'picked';
            } elseif (in_array($status, ['complete', 'completed', 'deliver', 'delivered'])) {
                $status = 'delivered';
            } elseif ($status === 'cancel' || $status === 'cancelled') {
                $status = 'canceled';
            }
            $request->merge(['status' => $status]);

            $vendorId = auth()->id();
 
            $order = Order::where('vendor_id', $vendorId)
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)
                      ->orWhere('order_no', $id)
                      ->orWhere('order_no', '#ORD' . $id)
                      ->orWhere('order_no', '#' . $id);
                })
                ->first();
 
            if (!$order) {
                $exists = Order::where('id', $id)->orWhere('order_no', $id)->exists();
                if ($exists) {
                    return $this->error([], 'Access denied. This order does not belong to your store.', 403);
                }
                return $this->error([], 'Order not found with the provided ID: ' . $id, 404);
            }

            $currentStatus = $order->status;
            $newStatus = $request->status;

            if ($newStatus !== $currentStatus) {
                // Enforce terminal states cannot be changed
                if (in_array($currentStatus, ['delivered', 'completed', 'canceled'])) {
                    return $this->error([], 'Cannot change status of a completed, delivered, or canceled order.', 400);
                }

                // Check allowed transitions
                if ($newStatus === 'accepted') {
                    if ($currentStatus !== 'pending') {
                        return $this->error([], 'Only pending orders can be accepted.', 400);
                    }
                } elseif ($newStatus === 'preparing') {
                    if ($currentStatus !== 'accepted') {
                        return $this->error([], 'Order must be accepted before it can be prepared.', 400);
                    }
                } elseif ($newStatus === 'ready') {
                    if ($currentStatus !== 'preparing') {
                        return $this->error([], 'Order must be in preparing stage before it can be marked as ready.', 400);
                    }
                } elseif ($newStatus === 'picked') {
                    if ($currentStatus !== 'ready') {
                        return $this->error([], 'Order must be ready before it can be picked up.', 400);
                    }
                } elseif ($newStatus === 'delivered') {
                    if ($currentStatus !== 'ready' && $currentStatus !== 'picked') {
                        return $this->error([], 'Order must be ready or picked up before it can be delivered.', 400);
                    }
                }
            } else {
                return $this->success(new OrderResource($order), 'Order status is already ' . $newStatus);
            }
 
            // ─────────────────────────────────────────────────────────────
            // 1. ACCEPTED → Place Stripe auth hold on the customer's card (if not already captured)
            // ─────────────────────────────────────────────────────────────
            if ($request->status === 'accepted') {
                if ($order->payment_type === 'wallet' || $order->payment_status === 'captured') {
                    // Payment already captured/processed via Wallet or Stripe. No auth hold needed.
                } else {
                    try {
                        $customer = $order->user;
     
                        if (!$customer || !$customer->hasDefaultPaymentMethod()) {
                            return $this->error([], 'Customer does not have a valid payment method.', 400);
                        }
     
                        $amount        = (int) round($order->total_amount * 100); // cents
                        $paymentMethod = $customer->defaultPaymentMethod();
     
                        $paymentIntent = $customer->createPaymentIntent($amount, [
                            'customer'       => $customer->stripe_id,
                            'payment_method' => $paymentMethod->id,
                            'capture_method' => 'manual',   // hold, don't capture yet
                            'confirm'        => true,
                            'off_session'    => true,
                            'metadata'       => ['order_id' => $order->id],
                        ]);
     
                        $order->payment_intent_id = $paymentIntent->id;
                    } catch (Exception $e) {
                        Log::error('Stripe Auth Hold (Vendor Acceptance) Error: ' . $e->getMessage());
                        return $this->error([], 'Payment authorization failed. Customer might have insufficient funds.', 400);
                    }
                }
            }
 
            // ─────────────────────────────────────────────────────────────
            // 2. CANCELED → Release the Stripe hold (if any) or refund Wallet/Stripe captured payment
            // ─────────────────────────────────────────────────────────────
            if ($request->status === 'canceled') {
                if ($order->payment_status === 'captured') {
                    if ($order->payment_type === 'wallet') {
                        try {
                            DB::beginTransaction();
                            $walletTx = WalletService::credit(
                                $order->user_id,
                                $order->total_amount,
                                'refund',
                                $order,
                                'Refund for canceled order #' . $order->order_no
                            );
                            $order->payment_status = 'refunded';
                            $order->refund_transaction_id = $walletTx->id;
                            $order->refunded_at = now();
                            $order->refunded_amount = $order->total_amount;
                            $order->save();
                            DB::commit();
                        } catch (Exception $ex) {
                            DB::rollBack();
                            Log::error('Wallet Refund (Vendor Cancellation) Error: ' . $ex->getMessage());
                        }
                    } elseif ($order->payment_type === 'stripe' && $order->payment_intent_id) {
                        try {
                            Stripe::setApiKey(config('services.stripe.secret'));
                            $refund = \Stripe\Refund::create([
                                'payment_intent' => $order->payment_intent_id,
                            ]);
                            $order->payment_status = 'refunded';
                            $order->refund_transaction_id = $refund->id;
                            $order->refunded_at = now();
                            $order->refunded_amount = $order->total_amount;
                            $order->save();
                        } catch (Exception $e) {
                            Log::error('Stripe Refund (Vendor Cancellation) Error: ' . $e->getMessage());
                        }
                    }
                } elseif ($order->payment_intent_id) {
                    try {
                        Stripe::setApiKey(config('services.stripe.secret'));
                        $intent = PaymentIntent::retrieve($order->payment_intent_id);
                        if ($intent->status === 'requires_capture') {
                            $intent->cancel();
                        }
                    } catch (Exception $e) {
                        Log::error('Stripe Cancel Hold (Vendor) Error: ' . $e->getMessage());
                        // Non-fatal — still update the order status
                    }
                }
            }
 
            // Update status
            $order->status = $request->status;
            $order->save();

            broadcast(new OrderStatusUpdated($order));

            if (in_array($request->status, ['preparing', 'ready'])) {
                broadcast(new OrderReady($order));
            }
 
            // ─────────────────────────────────────────────────────────────
            // 3. DELIVERED / COMPLETED → Capture Stripe + Wallet distribution
            //    Applies to BOTH delivery and pickup orders
            // ─────────────────────────────────────────────────────────────
            
            if ($request->status === 'delivered') {
                DB::beginTransaction();
                try {
                    // 3a. Capture the Stripe payment
                    if ($order->payment_intent_id) {
                        try {
                            Stripe::setApiKey(config('services.stripe.secret'));
                            $intent = PaymentIntent::retrieve($order->payment_intent_id);
                            if ($intent->status === 'requires_capture') {
                                $intent->capture([
                                    'amount_to_capture' => (int) round($order->total_amount * 100),
                                ]);
                            }
                        } catch (Exception $e) {
                            Log::error('Stripe Capture (' . $order->order_type . ') Error: ' . $e->getMessage());
                            // Log but continue — wallet distribution should still happen
                        }
                    }
 
                    // 3b. Calculate commission split
                    $foodPrice   = $order->subtotal;
                    $deliveryFee = $order->delivery_fee ?? 0;  // 0 for pickup orders
                    $discount    = $order->discount_amount ?? 0;
                    $vatAmount   = $order->vat_amount ?? 0;
 
                    $voucherOwner = 'admin';
                    if ($order->voucher_id) {
                        $voucher      = Voucher::find($order->voucher_id);
                        $voucherOwner = $voucher ? $voucher->owner_role : 'admin';
                    }
 
                    $commissionPercent = SystemSetting::first()?->food_commission ?? 10;
 
                    $split = CommissionService::calculateFoodSplit(
                        $foodPrice,
                        $deliveryFee,
                        $discount,
                        $voucherOwner,
                        $commissionPercent
                    );
 
                    // 3c. Store commission, debit admin wallet, and credit vendor wallet
                    $order->commission_amount = $split['admin_amount'] + $vatAmount;
                    $order->save();
 
                    $admin = \App\Models\User::where('user_type', 'admin')->first();
                    if ($admin && $split['vendor_amount'] > 0) {
                        try {
                            WalletService::debit(
                                $admin->id,
                                $split['vendor_amount'],
                                'order_payout_vendor',
                                $order,
                                'Vendor payout for ' . $order->order_type . ' order #' . $order->order_no
                            );
                        } catch (Exception $ex) {
                            Log::error('Admin Wallet Debit (Food Vendor status update) Error: ' . $ex->getMessage());
                        }
                    }

                    WalletService::credit(
                        $order->vendor_id,
                        $split['vendor_amount'],
                        'order_payment',
                        $order,
                        'Payment for ' . $order->order_type . ' order #' . $order->order_no
                    );
 
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Order Distribution Error (' . $order->order_type . '): ' . $e->getMessage());
                    // Status was already saved above; distribution failure is logged separately
                }
            }
 
            // ─────────────────────────────────────────────────────────────
            // 4. Notify Customer
            // ─────────────────────────────────────────────────────────────
            $statusMessages = [
                'accepted'  => "Your order #{$order->order_no} has been accepted by the store.",
                'preparing' => "Your order #{$order->order_no} is being prepared.",
                'ready'     => "Your order #{$order->order_no} is ready for " . ($order->order_type === 'pickup' ? 'pickup' : 'delivery') . ".",
                'canceled'  => "Your order #{$order->order_no} has been canceled by the store.",
                'delivered' => "Your order #{$order->order_no} has been delivered. Enjoy!",
                'completed' => "Your order #{$order->order_no} is complete. Thank you!",
            ];
 
            if (isset($statusMessages[$request->status])) {
                NotificationService::send(
                    $order->user_id,
                    "Order Update",
                    $statusMessages[$request->status],
                    'order',
                    'customer',
                    ['order_id' => $order->id, 'status' => $request->status]
                );
            }
 
            // ─────────────────────────────────────────────────────────────
            // 5. Notify Delivery Man
            // ─────────────────────────────────────────────────────────────
            if ($order->order_type !== 'pickup') {
                if ($request->status === 'ready') {
                    if ($order->delivery_man_id) {
                        NotificationService::send(
                            $order->delivery_man_id,
                            "Order Ready for Pickup",
                            "Order #{$order->order_no} is ready for pickup at " . ($order->vendor->business?->business_name ?? $order->vendor->name) . ".",
                            'order',
                            'delivery_man',
                            ['order_id' => $order->id, 'status' => 'ready']
                        );
                    } else {
                        // Notify all available delivery men
                        NotificationService::sendToRole(
                            'delivery_man',
                            "New Order Ready for Pickup",
                            "Order #{$order->order_no} is ready for pickup. Accept now to deliver!",
                            'order',
                            ['order_id' => $order->id, 'status' => 'ready', 'is_request' => true]
                        );
                    }
                } elseif (in_array($request->status, ['accepted', 'preparing']) && !$order->delivery_man_id) {
                    // Notify available delivery men that a new accepted/preparing order is ready to be assigned
                    NotificationService::sendToRole(
                        'delivery_man',
                        "New Order Request Nearby",
                        "A new order request #{$order->order_no} is available nearby.",
                        'order',
                        ['order_id' => $order->id, 'is_request' => true]
                    );
                }
            }

            return $this->success(new OrderResource($order), 'Order status updated to ' . $request->status);
 
        } catch (Exception $e) {
            return $this->error([], 'Failed to update order status: ' . $e->getMessage());
        }
    }
}
