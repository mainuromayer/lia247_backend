<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ForgotPasswordOtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        if (!$user || (!$user->is_admin && $user->user_type !== 'admin')) {
            throw ValidationException::withMessages([
                'email' => 'This email does not belong to an admin account.',
            ]);
        }

        // Generate 4-digit OTP
        $otp = rand(1000, 9999);

        $user->otp = $otp;
        $user->otp_created_at = now();
        $user->save();

        // Send email
        Mail::to($user->email)->queue(new OtpMail($user, $otp));

        return redirect()
            ->route('password.otp.verify.page')
            ->with('email', $request->email)
            ->with('status', 'OTP sent! Check your email.');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email'  => 'required|email|exists:users,email',
            'otp'    => 'required|digits:4',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user->otp || $user->otp != $request->otp) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP']);
        }

        if (now()->gt(Carbon::parse($user->otp_created_at)->addMinutes(15))) {
            throw ValidationException::withMessages(['otp' => 'OTP has expired']);
        }

        // Clear OTP
        $user->otp = null;
        $user->otp_created_at = null;
        $user->save();

        // Allow reset for next 10 minutes
        session(['password_reset_allowed' => true]);
        session(['reset_email' => $user->email]);

        return redirect()->route('password.reset.otp')
            ->with('status', 'OTP verified! Set your new password.');
    }

    public function resetPassword(Request $request)
    {
        if (!session('password_reset_allowed') || !session('reset_email')) {
            return redirect()->route('password.request.otp')
                ->with('error', 'Session expired. Please start again.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user = User::where('email', session('reset_email'))->firstOrFail();

        if (!$user->is_admin && $user->user_type !== 'admin') {
            session()->forget(['password_reset_allowed', 'reset_email']);
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Clean session
        session()->forget(['password_reset_allowed', 'reset_email']);

        // Log in the user automatically
        Auth::login($user);

        return redirect()->route('admin.dashboard')
            ->with('status', 'Password has been reset successfully. Welcome to your dashboard.');
    }
}