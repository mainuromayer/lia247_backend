<?php

namespace App\Http\Controllers\API;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


class UserAuthController extends Controller
{
    use ApiResponse;

    // ------------------ Customer Registration ------------------
    // public function registerCustomer(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
    //         'name' => 'required|string|max:250',
    //         'email' => 'required|email|unique:users,email',
    //         'phone' => 'required|string|max:20',
    //         'password' => 'required|string|min:8|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error([], $validator->errors(), 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'phone' => $request->phone,
    //             'password' => Hash::make($request->password),
    //             'user_type' => 'customer',
    //             'status' => 'active',
    //             'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
    //         ]);

    //         // avatar upload
    //         if ($request->hasFile('avatar')) {
    //             $photoUrl = Helper::fileUpload($request->file('avatar'), 'users', $user->name . "-" . time());
    //             if ($photoUrl)
    //                 $user->update(['avatar' => $photoUrl]);
    //         }

    //         $this->generateOtp($user); // assuming you have this method

    //         DB::commit();
    //         return $this->success([
    //             'user' => $user->only('id', 'name', 'email', 'phone', 'user_type'),
    //             'otp' => $user->otp,
    //             'message' => 'Check your email/phone for OTP verification'
    //         ], 'Customer registered successfully', 201);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Customer Reg Error: ' . $e->getMessage());
    //         return $this->error([], 'Registration failed: ' . $e->getMessage(), 500);
    //     }
    // }

    // ------------------ Delivery Man Registration ------------------
    // public function registerDeliveryMan(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:250',
    //         'email' => 'required|email|unique:users,email',
    //         'address' => 'required|string',
    //         'nid' => 'required|string',
    //         'gender' => 'required|in:male,female,other',
    //         'date_of_birth' => 'required|date|date_format:Y-m-d',
    //         'vehicle_information_documents' => 'required|file|mimes:jpg,jpeg,png,pdf|max:204800',
    //         'vehicle_type' => 'required|string',
    //         'vehicle_model' => 'required|string',
    //         'vehicle_color' => 'required|string',
    //         'insurance_number' => 'required|string',
    //         'manufacture_year' => 'required|string',
    //         'license_number' => 'required|string',
    //         'driving_license' => 'required|file|mimes:jpg,jpeg,png,pdf|max:204800',
    //         'driving_experience' => 'required|string',
    //         'terms_and_conditions' => 'required|accepted',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error([], $validator->errors(), 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'street_address' => $request->address,
    //             'user_type' => 'delivery_man',
    //             'status' => 'active',
    //             'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
    //             'nid' => $request->nid,
    //             'gender' => $request->gender,
    //             'date_of_birth' => $request->date_of_birth,
    //             'vehicle_type' => $request->vehicle_type,
    //             'vehicle_model' => $request->vehicle_model,
    //             'vehicle_color' => $request->vehicle_color,
    //             'insurance_number' => $request->insurance_number,
    //             'manufacture_year' => $request->manufacture_year,
    //             'license_number' => $request->license_number,
    //             'driving_experience' => $request->driving_experience,
    //             'terms_and_conditions' => true,
    //         ]);

    //         // vehicle info document upload
    //         if ($request->hasFile('vehicle_information_documents')) {
    //             $vehicleDocUrl = Helper::fileUpload($request->file('vehicle_information_documents'), 'documents', $user->name . "-vehicle-" . time());
    //             if ($vehicleDocUrl)
    //                 $user->update(['vehicle_information_documents' => $vehicleDocUrl]);
    //         }
    //         // driving license upload
    //         if ($request->hasFile('driving_license')) {
    //             $licenseDocUrl = Helper::fileUpload($request->file('driving_license'), 'documents', $user->name . "-license-" . time());
    //             if ($licenseDocUrl)
    //                 $user->update(['driving_license_documents' => $licenseDocUrl]);
    //         }

    //         $this->generateOtp($user);

    //         DB::commit();
    //         return $this->success([
    //             'user' => $user->only('id', 'name', 'email', 'user_type'),
    //             'otp' => $user->otp,
    //             'message' => 'Registration successful. Check your email for OTP verification to set your password.'
    //         ], 'Delivery man registered successfully', 201);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Delivery Man Reg Error: ' . $e->getMessage());
    //         return $this->error([], 'Registration failed: ' . $e->getMessage(), 500);
    //     }
    // }

    // ------------------ Rider Registration ------------------
    // public function registerRider(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:250',
    //         'email' => 'required|email|unique:users,email',
    //         'address' => 'required|string',
    //         'nid' => 'required|string',
    //         'gender' => 'required|in:male,female,other',
    //         'date_of_birth' => 'required|date|date_format:Y-m-d',
    //         'vehicle_information_documents' => 'required|file|mimes:jpg,jpeg,png,pdf|max:204800',
    //         'vehicle_type' => 'required|string',
    //         'vehicle_model' => 'required|string',
    //         'vehicle_color' => 'required|string',
    //         'insurance_number' => 'required|string',
    //         'manufacture_year' => 'required|string',
    //         'max_occupancy' => 'required|integer',
    //         'license_number' => 'required|string',
    //         'police_report_documents' => 'required|file|mimes:jpg,jpeg,png,pdf|max:204800',
    //         'driving_license' => 'required|file|mimes:jpg,jpeg,png,pdf|max:204800',
    //         'driving_experience' => 'required|string',
    //         'terms_and_conditions' => 'required|accepted',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error([], $validator->errors(), 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'street_address' => $request->address,
    //             'user_type' => 'rider',
    //             'status' => 'active',
    //             'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
    //             'nid' => $request->nid,
    //             'gender' => $request->gender,
    //             'date_of_birth' => $request->date_of_birth,
    //             'vehicle_type' => $request->vehicle_type,
    //             'vehicle_model' => $request->vehicle_model,
    //             'vehicle_color' => $request->vehicle_color,
    //             'insurance_number' => $request->insurance_number,
    //             'manufacture_year' => $request->manufacture_year,
    //             'max_occupancy' => $request->max_occupancy,
    //             'license_number' => $request->license_number,
    //             'driving_experience' => $request->driving_experience,
    //             'terms_and_conditions' => true,
    //         ]);

    //         // vehicle info document upload
    //         if ($request->hasFile('vehicle_information_documents')) {
    //             $vehicleDocUrl = Helper::fileUpload($request->file('vehicle_information_documents'), 'documents', $user->name . "-vehicle-" . time());
    //             if ($vehicleDocUrl)
    //                 $user->update(['vehicle_information_documents' => $vehicleDocUrl]);
    //         }
    //         // police report document upload
    //         if ($request->hasFile('police_report_documents')) {
    //             $policeDocUrl = Helper::fileUpload($request->file('police_report_documents'), 'documents', $user->name . "-police-" . time());
    //             if ($policeDocUrl)
    //                 $user->update(['police_report_documents' => $policeDocUrl]);
    //         }
    //         // driving license upload
    //         if ($request->hasFile('driving_license')) {
    //             $licenseDocUrl = Helper::fileUpload($request->file('driving_license'), 'documents', $user->name . "-license-" . time());
    //             if ($licenseDocUrl)
    //                 $user->update(['driving_license_documents' => $licenseDocUrl]);
    //         }

    //         $this->generateOtp($user);

    //         DB::commit();
    //         return $this->success([
    //             'user' => $user->only('id', 'name', 'email', 'user_type'),
    //             'otp' => $user->otp,
    //             'message' => 'Registration successful. Check your email for OTP verification to set your password.'
    //         ], 'Rider registered successfully', 201);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Rider Reg Error: ' . $e->getMessage());
    //         return $this->error([], 'Registration failed: ' . $e->getMessage(), 500);
    //     }
    // }

    // ------------------ Vendor Registration ------------------
    // public function registerVendor(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:250',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:8|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->error([], $validator->errors(), 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $user = User::create([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'password' => Hash::make($request->password),
    //             'user_type' => 'vendor',
    //             'status' => 'active',
    //             'avatar' => 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg',
    //         ]);

    //         $this->generateOtp($user);

    //         DB::commit();
    //         return $this->success([
    //             'user' => $user->only('id', 'name', 'email', 'user_type'),
    //             'otp' => $user->otp,
    //             'message' => 'Check your email/phone for OTP verification'
    //         ], 'Vendor registered successfully', 201);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Vendor Reg Error: ' . $e->getMessage());
    //         return $this->error([], 'Registration failed: ' . $e->getMessage(), 500);
    //     }
    // }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
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
            $msg = 'Account is inactive. Please contact admin.';
            if ($user->status === 'pending') {
                $msg = 'Your account is pending. Please wait for admin approval.';
            } elseif ($user->status === 'suspended') {
                $msg = 'Account is suspended. Please contact admin.';
            }
            return $this->error([], $msg, 403);
        }

        if ($user->email_verified_at == null) {
            $this->generateOtp($user);
            return $this->success([], 'Check your email to verify your account', 200);
        }

        return $this->success([
            'token' => $this->respondWithToken($token),
            'user' => $user->only('id', 'name', 'email', 'phone', 'user_type'),
        ], 'User logged in successfully.', 200);
    }


    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $this->generateOtp($user);
        return $this->success([
            'email' => $user->email,
            'otp' => $user->otp,
        ], 'Check your email for password reset OTP', 200);
    }

    /**
     * Reset Password Controller
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

            if (!$user) {
                return $this->error([], 'User not found', 404);
            }

            if ($payload['sub'] != $user->id || ($payload['action'] != 'set_password' && $payload['action'] != 'forgot_password')) {
                return $this->error([], 'Invalid or expired set token.', 400);
            }
        }
        catch (JWTException $e) {
            return $this->error([], 'Invalid or expired set token.', 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        if ($user->email_verified_at == null) {
            $user->email_verified_at = now();
            $user->save();
        }

        // Invalidate the set_token so it cannot be used again
        try {
            JWTAuth::invalidate($request->set_token);
        }
        catch (JWTException $e) {
        // Token might already be invalid, safely ignore
        }

        $token = JWTAuth::fromUser($user);

        return $this->success([
            'token' => $this->respondWithToken($token),
            'user' => $user->only('id', 'name', 'email', 'date_of_birth'),
            'email_verified_at' => $user->email_verified_at
        ], 'Password set and user authenticated successfully.', 200);
    }

    // Resend Otp
    public function resendOtp(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'action' => 'required|in:email_verification,forgot_password',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        // Check for email verification
        if ($request->action === 'email_verification') {
            if ($user->email_verified_at) {
                return $this->error([], 'Email is already verified.', 400);
            }
            $this->generateOtp($user);
            return $this->success([
                'email' => $user->email,
                'otp' => $user->otp,
            ], 'OTP for email verification resent successfully. Check your email.', 200);
        }

        // Check for forgot password
        if ($request->action === 'forgot_password') {
            $this->generateOtp($user);
            return $this->success([
                'email' => $user->email,
                'otp' => $user->otp,
            ], 'OTP for password reset resent successfully. Check your email.', 200);
        }

        return $this->error([], 'Invalid action specified.', 400);
    }


    public function varifyOtpWithOutAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:4',
            'action' => 'required|in:email_verification,forgot_password',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        if ($request->action == 'email_verification') {
            if ($user->email_verified_at) {
                return $this->error([], 'Email already verified.', 400);
            }
            if ((int)$request->otp !== (int)$user->otp) {
                return $this->error([], 'Invalid OTP!', 400);
            }
            if ($user->otp_created_at && now()->gt(Carbon::parse($user->otp_created_at)->addMinutes(15))) {
                return $this->error([], 'OTP has expired.', 400);
            }

            $user->otp = null;
            $user->otp_created_at = null;
            $user->otp_expires_at = null;
            $user->email_verified_at = now();
            $user->save();

            if (in_array($user->user_type, ['delivery_man', 'rider']) && empty($user->password)) {
                $setToken = JWTAuth::customClaims(['action' => 'set_password'])->fromUser($user);

                return $this->success([
                    'set_token' => $setToken,
                    'user' => $user->only('id', 'name', 'email', 'user_type', 'email_verified_at'),
                ], 'Email verified successfully. Please set your password.', 200);
            }

            $token = JWTAuth::fromUser($user);

            return $this->success([
                'token' => $this->respondWithToken($token),
                'user' => $user->only('id', 'name', 'email', 'user_type', 'email_verified_at'),
            ], 'Email verified successfully', 201);
        }

        if ($request->action == 'forgot_password') {
            if ((int)$request->otp !== (int)$user->otp) {
                return $this->error([], 'Invalid OTP!', 400);
            }
            if ($user->otp_created_at && now()->gt(Carbon::parse($user->otp_created_at)->addMinutes(15))) {
                return $this->error([], 'OTP has expired.', 400);
            }
            $user->otp = null;
            $user->otp_created_at = null;
            $user->otp_expires_at = null;
            $user->save();

            $setToken = JWTAuth::customClaims(['action' => 'forgot_password'])->fromUser($user);

            return $this->success([
                'set_token' => $setToken,
                'user' => $user->only('id', 'name', 'email', 'phone', 'user_type', 'email_verified_at'),
            ], 'OTP verified successfully', 201);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            auth()->logout();
            return $this->success([], 'Successfully logged out', 200);
        }
        catch (Exception $e) {
            return $this->error([], "Logout failed, please try again.", 400);
        }
    }

    public function me()
    {
        dd(Auth::user());
        $user = Auth::user();

        return $this->success([
            'id' => $user->id,
            'user_type' => $user->user_type,
            'avatar' => $user->avatar,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ], 'User retrieved successfully', 200);
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return $this->success([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ], 'Token refreshed successfully', 200);
        }
        catch (Exception $e) {
            return $this->error([], $e->getMessage(), 400);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }


}
