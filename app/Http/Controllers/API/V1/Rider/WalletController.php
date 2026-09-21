<?php

namespace App\Http\Controllers\API\V1\Rider;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Ride;
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
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Transfer;

class WalletController extends Controller
{
    use ApiResponse;

    /**
     * Helper to get or create/correct a rider's wallet
     */
    private function getOrCreateWallet($user, $type = 'rider')
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
     * Get Wallet Info and Connected Account from Stripe
     */
    public function getWallet()
    {
        try {
            $user = auth()->user();
            $wallet = $this->getOrCreateWallet($user, 'rider');
            $balance = (float)$wallet->balance;

            // This month stats
            $startOfMonth = Carbon::now()->startOfMonth();

            $thisMonthDeliveries = Ride::where('rider_id', $user->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startOfMonth)
                ->count();

            $thisMonthEarnings = Ride::where('rider_id', $user->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startOfMonth)
                ->get()
                ->sum(function ($ride) {
                    return $ride->actual_fare;
                });

            $thisMonthHours = round($thisMonthDeliveries * 0.33, 1);

            return $this->success([
                'available_balance' => (float) number_format($balance, 2, '.', ''),
                'this_month_delivery' => (int) $thisMonthDeliveries,
                'this_month_hour' => (float) $thisMonthHours,
                'this_month_earnings' => (float) number_format($thisMonthEarnings, 2, '.', ''),
            ], 'Wallet info retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve wallet info: ' . $e->getMessage());
        }
    }

    /**
     * Transaction History
     */
    public function history(Request $request)
    {
        try {
            $user = auth()->user();
            $wallet = $this->getOrCreateWallet($user, 'rider');

            $limit = $request->limit ?? 10;
            $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                ->orderBy('created_at', 'desc')
                ->paginate($limit);

            $formattedTransactions = collect($transactions->items())->map(function ($txn) {
                $rideNo = null;
                $rideTotal = 0.00;
                $bankName = 'Bank Transfer';

                if ($txn->reference_type === 'App\Models\Ride') {
                    $ride = Ride::find($txn->reference_id);
                    if ($ride) {
                        $rideNo = $ride->ride_no;
                        $rideTotal = (float)$ride->total_amount;
                    }
                } elseif ($txn->reference_type === 'App\Models\Settlement') {
                    $settlement = Settlement::find($txn->reference_id);
                    if ($settlement) {
                        $bankName = $settlement->payment_method ?? 'Bank Transfer';
                    }
                }

                $isCredit = $txn->type === 'credit';
                
                return [
                    'id' => $txn->id,
                    'title' => $rideNo ? 'Ride ' . $rideNo : 'Send to Bank',
                    'subtitle' => $txn->created_at->format('d M Y, h.i a'),
                    'ride_amount' => $rideNo ? (float)number_format($rideTotal, 2, '.', '') : null,
                    'amount' => ($isCredit ? '' : '-') . number_format($txn->amount, 2, '.', ''),
                    'type' => $txn->type,
                    'category' => $txn->category,
                    'bank_name' => !$rideNo ? $bankName : null,
                    'tag_type' => $rideNo ? 'Earning' : 'Withdrawal',
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
     * Connect Stripe Account (Initiates Onboarding)
     */
    public function connectAccount()
    {
        try {
            $user = auth()->user();
            Stripe::setApiKey(config('services.stripe.secret'));

            if (!$user->stripe_account_id) {
                // Create Stripe Express account
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
                'refresh_url' => route('connect_account.rider.refresh', ['userID' => $user->id]),
                'return_url' => route('connect_account.rider.success', ['userID' => $user->id]),
                'type' => 'account_onboarding',
            ]);

            return $this->success([
                'onboard_complete' => (bool) $user->onboard_complete,
                'stripe_account_id' => $account->id,
                'onboarding_url' => $accountLink->url,
            ], 'Stripe onboarding link generated successfully');
        } catch (ApiErrorException $e) {
            if (str_contains($e->getMessage(), 'signed up for Connect')) {
                return $this->error([], 'Stripe Connect is not enabled on your Stripe account. Please go to https://dashboard.stripe.com/connect to complete the Connect setup.', 400);
            }
            return $this->error([], 'Failed to create Stripe account: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            return $this->error([], 'An unexpected error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function refresh($userID)
    {
        // Check if the user exists
        $user = User::find($userID);

        $link = AccountLink::create([
            'account'     => $user->stripe_account_id,
            'refresh_url' => route('connect_account.rider.refresh', ['userID' => $user->id]),
            'return_url'  => route('connect_account.rider.success', ['userID' => $user->id]),
            'type'        => 'account_onboarding',
        ]);

        return redirect($link->url);
    }

    public function successConnect(Request $request, $userID)
    {
        // Check if the user exists
        $user = User::find($userID);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found.'], 404);
        }

        // Check onboarding status via helper
        $stripeCheck = Helper::checkStripeOnboarding($user, 'Your Stripe account is not fully connected. Please complete the onboarding process.');
        if ($stripeCheck !== true) {
            if ($request->wantsJson()) {
                return $stripeCheck;
            }
            return view('stripe.connect-failed', [
                'user' => $user,
                'message' => 'Your Stripe account is not fully connected. Please complete the onboarding process.',
                'refreshUrl' => route('connect_account.rider.refresh', ['userID' => $user->id]),
            ]);
        }

        if ($request->wantsJson()) {
            return $this->success([], 'Stripe account successfully setup.');
        }

        return view('stripe.connect-success', [
            'user' => $user,
            'message' => 'Stripe account connected successfully! You can now close this window and return to the app.',
        ]);
    }

    /**
     * Withdraw Request directly via Stripe Payout
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

            $wallet = $this->getOrCreateWallet($user, 'rider');
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
}
