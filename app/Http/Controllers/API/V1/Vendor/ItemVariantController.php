<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\ItemVariantResource;
use App\Models\Item;
use App\Models\ItemVariant;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItemVariantController extends Controller
{
    use ApiResponse;

    /**
     * List variants for a specific item.
     */
    public function index($itemSlug)
    {
        try {
            $user = Auth::user();
            $item = Item::where('vendor_id', $user->id)->where('slug', $itemSlug)->first();
            if (!$item) return $this->error([], 'Item not found', 404);

            $variants = $item->variants()->latest()->get();
            return $this->success(ItemVariantResource::collection($variants), 'Variants retrieved successfully');
        } catch (Exception $e) {
            Log::error('Variant Index Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve variants', 500);
        }
    }

    /**
     * Store a new variant.
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

            $variant = $item->variants()->create($request->only('name', 'price'));
            return $this->success(new ItemVariantResource($variant), 'Variant created successfully', 201);
        } catch (Exception $e) {
            Log::error('Variant Store Error: ' . $e->getMessage());
            return $this->error([], 'Failed to create variant', 500);
        }
    }

    /**
     * Update a variant.
     */
    public function update(Request $request, $slug)
    {
        try {
            $user = Auth::user();
            $variant = ItemVariant::whereHas('item', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            })->where('slug', $slug)->first();

            if (!$variant) return $this->error([], 'Variant not found', 404);

            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
            ]);

            $variant->update($request->only('name', 'price'));
            return $this->success(new ItemVariantResource($variant), 'Variant updated successfully');
        } catch (Exception $e) {
            Log::error('Variant Update Error: ' . $e->getMessage());
            return $this->error([], 'Failed to update variant', 500);
        }
    }

    /**
     * Delete a variant.
     */
    public function destroy($slug)
    {
        try {
            $user = Auth::user();
            $variant = ItemVariant::whereHas('item', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            })->where('slug', $slug)->first();

            if (!$variant) return $this->error([], 'Variant not found', 404);

            $variant->delete();
            return $this->success([], 'Variant deleted successfully');
        } catch (Exception $e) {
            Log::error('Variant Destroy Error: ' . $e->getMessage());
            return $this->error([], 'Failed to delete variant', 500);
        }
    }
}
