@extends('backend.app')

@section('title', 'Integration Settings')

@section('content')
    <!--app-content open-->
    <div class="app-content content">
        <div class="side-app">
            <div class="main-container container-fluid">
                <div class="row" id="user-profile">
                    <div class="col-lg-12">
                        <div class="card mt-4">
                            <div class="card-body">
                                <h4 class="card-title">Integration & API Settings</h4>
                                <p class="card-description">Manage external integration credentials. Changing these values will update your <code>.env</code> file directly.</p>

                                <form class="forms-sample" action="{{ route('admin.setting.integration.store') }}" method="POST">
                                    @csrf

                                    <!-- General Settings -->
                                    <h5 class="text-primary mb-3 mt-4"><i class="fa-solid fa-globe me-1"></i> General Settings</h5>
                                    <div class="form-group row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">FRONTEND URL</label>
                                            <input type="text" class="form-control form-control-md @error('frontend_url') is-invalid @enderror" 
                                                   name="frontend_url" value="{{ old('frontend_url', env('FRONTEND_URL')) }}" required>
                                            @error('frontend_url')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">GOOGLE MAPS API KEY</label>
                                            <input type="text" class="form-control form-control-md @error('google_maps_api_key') is-invalid @enderror" 
                                                   name="google_maps_api_key" value="{{ old('google_maps_api_key', env('GOOGLE_MAPS_API_KEY')) }}">
                                            @error('google_maps_api_key')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Stripe Settings -->
                                    <h5 class="text-primary mb-3"><i class="fa-solid fa-credit-card me-1"></i> Stripe Configuration</h5>
                                    <div class="form-group row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">STRIPE KEY</label>
                                            <input type="text" class="form-control form-control-md @error('stripe_key') is-invalid @enderror" 
                                                   name="stripe_key" value="{{ old('stripe_key', env('STRIPE_KEY')) }}">
                                            @error('stripe_key')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">STRIPE SECRET</label>
                                            <input type="text" class="form-control form-control-md @error('stripe_secret') is-invalid @enderror" 
                                                   name="stripe_secret" value="{{ old('stripe_secret', env('STRIPE_SECRET')) }}">
                                            @error('stripe_secret')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">STRIPE WEBHOOK SECRET</label>
                                            <input type="text" class="form-control form-control-md @error('stripe_webhook_secret') is-invalid @enderror" 
                                                   name="stripe_webhook_secret" value="{{ old('stripe_webhook_secret', env('STRIPE_WEBHOOK_SECRET')) }}">
                                            @error('stripe_webhook_secret')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">CASHIER CURRENCY</label>
                                            <input type="text" class="form-control form-control-md @error('cashier_currency') is-invalid @enderror" 
                                                   name="cashier_currency" value="{{ old('cashier_currency', env('CASHIER_CURRENCY', 'usd')) }}" required>
                                            @error('cashier_currency')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">WEBHOOK REDIRECT URL</label>
                                            <input type="text" class="form-control form-control-md @error('webhook_redirect_url') is-invalid @enderror" 
                                                   name="webhook_redirect_url" value="{{ old('webhook_redirect_url', env('WEBHOOK_REDIRECT_URL')) }}">
                                            @error('webhook_redirect_url')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Reverb Settings -->
                                    <h5 class="text-primary mb-3"><i class="fa-solid fa-tower-broadcast me-1"></i> Laravel Reverb (WebSockets) Settings</h5>
                                    <div class="form-group row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">REVERB APP ID</label>
                                            <input type="text" class="form-control form-control-md @error('reverb_app_id') is-invalid @enderror" 
                                                   name="reverb_app_id" value="{{ old('reverb_app_id', env('REVERB_APP_ID')) }}">
                                            @error('reverb_app_id')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">REVERB APP KEY</label>
                                            <input type="text" class="form-control form-control-md @error('reverb_app_key') is-invalid @enderror" 
                                                   name="reverb_app_key" value="{{ old('reverb_app_key', env('REVERB_APP_KEY')) }}">
                                            @error('reverb_app_key')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">REVERB APP SECRET</label>
                                            <input type="text" class="form-control form-control-md @error('reverb_app_secret') is-invalid @enderror" 
                                                   name="reverb_app_secret" value="{{ old('reverb_app_secret', env('REVERB_APP_SECRET')) }}">
                                            @error('reverb_app_secret')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">REVERB HOST</label>
                                            <input type="text" class="form-control form-control-md @error('reverb_host') is-invalid @enderror" 
                                                   name="reverb_host" value="{{ old('reverb_host', env('REVERB_HOST')) }}">
                                            @error('reverb_host')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">REVERB PORT</label>
                                            <input type="text" class="form-control form-control-md @error('reverb_port') is-invalid @enderror" 
                                                   name="reverb_port" value="{{ old('reverb_port', env('REVERB_PORT')) }}">
                                            @error('reverb_port')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">REVERB SCHEME</label>
                                            <select class="form-select form-control-md @error('reverb_scheme') is-invalid @enderror" name="reverb_scheme">
                                                <option value="http" {{ old('reverb_scheme', env('REVERB_SCHEME')) == 'http' ? 'selected' : '' }}>http</option>
                                                <option value="https" {{ old('reverb_scheme', env('REVERB_SCHEME')) == 'https' ? 'selected' : '' }}>https</option>
                                            </select>
                                            @error('reverb_scheme')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">REVERB SERVER PORT</label>
                                            <input type="text" class="form-control form-control-md @error('reverb_server_port') is-invalid @enderror" 
                                                   name="reverb_server_port" value="{{ old('reverb_server_port', env('REVERB_SERVER_PORT')) }}">
                                            @error('reverb_server_port')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary me-2">Save Changes</button>
                                        <a href="{{ route('admin.dashboard') }}" class="btn btn-light">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
