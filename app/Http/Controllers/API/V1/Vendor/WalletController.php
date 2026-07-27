<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Vendor\WalletTransactionResource;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Balance;
use Stripe\BalanceTransaction;
use Stripe\Exception\ApiErrorException;
use Stripe\Payout;
use Stripe\Stripe;
use Stripe\Transfer;

class WalletController extends Controller
{
    use ApiResponse;

    

    /**
     * Helper to get or create/correct a vendor's wallet
     */
    private function getOrCreateWallet($user, $type = 'vendor')
    {
        $wallet = Wallet::where('user_id', $user->id)->where('user_type', $type)->first();
        if (!$wallet) {
            $existingWallet = Wallet::where('user_id', $user->id)->first();
            if ($existingWallet) {
                $existingWallet->user_type = $type;
                $existingWallet->save();
                $wallet = $existingWallet;
            } else {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'user_type' => $type,
                    'balance' => 0.00,
                    'pending_balance' => 0.00,
                ]);
            }
        }
        return $wallet;
    }

    /**
     * Get Wallet Info and Connected Account
     */
    public function getWallet()
    {
        try {
            $user = auth()->user();
            $wallet = $this->getOrCreateWallet($user, 'vendor');
            $balance = (float)$wallet->balance;

            return $this->success([
                'balance' => (float) number_format($balance, 2, '.', ''),
            ], 'Wallet info retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve wallet info: ' . $e->getMessage());
        }
    }

    /**
     * Connect Stripe Account (Initiates Onboarding)
     */
    public function connectAccount()
    {
        try {
            $user = auth()->user();

            if (!$user->stripe_account_id) {
                // Create Stripe Standard account (more compatible with simple setups)
                $account = Account::create([
                    'type' => 'express',
                    'country' => 'US',
                    'email' => $user->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);

                $user->stripe_account_id = $account->id;
                $user->save();
            } else {
                $account = Account::retrieve($user->stripe_account_id);
            }

            // Create Account Link for onboarding
            $accountLink = AccountLink::create([
                'account' => $account->id,
                'refresh_url' => route('connect_account.refresh', ['userID' => $user->id]),
                'return_url' => route('connect_account.success', ['userID' => $user->id]),
                'type' => 'account_onboarding',
            ]);

            return $this->success([
                'onboard_complete' => (bool) $user->onboard_complete,
                'stripe_account_id' => $account->id,
                'onboarding_url' => $accountLink->url,

            ], 'Stripe onboarding link generated successfully');
        } catch (ApiErrorException $e) {
            return $this->error( 'Failed to create Stripe account.',$e->getMessage(), 500);
        } catch (Exception $e) {
            return $this->error( 'An unexpected error occurred.',$e->getMessage(),500);
        }
    }

    public function refresh($userID)
    {
        // Check if the user exists
        $user = User::find($userID);

        $link = AccountLink::create([
            'account'     => $user->stripe_account_id,
            'refresh_url' => route('connect_account.refresh', ['userID' => $user->id]),
            'return_url'  => route('connect_account.success', ['userID' => $user->id]),
            'type'        => 'account_onboarding',
        ]);

        return redirect($link->url);
    }

    public function successConnect($userID)
    {
        // Check if the user exists
        $user = User::find($userID);
        //check user has stripe account or not if Setup Donation
        $stripeCheck = Helper::checkStripeOnboarding($user, 'Your Stripe account is not fully connected. Please complete the onboarding process to enable donations.');
        if ($stripeCheck !== true) {
            return $stripeCheck;
        }

        return $this->success([], 'Stripe account successfully setup.');
    }

    /**
     * Withdraw Request
     */
    public function withdraw(Request $request)
    {
        try {
            $user = auth()->user();

            if (empty($user->stripe_account_id) || empty($user->onboard_complete)) {
                return $this->error([], 'Please complete Stripe onboarding.', 400);
            }

            $account = Account::retrieve($user->stripe_account_id);
            if (!$account || !$account->charges_enabled) {
                return $this->error([], 'Stripe account is not fully activated.', 400);
            }

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return $this->error([], $validator->errors()->first(), 422);
            }

            $amount = (float) $request->amount;

            DB::beginTransaction();

            $wallet = $this->getOrCreateWallet($user, 'vendor');
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

            if (!$wallet || $wallet->balance < $amount) {
                DB::rollBack();
                return $this->error([], 'Insufficient balance.', 400);
            }

            // Transfer from Platform to Connected Account
            $transfer = Transfer::create([
                'amount'      => (int) ($amount * 100),
                'currency'    => 'usd',
                'destination' => $user->stripe_account_id,
                'metadata'    => ['user_id' => $user->id],
            ], [
                'idempotency_key' => 'withdraw_'.$user->id.'_'.time()
            ]);

            // Create Settlement record
            $settlement = new Settlement();
            $settlement->user_id = $user->id;
            $settlement->amount = $amount;
            $settlement->payment_method = 'Stripe Connect Transfer';
            $settlement->status = 'completed';
            $settlement->notes = "Transfer ID: {$transfer->id}";
            $settlement->save();

            // Deduct user wallet
            WalletService::debit(
                $user->id, 
                $amount, 
                'withdrawal', 
                $settlement, 
                "Withdrawal transfer to Stripe Connected Account (Transfer: {$transfer->id})"
            );

            DB::commit();

            return $this->success([], 'Withdrawal completed successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Withdraw Error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return $this->error([], 'Withdrawal failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transaction History
     */
    public function history(Request $request)
    {
        try {
            $user = auth()->user();
            $wallet = $this->getOrCreateWallet($user, 'vendor');

            $limit = $request->limit ?? 10;
            $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                ->with(['reference'])
                ->orderBy('created_at', 'desc')
                ->paginate($limit);

            return $this->success([
                'transactions' => WalletTransactionResource::collection($transactions),
                'pagination' => [
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                ]
            ], 'Transaction history retrieved successfully.');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve transaction history: ' . $e->getMessage());
        }
    }

    public function analytics()
    {
        try {
            $user = auth()->user();
            $wallet = Wallet::where('user_id', $user->id)->where('user_type', $user->user_type)->first();
            
            if (!$wallet) {
                return $this->success([
                    'total_income' => 0,
                    'total_withdraw' => 0,
                    'income_change' => 0,
                    'withdraw_change' => 0,
                    'graph_data' => []
                ]);
            }

            // Getting data for the last 7 days (as seen in the graph)
            $graphData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayName = now()->subDays($i)->format('D');

                $income = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'credit')
                    ->whereDate('created_at', $date)
                    ->sum('amount');

                $withdraw = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'debit')
                    ->where('category', 'withdrawal')
                    ->whereDate('created_at', $date)
                    ->sum('amount');

                $graphData[] = [
                    'day' => $dayName,
                    'income' => (float)$income,
                    'withdraw' => (float)$withdraw,
                ];
            }

            $totalIncome = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'credit')
                ->sum('amount');

            $totalWithdraw = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', 'debit')
                ->where('category', 'withdrawal')
                ->sum('amount');

            return $this->success([
                'total_income' => (float)$totalIncome,
                'total_withdraw' => (float)$totalWithdraw,
                'income_change' => 16, // Static placeholder for UI matching
                'withdraw_change' => 16, // Static placeholder for UI matching
                'graph_data' => $graphData,
            ], 'Wallet analytics retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve analytics: ' . $e->getMessage());
        }
    }
}
