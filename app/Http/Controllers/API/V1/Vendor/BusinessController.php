<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Actions\GeoLocation;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\BusinessSetupRequest;
use App\Http\Requests\V1\Vendor\UpdateBusinessRequest;
use App\Http\Resources\V1\Vendor\BusinessResource;
use App\Models\Business;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    use ApiResponse;

    protected $geoLocation;

    public function __construct(GeoLocation $geoLocation)
    {
        $this->geoLocation = $geoLocation;
    }


    /**
     * Setup business account for vendor
     */
    public function setupBusiness(BusinessSetupRequest $request)
    {
        $user = Auth::user();

        // Check if user is vendor
        if ($user->user_type !== 'vendor') {
            return $this->error([], 'Only vendors can setup a business account', 403);
        }

        // Check if business already exists
        if ($user->business) {
            return $this->error([], 'Business account already setup', 400);
        }

        DB::beginTransaction();

        try {
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                $coords = $this->geoLocation->getCoordinates($request->business_address);
                if (!is_null($coords['latitude'])) {
                    $latitude = $coords['latitude'];
                }
                if (!is_null($coords['longitude'])) {
                    $longitude = $coords['longitude'];
                }
            }

            $business = new Business();
            $business->user_id = $user->id;
            $business->business_name = $request->business_name;
            $business->business_type = $request->business_type;
            $business->business_description = $request->business_description;
            $business->business_phone = $request->business_phone;
            $business->business_address = $request->business_address;
            $business->opening_time = $request->opening_time;
            $business->closing_time = $request->closing_time;
            $business->license_number = $request->license_number;
            $business->latitude = $latitude;
            $business->longitude = $longitude;



            // Handle cover photo upload
            if ($request->hasFile('cover_photo')) {
                $photoUrl = Helper::fileUpload($request->file('cover_photo'), 'business/covers', $business->id . "-" . time());
                if ($photoUrl) {
                    $business->cover_photo = $photoUrl;
                }
            }

            $business->save();

            DB::commit();

            return $this->success([
                'business' => new BusinessResource($business)
            ], 'Business account created successfully', 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Business Setup Error: ' . $e->getMessage());
            return $this->error([], 'Failed to create business account', 500);
        }
    }

    /**
     * Update business account for vendor
     */
    public function updateBusiness(UpdateBusinessRequest $request)
    {
        $user = Auth::user();

        // Check if user is vendor
        if ($user->user_type !== 'vendor') {
            return $this->error([], 'Only vendors can update a business account', 403);
        }

        $business = $user->business;

        // Check if business exists
        if (!$business) {
            return $this->error([], 'Business account not found. Please setup business first.', 404);
        }

        DB::beginTransaction();

        try {
            $updateData = $request->validated();

            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                if ($request->filled('business_address')) {
                    $coords = $this->geoLocation->getCoordinates($request->business_address);
                    if (!is_null($coords['latitude'])) {
                        $updateData['latitude'] = $coords['latitude'];
                    }
                    if (!is_null($coords['longitude'])) {
                        $updateData['longitude'] = $coords['longitude'];
                    }
                }
            }

            // Handle cover photo upload
            if ($request->hasFile('cover_photo')) {
                $photoUrl = Helper::fileUpload($request->file('cover_photo'), 'business/covers', $business->id . "-" . time());
                if ($photoUrl) {
                    $updateData['cover_photo'] = $photoUrl;
                }
            }

            $business->update($updateData);

            DB::commit();

            return $this->success([
                'business' => new BusinessResource($business->fresh())
            ], 'Business account updated successfully', 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Business Update Error: ' . $e->getMessage());
            return $this->error([], 'Failed to update business account', 500);
        }
    }
}
