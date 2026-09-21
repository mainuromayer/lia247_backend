<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;


class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['status' => 'Unauthorised'], 401);
            }
            if ($user->status !== 'active') {
                $msg = 'Account is inactive. Please contact admin.';
                if ($user->status === 'pending') {
                    $msg = 'Your account is pending. Please wait for admin approval.';
                } elseif ($user->status === 'suspended') {
                    $msg = 'Account is suspended. Please contact admin.';
                }
                return response()->json(['status' => $msg], 403);
            }
            auth()->shouldUse('api');
            auth('api')->setUser($user);
            \Illuminate\Support\Facades\Auth::setUser($user);
            return $next($request);
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return response()->json(['status' => 'Token is Invalid'], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json(['status' => 'Token is Expired'], 401);
            } else {
                return response()->json(['status' => 'Unauthorised'], 401);
            }
        }
    }
}
