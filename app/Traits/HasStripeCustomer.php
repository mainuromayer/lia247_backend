<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Stripe\Customer as StripeCustomer;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Stripe;

/**
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $email
 * @property string|null $name
 */
trait HasStripeCustomer
{
    public static function bootHasStripeCustomer()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createOrGetStripeCustomer(): StripeCustomer
    {

        if ($this->stripe_id) {
            try {
                $customer = StripeCustomer::retrieve($this->stripe_id);

                if (!isset($customer->deleted) || !$customer->deleted) {
                    return $customer;
                }

                Log::warning('Stripe customer was deleted, recreating.', ['user_id' => $this->id]);
            } catch (Exception $e) {
                Log::warning('Could not retrieve Stripe customer, recreating.', [
                    'user_id' => $this->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $customer = StripeCustomer::create([
            'email' => $this->email ?? null,
            'name'  => $this->name  ?? null,
        ]);

        $this->update(['stripe_id' => $customer->id]);

        return $customer;
    }

    // Creates a SetupIntent so the frontend can securely collect card details via Stripe.js
    public function createSetupIntent(array $options = []): SetupIntent
    {

        $customer = $this->createOrGetStripeCustomer();

        return SetupIntent::create(array_merge([
            'customer' => $customer->id,
            'usage'    => 'off_session',
        ], $options));
    }

    public function paymentMethods(): array
    {
        if (!$this->stripe_id) {
            return [];
        }

        try {
            $result = StripePaymentMethod::all([
                'customer' => $this->stripe_id,
                'type'     => 'card',
            ]);

            return $result->data ?? [];
        } catch (Exception $e) {
            Log::error('Failed to fetch payment methods.', [
                'user_id' => $this->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function defaultPaymentMethod(): ?StripePaymentMethod
    {
        if (!$this->stripe_id) {
            return null;
        }

        try {
            $customer = StripeCustomer::retrieve($this->stripe_id);

            if (isset($customer->deleted) && $customer->deleted) {
                return null;
            }

            $defaultPmId = $customer->invoice_settings->default_payment_method ?? null;

            return $defaultPmId ? StripePaymentMethod::retrieve($defaultPmId) : null;
        } catch (Exception $e) {
            Log::error('Failed to get default payment method.', [
                'user_id' => $this->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function hasDefaultPaymentMethod(): bool
    {
        if (!$this->stripe_id) {
            return false;
        }

        try {
            $customer = StripeCustomer::retrieve($this->stripe_id);

            if (isset($customer->deleted) && $customer->deleted) {
                return false;
            }

            return !empty($customer->invoice_settings->default_payment_method);
        } catch (Exception $e) {
            Log::error('Failed to check default payment method.', [
                'user_id' => $this->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    // After stripe.confirmCardSetup() on the frontend, the PM is already attached to the customer.
    // This method verifies ownership and only attaches if the PM is completely fresh (no customer).
    public function addPaymentMethod(string $paymentMethodId): StripePaymentMethod
    {
        $customer = $this->createOrGetStripeCustomer();

        try {
            $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);

            // Already attached to this customer — nothing to do
            if ($paymentMethod->customer === $customer->id) {
                return $paymentMethod;
            }

            // Belongs to a different customer — reject
            if ($paymentMethod->customer) {
                throw new \RuntimeException('This card is linked to another account. Please use a different card.');
            }

            // Fresh PM with no customer — attach it
            $paymentMethod->attach(['customer' => $customer->id]);

            return $paymentMethod;

        } catch (CardException $e) {
            Log::error('Card declined while adding payment method.', ['user_id' => $this->id, 'pm_id' => $paymentMethodId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Your card was declined. Please try another card.', 0, $e);

        } catch (InvalidRequestException $e) {
            // This happens when the PM was previously used or detached without going through SetupIntent
            Log::error('Invalid PaymentMethod — possibly used or detached.', ['user_id' => $this->id, 'pm_id' => $paymentMethodId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('This card session has expired. Please re-enter your card details.', 0, $e);

        } catch (\RuntimeException $e) {
            throw $e;

        } catch (Exception $e) {
            Log::error('Unexpected error while adding payment method.', ['user_id' => $this->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to add payment method. Please try again.', 0, $e);
        }
    }


    //     public function addMethodToCustomer(Request $request)
    // {
    //     $request->validate([
    //         'payment_method_id' => 'required|string'
    //     ]);

    //     try {
    //         $paymentMethod = PaymentMethod::retrieve($request->payment_method_id);

    //         // Check if the PaymentMethod is attached or reusable
    //         if ($paymentMethod->attached) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'This payment method is already attached to a customer or is not reusable'
    //             ], 400);
    //         }

    //         // Create a new customer if it doesn't exist
    //         $customer = $this->createCustomerIfNotExist();
    //         // Attach the payment method to the customer
    //         $paymentMethod->attach([
    //             'customer' => $customer->id,
    //         ]);
    //         Customer::update($customer->id, [
    //             "invoice_settings" => [
    //                 'default_payment_method' => $request->payment_method_id,
    //             ]
    //         ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Card added successfully'
    //         ]);
    //     } catch (Exception $e) {
    //         return $this->error($e->getMessage());
    //     }
    // }

    public function updateDefaultPaymentMethod(string $paymentMethodId): static
    {
        $customer = $this->createOrGetStripeCustomer();

        try {
            $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);

            // Attach to this customer if not already attached
            if ($paymentMethod->customer !== $customer->id) {
                if ($paymentMethod->customer) {
                    try {
                        $paymentMethod->detach();
                    } catch (Exception $e) {
                        Log::warning('Could not detach PM from previous customer.', ['pm_id' => $paymentMethodId]);
                    }
                }
                $paymentMethod->attach(['customer' => $customer->id]);
            }

            StripeCustomer::update($customer->id, [
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);

            // Sync card brand and last 4 digits to local DB
            $this->update([
                'pm_type'      => $paymentMethod->card->brand ?? null,
                'pm_last_four' => $paymentMethod->card->last4 ?? null,
            ]);

            return $this;

        } catch (CardException $e) {
            Log::error('Card declined while setting default PM.', ['user_id' => $this->id, 'pm_id' => $paymentMethodId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Your card was declined. Please try a different card.', 0, $e);

        } catch (InvalidRequestException $e) {
            Log::error('Invalid request while setting default PM.', ['user_id' => $this->id, 'pm_id' => $paymentMethodId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Invalid payment information: ' . $e->getMessage(), 0, $e);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API error while setting default PM.', ['user_id' => $this->id, 'pm_id' => $paymentMethodId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Payment processing error. Please try again later.', 0, $e);

        } catch (Exception $e) {
            Log::error('Unexpected error while setting default PM.', ['user_id' => $this->id, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Something went wrong.', 0, $e);
        }
    }

    // Returns a wrapper object with a delete() method (similar to Laravel Cashier style).
    // Returns null if the PM doesn't exist or doesn't belong to this customer.
    public function findPaymentMethod(string $paymentMethodId): ?object
    {
        if (!$this->stripe_id) {
            return null;
        }

        try {
            $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);

            // Reject if it belongs to a different customer
            if ($paymentMethod->customer !== $this->stripe_id) {
                return null;
            }

            return new class ($paymentMethod) {
                private StripePaymentMethod $pm;

                public function __construct(StripePaymentMethod $pm)
                {
                    $this->pm = $pm;
                }

                public function __get(string $name)
                {
                    return $this->pm->$name;
                }

                public function delete(): void
                {
                    try {
                        $this->pm->detach();
                    } catch (Exception $e) {
                        Log::error('Failed to detach payment method.', ['pm_id' => $this->pm->id ?? null, 'error' => $e->getMessage()]);
                    }
                }
            };

        } catch (Exception $e) {
            Log::error('Failed to find payment method.', ['user_id' => $this->id ?? null, 'pm_id' => $paymentMethodId, 'error' => $e->getMessage()]);
            return null;
        }
    }
}