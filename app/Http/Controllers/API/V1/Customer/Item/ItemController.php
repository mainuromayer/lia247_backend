<?php

namespace App\Http\Controllers\API\V1\Customer\Item;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Customer\ItemResource;
use App\Models\Item;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    use ApiResponse;

    /**
     * Get list of items with advanced filtering
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $latitude = $user->latitude ?? null;
            $longitude = $user->longitude ?? null;

            $items = Item::select('items.*')
                ->where('items.status', 'active')
                ->with(['vendor.business', 'category', 'variants', 'toppings']);

            // Calculate distance if coordinates are available
            if ($latitude && $longitude) {
                $items->leftJoin('businesses', 'items.vendor_id', '=', 'businesses.user_id')
                    ->selectRaw("items.*, (6371 * acos(cos(radians(?)) * cos(radians(businesses.latitude)) * cos(radians(businesses.longitude) - radians(?)) + sin(radians(?)) * sin(radians(businesses.latitude)))) AS distance", [$latitude, $longitude, $latitude]);
            } else {
                $items->selectRaw("items.*, 0 AS distance");
            }

            // Filtering by Search
            if ($request->filled('search')) {
                $search = $request->search;
                $items->where(function($q) use ($search) {
                    $q->where('items.name', 'LIKE', "%{$search}%")
                      ->orWhere('items.description', 'LIKE', "%{$search}%");
                });
            }

            // Filtering by Category Slug
            if ($request->filled('category_slug')) {
                $items->whereHas('category', function($q) use ($request) {
                    $q->where('slug', $request->category_slug);
                });
            }

            // Filtering by Delivery Type ID (Assuming vendors or items have this relationship in future)
            if ($request->filled('delivery_type_id')) {
                // Placeholder
                $items->where('delivery_type_id', $request->delivery_type_id);
            }

            // Filtering by Offer Wise (Placeholder)
            if ($request->filled('offer_wise') && filter_var($request->offer_wise, FILTER_VALIDATE_BOOLEAN)) {
                // Placeholder
            }

            // Filtering by Ratings Wise
            if ($request->filled('ratings_wise')) {
                $items->whereRaw('(SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE target_id = items.id AND role = "item") >= ?', [$request->ratings_wise]);
            }

            $items = $items->latest()->get();

            return $this->success([
                'items' => ItemResource::collection($items),
            ], "Items retrieved successfully", 200);

        } catch (Exception $e) {
            Log::error('Fetch items failed: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve items: ' . $e->getMessage(), 500);
        }
    }

    public function nearbyItems(Request $request)
    {
        try {
            $user = Auth::user();
            
            $latitude = $user->latitude;
            $longitude = $user->longitude;
            $radius = 50; 

            if (empty($latitude) || empty($longitude)) {
                return $this->error([], "Customer has no current location set. Please update your current location first.", 400);
            }

            $items = Item::join('businesses', 'items.vendor_id', '=', 'businesses.user_id')
                ->selectRaw("items.*, (6371 * acos(cos(radians(?)) * cos(radians(businesses.latitude)) * cos(radians(businesses.longitude) - radians(?)) + sin(radians(?)) * sin(radians(businesses.latitude)))) AS distance", [$latitude, $longitude, $latitude])
                ->where('items.status', 'active')
                ->whereRaw("(6371 * acos(cos(radians(?)) * cos(radians(businesses.latitude)) * cos(radians(businesses.longitude) - radians(?)) + sin(radians(?)) * sin(radians(businesses.latitude)))) <= ?", [$latitude, $longitude, $latitude, $radius])
                ->with(['vendor.business', 'category', 'variants', 'toppings'])
                ->orderBy('distance', 'asc');

            if ($request->has('search') && !empty($request->search)) {
                $items->where('items.name', 'LIKE', '%' . $request->search . '%');
            }

            if ($request->has('category_id') && !empty($request->category_id)) {
                $items->where('items.category_id', $request->category_id);
            }

            $items = $items->get();

            return $this->success([
                'items' => ItemResource::collection($items),
            ], "Nearby items retrieved successfully", 200);

        }
        catch (Exception $e) {
            Log::error('Fetch nearby items failed: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve nearby items: ' . $e->getMessage(), 500);
        }
    }
}
