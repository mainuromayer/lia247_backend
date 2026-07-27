<?php

namespace App\Http\Controllers\API\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Vendor\WalletTransactionResource;
use App\Models\Order;
use App\Models\Ride;
use App\Models\Settlement;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;

class WalletController extends Controller
{
    use ApiResponse;

    /**
     * Get Wallet Info and Connected Account
     */
    public function getWallet()
    {
        try {
            $user = auth()->user();
            $wallet = Wallet::where('user_id', $user->id)->first();
            $bankAccount = UserBankAccount::where('user_id', $user->id)->where('is_default', true)->first();

            return $this->success([
                'balance' => $wallet ? (float)$wallet->balance : 0,
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
            Stripe::setApiKey(config('services.stripe.secret'));

            if (!$user->stripe_account_id) {
                // Create Stripe Standard account (more compatible with simple setups)
                $account = Account::create([
                    'type' => 'standard',
                    'email' => $user->email,
                ]);

                $user->stripe_account_id = $account->id;
                $user->save();
            }

            // Create Account Link for onboarding
            $accountLink = AccountLink::create([
                'account' => $user->stripe_account_id,
                'refresh_url' => route('stripe.refresh'),
                'return_url' => route('stripe.return'),
                'type' => 'account_onboarding',
            ]);

            return $this->success([
                'onboarding_url' => $accountLink->url
            ], 'Stripe onboarding link generated successfully');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Provide a very specific message for the Connect setup error
            if (str_contains($e->getMessage(), 'signed up for Connect')) {
                return $this->error([], 'Stripe Connect is not enabled on your Stripe account. Please go to https://dashboard.stripe.com/connect and click "Get Started" to enable it.', 400);
            }
            return $this->error([], 'Stripe Error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            return $this->error([], 'Failed to connect account: ' . $e->getMessage());
        }
    }

    /**
     * Withdraw Request (Only Amount)
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $wallet = Wallet::where('user_id', $user->id)->first();
            $bankAccount = UserBankAccount::where('user_id', $user->id)->where('is_default', true)->first();

            // Check if either Stripe or Manual Bank is available
            if (!$user->stripe_account_id && !$bankAccount) {
                return $this->error([], 'Please connect your Stripe account or add a bank account first', 400);
            }

            if (!$wallet || $wallet->balance < $request->amount) {
                return $this->error([], 'Insufficient balance', 400);
            }

            // Determine payment method
            $paymentMethod = $user->stripe_account_id ? 'Stripe Connect' : 'Manual Bank Transfer';
            $notes = $user->stripe_account_id 
                ? "Stripe Account ID: {$user->stripe_account_id}" 
                : "Bank: {$bankAccount->bank_name}, Acc: {$bankAccount->account_number}";

            // Create Settlement record
            $settlement = new Settlement();
            $settlement->user_id = $user->id;
            $settlement->amount = $request->amount;
            $settlement->payment_method = $paymentMethod;
            $settlement->status = 'pending';
            $settlement->notes = $notes;
            $settlement->save();

            // Debit user wallet
            WalletService::debit(
                $user->id, 
                $request->amount, 
                'withdrawal', 
                $settlement, 
                'Withdrawal request via Stripe Connect'
            );

            DB::commit();
            return $this->success([], 'Withdrawal request submitted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Withdrawal failed: ' . $e->getMessage());
        }
    }

    /**
     * Transaction History
     */
    public function history(Request $request)
    {
        try {
            $user = auth()->user();
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return $this->success([], 'No wallet found');
            }

            $query = WalletTransaction::with(['reference' => function ($morph) {
                $morph->morphWith([
                    Order::class => ['user', 'items'],
                    Ride::class => ['user'],
                ]);
            }])
            ->where('wallet_id', $wallet->id);

            // Basic filtering
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $transactions = $query->latest()->paginate($request->limit ?? 10);

            return $this->success([
                'transactions' => WalletTransactionResource::collection($transactions),
                'pagination' => [
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage() ?? 1, // Fix if pagination objects differ
                    'current_page' => $transactions->currentPage(),
                ]
            ], 'Transaction history retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve transaction history: ' . $e->getMessage());
        }
    }

    /**
     * Handle Stripe Connect Refresh (Onboarding expired or failed)
     */
    public function handleStripeRefresh()
    {
        $frontendUrl = config('app.frontend_url', url('/'));
        return redirect()->away($frontendUrl . '/wallet?stripe=refresh');
    }

    /**
     * Handle Stripe Connect Return (Onboarding completed)
     */
    public function handleStripeReturn()
    {
        $frontendUrl = config('app.frontend_url', url('/'));
        // In a real app, you might want to verify the account status with Stripe here
        return redirect()->away($frontendUrl . '/wallet?stripe=success');
    }
}
