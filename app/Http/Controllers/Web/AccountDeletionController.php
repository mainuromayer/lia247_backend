<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\FirebaseToken;
use App\Models\Item;
use App\Models\Order;
use App\Models\Review;
use App\Models\Ride;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AccountDeletionController extends Controller
{
    /**
     * Helper to get system settings & branding information
     */
    protected function getBranding(): array
    {
        $setting = SystemSetting::first();

        // Determine application title
        $appName = !empty($setting?->system_title) && !in_array($setting->system_title, ['My System', ''])
            ? $setting->system_title
            : 'LIA 24/7';

        // Determine logo URL
        $logoUrl = null;
        if (!empty($setting?->logo) && !str_contains($setting->logo, 'nologo')) {
            $logoUrl = filter_var($setting->logo, FILTER_VALIDATE_URL) ? $setting->logo : asset($setting->logo);
        } else {
            $defaultLogo = 'uploads/systems/logo/logo.png';
            if (file_exists(public_path($defaultLogo))) {
                $logoUrl = asset($defaultLogo);
            }
        }

        return [
            'setting' => $setting,
            'appName' => $appName,
            'logoUrl' => $logoUrl,
        ];
    }

    /**
     * Display Login page or redirect to portal if already authenticated
     */
    public function index(Request $request)
    {
        $userId = $request->session()->get('mobile_delete_portal_user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                return redirect()->route('account.delete.portal');
            }
            $request->session()->forget('mobile_delete_portal_user_id');
        }

        $branding = $this->getBranding();

        return view('account_deletion.login', array_merge($branding, [
            'email' => $request->query('email', old('email', '')),
        ]));
    }

    /**
     * Authenticate mobile user credentials
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->email) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->withInput();
        }

        // Check if user was already deleted
        $trashedUser = User::withTrashed()->where('email', $request->email)->first();
        if ($trashedUser && $trashedUser->trashed()) {
            RateLimiter::hit($throttleKey);
            return back()->withErrors([
                'email' => 'This account has already been permanently deleted from our system.',
            ])->withInput();
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            return back()->withErrors([
                'email' => 'Invalid email or password. Please verify your credentials.',
            ])->withInput();
        }

        if ($user->status === 'suspended') {
            RateLimiter::hit($throttleKey);
            return back()->withErrors([
                'email' => 'Your account is suspended. Please contact customer support.',
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);

        // Put user ID into dedicated session
        $request->session()->put('mobile_delete_portal_user_id', $user->id);

        return redirect()->route('account.delete.portal');
    }

    /**
     * Account Overview and Deletion Portal view
     */
    public function portal(Request $request)
    {
        $userId = $request->session()->get('mobile_delete_portal_user_id');
        if (!$userId) {
            return redirect()->route('account.delete.index');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('mobile_delete_portal_user_id');
            return redirect()->route('account.delete.index')->withErrors([
                'email' => 'User account not found or has already been deleted.',
            ]);
        }

        $branding = $this->getBranding();

        // Calculate dynamic metrics
        $stats = [
            'stat1_label' => 'Orders Placed',
            'stat1_count' => 0,
            'stat1_type'  => 'orders',
            'stat2_label' => 'Rides & Deliveries',
            'stat2_count' => 0,
            'stat2_type'  => 'rides',
            'stat3_label' => 'Saved Favorites',
            'stat3_count' => 0,
            'stat3_type'  => 'favorites',
        ];

        try {
            if ($user->user_type === 'vendor') {
                $stats['stat1_label'] = 'Menu Items';
                $stats['stat1_count'] = Item::where('user_id', $user->id)->count();
                $stats['stat1_type']  = 'items';

                $stats['stat2_label'] = 'Orders Received';
                $stats['stat2_count'] = Order::where('vendor_id', $user->id)->count();
                $stats['stat2_type']  = 'orders';

                $stats['stat3_label'] = 'Customer Reviews';
                $stats['stat3_count'] = Review::where('target_id', $user->id)->count();
                $stats['stat3_type']  = 'reviews';
            } elseif (in_array($user->user_type, ['rider', 'delivery_man'])) {
                $stats['stat1_label'] = 'Completed Deliveries';
                $stats['stat1_count'] = Order::where('delivery_man_id', $user->id)->whereIn('status', ['completed', 'delivered'])->count();
                $stats['stat1_type']  = 'orders';

                $stats['stat2_label'] = 'Rides Completed';
                $stats['stat2_count'] = Ride::where('rider_id', $user->id)->where('status', 'completed')->count();
                $stats['stat2_type']  = 'rides';

                $stats['stat3_label'] = 'Reviews & Ratings';
                $stats['stat3_count'] = Review::where('target_id', $user->id)->count();
                $stats['stat3_type']  = 'reviews';
            } else {
                // Customer or general app user
                $stats['stat1_count'] = Order::where('user_id', $user->id)->count();
                $stats['stat2_count'] = Ride::where('user_id', $user->id)->count();
                $stats['stat3_count'] = Favorite::where('user_id', $user->id)->count();
            }
        } catch (\Throwable $e) {
            Log::warning('Error loading user stats for deletion portal: ' . $e->getMessage());
        }

        // Parse first and last name
        $nameParts = explode(' ', trim($user->name ?? 'User'), 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName  = $nameParts[1] ?? '';

        return view('account_deletion.portal', array_merge($branding, [
            'user'      => $user,
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'stats'     => $stats,
        ]));
    }

    /**
     * Execute permanent account deletion
     */
    public function confirmDelete(Request $request)
    {
        $userId = $request->session()->get('mobile_delete_portal_user_id');
        if (!$userId) {
            return redirect()->route('account.delete.index');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget('mobile_delete_portal_user_id');
            return redirect()->route('account.delete.index');
        }

        // Verify password if user has one configured
        if (!empty($user->password)) {
            $request->validate([
                'password' => 'required|string',
            ]);

            if (!Hash::check($request->password, $user->password)) {
                return back()->with('error', 'Incorrect password. Account deletion was cancelled for your security.');
            }
        }

        try {
            $originalEmail = $user->email;

            // Execute deletion through UserDeletionService (anonymizes email to delete_user_{id}@gmail.com,
            // resets password to 12345678, sets status to inactive, deletes push tokens, and soft-deletes)
            \App\Services\UserDeletionService::deleteUser($user);

            Log::info("Mobile portal: Account successfully deleted for User #{$user->id} ({$originalEmail})");

            $request->session()->forget('mobile_delete_portal_user_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $branding = $this->getBranding();
            return view('account_deletion.success', array_merge($branding, [
                'deletedEmail' => $originalEmail,
            ]));
        } catch (\Exception $e) {
            Log::error('Mobile portal: Account deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete account. Please try again or contact support.');
        }
    }

    /**
     * Sign out of deletion portal
     */
    public function logout(Request $request)
    {
        $request->session()->forget('mobile_delete_portal_user_id');
        return redirect()->route('account.delete.index')->with('status', 'You have been signed out.');
    }
}
