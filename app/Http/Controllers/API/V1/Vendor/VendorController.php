<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Vendor\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\V1\Vendor\ItemResource;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use App\Actions\GeoLocation;

class VendorController extends Controller
{
    use ApiResponse;

    protected $geoLocation;

    public function __construct(GeoLocation $geoLocation)
    {
        $this->geoLocation = $geoLocation;
    }

    /**
     * Get current authenticated vendor profile with categorized items
     */
    public function me(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->error([], 'Unauthenticated', 401);
            }

            $user->load('business');

            $categories = Category::where('user_id', $user->id)
                ->when($request->category_slug, function ($q) use ($request) {
                    $q->where('slug', $request->category_slug);
                })
                ->with(['items' => function ($query) {
                    $query->with(['category', 'variants', 'toppings'])->latest();
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

            return $this->success([
                'user' => new UserResource($user),
                'categories' => $categorizedItems
            ], 'Vendor profile and items retrieved successfully', 200);
        } catch (Exception $e) {
            Log::error('Vendor Side - Profile Retrieval Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve profile', 500);
        }
    }

    /**
     * Update vendor profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validation = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:250'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $updateData = array_filter($request->only(['name', 'address', 'latitude', 'longitude']), function($value) {
                return !is_null($value);
            });

            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                if ($request->filled('address')) {
                    $coords = $this->geoLocation->getCoordinates($request->address);
                    if (!is_null($coords['latitude'])) {
                        $updateData['latitude'] = $coords['latitude'];
                    }
                    if (!is_null($coords['longitude'])) {
                        $updateData['longitude'] = $coords['longitude'];
                    }
                }
            }

            $user->update($updateData);
            DB::commit();

            return $this->success([
                'user' => new UserResource($user->fresh()->load('business')),
            ], 'Vendor profile updated successfully');

        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error('Vendor Profile update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update profile', 500);
        }
    }

    /**
     * Change vendor password
     */
    public function changePassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        try {
            $user = Auth::user();
            if (Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();
                return $this->success([], "Password changed successfully", 200);
            }
            else {
                return $this->error([], "Current password is incorrect", 400);
            }
        }
        catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    /**
     * Delete vendor account
     */
    public function deleteAccount()
    {
        $user = Auth::user();
        try {
            \App\Services\UserDeletionService::deleteUser($user);
            return $this->success([], "Vendor account deleted successfully", 200);
        }
        catch (Exception $e) {
            Log::error('Vendor Account delete failed: ' . $e->getMessage());
            return $this->error([], 'Failed to delete account', 500);
        }
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email_notification' => 'sometimes|boolean',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        try {
            $user = Auth::user();
            $user->update($request->only(['email_notification']));
            
            return $this->success([
                'email_notification' => (bool) $user->email_notification
            ], 'Notification settings updated successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to update notification settings', 500);
        }
    }
}
