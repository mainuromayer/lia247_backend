<?php

namespace App\Http\Controllers\API\V1\Customer\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use App\Services\WalletService;

class WalletController extends Controller
{
    use ApiResponse;

    /**
     * Helper to get or create/correct a customer's wallet
     */
    private function getOrCreateWallet($user)
    {
        $wallet = Wallet::where('user_id', $user->id)->where('user_type', 'customer')->first();
        if (!$wallet) {
            $existingWallet = Wallet::where('user_id', $user->id)->first();
            if ($existingWallet) {
                $existingWallet->user_type = 'customer';
                $existingWallet->save();
                $wallet = $existingWallet;
            } else {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'user_type' => 'customer',
                    'balance' => 0.00,
                    'pending_balance' => 0.00,
                ]);
            }
        }
        return $wallet;
    }

    /**
     * Get Customer Wallet Info
     */
    public function getWallet()
    {
        try {
            $user = auth()->user();
            $wallet = $this->getOrCreateWallet($user);

            // Sync with Stripe PaymentIntents in case webhook didn't run (e.g. local development)
            $stripeCustomerId = $user->stripe_id;
            if ($stripeCustomerId) {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $intents = PaymentIntent::all([
                        'customer' => $stripeCustomerId,
                        'limit' => 15,
                    ]);
                    $synced = false;
                    foreach ($intents->data as $intent) {
                        if (
                            isset($intent->metadata->type) && 
                            $intent->metadata->type === 'wallet_topup' &&
                            (int)$intent->metadata->user_id === $user->id &&
                            $intent->status === 'succeeded'
                        ) {
                            $amount = $intent->amount / 100;
                            
                            $txnExists = WalletTransaction::where('wallet_id', $wallet->id)
                                ->where('description', 'like', "%{$intent->id}%")
                                ->exists();

                            if (!$txnExists) {
                                WalletService::credit($user->id, $amount, 'topup', null, "Wallet top-up via Stripe (Intent: {$intent->id})");
                                $synced = true;
                            }
                        }
                    }
                    if ($synced) {
                        $wallet->refresh();
                    }
                } catch (Exception $stripeEx) {
                    Log::error('Stripe sync failed in getWallet: ' . $stripeEx->getMessage());
                }
            }

            $balance = (float)$wallet->balance;

            return $this->success([
                'available_balance' => (float) number_format($balance, 2, '.', ''),
            ], 'Wallet info retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve wallet info: ' . $e->getMessage());
        }
    }

    /**
     * Get Customer Wallet Transaction History  
     */
    public function history(Request $request)
    {
        try {
            $user = auth()->user();
            $wallet = $this->getOrCreateWallet($user);

            $limit = $request->limit ?? 10;
            $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                ->orderBy('created_at', 'desc')
                ->paginate($limit);

            $formattedTransactions = collect($transactions->items())->map(function ($txn) {
                $isCredit = $txn->type === 'credit';
                
                return [
                    'id' => $txn->id,
                    'title' => $txn->description ?? ($isCredit ? 'Credited to Wallet' : 'Debited from Wallet'),
                    'subtitle' => $txn->created_at->format('d M Y, h.i a'),
                    'amount' => ($isCredit ? '' : '-') . number_format($txn->amount, 2, '.', ''),
                    'type' => $txn->type,
                    'category' => $txn->category,
                    'created_at' => $txn->created_at->toISOString(),
                ];
            });

            return $this->success([
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                ]
            ], 'Wallet transaction history retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve transaction history: ' . $e->getMessage());
        }
    }

    /**
     * Create Stripe PaymentIntent for Wallet Top-up (Add Money)
     */
    public function addMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $amount = (float) $request->amount;

            Stripe::setApiKey(config('services.stripe.secret'));

            $stripeCustomerId = $user->stripe_id;
            if (!$stripeCustomerId) {
                $customerData = ['name' => $user->name];
                if (!empty($user->email)) $customerData['email'] = $user->email;
                if (!empty($user->phone)) $customerData['phone'] = $user->phone;
                
                $customer = \Stripe\Customer::create($customerData);
                $user->stripe_id = $customer->id;
                $user->save();
                $stripeCustomerId = $customer->id;
            }

            $options = [
                'amount' => (int) round($amount * 100),
                'currency' => 'usd',
                'customer' => $stripeCustomerId,
                'metadata' => [
                    'type' => 'wallet_topup',
                    'user_id' => $user->id,
                ],
                'description' => 'Wallet top-up via Stripe',
            ];

            if ($request->filled('payment_method_id')) {
                $options['payment_method'] = $request->payment_method_id;
                $options['confirm'] = true;
                $options['off_session'] = true; // Off-session charge using saved card
            }

            $intent = PaymentIntent::create($options);

            if ($intent->status === 'succeeded') {
                $wallet = $this->getOrCreateWallet($user);
                $txnExists = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('description', 'like', "%{$intent->id}%")
                    ->exists();

                if (!$txnExists) {
                    WalletService::credit($user->id, $amount, 'topup', null, "Wallet top-up via Stripe (Intent: {$intent->id})");
                }
            }

            return $this->success([
                'payment_intent_id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'status' => $intent->status,
            ], 'Payment intent created successfully for wallet top-up');
        } catch (Exception $e) {
            Log::error('Wallet top-up failed: ' . $e->getMessage());
            return $this->error([], 'Failed to initiate wallet top-up: ' . $e->getMessage(), 500);
        }
    }
}
