<?php

if (!defined('LARAVEL_START')) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    $app->instance('request', Illuminate\Http\Request::createFromGlobals());
}

use App\Models\User;
use App\Models\Order;
use App\Models\Ride;
use App\Models\Wallet;
use App\Models\Tip;
use App\Http\Controllers\API\V1\Customer\Tip\TipController;
use App\Http\Requests\API\V1\Customer\Tip\TipOrderRequest;
use App\Http\Requests\API\V1\Customer\Tip\TipRideRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "--- TIPPING SYSTEM VERIFICATION SCRIPT ---\n\n";

// Helper to reset a user's wallet
function resetWallet($userId, $balance) {
    $wallet = Wallet::updateOrCreate(
        ['user_id' => $userId, 'user_type' => $userId === 2 ? 'customer' : 'delivery_man'],
        ['balance' => $balance, 'pending_balance' => 0]
    );
    // Sync other types in case
    Wallet::where('user_id', $userId)->update(['balance' => $balance]);
    echo "Reset Wallet for User ID {$userId}: balance = {$balance}\n";
}

// 1. Setup/Verify order & ride
$customer = User::find(2); // customer
$deliveryMan = User::find(5); // delivery_man
$rider = User::find(4); // rider

if (!$customer || !$deliveryMan || !$rider) {
    echo "Error: Required test users (ID 2, 4, 5) not found!\n";
    exit(1);
}

// Prepare order: ID 1
$order = Order::find(1);
if (!$order) {
    echo "Creating a dummy order for testing...\n";
    $order = Order::create([
        'id' => 1,
        'user_id' => 2,
        'vendor_id' => 3,
        'delivery_man_id' => 5,
        'status' => 'delivered',
        'subtotal' => 15.00,
        'vat_amount' => 1.50,
        'delivery_fee' => 3.00,
        'total_amount' => 19.50,
        'payment_type' => 'wallet',
        'payment_status' => 'captured',
    ]);
} else {
    $order->update([
        'user_id' => 2,
        'delivery_man_id' => 5,
        'status' => 'delivered',
    ]);
}
echo "Order ID 1 prepared: Status={$order->status}, CustomerID={$order->user_id}, DeliveryManID={$order->delivery_man_id}\n";

// Prepare ride: ID 5
$ride = Ride::find(5);
if (!$ride) {
    echo "Creating a dummy ride for testing...\n";
    $ride = Ride::create([
        'id' => 5,
        'user_id' => 2,
        'rider_id' => 4,
        'pickup_address' => 'Test Pickup',
        'dropoff_address' => 'Test Dropoff',
        'distance' => 5.0,
        'estimated_fare' => 12.00,
        'actual_fare' => 12.00,
        'total_amount' => 12.00,
        'status' => 'completed',
        'payment_type' => 'wallet',
        'payment_status' => 'captured',
    ]);
} else {
    $ride->update([
        'user_id' => 2,
        'rider_id' => 4,
        'status' => 'completed',
    ]);
}
echo "Ride ID 5 prepared: Status={$ride->status}, CustomerID={$ride->user_id}, RiderID={$ride->rider_id}\n";

// Delete existing tips for these to start clean
Tip::where('order_id', 1)->delete();
Tip::where('ride_id', 5)->delete();
echo "Cleaned up old tips for order 1 and ride 5.\n\n";

// Authenticate customer (User 2)
Auth::login($customer);

$controller = new TipController();

// ----------------------------------------------------
// SCENARIO 1: Tipping with Wallet (Wallet Balance >= Amount)
// ----------------------------------------------------
echo "=== Scenario 1: Tip Delivery Man via Wallet (Amount = $5.00) ===\n";
resetWallet(2, 50.00); // Customer has $50
resetWallet(5, 0.00);  // Delivery man has $0

$request = TipOrderRequest::create('/api/customer/order/tip', 'POST', [
    'amount' => 5.00,
    'order_id' => 1,
    'user_id' => 5, // delivery man
    'payment_type' => 'wallet',
]);

$response = $controller->tipOrder($request);
$resData = json_decode($response->getContent(), true);

echo "Response Status Code: " . $response->getStatusCode() . "\n";
echo "Response JSON:\n" . json_encode($resData, JSON_PRETTY_PRINT) . "\n";

// Verify balances
$custWallet = Wallet::where('user_id', 2)->where('user_type', 'customer')->first();
$dmWallet = Wallet::where('user_id', 5)->where('user_type', 'delivery_man')->first();

echo "Customer Wallet Balance: {$custWallet->balance} (Expected: 45.00)\n";
echo "Delivery Man Wallet Balance: {$dmWallet->balance} (Expected: 5.00)\n";

$tipDb = Tip::where('order_id', 1)->first();
if ($tipDb) {
    echo "Tip Database Record Created:\n";
    echo " - Payment Type: {$tipDb->payment_type} (Expected: wallet)\n";
    echo " - Payment Status: {$tipDb->payment_status} (Expected: captured)\n";
    echo " - Transaction ID: {$tipDb->transaction_id}\n";
} else {
    echo "Error: Tip database record not found!\n";
}
echo "\n";

// ----------------------------------------------------
// SCENARIO 2: Tipping Multiple Times for the Same Order
// ----------------------------------------------------
echo "=== Scenario 2: Attempting to Tip Again for the Same Order ===\n";
$request2 = TipOrderRequest::create('/api/customer/order/tip', 'POST', [
    'amount' => 3.00,
    'order_id' => 1,
    'user_id' => 5,
    'payment_type' => 'wallet',
]);
$response2 = $controller->tipOrder($request2);
$resData2 = json_decode($response2->getContent(), true);
echo "Response Status Code: " . $response2->getStatusCode() . "\n";
echo "Response Message: " . ($resData2['message'] ?? 'None') . " (Expected: Tip processed successfully.)\n";

// Verify balances again
$custWallet2 = Wallet::where('user_id', 2)->where('user_type', 'customer')->first();
$dmWallet2 = Wallet::where('user_id', 5)->where('user_type', 'delivery_man')->first();
echo "Customer Wallet Balance: {$custWallet2->balance} (Expected: 42.00)\n";
echo "Delivery Man Wallet Balance: {$dmWallet2->balance} (Expected: 8.00)\n\n";

// ----------------------------------------------------
// SCENARIO 3: Tipping with Wallet (Wallet Balance < Amount) but No Card
// ----------------------------------------------------
echo "=== Scenario 3: Tip Rider via Wallet (Wallet Balance < Amount, No Card) ===\n";
resetWallet(2, 2.00); // Customer has $2
resetWallet(4, 0.00); // Rider has $0

// Remove Stripe Customer details from user 2 to simulate no card setup
$customer->stripe_id = null;
$customer->save();

$request3 = TipRideRequest::create('/api/customer/ride/tip', 'POST', [
    'amount' => 10.00,
    'ride_id' => 5,
    'user_id' => 4, // rider
    'payment_type' => 'wallet',
]);

$response3 = $controller->tipRide($request3);
$resData3 = json_decode($response3->getContent(), true);

echo "Response Status Code: " . $response3->getStatusCode() . "\n";
echo "Response Message: " . ($resData3['message'] ?? 'None') . " (Expected: Insufficient wallet balance.)\n\n";

echo "Verification script finished.\n";
