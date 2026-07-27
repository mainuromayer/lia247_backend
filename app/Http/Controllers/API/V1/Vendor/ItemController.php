<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\Item\ItemStoreRequest;
use App\Http\Requests\V1\Vendor\Item\ItemUpdateRequest;
use App\Http\Resources\V1\Vendor\ItemResource;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemTopping;
use App\Models\ItemVariant;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $categories = Category::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('is_admin_created', true);
            })
                ->whereHas('items', function ($query) use ($user) {
                    $query->where('vendor_id', $user->id);
                })
                ->when($request->category_slug, function ($q) use ($request) {
                    $q->where('slug', $request->category_slug);
                })
                ->with([
                    'items' => function ($query) use ($user) {
                        $query->where('vendor_id', $user->id)
                            ->with(['variants', 'toppings'])
                            ->latest();
                    }
                ])
                ->orderBy('name', 'asc')
                ->get();

            $data = [];
            foreach ($categories as $category) {
                $data[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_slug' => $category->slug,
                    'items' => ItemResource::collection($category->items),
                ];
            }

            return $this->success($data, 'Categorized items retrieved successfully');
        } catch (Exception $e) {
            Log::error('Item Index Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve items', 500);
        }
    }


    public function store(ItemStoreRequest $request)
    {
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $item = new Item();

            $item->vendor_id = $user->id;
            $item->category_id = $request->category_id;
            $item->name = $request->name;
            $item->cooking_time = $request->cooking_time;
            $item->description = $request->description;
            $item->price = $request->price;
            $item->status = 'active';

            // Handle Image Upload
            if ($request->hasFile('image')) {
                $imageUrl = Helper::fileUpload($request->file('image'), 'items');
                if ($imageUrl) {
                    $item->image = $imageUrl;
                }
            }

            $item->save();

            // Handle Variants
            if ($request->has('variants')) {
                foreach ($request->variants as $variant) {
                    $item->variants()->create([
                        'name' => $variant['name'],
                        'price' => $variant['price'],
                        'is_featured' => filter_var($variant['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }

            // Handle Toppings
            if ($request->has('toppings')) {
                foreach ($request->toppings as $topping) {
                    $item->toppings()->create([
                        'name' => $topping['name'],
                        'price' => $topping['price'],
                        'is_featured' => filter_var($topping['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }



            DB::commit();

            $item->load(['category', 'variants', 'toppings']);

            return $this->success(new ItemResource($item), 'Item created successfully', 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Item Store Error: ' . $e->getMessage());
            return $this->error([], 'Failed to create item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified item.
     */
    public function show($slug)
    {
        try {
            $user = Auth::user();
            $item = Item::with(['category', 'variants', 'toppings'])
                ->where('vendor_id', $user->id)
                ->where('slug', $slug)
                ->first();

            if (!$item) {
                return $this->error([], 'Item not found', 404);
            }

            return $this->success(new ItemResource($item), 'Item retrieved successfully');
        } catch (Exception $e) {
            Log::error('Item Show Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve item', 500);
        }
    }

    /**
     * Update the specified item in storage.
     */
    public function update(ItemUpdateRequest $request, $slug)
    {
        $user = Auth::user();

        $item = Item::where('vendor_id', $user->id)
            ->where('slug', $slug)
            ->first();

        if (!$item) {
            Log::warning('Item not found for update', [
                'vendor_id' => $user->id,
                'slug'      => $slug,
            ]);
            return $this->error([], 'Item not found', 404);
        }

        DB::beginTransaction();
        try {
            $item->category_id = $request->category_id;
            $item->name = $request->name;
            $item->cooking_time = $request->cooking_time;
            $item->description = $request->description;
            $item->price = $request->price;

            // Handle Image Upload
            if ($request->hasFile('image')) {
                if ($item->image) {
                    Helper::deleteFile($item->image);
                }
                $imageUrl = Helper::fileUpload($request->file('image'), 'items');
                if ($imageUrl) {
                    $item->image = $imageUrl;
                }
            }

            $item->save();

            // Update Variants (delete old + create new)
            if ($request->has('variants')) {
                $item->variants()->delete();
                foreach ($request->variants as $variant) {
                    $item->variants()->create([
                        'name' => $variant['name'],
                        'price' => $variant['price'],
                        'is_featured' => filter_var($variant['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }

            // Update Toppings
            if ($request->has('toppings')) {
                $item->toppings()->delete();
                foreach ($request->toppings as $topping) {
                    $item->toppings()->create([
                        'name' => $topping['name'],
                        'price' => $topping['price'],
                        'is_featured' => filter_var($topping['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }



            DB::commit();

            $item->load(['category', 'variants', 'toppings']);

            return $this->success(new ItemResource($item), 'Item updated successfully');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Item Update Error: ' . $e->getMessage());
            return $this->error([], 'Failed to update item: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Remove the specified item from storage.
     */
    public function destroy($slug)
    {
        try {
            $user = Auth::user();
            $item = Item::where('vendor_id', $user->id)->where('slug', $slug)->first();

            if (!$item) {
                return $this->error([], 'Item not found', 404);
            }

            // Delete image
            if ($item->image) {
                Helper::deleteFile($item->image);
            }

            $item->delete();

            return $this->success([], 'Item deleted successfully');
        } catch (Exception $e) {
            Log::error('Item Delete Error: ' . $e->getMessage());
            return $this->error([], 'Failed to delete item', 500);
        }
    }

}
