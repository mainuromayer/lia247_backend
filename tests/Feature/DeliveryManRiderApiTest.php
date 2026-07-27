<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Ride;
use App\Models\Wallet;
use App\Models\TransportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class DeliveryManRiderApiTest extends TestCase
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
     * Test Delivery Man APIs
     */
    public function test_delivery_man_endpoints(): void
    {
        // 1. Create a delivery man user
        $deliveryMan = User::create([
            'name' => 'John Delivery',
            'email' => 'john@delivery.com',
            'password' => bcrypt('password'),
            'user_type' => 'delivery_man',
            'is_online' => true,
        ]);

        $headers = $this->getHeadersForUser($deliveryMan);

        // 2. Test Dashboard statistics
        $response = $this->withHeaders($headers)->getJson('/api/delivery_man/dashboard');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'today_earnings',
                    'all_time_earnings',
                    'upcoming_trip',
                    'is_online',
                ]
            ]);

        // 3. Test Wallet info
        // Let's create a local wallet record
        Wallet::create([
            'user_id' => $deliveryMan->id,
            'user_type' => 'delivery_man',
            'balance' => 125.50,
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/delivery_man/wallet');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available_balance' => 125.50,
                    'total_delivery' => 0,
                    'total_hour' => 0.0,
                    'total_earnings' => 0.0,
                ]
            ]);

        // 4. Test Current Request endpoint (should be empty when no orders exist)
        $response = $this->withHeaders($headers)->getJson('/api/delivery_man/orders/current-request');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);

        // 5. Test Orders History endpoint
        $response = $this->withHeaders($headers)->getJson('/api/delivery_man/orders/history?tab=active');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test Rider APIs
     */
    public function test_rider_endpoints(): void
    {
        // 1. Create a rider user
        $rider = User::create([
            'name' => 'Jane Rideshare Driver',
            'email' => 'jane@rider.com',
            'password' => bcrypt('password'),
            'user_type' => 'rider',
            'is_online' => true,
        ]);

        $headers = $this->getHeadersForUser($rider);

        // 2. Test Dashboard statistics
        $response = $this->withHeaders($headers)->getJson('/api/rider/dashboard');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'today_earnings',
                    'all_time_earnings',
                    'upcoming_trip',
                    'is_online',
                ]
            ]);

        // 3. Test Wallet info
        Wallet::create([
            'user_id' => $rider->id,
            'user_type' => 'rider',
            'balance' => 88.00,
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/rider/wallet');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available_balance' => 88.00,
                    'total_delivery' => 0,
                    'total_hour' => 0.0,
                    'total_earnings' => 0.0,
                ]
            ]);

        // 4. Test Current Request endpoint (should be empty when no rides exist)
        $response = $this->withHeaders($headers)->getJson('/api/rider/rides/current-request');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);

        // 5. Test Rides History endpoint
        $response = $this->withHeaders($headers)->getJson('/api/rider/rides/history?tab=active');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }
}
