<?php

namespace App\Http\Controllers\API\V1\Customer\Checkout;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Customer\Checkout\RepayRequest;
use App\Http\Requests\V1\Customer\Checkout\CheckoutRequest;
use App\Http\Resources\V1\Customer\Checkout\OrderResource;
use App\Http\Resources\V1\Customer\Delivery\DeliveryOptionResource;
use App\Models\Business;
use App\Models\Cart;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SystemSetting;
use App\Models\Voucher;
use App\Models\Wallet;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function getCheckoutSettings()
    {
        try {
            $deliveryOptions = DeliveryOption::where('is_active', true)->get();
            $systemSettings = SystemSetting::first();

            return $this->success([
                'delivery_options' => DeliveryOptionResource::collection($deliveryOptions),
                'vat_percentage' => (float) ($systemSettings->vat_percentage ?? 0),
            ], 'Checkout settings retrieved successfully');
        } catch (Exception $exception) {
            Log::error('Settings Fetch Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to retrieve settings', 500);
        }
    }


    /*
    public function checkout(CheckoutRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $cart = Cart::where('user_id', $user->id)
                        ->with('items.item', 'items.variant')
                        ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return $this->error([], 'Your cart is empty', 400);
            }

            // 1. Check if user has a valid payment method (MANDATORY)
            if (!$user->hasDefaultPaymentMethod()) {
                return $this->error([], 'A valid payment method is required to place an order. Please add a card first.', 400);
            }

            // ==================== SERVER-SIDE: Load trusted data ====================
            $systemSettings = SystemSetting::first();
            $vatPercentage  = (float) ($systemSettings->vat_percentage ?? 0);

            // Delivery fee from DB (never trust client)
            $deliveryFee          = 0.00;
            $resolvedDeliveryOptId = null;

            if ($request->order_type === 'delivery') {
                $deliveryOption = $request->delivery_option_id
                    ? DeliveryOption::where('id', $request->delivery_option_id)->where('is_active', true)->first()
                    : DeliveryOption::where('is_default', true)->where('is_active', true)->first();

                if (!$deliveryOption) {
                    DB::rollBack();
                    return $this->error([], 'No valid delivery option found.', 422);
                }

                $deliveryFee           = (float) $deliveryOption->price;
                $resolvedDeliveryOptId = $deliveryOption->id;
            }

            // Voucher from DB (never trust client)
            $appliedVoucher = null;
            if ($request->voucher_id) {
                $appliedVoucher = Voucher::where('id', $request->voucher_id)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                    })
                    ->first();

                if (!$appliedVoucher) {
                    DB::rollBack();
                    return $this->error([], 'Voucher is invalid or expired.', 422);
                }
            }

            // ==================== SERVER-SIDE: Calculate cart subtotal ====================
            $cartSubtotal      = 0.00;
            $allPricesForBogo  = [];

            foreach ($cart->items as $cartItem) {
                $cartSubtotal += $cartItem->unit_price * $cartItem->quantity;
                for ($i = 0; $i < $cartItem->quantity; $i++) {
                    $allPricesForBogo[] = (float) $cartItem->unit_price;
                }
            }

            // ==================== SERVER-SIDE: Calculate discount ====================
            $discountAmount = 0.00;
            $discountType   = null;

            if ($appliedVoucher) {
                // If it's a vendor voucher, we only check against their specific subtotal later.
                // But for validation, we can check if the vendor has items in the cart.
                if ($appliedVoucher->vendor_id) {
                    $vendorSpecificSubtotal = 0;
                    foreach ($cart->items as $cartItem) {
                        if ($cartItem->item->vendor_id == $appliedVoucher->vendor_id) {
                            $vendorSpecificSubtotal += $cartItem->unit_price * $cartItem->quantity;
                        }
                    }

                    if ($vendorSpecificSubtotal <= 0) {
                         DB::rollBack();
                         return $this->error([], 'This voucher is only valid for items from a specific vendor.', 422);
                    }

                    if ($vendorSpecificSubtotal < (float) $appliedVoucher->min_order_amount) {
                        DB::rollBack();
                        return $this->error([], 'The subtotal for items from this vendor does not meet the voucher minimum order amount of ' . $appliedVoucher->min_order_amount . '.', 422);
                    }
                } else {
                    // Admin voucher - check against total subtotal
                    if ($cartSubtotal < (float) $appliedVoucher->min_order_amount) {
                        DB::rollBack();
                        return $this->error([], 'Subtotal does not meet voucher minimum order amount of ' . $appliedVoucher->min_order_amount . '.', 422);
                    }
                }

                $discountType = $appliedVoucher->discount_type;

                if ($appliedVoucher->discount_type === 'buy_one_get_one') {
                    // BOGO logic is tricky for multi-vendor, but if it's a vendor voucher:
                    $eligiblePrices = [];
                    foreach ($cart->items as $cartItem) {
                        if (!$appliedVoucher->vendor_id || $cartItem->item->vendor_id == $appliedVoucher->vendor_id) {
                            for ($i = 0; $i < $cartItem->quantity; $i++) {
                                $eligiblePrices[] = (float) $cartItem->unit_price;
                            }
                        }
                    }
                    sort($eligiblePrices);
                    $freeCount = floor(count($eligiblePrices) / 2);
                    for ($i = 0; $i < $freeCount; $i++) {
                        $discountAmount += $eligiblePrices[$i];
                    }
                } elseif ($appliedVoucher->discount_type === 'fixed') {
                    $discountAmount = (float) $appliedVoucher->discount_amount;
                } else {
                    // percentage
                    $baseForDiscount = $cartSubtotal;
                    if ($appliedVoucher->vendor_id) {
                        $baseForDiscount = 0;
                        foreach ($cart->items as $cartItem) {
                            if ($cartItem->item->vendor_id == $appliedVoucher->vendor_id) {
                                $baseForDiscount += $cartItem->unit_price * $cartItem->quantity;
                            }
                        }
                    }
                    $discountAmount = round(($baseForDiscount * (float) $appliedVoucher->discount_amount) / 100, 2);
                }
            }

            // ==================== SERVER-SIDE: Final totals ====================
            $taxableAmount = $cartSubtotal - $discountAmount;
            $vatAmount     = round(($taxableAmount * $vatPercentage) / 100, 2);
            $grandTotal    = round($taxableAmount + $vatAmount + $deliveryFee, 2);

            // ==================== GROUP BY VENDOR & CREATE ORDERS ====================
            $itemsByVendor = $cart->items->groupBy(fn($item) => $item->item->vendor_id);
            $vendorCount   = count($itemsByVendor);

            $orders = [];
            $totalCombinedPayable = 0.00;

            foreach ($itemsByVendor as $vendorId => $vendorItems) {
                // Vendor-proportional split of shared costs
                $vendorSubtotal = 0.00;
                foreach ($vendorItems as $ci) {
                    $vendorSubtotal += $ci->unit_price * $ci->quantity;
                }

                $proportion = $cartSubtotal > 0 ? ($vendorSubtotal / $cartSubtotal) : (1 / $vendorCount);

                // Calculate discount for this specific vendor
                $vendorDiscount = 0.00;
                $vendorVoucherId = null;
                $vendorDiscountType = null;

                if ($appliedVoucher) {
                    if ($appliedVoucher->vendor_id) {
                        // Vendor specific voucher
                        if ($vendorId == $appliedVoucher->vendor_id) {
                            $vendorDiscount = $discountAmount;
                            $vendorVoucherId = $appliedVoucher->id;
                            $vendorDiscountType = $discountType;
                        }
                    } else {
                        // Admin voucher - split proportionally
                        $vendorDiscount = round($discountAmount * $proportion, 2);
                        $vendorVoucherId = $appliedVoucher->id;
                        $vendorDiscountType = $discountType;
                    }
                }

                $vendorVat       = round($vatAmount * $proportion, 2);
                $vendorDelivery  = round($deliveryFee / $vendorCount, 2);

                // ==================== FREE DELIVERY LOGIC ====================
                $isFreeDelivery = false;
                $coveredBy = null;
                $adminContribution = 0;
                $vendorContribution = 0;

                $business = Business::where('user_id', $vendorId)->first();
                if ($business && $business->free_delivery && $request->order_type === 'delivery') {
                    $isFreeDelivery = true;
                    $coveredBy = $business->free_delivery_type;

                    if ($coveredBy === 'admin') {
                        $adminContribution = $vendorDelivery;
                    } elseif ($coveredBy === 'vendor') {
                        $vendorContribution = $vendorDelivery;
                    } elseif ($coveredBy === 'hybrid') {
                        $adminContribution = (float) $business->admin_contribution;
                        $vendorContribution = (float) $business->vendor_contribution;

                        // Ensure total contribution matches the delivery fee
                        // Or at least cap it at the delivery fee
                        $totalContributed = $adminContribution + $vendorContribution;
                        if ($totalContributed > 0) {
                            $adminContribution = ($adminContribution / $totalContributed) * $vendorDelivery;
                            $vendorContribution = ($vendorContribution / $totalContributed) * $vendorDelivery;
                        }
                    }
                    $vendorDelivery = 0; // Customer doesn't pay
                }
                // =============================================================

                $vendorTotal     = round(($vendorSubtotal - $vendorDiscount) + $vendorVat + $vendorDelivery, 2);

                $totalCombinedPayable += $vendorTotal;

                // Create Order
                $checkoutOrder = new Order();
                $checkoutOrder->user_id            = $user->id;
                $checkoutOrder->vendor_id          = $vendorId;
                $checkoutOrder->order_type         = $request->order_type;
                $checkoutOrder->delivery_option_id = $resolvedDeliveryOptId;

                // Handle Receiver Details
                if ($request->order_type === 'pickup') {
                    $checkoutOrder->receiver_name    = $request->receiver_name ?? $user->name;
                    $checkoutOrder->receiver_phone   = $request->receiver_phone ?? $user->phone;
                    $checkoutOrder->receiver_address = null;
                    $checkoutOrder->latitude         = null;
                    $checkoutOrder->longitude        = null;
                } else {
                    // Delivery: Use profile data as default
                    $checkoutOrder->receiver_name    = $request->receiver_name ?? $user->name;
                    $checkoutOrder->receiver_phone   = $request->receiver_phone ?? $user->phone;
                    $checkoutOrder->receiver_address = $request->address ?? $user->address;
                    $checkoutOrder->latitude         = $request->latitude ?? $user->latitude;
                    $checkoutOrder->longitude        = $request->longitude ?? $user->longitude;
                }

                $checkoutOrder->voucher_id         = $vendorVoucherId;
                $checkoutOrder->subtotal           = $vendorSubtotal;
                $checkoutOrder->discount_amount    = $vendorDiscount;
                $checkoutOrder->discount_type      = $vendorDiscountType;
                $checkoutOrder->vat_amount         = $vendorVat;
                $checkoutOrder->delivery_fee       = $vendorDelivery;
                $checkoutOrder->total_amount       = $vendorTotal;
                $checkoutOrder->payment_gateway    = $request->payment_gateway;
                $checkoutOrder->status             = 'pending';
                $checkoutOrder->notes              = $request->notes ?? null;

                // Free Delivery Campaign Info
                $checkoutOrder->free_delivery = $isFreeDelivery;
                $checkoutOrder->free_delivery_covered_by = $coveredBy;
                $checkoutOrder->admin_delivery_contribution = $adminContribution;
                $checkoutOrder->vendor_delivery_contribution = $vendorContribution;

                $checkoutOrder->save();

                // Create Order Items
                foreach ($vendorItems as $cartItem) {
                    $orderItem                    = new OrderItem();
                    $orderItem->order_id          = $checkoutOrder->id;
                    $orderItem->item_id           = $cartItem->item_id;
                    $orderItem->item_name         = $cartItem->item->name;
                    $orderItem->variant_id        = $cartItem->variant_id;
                    $orderItem->selected_toppings = $cartItem->selected_toppings;
                    $orderItem->quantity          = $cartItem->quantity;
                    $orderItem->price             = $cartItem->unit_price;
                    $orderItem->total_price       = $cartItem->unit_price * $cartItem->quantity;
                    $orderItem->special_instruction = $cartItem->special_instruction;
                    $orderItem->save();
                }

                $orders[] = $checkoutOrder;

                // Notify Vendor
                NotificationService::send(
                    $vendorId,
                    "New Order Received",
                    "You have a new order #{$checkoutOrder->order_no}",
                    'order',
                    'vendor',
                    ['order_id' => $checkoutOrder->id, 'is_request' => true]
                );

                // Notify Customer
                NotificationService::send(
                    $user->id,
                    "Order Placed",
                    "Your order #{$checkoutOrder->order_no} has been placed successfully",
                    'order',
                    'customer',
                    ['order_id' => $checkoutOrder->id]
                );
            }

            // ==================== PAYMENT GATEWAY ====================
            $client_secret = null;

            if ($request->payment_gateway === 'stripe') {
                $paymentIntent = $user->createPaymentIntent((int) round($totalCombinedPayable * 100), [
                    'metadata' => [
                        'order_ids' => implode(',', array_map(fn($o) => $o->id, $orders)),
                    ],
                    'description' => 'Payment for order(s): ' . implode(', ', array_map(fn($o) => $o->order_no, $orders)),
                ]);

                foreach ($orders as $o) {
                    $o->payment_intent_id = $paymentIntent->id;
                    $o->save();
                }

                $client_secret = $paymentIntent->client_secret;
            }

            // Clear cart after successful order creation
            $cart->items()->delete();

            DB::commit();

            return $this->success([
                'client_secret'   => $client_secret,
                'grand_total'     => round($totalCombinedPayable, 2),
                'summary'         => [
                    'subtotal'         => round($cartSubtotal, 2),
                    'discount'         => round($discountAmount, 2),
                    'vat_percentage'   => $vatPercentage,
                    'vat_amount'       => $vatAmount,
                    'delivery_fee'     => round(collect($orders)->sum('delivery_fee'), 2),
                    'total'            => round($totalCombinedPayable, 2),
                ],
                'orders' => OrderResource::collection(collect($orders)),
            ], 'Checkout initiated successfully');

        } catch (Exception $exception) {
            DB::rollBack();
            return $this->error([], 'Checkout failed: ' . $exception->getMessage(), 500);
        }
    }
    */

    /**
     * Checkout
     *
     * Payment flow:
     *  - No Stripe hold at checkout time.
     *  - Hold is created when the vendor ACCEPTS the order (in VendorOrderController).
     *  - Capture + wallet distribution happens when status moves to delivered/completed.
     *
     * VAT is always charged to the customer (applied on taxable amount = subtotal - discount).
     *
     * Pickup orders:
     *  - delivery_fee = 0  (customer picks up in person, no delivery charge)
     *  - No free-delivery logic needed for pickup.
     *
     * Before placing the order we verify the customer has a default payment method
     * so the vendor acceptance hold cannot fail due to a missing card.
     */
    public function checkout(CheckoutRequest $request)
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
            $user = Auth::user();

            // ── 2. Cart Validation ────────────────────────────────────────────────
            $cart = Cart::where('user_id', $user->id)
                ->with('items.item', 'items.variant')
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return $this->error([], 'Your cart is empty.', 400);
            }

            // ── 3. System Settings ────────────────────────────────────────────────
            $systemSettings = SystemSetting::first();
            $vatPercentage = (float) ($systemSettings->vat_percentage ?? 0);

            // ── 4. Cart Subtotal (server-side, never trust client) ────────────────
            $cartSubtotal = 0.00;
            foreach ($cart->items as $ci) {
                $cartSubtotal += $ci->unit_price * $ci->quantity;
            }

            // ── 5. Delivery Fee ───────────────────────────────────────────────────
            $deliveryFee = 0.00;
            $resolvedDeliveryOptId = null;

            if ($request->order_type === 'delivery') {
                $deliveryOption = $request->delivery_option_id
                    ? DeliveryOption::where('id', $request->delivery_option_id)
                        ->where('is_active', true)
                        ->first()
                    : DeliveryOption::where('is_default', true)
                        ->where('is_active', true)
                        ->first();

                if (!$deliveryOption) {
                    DB::rollBack();
                    return $this->error([], 'No valid delivery option found.', 422);
                }

                $deliveryFee = (float) $deliveryOption->price;
                $resolvedDeliveryOptId = $deliveryOption->id;
            }

            // ── 7. Voucher / Discount ─────────────────────────────────────────────
            $discountAmount = 0.00;
            $appliedVoucher = null;
            $discountType = null;

            if ($request->voucher_id) {
                $appliedVoucher = Voucher::where('id', $request->voucher_id)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                    })
                    ->first();

                if (!$appliedVoucher) {
                    DB::rollBack();
                    return $this->error([], 'Voucher is invalid or expired.', 422);
                }

                $discountType = $appliedVoucher->discount_type;

                // Determine the base amount for the discount calculation
                $baseForDiscount = $cartSubtotal;
                if ($appliedVoucher->vendor_id) {
                    $baseForDiscount = $cart->items
                        ->filter(fn($ci) => $ci->item->vendor_id == $appliedVoucher->vendor_id)
                        ->sum(fn($ci) => $ci->unit_price * $ci->quantity);

                    if ($baseForDiscount <= 0) {
                        DB::rollBack();
                        return $this->error([], 'This voucher is only valid for items from a specific vendor.', 422);
                    }
                }

                // Min order check
                if ($baseForDiscount < (float) $appliedVoucher->min_order_amount) {
                    DB::rollBack();
                    return $this->error([], 'Subtotal does not meet voucher minimum order amount of ' . $appliedVoucher->min_order_amount . '.', 422);
                }

                if ($appliedVoucher->discount_type === 'buy_one_get_one') {
                    $eligiblePrices = [];
                    foreach ($cart->items as $ci) {
                        if (!$appliedVoucher->vendor_id || $ci->item->vendor_id == $appliedVoucher->vendor_id) {
                            for ($i = 0; $i < $ci->quantity; $i++) {
                                $eligiblePrices[] = (float) $ci->unit_price;
                            }
                        }
                    }
                    sort($eligiblePrices);
                    $freeCount = (int) floor(count($eligiblePrices) / 2);
                    for ($i = 0; $i < $freeCount; $i++) {
                        $discountAmount += $eligiblePrices[$i];
                    }
                } elseif ($appliedVoucher->discount_type === 'fixed') {
                    $discountAmount = (float) $appliedVoucher->discount_amount;
                } else {
                    // percentage
                    $discountAmount = round(($baseForDiscount * (float) $appliedVoucher->discount_amount) / 100, 2);
                }
            }

            // ── 8. VAT ────────────────────────────────────────────────────────────
            $taxableAmount = max(0, $cartSubtotal - $discountAmount);
            $vatAmount = round(($taxableAmount * $vatPercentage) / 100, 2);

            // ── 9. Calculate Total Combined Payable ────────────────────────────────
            $itemsByVendor = $cart->items->groupBy(fn($item) => $item->item->vendor_id);
            $vendorCount = count($itemsByVendor);
            $totalCombinedPayable = 0.00;

            // Cache split calculations for order creation later
            $splitsInfo = [];
            foreach ($itemsByVendor as $vendorId => $vendorItems) {
                $vendorSubtotal = collect($vendorItems)->sum(fn($ci) => $ci->unit_price * $ci->quantity);
                $proportion = $cartSubtotal > 0 ? ($vendorSubtotal / $cartSubtotal) : (1 / $vendorCount);

                $vendorDiscount = 0.00;
                $vendorVoucherId = null;
                $vendorDiscType = null;

                if ($appliedVoucher) {
                    if ($appliedVoucher->vendor_id) {
                        if ($vendorId == $appliedVoucher->vendor_id) {
                            $vendorDiscount = $discountAmount;
                            $vendorVoucherId = $appliedVoucher->id;
                            $vendorDiscType = $discountType;
                        }
                    } else {
                        $vendorDiscount = round($discountAmount * $proportion, 2);
                        $vendorVoucherId = $appliedVoucher->id;
                        $vendorDiscType = $discountType;
                    }
                }

                $vendorVat = round($vatAmount * $proportion, 2);

                $vendorDelivery = 0.00;
                $isFree = false;
                $coveredBy = null;
                $adminContrib = 0.00;
                $vendorContrib = 0.00;

                if ($request->order_type === 'delivery') {
                    $vendorDelivery = round($deliveryFee / $vendorCount, 2);

                    $business = Business::where('user_id', $vendorId)->first();
                    if ($business && $business->free_delivery) {
                        $isFree = true;
                        $coveredBy = $business->free_delivery_type;

                        if ($coveredBy === 'admin') {
                            $adminContrib = $vendorDelivery;
                        } elseif ($coveredBy === 'vendor') {
                            $vendorContrib = $vendorDelivery;
                        } elseif ($coveredBy === 'hybrid') {
                            $total = (float) $business->admin_contribution + (float) $business->vendor_contribution;
                            if ($total > 0) {
                                $adminContrib = ((float) $business->admin_contribution / $total) * $vendorDelivery;
                                $vendorContrib = ((float) $business->vendor_contribution / $total) * $vendorDelivery;
                            }
                        }

                        $vendorDelivery = 0.00;
                    }
                }

                $vendorTotal = round(
                    ($vendorSubtotal - $vendorDiscount) + $vendorVat + $vendorDelivery,
                    2
                );
                $totalCombinedPayable += $vendorTotal;

                $splitsInfo[$vendorId] = [
                    'vendorSubtotal' => $vendorSubtotal,
                    'vendorDiscount' => $vendorDiscount,
                    'vendorVat' => $vendorVat,
                    'vendorDelivery' => $vendorDelivery,
                    'vendorTotal' => $vendorTotal,
                    'vendorVoucherId' => $vendorVoucherId,
                    'vendorDiscType' => $vendorDiscType,
                    'isFree' => $isFree,
                    'coveredBy' => $coveredBy,
                    'adminContrib' => $adminContrib,
                    'vendorContrib' => $vendorContrib,
                    'vendorItems' => $vendorItems,
                ];
            }

            // ── 10. Process Payment ───────────────────────────────────────────────
            $payment_type = $request->payment_type;
            $payment_status = 'pending';
            $payment_intent_id = null;
            $transaction_id = null;

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
                // dd("Balance: $balance", "Total Combined Payable: $totalCombinedPayable");
                if ($balance < $totalCombinedPayable) {
                    DB::rollBack();
                    return $this->error([], 'Insufficient wallet balance.', 400);
                }

                $walletTx = WalletService::debit(
                    $user->id,
                    $totalCombinedPayable,
                    'order_payment',
                    null,
                    'Payment for order checkout'
                );
                $payment_status = 'captured';
                $transaction_id = $walletTx->id;
            } elseif ($payment_type === 'stripe') {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = PaymentIntent::create([
                        'amount' => (int) round($totalCombinedPayable * 100),
                        'currency' => 'usd',
                        'customer' => $user->createOrGetStripeCustomer()->id,
                        'payment_method' => $request->payment_method_id,
                        'confirm' => true,
                        'off_session' => true,
                        'capture_method' => 'automatic',
                        'description' => 'Payment for order checkout',
                    ]);

                    if (!in_array($intent->status, ['succeeded', 'processing'])) {
                        DB::rollBack();
                        return $this->error([], 'Payment failed. Stripe status: ' . $intent->status, 400);
                    }

                    $payment_intent_id = $intent->id;
                    $transaction_id = $intent->latest_charge ?? $intent->id;
                    $payment_status = 'captured';
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Stripe immediate charge failed: ' . $e->getMessage());
                    return $this->error([], 'Payment failed: ' . $e->getMessage(), 400);
                }
            }

            // ── 11. Create Orders in DB ───────────────────────────────────────────
            $orders = [];
            foreach ($splitsInfo as $vendorId => $info) {
                $order = new Order();
                $order->user_id = $user->id;
                $order->vendor_id = $vendorId;
                $order->order_type = $request->order_type;
                $order->delivery_option_id = $resolvedDeliveryOptId;

                $order->receiver_name = $request->receiver_name ?? $user->name;
                $order->receiver_phone = $request->receiver_phone ?? $user->phone;

                if ($request->order_type === 'pickup') {
                    $order->receiver_address = null;
                    $order->latitude = null;
                    $order->longitude = null;
                } else {
                    $order->receiver_address = $request->address ?? $user->address;
                    $order->latitude = $request->latitude ?? $user->latitude;
                    $order->longitude = $request->longitude ?? $user->longitude;
                }

                $order->voucher_id = $info['vendorVoucherId'];
                $order->subtotal = $info['vendorSubtotal'];
                $order->discount_amount = $info['vendorDiscount'];
                $order->discount_type = $info['vendorDiscType'];
                $order->vat_amount = $info['vendorVat'];
                $order->delivery_fee = $info['vendorDelivery'];
                $order->total_amount = $info['vendorTotal'];
                $order->payment_gateway = $payment_type;
                $order->status = 'pending';
                $order->notes = $request->notes ?? null;

                $order->free_delivery = $info['isFree'];
                $order->free_delivery_covered_by = $info['coveredBy'];
                $order->admin_delivery_contribution = $info['adminContrib'];
                $order->vendor_delivery_contribution = $info['vendorContrib'];

                // Save payment details
                $order->payment_type = $payment_type;
                $order->payment_status = $payment_status;
                $order->amount = $info['vendorTotal'];
                $order->transaction_id = $transaction_id;
                $order->payment_intent_id = $payment_intent_id;

                $order->save();

                // Order items
                foreach ($info['vendorItems'] as $cartItem) {
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->item_id = $cartItem->item_id;
                    $orderItem->item_name = $cartItem->item->name;
                    $orderItem->variant_id = $cartItem->variant_id;
                    $orderItem->selected_toppings = $cartItem->selected_toppings;
                    $orderItem->quantity = $cartItem->quantity;
                    $orderItem->price = $cartItem->unit_price;
                    $orderItem->total_price = $cartItem->unit_price * $cartItem->quantity;
                    $orderItem->special_instruction = $cartItem->special_instruction ?? null;
                    $orderItem->save();
                }

                // Broadcast OrderPlaced event to Vendor in real-time
                broadcast(new OrderPlaced($order));

                // Notify Vendor
                NotificationService::send(
                    $vendorId,
                    "New Order Received",
                    "You have a new order #{$order->order_no}",
                    'order',
                    'vendor',
                    ['order_id' => $order->id, 'is_request' => true]
                );

                // Notify Customer
                NotificationService::send(
                    $user->id,
                    "Order Placed",
                    "Your order #{$order->order_no} has been placed successfully.",
                    'order',
                    'customer',
                    ['order_id' => $order->id]
                );

                $orders[] = $order;
            }

            // ── 12. Clear Cart ────────────────────────────────────────────────────
            $cart->items()->delete();

            DB::commit();

            $responseData = [
                'grand_total' => round($totalCombinedPayable, 2),
                'summary' => [
                    'subtotal' => round($cartSubtotal, 2),
                    'discount' => round($discountAmount, 2),
                    'vat_percentage' => $vatPercentage,
                    'vat_amount' => round($vatAmount, 2),
                    'delivery_fee' => round(collect($orders)->sum('delivery_fee'), 2),
                    'total' => round($totalCombinedPayable, 2),
                ],
                'orders' => OrderResource::collection(collect($orders)),
            ];

            if ($payment_type === 'stripe') {
                $responseData['client_secret'] = $payment_intent_id ? (PaymentIntent::retrieve($payment_intent_id)->client_secret ?? null) : null;
            }

            return $this->success($responseData, 'Order placed successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Checkout Error: ' . $e->getMessage());
            return $this->error([], 'Checkout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Re-initiate payment for a pending order (e.g. customer changed card)
     */
    public function reinitiatePayment(RepayRequest $request)
    {
        if ($request->payment_type === 'wallet' && $request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id must not be provided for wallet payments.', 422);
        }

        if ($request->payment_type === 'stripe' && !$request->filled('payment_method_id')) {
            return $this->error([], 'payment_method_id is required for Stripe card payments.', 422);
        }

        try {
            $user = Auth::user();
            $orderId = $request->order_id;

            $order = Order::where('user_id', $user->id)
                ->where('id', $orderId)
                ->firstOrFail();

            if ($order->status !== 'pending') {
                return $this->error([], 'This order is already processed or canceled.', 400);
            }

            // Collect all sibling orders sharing the same payment intent
            $orders = collect([$order]);
            if ($order->payment_intent_id) {
                $orders = Order::with('items')->where('payment_intent_id', $order->payment_intent_id)
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->get();
            } elseif ($order->stripe_session_id) {
                $orders = Order::with('items')->where('stripe_session_id', $order->stripe_session_id)
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->get();
            } else {
                $order->load('items');
            }

            $payment_type = $request->payment_type;
            $payment_status = 'pending';
            $payment_intent_id = null;
            $transaction_id = null;
            $client_secret = null;
            $totalAmount = $orders->sum('total_amount');

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
                if ($balance < $totalAmount) {
                    return $this->error([], 'Insufficient wallet balance.', 400);
                }

                DB::beginTransaction();
                try {
                    $walletTx = WalletService::debit(
                        $user->id,
                        $totalAmount,
                        'order_payment',
                        null,
                        'Payment for order checkout'
                    );
                    $payment_status = 'captured';
                    $transaction_id = $walletTx->id;

                    foreach ($orders as $o) {
                        $o->payment_gateway = $payment_type;
                        $o->payment_type = $payment_type;
                        $o->payment_status = $payment_status;
                        $o->transaction_id = $transaction_id;
                        $o->payment_intent_id = $payment_intent_id;
                        $o->save();
                    }
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Wallet repay payment failed: ' . $e->getMessage());
                    return $this->error([], 'Payment failed: ' . $e->getMessage(), 400);
                }
            } elseif ($payment_type === 'stripe') {
                DB::beginTransaction();
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intent = PaymentIntent::create([
                        'amount' => (int) round($totalAmount * 100),
                        'currency' => 'usd',
                        'customer' => $user->createOrGetStripeCustomer()->id,
                        'payment_method' => $request->payment_method_id,
                        'confirm' => true,
                        'off_session' => true,
                        'capture_method' => 'automatic',
                        'description' => 'Payment for pending orders (Retry)',
                    ]);

                    if (!in_array($intent->status, ['succeeded', 'processing'])) {
                        DB::rollBack();
                        return $this->error([], 'Payment failed. Stripe status: ' . $intent->status, 400);
                    }

                    $payment_intent_id = $intent->id;
                    $transaction_id = $intent->latest_charge ?? $intent->id;
                    $payment_status = 'captured';
                    $client_secret = $intent->client_secret;

                    foreach ($orders as $o) {
                        $o->payment_gateway = $payment_type;
                        $o->payment_type = $payment_type;
                        $o->payment_status = $payment_status;
                        $o->transaction_id = $transaction_id;
                        $o->payment_intent_id = $payment_intent_id;
                        $o->save();
                    }
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Stripe repay payment failed: ' . $e->getMessage());
                    return $this->error([], 'Payment failed: ' . $e->getMessage(), 400);
                }
            }

            $responseData = [
                'grand_total' => round($totalAmount, 2),
                'orders' => OrderResource::collection($orders)
            ];

            if ($payment_type === 'stripe') {
                $responseData['client_secret'] = $client_secret;
            }

            return $this->success($responseData, 'Payment re-initiated successfully.');

        } catch (Exception $e) {
            return $this->error([], 'Failed to reinitiate payment: ' . $e->getMessage());
        }
    }
}

