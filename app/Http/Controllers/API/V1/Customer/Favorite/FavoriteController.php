<?php

namespace App\Http\Controllers\API\V1\Customer\Favorite;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Customer\ItemResource;
use App\Http\Resources\V1\Vendor\UserResource as VendorResource;
use App\Models\Favorite;
use App\Models\Item;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    use ApiResponse;

    /**
     * Toggle favorite for an item or vendor
     */
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'type' => 'required|string|in:item,vendor',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation failed', 422);
        }

        try {
            $user = Auth::user();
            $type = $request->type === 'item' ? Item::class : User::class;
            
            // Check if exists
            $model = $type::find($request->id);
            if (!$model) {
                return $this->error([], ucfirst($request->type) . ' not found', 404);
            }

            if ($request->type === 'vendor' && $model->user_type !== 'vendor') {
                return $this->error([], 'The specified user is not a vendor', 400);
            }

            $favorite = Favorite::where([
                'user_id' => $user->id,
                'favouritable_id' => $request->id,
                'favouritable_type' => $type,
            ])->first();

            if ($favorite) {
                $favorite->delete();
                return $this->success([], ucfirst($request->type) . ' removed from favorites');
            } else {
                Favorite::create([
                    'user_id' => $user->id,
                    'favouritable_id' => $request->id,
                    'favouritable_type' => $type,
                ]);
                return $this->success([], ucfirst($request->type) . ' added to favorites');
            }

        } catch (Exception $e) {
            Log::error('Favorite Toggle Error: ' . $e->getMessage());
            return $this->error([], 'Failed to toggle favorite', 500);
        }
    }

    /**
     * Get list of favorite items and vendors
     */
    public function index()
    {
        try {
            $user = Auth::user();

            $favoriteItems = Favorite::where('user_id', $user->id)
                ->where('favouritable_type', Item::class)
                ->with(['favouritable.vendor.business', 'favouritable.category', 'favouritable.variants', 'favouritable.toppings'])
                ->get()
                ->pluck('favouritable');

            $favoriteVendors = Favorite::where('user_id', $user->id)
                ->where('favouritable_type', User::class)
                ->with(['favouritable.business'])
                ->get()
                ->pluck('favouritable');

            return $this->success([
                'items' => ItemResource::collection($favoriteItems),
                'vendors' => VendorResource::collection($favoriteVendors),
            ], 'Favorite items and vendors retrieved successfully');

        } catch (Exception $e) {
            Log::error('Favorite Index Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve favorites', 500);
        }
    }
}
