<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\ItemToppingResource;
use App\Models\Item;
use App\Models\ItemTopping;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItemToppingController extends Controller
{
    use ApiResponse;

    /**
     * List toppings for a specific item.
     */
    public function index($itemSlug)
    {
        try {
            $user = Auth::user();
            $item = Item::where('vendor_id', $user->id)->where('slug', $itemSlug)->first();
            if (!$item) return $this->error([], 'Item not found', 404);

            $toppings = $item->toppings()->latest()->get();
            return $this->success(ItemToppingResource::collection($toppings), 'Toppings retrieved successfully');
        } catch (Exception $e) {
            Log::error('Topping Index Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve toppings', 500);
        }
    }

    /**
     * Store a new topping.
     */
    public function store(Request $request, $itemSlug)
    {
        try {
            $user = Auth::user();
            $item = Item::where('vendor_id', $user->id)->where('slug', $itemSlug)->first();
            if (!$item) return $this->error([], 'Item not found', 404);

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
            ]);

            $topping = $item->toppings()->create($request->only('name', 'price'));
            return $this->success(new ItemToppingResource($topping), 'Topping created successfully', 201);
        } catch (Exception $e) {
            Log::error('Topping Store Error: ' . $e->getMessage());
            return $this->error([], 'Failed to create topping', 500);
        }
    }

    /**
     * Update a topping.
     */
    public function update(Request $request, $slug)
    {
        try {
            $user = Auth::user();
            $topping = ItemTopping::whereHas('item', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            })->where('slug', $slug)->first();

            if (!$topping) return $this->error([], 'Topping not found', 404);

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
            ]);

            $topping->update($request->only('name', 'price'));
            return $this->success(new ItemToppingResource($topping), 'Topping updated successfully');
        } catch (Exception $e) {
            Log::error('Topping Update Error: ' . $e->getMessage());
            return $this->error([], 'Failed to update topping', 500);
        }
    }

    /**
     * Delete a topping.
     */
    public function destroy($slug)
    {
        try {
            $user = Auth::user();
            $topping = ItemTopping::whereHas('item', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            })->where('slug', $slug)->first();

            if (!$topping) return $this->error([], 'Topping not found', 404);

            $topping->delete();
            return $this->success([], 'Topping deleted successfully');
        } catch (Exception $e) {
            Log::error('Topping Destroy Error: ' . $e->getMessage());
            return $this->error([], 'Failed to delete topping', 500);
        }
    }
}
