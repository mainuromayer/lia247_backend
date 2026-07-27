<?php

namespace App\Http\Controllers\API\V1\Customer\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Customer\Review\ReviewStoreRequest;
use App\Models\Order;
use App\Models\Review;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    use ApiResponse;

    public function store(ReviewStoreRequest $request)
    {
        try {
            $user = Auth::user();
            $order = Order::with('items.item')->where('id', $request->order_id)->first();

            if (!$order) {
                return $this->error([], 'Order not found', 404);
            }

            if ($user->id !== $order->user_id) {
                return $this->error([], 'You can only review your own orders', 403);
            }

            if ($order->status !== 'delivered') {
                return $this->error([], 'You can only review a delivered order', 403);
            }

            $reviews = [];

            // vendor review
            $vendor_id = $order->vendor_id;
            if (!$vendor_id && $order->items->isNotEmpty()) {
                $vendor_id = $order->items->first()->item?->vendor_id;
            }

            if ($request->filled('vendor_rating') && $vendor_id) {
                $vendorReview = Review::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'target_id' => $vendor_id,
                        'role' => 'vendor'
                    ],
                    [
                        'rating' => $request->vendor_rating,
                        'comment' => $request->vendor_comment
                    ]
                );
                $reviews[] = $vendorReview;
            }

            // delivery_man review
            $delivery_man_id = $order->delivery_man_id;
            // if delivery man is not assigned, we can't review them.

            if ($request->filled('delivery_man_rating') && $delivery_man_id) {
                $deliveryReview = Review::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'target_id' => $delivery_man_id,
                        'role' => 'delivery_man'
                    ],
                    [
                        'rating' => $request->delivery_man_rating,
                        'comment' => $request->delivery_man_comment
                    ]
                );
                $reviews[] = $deliveryReview;
            }

            // rider review
            if ($request->filled('rider_rating') && property_exists($order, 'rider_id') && $order->rider_id) {
                $riderReview = Review::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'target_id' => $order->rider_id,
                        'role' => 'rider'
                    ],
                    [
                        'rating' => $request->rider_rating,
                        'comment' => $request->rider_comment
                    ]
                );
                $reviews[] = $riderReview;
            }

            return $this->success($reviews, 'Reviews submitted successfully', 201);

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while submitting reviews', 500);
        }
    }
}