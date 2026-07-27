<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\Ride;
use App\Models\TransportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CustomerPaymentAndCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function getHeadersForUser(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Test Validation rules for checkout and ride request.
     */
    public function test_payment_validation_rules(): void
    {
        $customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@customer.com',
            'password' => bcrypt('password'),
            'user_type' => 'customer',
        ]);

        $headers = $this->getHeadersForUser($customer);

        // 1. Checkout validation - missing payment_type
        $response = $this->withHeaders($headers)->postJson('/api/customer/checkout', [
            'order_type' => 'pickup',
            'receiver_name' => 'Jane',
            'receiver_phone' => '12345678',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_type']);

        // 2. Checkout validation - Stripe payment missing payment_method_id
        $response = $this->withHeaders($headers)->postJson('/api/customer/checkout', [
            'order_type' => 'pickup',
            'receiver_name' => 'Jane',
            'receiver_phone' => '12345678',
            'payment_type' => 'stripe',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method_id']);

        // 3. Checkout validation - Wallet payment with payment_method_id (Invalid combination)
        $response = $this->withHeaders($headers)->postJson('/api/customer/checkout', [
            'order_type' => 'pickup',
            'receiver_name' => 'Jane',
            'receiver_phone' => '12345678',
            'payment_type' => 'wallet',
            'payment_method_id' => 'pm_123',
        ]);
        $response->assertStatus(422);

        // 4. Ride request validation - missing payment_type
        $response = $this->withHeaders($headers)->postJson('/api/customer/rides', [
            'pickup_address' => 'Pickup',
            'pickup_lat' => 40.7128,
            'pickup_lng' => -74.0060,
            'dropoff_address' => 'Dropoff',
            'dropoff_lat' => 40.7128,
            'dropoff_lng' => -74.0060,
            'distance' => 5.0,
            'estimated_fare' => 15.00,
            'transport_type_id' => 1,
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_type']);
    }

    /**
     * Test Wallet Insufficient Balance
     */
    public function test_insufficient_wallet_balance(): void
    {
        $customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@customer.com',
            'password' => bcrypt('password'),
            'user_type' => 'customer',
        ]);

        $headers = $this->getHeadersForUser($customer);

        // Create empty wallet (0 balance)
        Wallet::create([
            'user_id' => $customer->id,
            'user_type' => 'customer',
            'balance' => 0.00,
        ]);

        // Ride request with Wallet payment -> Should fail
        $transportType = TransportType::create([
            'name' => 'Car',
            'base_fare' => 5.00,
            'rate_per_km' => 2.00,
            'type' => 'car',
        ]);

        $response = $this->withHeaders($headers)->postJson('/api/customer/rides', [
            'pickup_address' => 'Pickup',
            'pickup_lat' => 40.7128,
            'pickup_lng' => -74.0060,
            'dropoff_address' => 'Dropoff',
            'dropoff_lat' => 40.7128,
            'dropoff_lng' => -74.0060,
            'distance' => 5.0,
            'estimated_fare' => 15.00,
            'transport_type_id' => $transportType->id,
            'payment_type' => 'wallet',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Insufficient wallet balance.',
            ]);
    }

    /**
     * Test Ride Cancellation and Refund
     */
    public function test_ride_cancellation_and_refund(): void
    {
        $customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@customer.com',
            'password' => bcrypt('password'),
            'user_type' => 'customer',
        ]);

        $headers = $this->getHeadersForUser($customer);

        $wallet = Wallet::create([
            'user_id' => $customer->id,
            'user_type' => 'customer',
            'balance' => 100.00,
        ]);

        $transportType = TransportType::create([
            'name' => 'Car',
            'base_fare' => 5.00,
            'rate_per_km' => 2.00,
            'type' => 'car',
        ]);

        // Create a Ride Request
        $ride = Ride::create([
            'ride_no' => '#RD20002',
            'user_id' => $customer->id,
            'pickup_address' => 'Pickup',
            'pickup_lat' => 40.7128,
            'pickup_lng' => -74.0060,
            'dropoff_address' => 'Dropoff',
            'dropoff_lat' => 40.7128,
            'dropoff_lng' => -74.0060,
            'distance' => 5.0,
            'estimated_fare' => 15.00,
            'total_amount' => 15.00,
            'transport_type_id' => $transportType->id,
            'payment_type' => 'wallet',
            'payment_status' => 'captured',
            'amount' => 15.00,
            'status' => 'pending',
        ]);

        // 1. Cancel a started ride -> should fail
        $ride->status = 'started';
        $ride->save();

        $response = $this->withHeaders($headers)->postJson('/api/customer/rides/cancel', [
            'id' => $ride->id,
            'cancel_reason' => 'Too slow',
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Ride has already started and cannot be cancelled.',
            ]);

        // 2. Cancel a pending ride -> should succeed and refund
        $ride->status = 'pending';
        $ride->save();

        $response = $this->withHeaders($headers)->postJson('/api/customer/rides/cancel', [
            'id' => $ride->id,
            'cancel_reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(115.00, (float) $wallet->fresh()->balance);

        $freshRide = $ride->fresh();
        $this->assertEquals('canceled', $freshRide->status);
        $this->assertEquals('refunded', $freshRide->payment_status);
        $this->assertEquals(15.00, (float) $freshRide->refunded_amount);
        $this->assertNotNull($freshRide->refunded_at);
        $this->assertNotNull($freshRide->refund_transaction_id);
    }

    /**
     * Test Order Cancellation and Refund
     */
    public function test_order_cancellation_and_refund(): void
    {
        $customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@customer.com',
            'password' => bcrypt('password'),
            'user_type' => 'customer',
        ]);

        $headers = $this->getHeadersForUser($customer);

        $wallet = Wallet::create([
            'user_id' => $customer->id,
            'user_type' => 'customer',
            'balance' => 100.00,
        ]);

        $order = Order::create([
            'order_no' => '#ORD90001',
            'user_id' => $customer->id,
            'vendor_id' => 999, // Dummy
            'subtotal' => 30.00,
            'total_amount' => 30.00,
            'amount' => 30.00,
            'payment_type' => 'wallet',
            'payment_status' => 'captured',
            'status' => 'accepted',
        ]);

        // 1. Cancel ready order -> should fail
        $order->status = 'ready';
        $order->save();

        $response = $this->withHeaders($headers)->postJson('/api/customer/order/cancel', [
            'order_id' => $order->id,
            'cancel_reason' => 'No longer needed',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Order is already prepared and cannot be cancelled.',
            ]);

        // 2. Cancel accepted order -> should succeed and refund
        $order->status = 'accepted';
        $order->save();

        $response = $this->withHeaders($headers)->postJson('/api/customer/order/cancel', [
            'order_id' => $order->id,
            'cancel_reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(130.00, (float) $wallet->fresh()->balance);

        $freshOrder = $order->fresh();
        $this->assertEquals('canceled', $freshOrder->status);
        $this->assertEquals('refunded', $freshOrder->payment_status);
        $this->assertEquals(30.00, (float) $freshOrder->refunded_amount);
        $this->assertNotNull($freshOrder->refunded_at);
        $this->assertNotNull($freshOrder->refund_transaction_id);
    }
}
