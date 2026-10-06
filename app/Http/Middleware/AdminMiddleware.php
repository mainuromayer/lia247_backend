<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            auth()->logout();
            $msg = 'Account is inactive. Please contact admin.';
            if ($user->status === 'pending') {
                $msg = 'Your account is pending. Please wait for admin approval.';
            } elseif ($user->status === 'suspended') {
                $msg = 'Account is suspended. Please contact admin.';
            }
            return redirect()->route('login')->with('t-error', $msg);
        }

        $isAdminOrSubAdmin = $user->is_admin 
            || in_array($user->user_type, ['admin', 'sub_admin']) 
            || $user->hasAnyRole(['admin', 'sub_admin']);

        if (!$isAdminOrSubAdmin) {
            auth()->logout();
            return redirect()->route('login')->with('t-error', 'Unauthorized access! Only admin and sub_admin can access the admin dashboard.');
        }
        return $next($request);
    }
}
