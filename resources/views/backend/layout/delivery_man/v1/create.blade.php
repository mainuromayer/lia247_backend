@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5n/N0ZDv4r4=" crossorigin="" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" />
    <style>
        #map { height: 350px; border: 1px solid #ddd; border-radius: 8px; }
        .map-container { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid #ebe9f1; margin-top: 10px; }
        .map-search-wrapper { position: absolute; top: 10px; left: 50px; z-index: 1000; width: calc(100% - 70px); max-width: 400px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
    </style>
@endpush

@section('title', 'Add Delivery Driver')

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Delivery Driver Management</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.delivery_man.index') }}">Delivery Men</a></li>
                                    <li class="breadcrumb-item active">Add New Delivery Driver</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-body">
                <form class="form" method="POST" action="{{ route('admin.delivery_man.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column: Personal Information -->
                        <div class="col-md-8">
                            <div class="card premium-card border-top-primary border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-primary">
                                        <i class="fa-solid fa-user-gear me-1"></i> Personal Information
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    @if ($errors->any())
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <div class="alert-body">
                                                <ul class="mb-0">
                                                    @foreach ($errors->all() as $error)
                                                        <li><i class="fa-solid fa-circle-exclamation me-1"></i> {{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    @endif

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="name">Full Name <span class="text-danger">*</span></label>
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
                                                <label class="form-label font-weight-bold" for="phone">Phone Number <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" required placeholder="+1234567890">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="nid">National ID / NID <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                                                    <input type="text" name="nid" id="nid" class="form-control" value="{{ old('nid') }}" required placeholder="123456789">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="address">Address <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge mb-1">
                                                    <span class="input-group-text"><i class="fa-solid fa-location-dot"></i></span>
                                                    <input type="text" name="address" id="address" class="form-control" value="{{ old('address') }}" required placeholder="Select from map below or type here...">
                                                </div>
                                                <div class="row g-1">
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
                                                
                                                <div class="map-container rounded shadow-sm overflow-hidden">
                                                    <div id="map"></div>
                                                    <div class="map-search-wrapper p-1 bg-white border rounded">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" id="locationInput" class="form-control border-0" placeholder="Search areas..." onkeydown="if(event.key==='Enter') { document.getElementById('searchBtn').click(); return false; }">
                                                            <button type="button" id="searchBtn" class="btn btn-primary btn-sm rounded">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-muted extra-small mt-1 mb-0"><i class="fa-solid fa-circle-info me-1"></i> Use search or click the map to set address. It will auto-fill above.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="gender">Gender <span class="text-danger">*</span></label>
                                                <select name="gender" id="gender" class="form-select select2" required>
                                                    <option value="" selected disabled>Select Gender</option>
                                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control flatpickr-basic" value="{{ old('date_of_birth') }}" required>
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
                                        <i class="fa-solid fa-truck-ramp-box me-1"></i> Vehicle & Experience Details
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="vehicle_type">Vehicle Type <span class="text-danger">*</span></label>
                                                <input type="text" name="vehicle_type" id="vehicle_type" class="form-control" value="{{ old('vehicle_type') }}" required placeholder="e.g. Van">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="vehicle_model">Vehicle Model <span class="text-danger">*</span></label>
                                                <input type="text" name="vehicle_model" id="vehicle_model" class="form-control" value="{{ old('vehicle_model') }}" required placeholder="e.g. Ford Transit">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="vehicle_color">Vehicle Color <span class="text-danger">*</span></label>
                                                <input type="text" name="vehicle_color" id="vehicle_color" class="form-control" value="{{ old('vehicle_color') }}" required placeholder="e.g. Silver">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="insurance_number">Insurance Number <span class="text-danger">*</span></label>
                                                <input type="text" name="insurance_number" id="insurance_number" class="form-control" value="{{ old('insurance_number') }}" required placeholder="INS-123456">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="manufacture_year">Manufacture Year <span class="text-danger">*</span></label>
                                                <input type="text" name="manufacture_year" id="manufacture_year" class="form-control" value="{{ old('manufacture_year') }}" required placeholder="2022">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="max_occupancy">Max Occupancy <span class="text-danger">*</span></label>
                                                <input type="number" name="max_occupancy" id="max_occupancy" class="form-control" value="{{ old('max_occupancy') }}" required min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="license_number">Driving License Number <span class="text-danger">*</span></label>
                                                <input type="text" name="license_number" id="license_number" class="form-control" value="{{ old('license_number') }}" required placeholder="LIC-7890">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="driving_experience">Years of Experience <span class="text-danger">*</span></label>
                                                <input type="text" name="driving_experience" id="driving_experience" class="form-control" value="{{ old('driving_experience') }}" required placeholder="e.g. 5 Years">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-1">
                                                <label class="form-label font-weight-bold" for="driving_license">Driving License Number <span class="text-danger">*</span></label>
                                                <input type="text" name="driving_license" id="driving_license" class="form-control" value="{{ old('driving_license') }}" required placeholder="e.g. LIC-1234">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Avatar & Documents -->
                        <div class="col-md-4">
                            <div class="card premium-card border-top-secondary border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-secondary">
                                        <i class="fa-solid fa-camera me-1"></i> Profile Photo
                                    </h4>
                                </div>
                                <div class="card-body pt-2 text-center">
                                    <div class="mb-1">
                                        <input type="file" name="avatar" id="avatar" class="dropify" data-height="180" />
                                        <span class="text-muted small">Allowed JPG, PNG or WebP. Max 2MB.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card premium-card mt-2 border-top-warning border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-warning">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Verification Docs
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="mb-2">
                                        <label class="form-label font-weight-bold mb-1">Vehicle Documents <span class="text-danger">*</span></label>
                                        <div class="doc-upload-zone" onclick="document.getElementById('vehicle_information_documents').click()">
                                            <i class="fa-solid fa-cloud-arrow-up fa-2x mb-2 text-primary"></i>
                                            <p class="mb-0">Upload Vehicle Images</p>
                                            <span class="text-muted extra-small">Multiple files allowed</span>
                                            <input type="file" name="vehicle_information_documents[]" id="vehicle_information_documents" class="d-none" multiple required onchange="previewImages(this, 'vehicle_preview')">
                                        </div>
                                        <div id="vehicle_preview" class="d-flex flex-wrap gap-1 mt-1"></div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label font-weight-bold mb-1">Police Report Documents <span class="text-danger">*</span></label>
                                        <div class="doc-upload-zone" onclick="document.getElementById('police_report_documents').click()">
                                            <i class="fa-solid fa-file-shield fa-2x mb-2 text-warning"></i>
                                            <p class="mb-0">Upload Police Reports</p>
                                            <span class="text-muted extra-small">Multiple files allowed</span>
                                            <input type="file" name="police_report_documents[]" id="police_report_documents" class="d-none" multiple required onchange="previewImages(this, 'police_preview')">
                                        </div>
                                        <div id="police_preview" class="d-flex flex-wrap gap-1 mt-1"></div>
                                    </div>


                                </div>
                            </div>

                            <div class="card premium-card mt-2 border-top-info border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-info">
                                        <i class="fa-solid fa-face-smile me-1"></i> Selfie Verification
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="mb-2">
                                        <label class="form-label font-weight-bold mb-1">Front Profile Selfie <span class="text-danger">*</span></label>
                                        <input type="file" name="front_prfile_selfie" id="front_prfile_selfie" class="dropify" data-height="120" required />
                                        <span class="text-muted small">Allowed JPG, PNG or WebP. Max 2MB.</span>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label font-weight-bold mb-1">Side Profile Selfie <span class="text-danger">*</span></label>
                                        <input type="file" name="side_profile_selfie" id="side_profile_selfie" class="dropify" data-height="120" required />
                                        <span class="text-muted small">Allowed JPG, PNG or WebP. Max 2MB.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card mt-2 bg-transparent shadow-none border-0">
                                <div class="card-body p-0">
                                    <button type="submit" class="btn btn-primary w-100 mb-1">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Create Delivery Driver
                                    </button>
                                    <a href="{{ route('admin.delivery_man.index') }}" class="btn btn-secondary w-100">
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
        </div>
    </div>
    
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" />
    <style>
        .premium-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1); transition: all 0.3s ease; background: #fff; }
        .premium-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px 0 rgba(34, 41, 47, 0.15); }
        .form-label { font-weight: 600; color: #5e5873; font-size: 0.9rem; }
        .input-group-merge .input-group-text { background-color: #f8f8f8; border-right: none; color: #b9b9c3; }
        .input-group-merge .form-control { border-left: none; padding-left: 0; }
        .input-group-merge .form-control:focus ~ .input-group-text { border-color: #7367f0; }
        
        .doc-upload-zone { 
            border: 2px dashed #ebe9f1; border-radius: 8px; padding: 1.5rem; text-align: center; 
            cursor: pointer; transition: all 0.2s ease; background: #fbfbfb; 
        }
        .doc-upload-zone:hover { border-color: #7367f0; background: #f4f2ff; }
        .doc-upload-zone p { font-weight: 600; color: #5e5873; margin-top: 0.5rem; }
        .extra-small { font-size: 0.75rem; }

        .preview-img-container { position: relative; width: 70px; height: 70px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd; }
        .preview-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .btn-delete-file { 
            position: absolute; top: 0; right: 0; background: rgba(234, 84, 85, 0.9); 
            color: white; border: none; width: 20px; height: 20px; line-height: 20px; 
            text-align: center; cursor: pointer; font-size: 12px; z-index: 10; border-bottom-left-radius: 4px; 
        }
    </style>
@endpush

@push('script')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // ── MAP SETUP ────────────────────────────────────────
        $(document).ready(function() {
            const defaultLat = 23.8103;  // Dhaka
            const defaultLng = 90.4125;

            var map = L.map('map').setView([defaultLat, defaultLng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var marker = null;
            setTimeout(() => map.invalidateSize(), 300);

            // Inverse Geocoding for clicks
            function updateAddressFromCoords(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.display_name) {
                            $('#address').val(data.display_name);
                        }
                    });
            }

            // Map click
            map.on('click', function(e) {
                let lat = e.latlng.lat.toFixed(6);
                let lng = e.latlng.lng.toFixed(6);

                $('#latitude').val(lat);
                $('#longitude').val(lng);
                
                updateAddressFromCoords(lat, lng);

                if (marker) marker.remove();
                marker = L.marker([lat, lng]).addTo(map)
                    .bindPopup(`Lat: ${lat}<br>Lng: ${lng}`)
                    .openPopup();
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
                            $('#address').val(result.display_name);

                            if (marker) marker.remove();
                            marker = L.marker([lat, lng]).addTo(map)
                                .bindPopup(result.display_name || 'Found location')
                                .openPopup();

                            map.setView([lat, lng], 15);
                        } else {
                            alert('No location found for: ' + query);
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        alert('Something went wrong with the search');
                    });
            });
        });

        function previewImages(input, previewId) {
            const previewContainer = document.getElementById(previewId);
            previewContainer.innerHTML = '';
            if (input.files) {
                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-img-container shadow-sm';
                        div.innerHTML = `
                            <button type="button" class="btn-delete-file" onclick="removeSelectedFile('${previewId}', ${index})">&times;</button>
                            <img src="${e.target.result}" alt="preview">
                        `;
                        previewContainer.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            }
        }

        function removeSelectedFile(previewId, index) {
            const inputMap = {
                'vehicle_preview': 'vehicle_information_documents',
                'police_preview': 'police_report_documents'
            };
            const input = document.getElementById(inputMap[previewId]);
            const dt = new DataTransfer();
            const { files } = input;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) dt.items.add(files[i]);
            }
            
            input.files = dt.files;
            previewImages(input, previewId);
        }

        $(document).ready(function() {
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
            const passwordConfirmation = document.getElementById('password_confirmation');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            togglePasswordConfirmation.addEventListener('click', function() {
                const type = passwordConfirmation.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirmation.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });
    </script>
@endpush
