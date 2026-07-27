<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CustomerWalletApiTest extends TestCase
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
     * Test Customer Wallet APIs
     */
    public function test_customer_wallet_endpoints(): void
    {
        // 1. Create a customer user
        $customer = User::create([
            'name' => 'Jane Customer',
            'email' => 'jane@customer.com',
            'password' => bcrypt('password'),
            'user_type' => 'customer',
        ]);

        $headers = $this->getHeadersForUser($customer);

        // 2. Test Wallet info when wallet doesn't exist
        $response = $this->withHeaders($headers)->getJson('/api/customer/wallet');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available_balance' => 0.00,
                ]
            ]);

        // 3. Create wallet and test Wallet info
        $wallet = Wallet::create([
            'user_id' => $customer->id,
            'user_type' => 'customer',
            'balance' => 150.75,
            'pending_balance' => 20.00,
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/customer/wallet');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'available_balance' => 150.75,
                ]
            ]);

        // 4. Test wallet history when there are no transactions
        $response = $this->withHeaders($headers)->getJson('/api/customer/transaction-history');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'transactions' => [],
                ]
            ]);

        // 5. Create transactions and test history
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 50.00,
            'type' => 'credit',
            'category' => 'deposit',
            'description' => 'Loaded money',
            'status' => 'completed',
        ]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 15.50,
            'type' => 'debit',
            'category' => 'payment',
            'description' => 'Ride payment',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/customer/transaction-history');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'transactions' => [
                        [
                            'amount' => '-15.50',
                            'type' => 'debit',
                            'title' => 'Ride payment',
                        ],
                        [
                            'amount' => '50.00',
                            'type' => 'credit',
                            'title' => 'Loaded money',
                        ]
                    ]
                ]
            ]);

        // 6. Test Add Money validation
        $response = $this->withHeaders($headers)->postJson('/api/customer/wallet/add-money', [
            'amount' => 0
        ]);
        $response->assertStatus(422);
    }
}
