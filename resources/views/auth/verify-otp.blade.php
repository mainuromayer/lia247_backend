@extends('auth.app')

@section('title', 'Login')

@push('style')
@endpush

@section('content')
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <div class="auth-wrapper auth-v1 px-2">
                    <div class="auth-inner py-2">

                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="javascript:void(0);"
                                    class="brand-logo d-flex align-items-center justify-content-center">
                                    <img src="{{ asset($setting->logo ?? 'backend/app-assets/images/logo/logo.png') }}"
                                        width="150" alt="logo">
                                    {{-- <h2 class="brand-text text-primary ml-1">{{ $setting->title ?? 'Prayer App' }}</h2>
                                    --}}
                                </a>

                                <form class="auth-login-form mt-2" action="{{ route('password.otp.verify') }}"
                                    method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="login-email" class="form-label">Verify OTP</label>
                                        <p>Enter the 6-digit code sent to
                                            <strong>{{ session('email') ?? old('email') }}</strong>
                                        </p>
                                        @if (session('status'))
                                            <div class="alert alert-success">{{ session('status') }}</div>
                                        @endif


                                        <input type="text" name="otp"
                                            class="form-control @error('otp') is-invalid @enderror" required
                                            autocomplete="one-time-code" inputmode="numeric" pattern="\d{6}">
                                        @error('otp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>


                                    <button class="btn btn-primary btn-block" tabindex="4"> Verify OTP</button>
                                </form>


                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
@endpush
