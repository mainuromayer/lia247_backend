@extends('backend.app')

@section('title', 'Vendor Details')

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Vendor Business Profile</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.vendor.index') }}">Vendors</a></li>
                                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <div class="row">
                    <div class="col-12">
                        <!-- Business Cover -->
                        <div class="card premium-card overflow-hidden mb-2">
                            <div class="business-cover-photo">
                                @if($user->business && $user->business->cover_photo)
                                    <img src="{{ asset($user->business->cover_photo) }}" alt="cover" class="w-100 h-100 object-fit-cover">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                        <i class="fa-solid fa-image fa-4x opacity-25"></i>
                                    </div>
                                @endif
                                <div class="cover-overlay d-flex align-items-end p-2">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="avatar-wrapper shadow">
                                            <img src="https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg" alt="avatar" height="80" width="80" class="rounded">
                                        </div>
                                        <div class="ms-1 text-white shadow-text">
                                            <h3 class="mb-0 text-white fw-bold">{{ $user->business->business_name ?? 'N/A' }}</h3>
                                            <p class="mb-0 small"><i class="fa-solid fa-user me-50"></i> Owner: {{ $user->name }}</p>
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge {{ $user->status == 'active' ? 'bg-success' : 'bg-warning' }} shadow">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Owner Info -->
                    <div class="col-md-4">
                        <div class="card premium-card border-top-primary border-3">
                            <div class="card-header bg-transparent border-bottom">
                                <h4 class="card-title text-primary"><i class="fa-solid fa-user-tie me-1"></i> Owner Details</h4>
                            </div>
                            <div class="card-body pt-2">
                                <div class="info-list">
                                    <div class="mb-1">
                                        <label class="text-muted extra-small text-uppercase fw-bold d-block">Full Name</label>
                                        <span class="h6">{{ $user->name }}</span>
                                    </div>
                                    <div class="mb-1">
                                        <label class="text-muted extra-small text-uppercase fw-bold d-block">Email Address</label>
                                        <span class="h6">{{ $user->email }}</span>
                                    </div>
                                    <div class="mb-1">
                                        <label class="text-muted extra-small text-uppercase fw-bold d-block">Account Status</label>
                                        <span class="badge bg-light-{{ $user->status == 'active' ? 'success' : 'warning' }} rounded-pill">{{ ucfirst($user->status) }}</span>
                                    </div>
                                    <div class="mb-0">
                                        <label class="text-muted extra-small text-uppercase fw-bold d-block">Joined Date</label>
                                        <span class="h6">{{ $user->created_at->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="mt-2 border-top pt-2 d-flex gap-1">
                                    <a href="{{ route('admin.vendor.edit', $user->id) }}" class="btn btn-primary btn-sm flex-grow-1">Edit Profile</a>
                                    <a href="{{ route('admin.vendor.index') }}" class="btn btn-outline-secondary btn-sm flex-grow-1">Rideshare Driver List</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Details -->
                    <div class="col-md-8">
                        @if($user->business)
                            <div class="card premium-card border-top-info border-3 mb-2">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-info"><i class="fa-solid fa-briefcase me-1"></i> Business Details</h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Business Phone</p>
                                            <p class="h6">{{ $user->business->business_phone }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">License Number</p>
                                            <p class="h6">{{ $user->business->license_number }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Opening Hours</p>
                                            <p class="h6"><i class="fa-regular fa-clock me-50"></i> {{ \Carbon\Carbon::parse($user->business->opening_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($user->business->closing_time)->format('h:i A') }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Business Address</p>
                                            <p class="h6">{{ $user->business->business_address }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 border-top pt-1">
                                        <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Business Description</p>
                                        <div class="small mt-50">
                                            {!! $user->business->business_description !!}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card premium-card border-top-warning border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-warning"><i class="fa-solid fa-earth-americas me-1"></i> Location Information</h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <p class="mb-50"><i class="fa-solid fa-location-dot text-danger me-1"></i> <strong>Coordinates:</strong> {{ $user->business->latitude }}, {{ $user->business->longitude }}</p>
                                            <p class="text-muted small">You can view this exact location on Google Maps for navigation and logistics verification.</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="https://www.google.com/maps?q={{ $user->business->latitude }},{{ $user->business->longitude }}" target="_blank" class="btn btn-warning shadow-sm">
                                                <i class="fa-solid fa-map-marked-alt me-1"></i> Google Maps
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-danger">
                                <h4 class="alert-heading">Missing Profile</h4>
                                <div class="alert-body">
                                    This vendor does not have a business profile associated with their account yet.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('style')
<style>
    .premium-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1); background: #fff; }
    .business-cover-photo { height: 260px; position: relative; border-radius: 12px; }
    .cover-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 100px; background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); }
    .avatar-wrapper { border: 4px solid #fff; border-radius: 10px; overflow: hidden; background: #fff; }
    .shadow-text { text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
    .extra-small { font-size: 0.7rem; letter-spacing: 0.5px; }
    .bg-light-success { background-color: rgba(40, 199, 111, 0.12) !important; color: #28c76f !important; }
    .bg-light-warning { background-color: rgba(255, 159, 67, 0.12) !important; color: #ff9f43 !important; }
</style>
@endpush
@endsection
