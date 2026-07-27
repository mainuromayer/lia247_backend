<?php

namespace App\Http\Controllers\API\V1\DeliveryMan\Auth;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Requests\V1\DeliveryMan\Auth\RegisterRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use App\Actions\GeoLocation;

use App\Http\Resources\V1\DeliveryMan\UserResource;

class DeliveryManAuthController extends Controller
{
    use ApiResponse;

    private $geoLocation;

    public function __construct()
    {
        $this->geoLocation = new GeoLocation();
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1. Get Coordinates from Address
            $coords = $this->geoLocation->getCoordinates($request->address);

            // 2. Create User
            $user = User::create([
                'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
                'phone' => $request->phone,
                'user_type' => 'delivery_man',
                'status' => 'pending',
                'nid' => $request->nid,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'terms_and_conditions' => $request->boolean('terms_and_conditions'),
            ]);

            // 2. Create Vehicle Information
            $vehicle = $user->vehicleInformation()->create([
                'vehicle_type' => $request->vehicle_type,
                'vehicle_model' => $request->vehicle_model,
                'vehicle_color' => $request->vehicle_color,
                'insurance_number' => $request->insurance_number,
                'manufacture_year' => $request->manufacture_year,
                'max_occupancy' => $request->max_occupancy,
                'license_number' => $request->license_number,
            ]);

            // 3. Create Driving Information
            $driving = $user->drivingInformation()->create([
                'driving_license' => $request->driving_license,
                'driving_experience' => $request->driving_experience,
            ]);

            // delivery man avatar upload
            if ($request->hasFile('avatar')) {
                $avatarDocUrl = Helper::fileUpload($request->file('avatar'), 'documents', $user->name . "-avatar-" . time());
                if ($avatarDocUrl)
                    $user->update(['avatar' => $avatarDocUrl]);
            }

            // vehicle info documents upload (new migration columns)
            if ($request->hasFile('vehicle_photos')) {
                $vehiclePhotos = [];
                foreach ($request->file('vehicle_photos') as $index => $file) {
                    $docUrl = Helper::fileUpload($file, 'documents', $user->name . "-vehicle-photo-" . $index . "-" . time());
                    if ($docUrl) {
                        $vehiclePhotos[] = $docUrl;
                    }
                }
                $vehicle->update(['vehicle_photos' => $vehiclePhotos]);
            }

            if ($request->hasFile('license_front')) {
                $docUrl = Helper::fileUpload($request->file('license_front'), 'documents', $user->name . "-license-front-" . time());
                if ($docUrl) {
                    $vehicle->update(['license_front' => $docUrl]);
                }
            }

            if ($request->hasFile('license_back')) {
                $docUrl = Helper::fileUpload($request->file('license_back'), 'documents', $user->name . "-license-back-" . time());
                if ($docUrl) {
                    $vehicle->update(['license_back' => $docUrl]);
                }
            }

            // police report documents upload (new migration columns)
            if ($request->hasFile('police_reports')) {
                $policeReports = [];
                foreach ($request->file('police_reports') as $index => $file) {
                    $docUrl = Helper::fileUpload($file, 'documents', $user->name . "-police-report-" . $index . "-" . time());
                    if ($docUrl) {
                        $policeReports[] = $docUrl;
                    }
                }
                $driving->update(['police_reports' => $policeReports]);
            }

            $this->generateOtp($user);

            DB::commit();
            $user->load(['vehicleInformation', 'drivingInformation']);
            return $this->success([
                'user' => new UserResource($user),
                'otp' => $user->otp,
                'message' => 'Registration successful. Check your email for OTP verification to set your password.'
            ], 'Delivery driver registered successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->where('user_type', 'delivery_man')->first();
        if (!$user) {
            return $this->error([], 'Invalid Credentials', 401);
        }

        if (!$user->password) {
            $this->generateOtp($user);
            return $this->success([
                'email' => $user->email,
                'otp' => $user->otp,
            ], 'Check your email to verify and set your password', 200);
        }

        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->error([], 'Incorrect email or password', 401);
        }

        if ($user->status !== 'active') {
            return $this->error([], 'Your account is pending or inactive. Please contact admin.', 403);
        }

        if ($user->email_verified_at == null) {
            $this->generateOtp($user);
            return $this->success([], 'Check your email to verify your account', 200);
        }

        return $this->success([
            'token' => $this->respondWithToken($token),
            'user' => new UserResource($user),
        ], 'Delivery driver logged in successfully.', 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->where('user_type', 'delivery_man')->first();
        if (!$user) {
            return $this->error([], 'Delivery driver not found', 404);
        }

        $this->generateOtp($user);
        return $this->success([
            'email' => $user->email,
            'otp' => $user->otp,
        ], 'Check your email for password reset OTP', 200);
    }

    public function setPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
            'set_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        try {
            JWTAuth::setToken($request->set_token);
            $payload = JWTAuth::getPayload();
            $user = User::find($payload['sub']);

            if (!$user || $user->user_type !== 'delivery_man') {
                return $this->error([], 'Delivery driver not found', 404);
            }

            if ($payload['sub'] != $user->id || ($payload['action'] != 'set_password' && $payload['action'] != 'forgot_password')) {
                return $this->error([], 'Invalid or expired set token.', 400);
            }
        } catch (JWTException $e) {
            return $this->error([], 'Invalid or expired set token.', 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        if ($user->email_verified_at == null) {
            $user->email_verified_at = now();
            $user->save();
        }

        try {
            JWTAuth::invalidate($request->set_token);
        } catch (JWTException $e) {
        }

        $token = JWTAuth::fromUser($user);
        $user->load(['vehicleInformation', 'drivingInformation']);
        return $this->success([
            'token' => $this->respondWithToken($token),
            'user' => new UserResource($user),
        ], 'Password set successfully.', 200);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'action' => 'required|in:email_verification,forgot_password',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->where('user_type', 'delivery_man')->first();
        if (!$user) {
            return $this->error([], 'Delivery driver not found', 404);
        }

        $this->generateOtp($user);
        return $this->success([
            'email' => $user->email,
            'otp' => $user->otp,
        ], 'OTP resent successfully.', 200);
    }

    public function varifyOtpWithOutAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:4',
            'action' => 'required|in:email_verification,forgot_password',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->where('user_type', 'delivery_man')->first();
        if (!$user) {
            return $this->error([], 'Delivery driver not found', 404);
        }

        if ((int) $request->otp !== (int) $user->otp) {
            return $this->error([], 'Invalid OTP!', 400);
        }

        if ($user->otp_created_at && now()->gt(Carbon::parse($user->otp_created_at)->addMinutes(15))) {
            return $this->error([], 'OTP has expired.', 400);
        }

        $user->otp = null;
        $user->otp_created_at = null;
        $user->save();

        if ($request->action == 'email_verification') {
            $user->email_verified_at = now();
            $user->save();

            if (empty($user->password)) {
                $setToken = JWTAuth::customClaims(['action' => 'set_password'])->fromUser($user);
                $user->load(['vehicleInformation', 'drivingInformation']);
                return $this->success([
                    'set_token' => $setToken,
                    'user' => new UserResource($user),
                ], 'Email verified successfully. Please set your password.', 200);
            }

            $token = JWTAuth::fromUser($user);
            return $this->success([
                'token' => $this->respondWithToken($token),
                'user' => new UserResource($user),
            ], 'Email verified successfully', 200);
        }

        if ($request->action == 'forgot_password') {
            $setToken = JWTAuth::customClaims(['action' => 'forgot_password'])->fromUser($user);
            return $this->success([
                'set_token' => $setToken,
                'user' => new UserResource($user),
            ], 'OTP verified successfully', 200);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->success([], 'Successfully logged out', 200);
        } catch (Exception $e) {
            return $this->error([], "Logout failed", 400);
        }
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return $this->success([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ], 'Token refreshed successfully', 200);
        } catch (Exception $e) {
            return $this->error([], $e->getMessage(), 400);
        }
    }

    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }
}
