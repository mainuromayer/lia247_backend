<?php

namespace App\Http\Controllers\API\V1\Customer\Auth;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Requests\V1\Customer\Auth\RegisterRequest;
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

use App\Http\Resources\V1\Customer\UserResource;

class CustomerAuthController extends Controller
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
            $address = null;
            if ($request->latitude && $request->longitude) {
                $address = $this->geoLocation->getAddress($request->latitude, $request->longitude);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'address' => $address,
                'user_type' => 'customer',
                'status' => 'active',
            ]);

            $this->generateOtp($user);

            DB::commit();
            return $this->success([
                'user' => new UserResource($user),
                'otp' => $user->otp,
                'message' => 'Check your email/phone for OTP verification'
            ], 'Customer registered successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Customer Reg Error: ' . $e->getMessage());
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

        $user = User::where('email', $request->email)->where('user_type', 'customer')->first();
        if (!$user) {
            return $this->error([], 'Customer not found', 404);
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
        ], 'Customer logged in successfully.', 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->where('user_type', 'customer')->first();
        if (!$user) {
            return $this->error([], 'Customer not found', 404);
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

            if (!$user || $user->user_type !== 'customer') {
                return $this->error([], 'Customer not found', 404);
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

        $user = User::where('email', $request->email)->where('user_type', 'customer')->first();
        if (!$user) {
            return $this->error([], 'Customer not found', 404);
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

        $user = User::where('email', $request->email)->where('user_type', 'customer')->first();
        if (!$user) {
            return $this->error([], 'Customer not found', 404);
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
                'expires_in' => auth('api')->factory()->getTTL() * 60,
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
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
