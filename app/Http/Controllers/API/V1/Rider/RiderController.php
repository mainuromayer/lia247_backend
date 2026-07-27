<?php

namespace App\Http\Controllers\API\V1\Rider;

use App\Actions\GeoLocation;
use App\Events\RiderStatusUpdated;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Rider\UserResource;
use App\Models\DrivingInformation;
use App\Models\User;
use App\Models\VehicleInformation;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RiderController extends Controller
{
    use ApiResponse;

    protected $geoLocation;

    public function __construct(GeoLocation $geoLocation)
    {
        $this->geoLocation = $geoLocation;
    }

    /**
     * Get current authenticated rider profile
     */
    public function me()
    {
        $user = auth('api')->user() ?? Auth::user();
        $user->load('vehicleInformation', 'drivingInformation', 'wallet');
        return $this->success([
            'user' => new UserResource($user)
        ], 'Rider profile retrieved successfully', 200);
    }

    /**
     * Update rider profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user() ?? Auth::user();

        $validation = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:250'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'nid' => ['required', 'string'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date'],
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $updateData = $request->only(['name', 'phone', 'address', 'nid', 'gender', 'date_of_birth', 'latitude', 'longitude']);

            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                if ($request->filled('address')) {
                    $coords = $this->geoLocation->getCoordinates($request->address);
                    $updateData['latitude'] = $coords['latitude'];
                    $updateData['longitude'] = $coords['longitude'];
                }
            }

            $user->update($updateData);
            DB::commit();
            $user->load('vehicleInformation', 'drivingInformation');

            return $this->success([
                'user' => new UserResource($user),
            ], 'Rider profile updated successfully');

        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error('Rider Profile update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update rider vehicle information
     */
    public function updateVehicle(Request $request)
    {
        $user = auth('api')->user() ?? Auth::user();

        $validation = Validator::make($request->all(), [
            'vehicle_type' => ['required', 'string'],
            'vehicle_model' => ['required', 'string'],
            'vehicle_color' => ['required', 'string'],
            'insurance_number' => ['required', 'string'],
            'manufacture_year' => ['required', 'string'],
            'max_occupancy' => ['required', 'integer'],
            'license_number' => ['required', 'string'],
            'vehicle_photos' => ['nullable', 'array'],
            'vehicle_photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:204800'],
            'license_front' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:204800'],
            'license_back' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:204800'],
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $vehicle = $user->vehicleInformation;
            if (!$vehicle) {
                $vehicle = $user->vehicleInformation()->create([]);
            }

            $updateData = $request->only([
                'vehicle_type', 'vehicle_model', 'vehicle_color', 
                'insurance_number', 'manufacture_year', 'max_occupancy', 'license_number'
            ]);

            if ($request->hasFile('vehicle_photos')) {
                $vehiclePhotos = [];
                foreach ($request->file('vehicle_photos') as $index => $file) {
                    $docUrl = Helper::fileUpload($file, 'documents', $user->name . "-vehicle-photo-" . $index . "-" . time());
                    if ($docUrl) {
                        $vehiclePhotos[] = $docUrl;
                    }
                }
                $updateData['vehicle_photos'] = $vehiclePhotos;
            }

            if ($request->hasFile('license_front')) {
                $docUrl = Helper::fileUpload($request->file('license_front'), 'documents', $user->name . "-license-front-" . time());
                if ($docUrl) {
                    $updateData['license_front'] = $docUrl;
                }
            }

            if ($request->hasFile('license_back')) {
                $docUrl = Helper::fileUpload($request->file('license_back'), 'documents', $user->name . "-license-back-" . time());
                if ($docUrl) {
                    $updateData['license_back'] = $docUrl;
                }
            }

            $vehicle->update($updateData);
            DB::commit();
            $user->load('vehicleInformation', 'drivingInformation');

            return $this->success([
                'user' => new UserResource($user),
            ], 'Vehicle information updated successfully');
        }
        catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to update vehicle information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update rider driving information
     */
    public function updateDriving(Request $request)
    {
        $user = auth('api')->user() ?? Auth::user();

        $validation = Validator::make($request->all(), [
            'driving_license' => ['required', 'string'],
            'driving_experience' => ['required', 'string'],
            'police_reports' => ['nullable', 'array'],
            'police_reports.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:204800'],
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $driving = $user->drivingInformation;
            if (!$driving) {
                $driving = $user->drivingInformation()->create([]);
            }

            $updateData = $request->only(['driving_license', 'driving_experience']);

            if ($request->hasFile('police_reports')) {
                $policeReports = [];
                foreach ($request->file('police_reports') as $index => $file) {
                    $docUrl = Helper::fileUpload($file, 'documents', $user->name . "-police-report-" . $index . "-" . time());
                    if ($docUrl) {
                        $policeReports[] = $docUrl;
                    }
                }
                $updateData['police_reports'] = $policeReports;
            }

            $driving->update($updateData);
            DB::commit();
            $user->load('vehicleInformation', 'drivingInformation');

            return $this->success([
                'user' => new UserResource($user),
            ], 'Driving information updated successfully');
        }
        catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to update driving information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change rider password
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
            $user = auth('api')->user() ?? Auth::user();
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
     * Delete rider account
     */
    public function deleteAccount()
    {
        $user = auth('api')->user() ?? Auth::user();
        try {
            $user->delete();
            return $this->success([], "Rider account deleted successfully", 200);
        }
        catch (Exception $e) {
            Log::error('Rider Account delete failed: ' . $e->getMessage());
            return $this->error([], 'Failed to delete account', 500);
        }
    }
    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'push_notification' => 'sometimes|boolean',
            'order_request_notification' => 'sometimes|boolean',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        try {
            $user = auth('api')->user() ?? Auth::user();
            $user->update($request->only(['push_notification', 'order_request_notification']));
            
            return $this->success([
                'push_notification' => (bool) $user->push_notification,
                'order_request_notification' => (bool) $user->order_request_notification
            ], 'Notification settings updated successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to update notification settings', 500);
        }
    }

    /**
     * Toggle online/offline status for rider
     */
    public function toggleStatus()
    {
        try {
            $user = auth('api')->user() ?? Auth::user();
            $user->is_online = !$user->is_online;
            $user->save();

            broadcast(new RiderStatusUpdated($user));

            return $this->success([
                'is_online' => (bool)$user->is_online
            ], 'Online status toggled successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to toggle status: ' . $e->getMessage(), 500);
        }
    }
}
