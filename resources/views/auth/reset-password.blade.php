@extends('auth.app')

@section('title', 'Reset Password')

@section('content')
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row"></div>
            <div class="content-body">
                <div class="auth-wrapper auth-v1 px-2">
                    <div class="auth-inner py-2">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="javascript:void(0);" class="brand-logo d-flex align-items-center justify-content-center">
                                    <img src="{{ asset($setting->logo ?? 'backend/app-assets/images/logo/logo.png') }}" width="150" alt="logo">
                                </a>

                                <h4 class="card-title mb-1 text-center">Set New Password</h4>
                                <p class="card-text mb-2 text-center">Your OTP is verified. Please enter your new password.</p>

                                @if (session('status'))
                                    <div class="alert alert-success p-1 text-center mb-1">{{ session('status') }}</div>
                                @endif

                                <form class="auth-login-form mt-2" method="POST" action="{{ route('password.update.otp') }}">
                                    @csrf

                                    <div class="form-group mb-2">
                                        <label class="form-label">New Password</label>
                                        <div class="input-group input-group-merge form-password-toggle">
                                            <input type="password" name="password" class="form-control form-control-merge @error('password') is-invalid @enderror" required placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                            <div class="input-group-append">
                                                <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                            </div>
                                        </div>
                                        @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="input-group input-group-merge form-password-toggle">
                                            <input type="password" name="password_confirmation" class="form-control form-control-merge" required placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                                            <div class="input-group-append">
                                                <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-block">Reset Password & Login</button>
                                </form>

                                <p class="text-center mt-2">
                                    <a href="{{ route('login') }}"> <i data-feather="chevron-left"></i> Back to login </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection