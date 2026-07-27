<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SocialiteController extends Controller
{
    use ApiResponse;

    public function callback(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'provider' => 'required|in:google,apple',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', $validator->errors()->all(), 422);
        }

        try {
            $provider = $request->provider;
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->token);

            if (!$socialUser) {
                return $this->error('Invalid credentials', ['Unable to authenticate with provided token'], 401);
            }

            // Find or create user
            $user = User::where('email', $socialUser->email)
                ->orWhere('provider_id', $socialUser->getId())
                ->first();

            $isNewUser = false;

            if (!$user) {
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Unknown',
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_refresh_token' => $socialUser->refreshToken,
                    'email_verified_at' => now(),
                ]);
                $isNewUser = true;
            }
            else {
                // Update provider refresh token if user exists
                $user->update([
                    'provider_refresh_token' => $socialUser->refreshToken,
                ]);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Prepare success response
            $success = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];

            $message = $isNewUser ? 'User registered and logged in successfully' : 'User logged in successfully';

            return $this->success([
                'token' => $token,
                'data' => $success,
            ], $message);
        }
        catch (Exception $e) {
            return $this->error('Authentication failed', [$e->getMessage()], 401);
        }
    }
}