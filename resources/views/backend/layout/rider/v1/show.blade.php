@extends('backend.app')

@section('title', 'Rideshare Driver Details')

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Rideshare Driver Profile</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.rider.index') }}">Rideshare Drivers</a></li>
                                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <section class="app-user-view-account">
                    <div class="row">
                        <!-- User Sidebar/Header -->
                        <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
                            <div class="card premium-card border-top-primary border-3">
                                <div class="card-body">
                                    <div class="user-avatar-section text-center mb-2">
                                        <div class="d-flex align-items-center flex-column">
                                            <div class="profile-img-preview mb-1">
                                                <img class="img-fluid rounded mt-3 mb-2" src="{{ $user->avatar ? asset($user->avatar) : 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg' }}" height="110" width="110" alt="User avatar" />
                                            </div>
                                            <div class="user-info text-center">
                                                <h4>{{ $user->name }}</h4>
                                                <span class="badge bg-light-secondary rounded-pill">{{ ucfirst($user->user_type ?? 'Rideshare Driver') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-around my-2 pt-75">
                                        <div class="d-flex align-items-start me-2">
                                            <span class="badge bg-light-primary p-75 rounded">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                            <div class="ms-75 text-center px-1">
                                                <h5 class="mb-0">Status</h5>
                                                <small class="text-{{ $user->status == 'active' ? 'success' : 'warning' }} font-weight-bold">{{ ucfirst($user->status) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <h4 class="fw-bolder border-bottom pb-50 mb-1 mt-3">Personal Details</h4>
                                    <div class="info-container">
                                        <ul class="list-unstyled">
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25 text-primary">Email:</span>
                                                <span class="text-muted">{{ $user->email }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25 text-primary">Phone:</span>
                                                <span class="text-muted">{{ $user->phone }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25 text-primary">Gender:</span>
                                                <span class="text-muted">{{ ucfirst($user->gender) }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25 text-primary">NID:</span>
                                                <span class="text-muted">{{ $user->nid }}</span>
                                            </li>
                                            <li class="mb-75">
                                                <span class="fw-bolder me-25 text-primary">DOB:</span>
                                                <span class="text-muted">{{ $user->date_of_birth }}</span>
                                            </li>
                                            <li class="mb-75 text-center mt-2">
                                                <span class="fw-bolder d-block mb-25 text-primary border-bottom pb-25">Address:</span>
                                                <span class="text-muted italic">{{ $user->address }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="d-flex justify-content-center pt-2">
                                        <a href="{{ route('admin.rider.edit', $user->id) }}" class="btn btn-primary me-1">Edit Profile</a>
                                        <a href="{{ route('admin.rider.index') }}" class="btn btn-outline-secondary">List View</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Details -->
                        <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                            <div class="card premium-card border-top-info border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-info"><i class="fa-solid fa-car-side me-1"></i> Vehicle Information</h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Vehicle Type</p>
                                            <p class="h6">{{ $user->vehicleInformation->vehicle_type ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Model</p>
                                            <p class="h6">{{ $user->vehicleInformation->vehicle_model ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Color</p>
                                            <p class="h6">{{ $user->vehicleInformation->vehicle_color ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Insurance #</p>
                                            <p class="h6">{{ $user->vehicleInformation->insurance_number ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Manufacture Year</p>
                                            <p class="h6">{{ $user->vehicleInformation->manufacture_year ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Max Occupancy</p>
                                            <p class="h6">{{ $user->vehicleInformation->max_occupancy ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">License #</p>
                                            <p class="h6">{{ $user->vehicleInformation->license_number ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Experience</p>
                                            <p class="h6">{{ $user->drivingInformation->driving_experience ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Registration Date</p>
                                            <p class="h6">{{ $user->created_at->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 border-top pt-1">
                                        <p class="mb-0 fw-bold extra-small text-uppercase text-muted">Additional License Info</p>
                                        <p class="small italic text-dark">{{ $user->drivingInformation->driving_license ?? 'No additional info provided' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="card premium-card mt-2 border-top-warning border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-warning"><i class="fa-solid fa-file-shield me-1"></i> Verification Documents</h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-image text-primary me-50"></i> Vehicle Documents</h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($user->vehicleInformation && is_array($user->vehicleInformation->vehicle_information_documents) && count($user->vehicleInformation->vehicle_information_documents) > 0)
                                                @foreach($user->vehicleInformation->vehicle_information_documents as $doc)
                                                    <div class="doc-view-card">
                                                        <a href="{{ asset($doc) }}" target="_blank">
                                                            <img src="{{ asset($doc) }}" alt="vehicle doc">
                                                        </a>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="alert alert-light p-1 mb-0 w-100">
                                                    <small class="text-muted"><i class="fa-solid fa-circle-info me-50"></i> No vehicle documents uploaded.</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-warning me-50"></i> Police Report Documents</h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($user->drivingInformation && is_array($user->drivingInformation->police_report_documents) && count($user->drivingInformation->police_report_documents) > 0)
                                                @foreach($user->drivingInformation->police_report_documents as $doc)
                                                    <div class="doc-view-card">
                                                        <a href="{{ asset($doc) }}" target="_blank">
                                                            <img src="{{ asset($doc) }}" alt="police report">
                                                        </a>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="alert alert-light p-1 mb-0 w-100">
                                                    <small class="text-muted"><i class="fa-solid fa-circle-info me-50"></i> No police reports uploaded.</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

@push('style')
<style>
    .premium-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1); background: #fff; }
    .profile-img-preview { width: 120px; height: 120px; background: #f8f8f8; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-radius: 50%; overflow: hidden; margin: 0 auto; display: flex; align-items: center; justify-content: center; }
    .profile-img-preview img { width: 100%; height: 100%; object-fit: cover; }
    .extra-small { font-size: 0.7rem; letter-spacing: 0.5px; }
    .doc-view-card { width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid #eee; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .doc-view-card:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .doc-view-card img { width: 100%; height: 100%; object-fit: cover; }
    .bg-light-secondary { background-color: #f1f0f2 !important; color: #82868b !important; }
    .bg-light-primary { background-color: rgba(115, 103, 240, 0.12) !important; color: #7367f0 !important; }
</style>
@endpush
@endsection
