@extends('backend.app')

@section('title', 'Add Vendor')

@push('style')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5n/N0ZDv4r4=" crossorigin="" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

@push('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" />
    <style>
        .premium-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1); transition: all 0.3s ease; background: #fff; }
        .premium-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px 0 rgba(34, 41, 47, 0.15); }
        .form-label { font-weight: 600; color: #5e5873; font-size: 0.9rem; }
        .input-group-merge .input-group-text { background-color: #f8f8f8; border-right: none; color: #b9b9c3; }
        .input-group-merge .form-control { border-left: none; padding-left: 0; }
        .input-group-merge .form-control:focus ~ .input-group-text { border-color: #7367f0; }

        .map-container { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid #ebe9f1; }
        .extra-small { font-size: 0.75rem; }
        #map { height: 380px; border: 1px solid #ddd; border-radius: 8px; }
    </style>
@endpush
@endpush

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Vendor Management</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.vendor.index') }}">Vendors</a></li>
                                    <li class="breadcrumb-item active">Add New Vendor</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-body">
                <form class="form" method="POST" action="{{ route('admin.vendor.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column: Owner & Business Info -->
                        <div class="col-md-7">
                            <div class="card premium-card border-top-primary border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-primary">
                                        <i class="fa-solid fa-user-tie me-1"></i> Owner Information
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="name">Owner Full Name <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="John Doe">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="email">Email Address <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required placeholder="john@example.com">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="password">Password <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge form-password-toggle">
                                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                                    <input type="password" name="password" id="password" class="form-control" required placeholder="············">
                                                    <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge form-password-toggle">
                                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required placeholder="············">
                                                    <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card premium-card mt-2 border-top-info border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-info">
                                        <i class="fa-solid fa-briefcase me-1"></i> Business Details
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row g-2">
                                        <div class="col-md-12">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="business_name">Business Name <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-shop"></i></span>
                                                    <input type="text" name="business_name" id="business_name" class="form-control" value="{{ old('business_name') }}" required placeholder="John's Shop">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="business_phone">Business Phone <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                                    <input type="text" name="business_phone" id="business_phone" class="form-control" value="{{ old('business_phone') }}" required placeholder="1234567890">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="license_number">Business License <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-certificate"></i></span>
                                                    <input type="text" name="license_number" id="license_number" class="form-control" value="{{ old('license_number') }}" required placeholder="LIC-998877">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="business_address">Business Address <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge mb-1">
                                                    <span class="input-group-text"><i class="fa-solid fa-map-location-dot"></i></span>
                                                    <input type="text" name="business_address" id="business_address" class="form-control" value="{{ old('business_address') }}" required placeholder="123 Business Way, City">
                                                </div>
                                                <div class="row g-1 mb-1">
                                                    <div class="col-6">
                                                        <div class="input-group input-group-merge input-group-sm">
                                                            <span class="input-group-text small">Lat</span>
                                                            <input type="text" name="latitude" id="latitude" class="form-control bg-light" value="{{ old('latitude') }}" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="input-group input-group-merge input-group-sm">
                                                            <span class="input-group-text small">Lng</span>
                                                            <input type="text" name="longitude" id="longitude" class="form-control bg-light" value="{{ old('longitude') }}" readonly required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <label class="form-label font-weight-bold mb-1"><i class="fa-solid fa-earth-americas me-50"></i> Precise Location Picker</label>
                                                <div class="map-container rounded shadow-sm overflow-hidden">
                                                    <div id="map" style="height: 380px;"></div>
                                                    <div class="map-search-wrapper p-1 bg-white border rounded">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" id="locationInput" class="form-control border-0" placeholder="Search areas..." onkeydown="if(event.key==='Enter') { document.getElementById('searchBtn').click(); return false; }">
                                                            <button type="button" id="searchBtn" class="btn btn-primary btn-sm rounded">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-muted extra-small mt-1 mb-0"><i class="fa-solid fa-circle-info me-1"></i> Use search or click the map to set location. The address will auto-fill.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="opening_time">Opening Time <span class="text-danger">*</span></label>
                                                <input type="time" name="opening_time" id="opening_time" class="form-control" value="{{ old('opening_time') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="closing_time">Closing Time <span class="text-danger">*</span></label>
                                                <input type="time" name="closing_time" id="closing_time" class="form-control" value="{{ old('closing_time') }}" required>
                                            </div>
                                        </div>
                                            <div class="col-12 mt-2">
                                                <div class="card bg-light-primary shadow-none mb-1">
                                                    <div class="card-header pb-1 pt-1 bg-transparent">
                                                        <h5 class="text-primary mb-0"><i class="fa-solid fa-gift me-50"></i> Free Delivery Settings (Campaign)</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4 mb-1">
                                                                <label class="form-label d-block">Free Delivery Status</label>
                                                                <div class="form-check form-switch mt-50">
                                                                    <input type="checkbox" name="free_delivery" class="form-check-input" id="free_delivery" value="1">
                                                                    <label class="form-check-label" for="free_delivery">Enable Free Delivery</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-8 mb-1">
                                                                <label class="form-label">Free Delivery Type</label>
                                                                <select name="free_delivery_type" class="form-select" id="free_delivery_type">
                                                                    <option value="admin">Admin Pays (Full)</option>
                                                                    <option value="vendor">Vendor Pays (Full)</option>
                                                                    <option value="hybrid">Hybrid (Split Cost)</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div id="hybrid_settings" class="col-12 d-none">
                                                                <div class="row">
                                                                    <div class="col-md-6 mb-1">
                                                                        <label class="form-label">Admin Contribution (Amount)</label>
                                                                        <input type="number" name="admin_contribution" class="form-control" value="0" step="0.01">
                                                                    </div>
                                                                    <div class="col-md-6 mb-1">
                                                                        <label class="form-label">Vendor Contribution (Amount)</label>
                                                                        <input type="number" name="vendor_contribution" class="form-control" value="0" step="0.01">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="mb-1">
                                                    <label class="form-label font-weight-bold" for="business_description">Short Description <span class="text-danger">*</span></label>
                                                    <textarea name="business_description" id="business_description" class="summernote" required>{{ old('business_description') }}</textarea>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Map & Photos -->
                        <div class="col-md-5">
                            <div class="card premium-card border-top-secondary border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-secondary">
                                        <i class="fa-solid fa-image me-1"></i> Business Cover
                                    </h4>
                                </div>
                                <div class="card-body pt-2 text-center">
                                    <div class="mb-1">
                                        <input type="file" name="cover_photo" id="cover_photo" class="dropify" data-height="160" />
                                        <span class="text-muted small">Max 5MB. Prefer wide landscape aspect.</span>
                                    </div>
                                </div>
                            </div>



                            <!-- Actions -->
                            <div class="card mt-2 bg-transparent shadow-none border-0">
                                <div class="card-body p-0">
                                    <button type="submit" class="btn btn-primary w-100 mb-1">
                                        <i class="fa-solid fa-store me-1"></i> Create Vendor Business
                                    </button>
                                    <a href="{{ route('admin.vendor.index') }}" class="btn btn-secondary w-100">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Cancel & Return
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('script')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
    $(document).ready(function () {

        $('.summernote').summernote({
            height: 220,
            placeholder: 'Write business description here...',
        });

        $('#free_delivery_type').on('change', function() {
            if ($(this).val() === 'hybrid') {
                $('#hybrid_settings').removeClass('d-none');
            } else {
                $('#hybrid_settings').addClass('d-none');
            }
        });

        // ── MAP SETUP ────────────────────────────────────────
        const defaultLat = 23.8103;  // Dhaka
        const defaultLng = 90.4125;

        // Edit page existing value (create page empty → fallback default)
        let initialLat  = parseFloat($('#latitude').val())  || defaultLat;
        let initialLng  = parseFloat($('#longitude').val()) || defaultLng;

        // If edit page is empty then default
        if (isNaN(initialLat) || isNaN(initialLng)) {
            initialLat = defaultLat;
            initialLng = defaultLng;
        }

        var map = L.map('map').setView([initialLat, initialLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker = null;

        // Edit existing marker
        if ($('#latitude').val() && $('#longitude').val()) {
            marker = L.marker([initialLat, initialLng]).addTo(map)
                .bindPopup('Current position')
                .openPopup();
        }

        // Map render fix (sometimes hidden size is not correct)
        setTimeout(() => map.invalidateSize(), 300);

        // Map click
        map.on('click', function(e) {
            let lat = e.latlng.lat.toFixed(6);
            let lng = e.latlng.lng.toFixed(6);

            $('#latitude').val(lat);
            $('#longitude').val(lng);

            if (marker) marker.remove();
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup(`Lat: ${lat}<br>Lng: ${lng}`)
                .openPopup();

            console.log('Clicked → Lat:', lat, 'Lng:', lng); // debug
        });

        // Search button
        $('#searchBtn').on('click', function() {
            let query = $('#locationInput').val().trim();
            if (!query) {
                alert('Please type something to search');
                return;
            }

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        let result = data[0];
                        let lat = parseFloat(result.lat).toFixed(6);
                        let lng = parseFloat(result.lon).toFixed(6);

                        $('#latitude').val(lat);
                        $('#longitude').val(lng);
                        $('#business_address').val(result.display_name);

                        if (marker) marker.remove();
                        marker = L.marker([lat, lng]).addTo(map)
                            .bindPopup(result.display_name || 'Found location')
                            .openPopup();

                        map.setView([lat, lng], 15);

                        console.log('Search result → Lat:', lat, 'Lng:', lng, 'Name:', result.display_name);
                    } else {
                        alert('No location found for: ' + query);
                    }
                })
                .catch(err => {
                    console.error('Search error:', err);
                    alert('Something went wrong with the search');
                });
        });

        // Inverse Geocoding for clicks
        function updateAddressFromCoords(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        $('#business_address').val(data.display_name);
                    }
                });
        }

        // before submit check (optional debug)
        $('form').on('submit', function(e) {
            let lat = $('#latitude').val().trim();
            let lng = $('#longitude').val().trim();

            if (!lat || !lng) {
                alert('Please select a location on the map or search for one.');
                e.preventDefault();
                return false;
            }

            console.log('Submitting → Lat:', lat, 'Lng:', lng);
        });
    });
    </script>
@endpush
@endsection

