@extends('backend.app')
@section('title', 'Ride Detail - ' . $ride->ride_no)
@section('content')
<div class="app-content content">
    <div class="d-flex align-items-center mb-3" style="gap:12px">
        <a href="{{ route('admin.rides.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h4 class="mb-0">Ride Detail &nbsp;<strong>{{ $ride->ride_no }}</strong></h4>
    </div>

    <div class="row">
        {{-- LEFT: Ride Info --}}
        <div class="col-md-8">

            {{-- Ride Route --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Route Information</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small text-uppercase">Pickup Address</label>
                        <p class="mb-0 fw-bold"><i class="fa-solid fa-location-dot text-success me-2"></i>{{ $ride->pickup_address }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small text-uppercase">Dropoff Address</label>
                        <p class="mb-0 fw-bold"><i class="fa-solid fa-location-arrow text-danger me-2"></i>{{ $ride->dropoff_address }}</p>
                    </div>
                </div>
            </div>

            {{-- Financial Summary --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Fare Details</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless" style="max-width:400px">
                        <tr><td>Estimated Fare</td><td class="text-end fw-bold">${{ number_format($ride->estimated_fare, 2) }}</td></tr>
                        <tr><td>Actual Fare</td><td class="text-end">${{ number_format($ride->actual_fare ?? 0, 2) }}</td></tr>
                        <tr><td>Tips</td><td class="text-end">${{ number_format($ride->tip_amount ?? 0, 2) }}</td></tr>
                        <tr class="border-top"><td><strong>Grand Total</strong></td><td class="text-end"><strong>${{ number_format($ride->total_amount, 2) }}</strong></td></tr>
                    </table>
                </div>
            </div>

        </div>

        {{-- RIGHT: Status & Parties --}}
        <div class="col-md-4">

            {{-- Status --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Ride Status</h5></div>
                <div class="card-body">
                    @php
                        $statusBadges = [
                            'pending'   => 'warning',   
                            'accepted'  => 'info',
                            'started'   => 'primary',   
                            'completed' => 'success',   
                            'canceled'  => 'danger',
                        ];
                        $badge = $statusBadges[$ride->status] ?? 'secondary';
                    @endphp
                    <div class="text-center">
                        <span class="badge bg-{{ $badge }} fs-6 px-4 py-2">{{ ucfirst($ride->status) }}</span>
                    </div>
                    @if($ride->status === 'canceled' && $ride->cancel_reason)
                        <div class="alert alert-danger mt-3 mb-0">
                            <strong>Cancellation Reason:</strong><br>
                            {{ $ride->cancel_reason }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Customer</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $ride->user?->name ?? 'N/A' }}</strong></p>
                    <p class="mb-1 text-muted"><i class="fa-solid fa-envelope me-1"></i>{{ $ride->user?->email ?? '' }}</p>
                    <p class="mb-1 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $ride->user?->phone ?? '' }}</p>
                    @if($ride->customer_address)
                        <hr class="my-2">
                        <p class="mb-1"><strong>Recorded Profile Address:</strong></p>
                        <p class="mb-1 text-muted small"><i class="fa-solid fa-house-user me-1"></i>{{ $ride->customer_address }}</p>
                        @if($ride->customer_lat && $ride->customer_lng)
                            <p class="mb-0">
                                <a href="https://www.google.com/maps?q={{ $ride->customer_lat }},{{ $ride->customer_lng }}" target="_blank" class="badge bg-light-primary text-primary">
                                    <i class="fa-solid fa-map-location-dot me-1"></i>Customer Home Map
                                </a>
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Rider Info --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Rideshare Driver</h5></div>
                <div class="card-body">
                    @if($ride->rider)
                        <p class="mb-1"><strong>{{ $ride->rider->name }}</strong></p>
                        <p class="mb-1 text-muted"><i class="fa-solid fa-envelope me-1"></i>{{ $ride->rider->email }}</p>
                        <p class="mb-1 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $ride->rider->phone }}</p>
                        @if($ride->rider_address)
                            <hr class="my-2">
                            <p class="mb-1"><strong>Recorded Location:</strong></p>
                            <p class="mb-1 text-muted small"><i class="fa-solid fa-motorcycle me-1"></i>{{ $ride->rider_address }}</p>
                            @if($ride->rider_lat && $ride->rider_lng)
                                <p class="mb-0">
                                    <a href="https://www.google.com/maps?q={{ $ride->rider_lat }},{{ $ride->rider_lng }}" target="_blank" class="badge bg-light-info text-info">
                                        <i class="fa-solid fa-location-arrow me-1"></i>Rideshare Driver Map
                                    </a>
                                </p>
                            @endif
                        @endif
                    @else
                        <p class="mb-0 text-muted">Searching for rideshare driver...</p>
                    @endif
                </div>
            </div>

            {{-- Ride Meta --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Ride Info</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td>Distance</td><td class="fw-bold">{{ $ride->distance }} km</td></tr>
                        <tr><td>Payment</td><td class="fw-bold">{{ ucfirst($ride->payment_gateway ?? '—') }}</td></tr>
                        <tr><td>Status</td><td class="fw-bold">{{ ucfirst($ride->payment_status ?? '—') }}</td></tr>
                        <tr><td>Placed At</td><td>{{ $ride->created_at->format('d M Y, h:i A') }}</td></tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
