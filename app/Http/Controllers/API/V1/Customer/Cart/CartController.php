<?php

namespace App\Http\Controllers\API\V1\Customer\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Item;
use App\Models\ItemTopping;
use App\Models\ItemVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Requests\V1\Customer\Cart\AddToCartRequest;
use App\Http\Requests\V1\Customer\Cart\RemoveFromCartRequest;
use App\Http\Requests\V1\Customer\Cart\UpdateCartItemRequest;
use App\Http\Resources\V1\Customer\Cart\CartResource;

use Illuminate\Support\Facades\Log;
use Exception;

class CartController extends Controller
{
    use ApiResponse;

    public function getCart()
    {
        try {
            $user = auth('api')->user();
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $cart->load([
                'items.item',
                'items.variant',
            ]);

            return $this->success(new CartResource($cart), 'Cart fetched successfully');
        } catch (Exception $exception) {
            Log::error('Cart fetch failed: ' . $exception->getMessage());
            return $this->error([], 'Failed to fetch cart: ' . $exception->getMessage(), 500);
        }
    }

    public function addToCart(AddToCartRequest $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->error([], 'User not authenticated', 401);
            }

            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $product = Item::findOrFail($request->item_id);

            $selectedVariant = null;
            if ($request->variant_id) {
                $selectedVariant = ItemVariant::where('id', $request->variant_id)
                    ->where('item_id', $product->id)
                    ->first();

                if (!$selectedVariant) {
                    return $this->error([], 'Invalid variant for this item', 422);
                }
            }

            $formattedToppings = [];
            if ($request->topping_ids) {
                $matchedToppings = ItemTopping::whereIn('id', $request->topping_ids)
                    ->where('item_id', $product->id)
                    ->get();

                if ($matchedToppings->count() !== count($request->topping_ids)) {
                    return $this->error([], 'Some toppings are invalid for this item', 422);
                }

                foreach ($matchedToppings as $topping) {
                    $formattedToppings[] = [
                        'id' => $topping->id,
                        'name' => $topping->name,
                        'price' => $topping->price,
                    ];
                }
            }

            $basePrice = $selectedVariant ? $selectedVariant->price : $product->price;

            $totalToppingsPrice = 0;
            foreach ($formattedToppings as $topping) {
                $totalToppingsPrice += $topping['price'];
            }

            $unitPrice = $basePrice + $totalToppingsPrice;

            if (!empty($formattedToppings)) {
                usort($formattedToppings, function ($a, $b) {
                    return $a['id'] <=> $b['id'];
                });
            }

            $existingCartItems = CartItem::where('cart_id', $cart->id)->get();
            $matchingItem = null;

            foreach ($existingCartItems as $existingCartItem) {
                if ($existingCartItem->item_id != $product->id || $existingCartItem->variant_id != ($selectedVariant ? $selectedVariant->id : null)) {
                    continue;
                }

                if (($existingCartItem->special_instruction ?? '') !== ($request->special_instruction ?? '')) {
                    continue;
                }

                $existingToppings = (function ($toppingsData) {
                    if (empty($toppingsData))
                        return [];
                    if (is_string($toppingsData)) {
                        $decoded = json_decode($toppingsData, true);
                        $toppingsData = is_array($decoded) ? $decoded : [];
                    }
                    if (!is_array($toppingsData))
                        return [];

                    $formatted = array_map(function ($t) {
                        return [
                            'id' => (string) ($t['id'] ?? ''),
                            'price' => (string) ($t['price'] ?? '0.00'),
                        ];
                    }, $toppingsData);

                    usort($formatted, function ($a, $b) {
                        return $a['id'] <=> $b['id'];
                    });

                    return $formatted;
                })($existingCartItem->selected_toppings);

                $newToppings = (function ($toppingsData) {
                    if (empty($toppingsData))
                        return [];

                    $formatted = array_map(function ($t) {
                        return [
                            'id' => (string) ($t['id'] ?? ''),
                            'price' => (string) ($t['price'] ?? '0.00'),
                        ];
                    }, $toppingsData);

                    usort($formatted, function ($a, $b) {
                        return $a['id'] <=> $b['id'];
                    });

                    return $formatted;
                })($formattedToppings);

                if (json_encode($existingToppings) === json_encode($newToppings)) {
                    $matchingItem = $existingCartItem;
                    break;
                }
            }

            $cartEntry = $matchingItem ?? new CartItem();

            $cartEntry->cart_id = $cart->id;
            $cartEntry->item_id = $product->id;
            $cartEntry->variant_id = $selectedVariant ? $selectedVariant->id : null;
            $cartEntry->selected_toppings = $formattedToppings;
            $cartEntry->unit_price = $unitPrice;
            $cartEntry->special_instruction = $request->special_instruction;

            if ($matchingItem) {
                $cartEntry->quantity += $request->quantity;
            } else {
                $cartEntry->quantity = $request->quantity;
            }

            $cartEntry->save();

            return $this->success([], 'Item(s) added to cart successfully');
        } catch (Exception $exception) {
            Log::error('Add to cart failed: ' . $exception->getMessage());
            return $this->error([], 'Failed to add item to cart: ' . $exception->getMessage(), 500);
        }
    }

    public function updateCartItem(UpdateCartItemRequest $request)
    {
        try {
            $user = auth('api')->user();
            $cart = Cart::where('user_id', $user->id)->first();
            if (!$cart)
                return $this->error([], 'Cart not found', 404);

            $cartEntry = CartItem::where('id', $request->cart_item_id)->where('cart_id', $cart->id)->first();
            if (!$cartEntry)
                return $this->error([], 'Cart item not found', 404);

            $product = Item::with('variants', 'toppings')->findOrFail($request->item_id ?? $cartEntry->item_id);

            // Handle Variant
            if ($request->has('variant_id')) {
                if ($request->variant_id) {
                    $selectedVariant = ItemVariant::where('id', $request->variant_id)
                        ->where('item_id', $product->id)
                        ->first();
                    if (!$selectedVariant)
                        return $this->error([], 'Invalid variant for this item', 422);
                    $cartEntry->variant_id = $selectedVariant->id;
                } else {
                    $cartEntry->variant_id = null;
                }
            }
            $selectedVariant = $cartEntry->variant_id ? ItemVariant::find($cartEntry->variant_id) : null;

            // Handle Toppings
            if ($request->has('topping_ids')) {
                $formattedToppings = [];
                if ($request->topping_ids) {
                    $matchedToppings = ItemTopping::whereIn('id', $request->topping_ids)
                        ->where('item_id', $product->id)
                        ->get();

                    if ($matchedToppings->count() !== count($request->topping_ids)) {
                        return $this->error([], 'Some toppings are invalid for this item', 422);
                    }

                    foreach ($matchedToppings as $topping) {
                        $formattedToppings[] = [
                            'id' => $topping->id,
                            'name' => $topping->name,
                            'price' => $topping->price,
                        ];
                    }
                }
                $cartEntry->selected_toppings = $formattedToppings;
            }

            // Recalculate Unit Price
            $basePrice = $selectedVariant ? $selectedVariant->price : $product->price;
            $toppings = is_array($cartEntry->selected_toppings) ? $cartEntry->selected_toppings : json_decode($cartEntry->selected_toppings ?? '[]', true);
            $totalToppingsPrice = 0;
            if (is_array($toppings)) {
                foreach ($toppings as $topping) {
                    $totalToppingsPrice += $topping['price'] ?? 0;
                }
            }
            $unitPrice = $basePrice + $totalToppingsPrice;

            $cartEntry->unit_price = $unitPrice;
            if ($request->has('quantity')) {
                $cartEntry->quantity = $request->quantity;
            }
            if ($request->has('special_instruction')) {
                $cartEntry->special_instruction = $request->special_instruction;
            }
            $cartEntry->save();

            return $this->success([], 'Cart item updated successfully');
        } catch (Exception $exception) {
            Log::error('Cart item update failed: ' . $exception->getMessage());
            return $this->error([], 'Failed to update cart item: ' . $exception->getMessage(), 500);
        }
    }

    public function removeFromCart(RemoveFromCartRequest $request)
    {
        try {
            $user = auth('api')->user();
            $cart = Cart::where('user_id', $user->id)->first();
            if ($cart) {
                CartItem::where('id', $request->cart_item_id)->where('cart_id', $cart->id)->delete();
            }
            return $this->success([], 'Item removed from cart successfully');
        } catch (Exception $exception) {
            Log::error('Remove from cart failed: ' . $exception->getMessage());
            return $this->error([], 'Failed to remove item: ' . $exception->getMessage(), 500);
        }
    }

    public function clearCart()
    {
        try {
            $user = auth('api')->user();
            $cart = Cart::where('user_id', $user->id)->first();
            if ($cart) {
                $cart->items()->delete();
            }
            return $this->success([], 'Cart cleared successfully');
        } catch (Exception $exception) {
            Log::error('Clear cart failed: ' . $exception->getMessage());
            return $this->error([], 'Failed to clear cart: ' . $exception->getMessage(), 500);
        }
    }
}
