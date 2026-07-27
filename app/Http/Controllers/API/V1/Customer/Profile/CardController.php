<?php

namespace App\Http\Controllers\API\V1\Customer\Profile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class CardController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // Step 1 of the card flow: generate a SetupIntent so the frontend
    // can collect card details securely using Stripe.js / Elements.
    public function generateSetupIntent()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $user->createOrGetStripeCustomer();

            $intent = $user->createSetupIntent();

            return $this->success(['client_secret' => $intent->client_secret], 'Setup intent generated successfully');
        } catch (Exception $e) {
            Log::error('Setup Intent Error: ' . $e->getMessage());
            return $this->error([], 'Failed to generate setup intent: ' . $e->getMessage(), 500);
        }
    }

    public function listCards()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $paymentMethods = $user->paymentMethods();
            $defaultMethod = $user->defaultPaymentMethod();

            $cards = array_map(fn($method) => [
                'id' => $method->id,
                'brand' => $method->card->brand ?? null,
                'last4' => $method->card->last4 ?? null,
                'exp_month' => $method->card->exp_month ?? null,
                'exp_year' => $method->card->exp_year ?? null,
                'is_default' => $defaultMethod?->id === $method->id,
            ], $paymentMethods);

            return $this->success($cards, 'Payment methods retrieved successfully');
        } catch (Exception $e) {
            Log::error('List Cards Error: ' . $e->getMessage());
            return $this->error([], 'Failed to list cards', 500);
        }
    }

    public function addCard(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string'
        ]);

        try {
            $paymentMethod = PaymentMethod::retrieve($request->payment_method_id);

            // Check if the PaymentMethod is attached or reusable
            if ($paymentMethod->attached) {
                return response()->json([
                    'status' => false,
                    'message' => 'This payment method is already attached to a customer or is not reusable'
                ], 400);
            }

           
            // Create a new customer if it doesn't exist
            $customer = $this->createCustomerIfNotExist();
        
            // Attach the payment method to the customer
            $paymentMethod->attach([
                'customer' => $customer->id,
            ]);
            Customer::update($customer->id, [
                "invoice_settings" => [
                    'default_payment_method' => $request->payment_method_id,
                ]
            ]);

            return $this->success([], 'Card added successfully');
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    private function createCustomerIfNotExist()
    {
        //get current user
        $user = auth()->user();
        //customer data
        $customerData = [
            'name' => $user->name,
        ];
        // Check if the user has an email
        if (!empty($user->email)) {
            $customerData['email'] = $user->email;
        }
        // Check if the user has a phone number
        if (!empty($user->phone)) {
            $customerData['phone'] = $user->phone;
        }
        // Check if the user has a Stripe customer ID
        if (empty($user->stripe_id)) {
            // If not, create a new Stripe customer
            $customer = Customer::create($customerData);
            $user->stripe_id = $customer->id;
            $user->save();
        } else {
            // Retrieve the existing customer on Stripe
            $customer = Customer::retrieve($user->stripe_id);
            if (!$customer) {
                $customer = Customer::create($customerData);
                $user->stripe_id = $customer->id;
                $user->save();
            }
        }
        return $customer;
    }

    public function setDefaultCard(Request $request)
    {
        $request->validate(['payment_method_id' => 'required|string']);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Verify the card belongs to this user before setting as default
            if (!$user->findPaymentMethod($request->payment_method_id)) {
                return $this->error([], 'This card does not belong to your account.', 400);
            }

            $user->updateDefaultPaymentMethod($request->payment_method_id);

            return $this->success([], 'Default card updated successfully');
        } catch (\RuntimeException $e) {
            Log::error('Set Default Card Error: ' . $e->getMessage());
            return $this->error([], $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('Set Default Card Unexpected Error: ' . $e->getMessage());
            return $this->error([], 'Failed to set default card. Please try again.', 500);
        }
    }

    // The default card cannot be deleted. Set another card as default first.
    public function deleteCard($paymentMethodId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $paymentMethod = $user->findPaymentMethod($paymentMethodId);

            if (!$paymentMethod) {
                return $this->error([], 'Card not found or does not belong to your account.', 404);
            }

            if ($user->defaultPaymentMethod()?->id === $paymentMethodId) {
                return $this->error([], 'Cannot delete your default card. Set another card as default first.', 400);
            }

            $paymentMethod->delete();

            return $this->success([], 'Card deleted successfully');
        } catch (Exception $e) {
            Log::error('Delete Card Error: ' . $e->getMessage());
            return $this->error([], 'Failed to delete card. Please try again.', 500);
        }
    }
}