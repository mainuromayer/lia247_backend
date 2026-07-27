<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class WalletService
{
    /**
     * Credit a user's wallet (add money)
     */
    public static function credit($userId, $amount, $category, $reference = null, $description = null)
    {
        return DB::transaction(function () use ($userId, $amount, $category, $reference, $description) {
            $user = User::find($userId);
            $userType = $user ? $user->user_type : 'vendor';

            $wallet = Wallet::where('user_id', $userId)->where('user_type', $userType)->first();
            if (!$wallet) {
                $existingWallet = Wallet::where('user_id', $userId)->first();
                if ($existingWallet) {
                    $existingWallet->user_type = $userType;
                    $existingWallet->save();
                    $wallet = $existingWallet;
                } else {
                    $wallet = new Wallet();
                    $wallet->user_id = $userId;
                    $wallet->user_type = $userType;
                    $wallet->save();
                }
            }
            $wallet->increment('balance', $amount);

            $transaction = new WalletTransaction();
            $transaction->wallet_id = $wallet->id;
            $transaction->amount = $amount;
            $transaction->type = 'credit';
            $transaction->category = $category;
            $transaction->reference_type = $reference ? get_class($reference) : null;
            $transaction->reference_id = $reference ? $reference->id : null;
            $transaction->description = $description;
            $transaction->status = 'completed';
            $transaction->save();

            // Notify User
            NotificationService::send(
                $userId,
                "Wallet Credited",
                "Your wallet has been credited with {$amount} for " . str_replace('_', ' ', $category) . ".",
                'payment',
                $userType,
                ['amount' => $amount, 'category' => $category]
            );

            return $transaction;
        });
    }

    /**
     * Debit a user's wallet (withdraw/spend)
     */
    public static function debit($userId, $amount, $category, $reference = null, $description = null)
    {
        return DB::transaction(function () use ($userId, $amount, $category, $reference, $description) {
            $user = User::find($userId);
            $userType = $user ? $user->user_type : 'vendor';
            $wallet = Wallet::where('user_id', $userId)->where('user_type', $userType)->first();
            if (!$wallet) {
                $existingWallet = Wallet::where('user_id', $userId)->first();
                if ($existingWallet) {
                    $existingWallet->user_type = $userType;
                    $existingWallet->save();
                    $wallet = $existingWallet;
                }
            }
            
            if (!$wallet || $wallet->balance < $amount) {
                throw new Exception("Insufficient balance in wallet.");
            }

            $wallet->decrement('balance', $amount);

            $transaction = new WalletTransaction();
            $transaction->wallet_id = $wallet->id;
            $transaction->amount = $amount;
            $transaction->type = 'debit';
            $transaction->category = $category;
            $transaction->reference_type = $reference ? get_class($reference) : null;
            $transaction->reference_id = $reference ? $reference->id : null;
            $transaction->description = $description;
            $transaction->status = 'completed';
            $transaction->save();

            // Notify User
            NotificationService::send(
                $userId,
                "Wallet Debited",
                "Your wallet has been debited with {$amount} for " . str_replace('_', ' ', $category) . ".",
                'payment',
                $userType,
                ['amount' => $amount, 'category' => $category]
            );

            return $transaction;
        });
    }

    /**
     * Move balance from pending to main balance (Settlement)
     */
    public static function settle($userId, $amount)
    {
        return DB::transaction(function () use ($userId, $amount) {
            $user = User::find($userId);
            $userType = $user ? $user->user_type : 'vendor';
            $wallet = Wallet::where('user_id', $userId)->where('user_type', $userType)->first();
            if (!$wallet) {
                $existingWallet = Wallet::where('user_id', $userId)->first();
                if ($existingWallet) {
                    $existingWallet->user_type = $userType;
                    $existingWallet->save();
                    $wallet = $existingWallet;
                }
            }
            
            if (!$wallet || $wallet->pending_balance < $amount) {
                throw new Exception("Insufficient pending balance.");
            }

            $wallet->decrement('pending_balance', $amount);
            $wallet->increment('balance', $amount);

            return true;
        });
    }

    /**
     * Add to pending balance (Before settlement)
     */
    public static function addToPending($userId, $amount)
    {
        $user = User::find($userId);
        $userType = $user ? $user->user_type : 'vendor';

        $wallet = Wallet::where('user_id', $userId)->where('user_type', $userType)->first();
        if (!$wallet) {
            $existingWallet = Wallet::where('user_id', $userId)->first();
            if ($existingWallet) {
                $existingWallet->user_type = $userType;
                $existingWallet->save();
                $wallet = $existingWallet;
            } else {
                $wallet = new Wallet();
                $wallet->user_id = $userId;
                $wallet->user_type = $userType;
                $wallet->save();
            }
        }
        $wallet->increment('pending_balance', $amount);
        return $wallet;
    }
}
