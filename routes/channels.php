<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ==================== Order Location ====================
Broadcast::channel('order.{orderId}', function (User $user, $orderId) {
    Log::info("Order id:".$orderId);
    return true;
});

// ==================== Ride Location ====================
Broadcast::channel('ride.{rideId}', function (User $user, $rideId) {
    Log::info("Ride id:".$rideId);
    return true;
});


// ==================== ORDER CHAT ====================
// Broadcast::channel('order.{orderId}.chat', function (User $user, $orderId) {
//     $order = Order::findOrFail($orderId);

//     if (!$order) {
//         return false;
//     }

//     // order related any participant + admin can access this channel
//     return $user->id === $order->user_id ||
//            $user->id === $order->vendor_id ||
//            $user->id === $order->delivery_man_id ||
//            $user->is_admin === 1 || $user->user_type === 'admin';
// });
Broadcast::channel('order.{orderId}.chat', function (User $user, $orderId) {
    // $order = Order::find($orderId);

    // if (!$order) return false;

    // return $user->id === $order->user_id ||
    //        $user->id === $order->vendor_id ||
    //        $user->id === $order->delivery_man_id ||
    //        $user->is_admin == 1 ||
    //        $user->user_type === 'admin';
    return true;
});

// ==================== SUPPORT CHAT ====================
Broadcast::channel('support.{userId}', function (User $user, $userId) {
    // customer only can access their own support chat
    if ($user->user_type === 'customer') {
        return (int) $user->id === (int) $userId;
    }

    // admin all support chat can access
    return $user->is_admin || $user->user_type === 'admin';
});