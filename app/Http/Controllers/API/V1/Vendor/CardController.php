<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{
    use ApiResponse;

    public function generateSetupIntent()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->createOrGetStripeCustomer();
            $intent = $user->createSetupIntent();

            return $this->success([
                'client_secret' => $intent->client_secret,
            ], 'Setup intent generated successfully');
        } catch (Exception $exception) {
            Log::error('Vendor Setup Intent Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to generate setup intent: ' . $exception->getMessage(), 500);
        }
    }

    public function listCards()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $paymentMethods = $user->paymentMethods();

            $formattedCards = [];
            foreach ($paymentMethods as $method) {
                $formattedCards[] = [
                    'id' => $method->id,
                    'brand' => $method->card->brand,
                    'last4' => $method->card->last4,
                    'exp_month' => $method->card->exp_month,
                    'exp_year' => $method->card->exp_year,
                    'is_default' => $user->defaultPaymentMethod()?->id === $method->id,
                ];
            }

            return $this->success($formattedCards, 'Payment methods retrieved successfully');
        } catch (Exception $exception) {
            Log::error('Vendor List Cards Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to list cards', 500);
        }
    }

    public function addCard(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->createOrGetStripeCustomer();
            $user->addPaymentMethod($request->payment_method_id);

            if (!$user->hasDefaultPaymentMethod()) {
                $user->updateDefaultPaymentMethod($request->payment_method_id);
            }

            return $this->success([], 'Card added successfully');
        } catch (Exception $exception) {
            Log::error('Vendor Add Card Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to add card: ' . $exception->getMessage(), 500);
        }
    }

    public function setDefaultCard(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->updateDefaultPaymentMethod($request->payment_method_id);

            return $this->success([], 'Default payment method updated successfully');
        } catch (Exception $exception) {
            Log::error('Vendor Set Default Card Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to set default card', 500);
        }
    }

    public function deleteCard($paymentMethodId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $paymentMethod = $user->findPaymentMethod($paymentMethodId);

            if ($paymentMethod) {
                $paymentMethod->delete();
            }

            return $this->success([], 'Card deleted successfully');
        } catch (Exception $exception) {
            Log::error('Vendor Delete Card Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to delete card', 500);
        }
    }
}
