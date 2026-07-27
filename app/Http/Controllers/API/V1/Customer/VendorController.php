<?php

namespace App\Http\Controllers\API\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Customer\ItemResource;
use App\Http\Resources\V1\Customer\PopularVendorResource;
use App\Http\Resources\V1\Customer\UserResource;
use App\Models\Category;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{
    use ApiResponse;

    /**
     * Get popular restaurants based on ratings and proximity
     */
    public function popularVendors(Request $request)
    {
        try {
            $user = Auth::user();
            $latitude = $user->latitude ?? null;
            $longitude = $user->longitude ?? null;

            $vendors = User::where('user_type', 'vendor')
                ->with('business')
                ->whereHas('business')
                ->when($latitude && $longitude, function ($query) use ($latitude, $longitude) {
                    $query->join('businesses', 'users.id', '=', 'businesses.user_id')
                        ->selectRaw("users.*, (6371 * acos(cos(radians(?)) * cos(radians(businesses.latitude)) * cos(radians(businesses.longitude) - radians(?)) + sin(radians(?)) * sin(radians(businesses.latitude)))) AS distance", [$latitude, $longitude, $latitude])
                        ->orderBy('distance', 'asc');
                }, function ($query) {
                    $query->selectRaw("users.*, 0 AS distance");
                })
                ->get();


            foreach ($vendors as $vendor) {
                // Inject favorite status
                $vendor->is_favourite = $user ? $user->favorites()
                    ->where('favouritable_id', $vendor->id)
                    ->where('favouritable_type', User::class)
                    ->exists() : false;
            }

            $vendors = $vendors->sortByDesc('average_rating')->take(10);

            return $this->success(PopularVendorResource::collection($vendors), 'Popular vendors retrieved successfully');
        } catch (Exception $e) {
            Log::error('Popular Vendors Retrieval Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve popular vendors: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get items categorized by category for a specific vendor, including vendor details
     */
    public function vendorItems(Request $request, $id)
    {
        try {
            $userLoc = Auth::user();
            $latitude = $userLoc->latitude ?? null;
            $longitude = $userLoc->longitude ?? null;

            $vendor = User::with('business')->find($id);

            if (!$vendor) {
                return $this->error([], 'Vendor not found', 404);
            }

            $categories = Category::whereHas('items', function ($query) use ($id) {
                    $query->where('vendor_id', $id)->where('status', 'active');
                })
                ->when($request->category_slug, function ($q) use ($request) {
                    $q->where('slug', $request->category_slug);
                })
                ->with(['items' => function ($query) use ($id, $latitude, $longitude) {
                    $query->select('items.*');
                    
                    if ($latitude && $longitude) {
                        $query->leftJoin('businesses', 'items.vendor_id', '=', 'businesses.user_id')
                            ->selectRaw("items.*, (6371 * acos(cos(radians(?)) * cos(radians(businesses.latitude)) * cos(radians(businesses.longitude) - radians(?)) + sin(radians(?)) * sin(radians(businesses.latitude)))) AS distance", [$latitude, $longitude, $latitude]);
                    } else {
                        $query->selectRaw("items.*, 0 AS distance");
                    }
                    
                    $query->where('items.vendor_id', $id)
                        ->where('items.status', 'active')
                        ->with(['vendor.business', 'category', 'variants', 'toppings'])
                        ->latest();
                }])
                ->orderBy('name', 'asc')
                ->get();

            $categorizedItems = [];
            foreach ($categories as $category) {
                if ($category->items->count() > 0) {
                    $categorizedItems[] = [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'category_slug' => $category->slug,
                        'items' => ItemResource::collection($category->items),
                    ];
                }
            }

            $data = [
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'business_name' => $vendor->business?->business_name,
                    'business_address' => $vendor->business?->business_address,
                    'logo' => $vendor->avatar,
                    'cover_image' => $vendor->business?->cover_photo,
                    'free_delivery' => (bool) $vendor->business?->free_delivery,
                    'rating' => (float) $vendor->average_rating,
                ],
                'categories' => $categorizedItems
            ];

            return $this->success($data, 'Vendor details and items retrieved successfully');
        } catch (Exception $e) {
            Log::error('Vendor Items Retrieval Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve vendor items: ' . $e->getMessage(), 500);
        }
    }
}
