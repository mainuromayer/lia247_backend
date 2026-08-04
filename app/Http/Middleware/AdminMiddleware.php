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
        $user = auth()->user();
        if (!auth()->check() || (!$user->is_admin && !in_array($user->user_type, ['admin', 'sub_admin']) && !$user->hasAnyRole(['admin', 'sub_admin']))) {
            auth()->logout();
            return redirect()->route('login')->with('t-error', 'Unauthorized access! Only admin users can access admin dashboard.');
        }
        return $next($request);
    }
}
