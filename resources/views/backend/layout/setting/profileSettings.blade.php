@extends('backend.app')

@section('title', 'My Profile')

@push('style')
    <style>
        .nav-tabs.nav-justified {
            width: 400px;
        }

        .profile-img-main {
            width: 120px;
            height: 120px;
            overflow: hidden;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 3px solid #f8f9fa;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-img-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Cropper improvements */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }

        .img-container {
            min-height: 400px;
            max-height: 500px;
            width: 100%;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        #cropAndSaveBtn {
            font-weight: 600;
            padding: 0.5rem 1.5rem;
        }

        .cropper-controls .btn {
            margin-right: 5px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
@endpush

@section('content')
    <!--app-content open-->
    <div class="app-content content">
        <div class="side-app">
            <div class="main-container container-fluid">
                <div class="row" id="user-profile">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-xl-6">
                                        <div class="d-flex align-items-center">
                                            <div class="profile-img-main">
                                                <img src="{{ asset(Auth::user()->avatar) }}" alt="Profile Picture"
                                                    class="img-fluid" id="headerProfilePic">
                                            </div>
                                            <div class="ms-4">
                                                <h4 class="mb-1 fw-bold">{{ Auth::user()->name ?? 'N/A' }}</h4>
                                                <p class="text-muted mb-3">{{ Auth::user()->email ?? 'N/A' }}</p>
                                                <button class="btn btn-primary d-flex align-items-center" id="uploadImageBtn">
                                                    <i class="fa fa-camera me-2"></i> Update Profile Picture
                                                </button>
                                                <input type="file" name="avatar" id="profile_picture_input" hidden
                                                    accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-top">
                                <ul class="nav nav-tabs nav-justified">
                                    <li class="nav-item">
                                        <a class="nav-link {{ session('type') ? session('type') == 'profile' ? 'active' : '' : 'active' }}"
                                            data-bs-toggle="tab" href="#editProfile">Edit Profile</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ session('type') ? session('type') == 'password' ? 'active' : '' : '' }}"
                                            data-bs-toggle="tab" href="#updatePassword">Update Password</a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="tab-content mt-4">
                            <div class="tab-pane fade {{ session('type') ? (session('type') == 'profile' ? 'show active' : '') : 'show active' }}"
                                id="editProfile">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">Personal Information</h5>
                                        <form method="post" action="{{ route('profile.update') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                    name="name" id="name" value="{{ Auth::user()->name }}"
                                                    placeholder="Enter your name">
                                                @error('name')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email"
                                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                                    id="email" value="{{ Auth::user()->email }}"
                                                    placeholder="Enter your email">
                                                @error('email')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade {{ session('type') ? (session('type') == 'password' ? 'show active' : '') : '' }}"
                                id="updatePassword">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">Change Password</h5>
                                         <form method="post" action="{{ route('profile.update.password') }}">
                                             @csrf
                                             <div class="mb-3">
                                                 <label for="old_password" class="form-label">Current Password</label>
                                                 <div class="input-group input-group-merge form-password-toggle">
                                                     <input type="password"
                                                         class="form-control @error('old_password') is-invalid @enderror"
                                                         name="old_password" id="old_password"
                                                         placeholder="Enter current password">
                                                     <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                                 </div>
                                                 @error('old_password')
                                                     <div class="text-danger small mt-1">{{ $message }}</div>
                                                 @enderror
                                             </div>
                                             <div class="row">
                                                 <div class="mb-3 col-md-6">
                                                     <label for="password" class="form-label">New Password</label>
                                                     <div class="input-group input-group-merge form-password-toggle">
                                                         <input type="password"
                                                             class="form-control @error('password') is-invalid @enderror"
                                                             name="password" id="password" placeholder="Enter new password">
                                                         <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                                     </div>
                                                     @error('password')
                                                         <div class="text-danger small mt-1">{{ $message }}</div>
                                                     @enderror
                                                 </div>
                                                 <div class="mb-3 col-md-6">
                                                     <label for="password_confirmation" class="form-label">Confirm Password</label>
                                                     <div class="input-group input-group-merge form-password-toggle">
                                                         <input type="password"
                                                             class="form-control @error('password_confirmation') is-invalid @enderror"
                                                             name="password_confirmation" id="password_confirmation"
                                                             placeholder="Confirm new password">
                                                         <span class="input-group-text cursor-pointer toggle-password-visibility"><i data-feather="eye"></i></span>
                                                     </div>
                                                     @error('password_confirmation')
                                                         <div class="text-danger small mt-1">{{ $message }}</div>
                                                     @enderror
                                                 </div>
                                             </div>
                                             <button type="submit" class="btn btn-primary px-4">Update Password</button>
                                         </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cropper Modal -->
    <div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="cropperModalLabel">
                        <i class="fa fa-crop me-2"></i> Adjust Profile Picture
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="img-container">
                        <img id="cropperTempImg" src="" alt="Source Image">
                    </div>
                </div>
                <div class="modal-footer justify-content-between bg-light">
                    <div class="cropper-controls">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm"
                            id="zoomInBtn" title="Zoom In">
                            <i class="fa fa-search-plus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm"
                            id="zoomOutBtn" title="Zoom Out">
                            <i class="fa fa-search-minus"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm"
                            id="rotateLeftBtn" title="Rotate Left">
                            <i class="fa fa-undo"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm"
                            id="rotateRightBtn" title="Rotate Right">
                            <i class="fa fa-redo"></i>
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-link text-muted text-decoration-none me-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary shadow-sm" id="cropAndSaveBtn">
                            <i class="fa fa-check me-1"></i> Crop & Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        $(document).ready(function() {
            let cropper;
            const $image = $('#cropperTempImg');
            const $modal = new bootstrap.Modal(document.getElementById('cropperModal'));

            $('#uploadImageBtn').click(function() {
                $('#profile_picture_input').click();
            });

            $('#profile_picture_input').change(function(e) {
                const file = e.target.files[0];
                if (!file) return;

                if (!file.type.match('image.*')) {
                    new Notyf().error('Please select an image file.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    $image.attr('src', event.target.result);
                    $modal.show();
                };
                reader.readAsDataURL(file);
            });

            document.getElementById('cropperModal').addEventListener('shown.bs.modal', function() {
                cropper = new Cropper($image[0], {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            });

            document.getElementById('cropperModal').addEventListener('hidden.bs.modal', function() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                $('#profile_picture_input').val(''); // Reset input
            });

            // Cropper Controls
            $('#zoomInBtn').click(() => cropper.zoom(0.1));
            $('#zoomOutBtn').click(() => cropper.zoom(-0.1));
            $('#rotateLeftBtn').click(() => cropper.rotate(-45));
            $('#rotateRightBtn').click(() => cropper.rotate(45));

            $('#cropAndSaveBtn').click(function() {
                const canvas = cropper.getCroppedCanvas({
                    width: 400,
                    height: 400,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                if (!canvas) {
                    new Notyf().error('Could not generate cropped image.');
                    return;
                }

                const croppedImage = canvas.toDataURL('image/png');

                // Validate size (client-side)
                const base64String = croppedImage.split(',')[1];
                const binaryData = atob(base64String);
                if (binaryData.length > 20 * 1024 * 1024) {
                    new Notyf().error('The image size must not exceed 20MB.');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('profile.update.profile.picture') }}",
                    type: 'POST',
                    data: {
                        profile_picture: croppedImage,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.profile-img-main img').attr('src', response.image_url);
                            // Update header profile pic if exists
                            if ($('#headerProfilePic').length) {
                                $('#headerProfilePic').attr('src', response.image_url);
                            }
                            new Notyf().success(response.message);
                            $modal.hide();
                        } else {
                            new Notyf().error(response.message || 'Failed to upload profile picture.');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while uploading the image.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        new Notyf().error(errorMessage);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fa fa-check me-1"></i> Crop & Save');
                    }
                });
            });

            // Toggle password visibility
            $(document).on('click', '.toggle-password-visibility', function() {
                const $input = $(this).closest('.input-group').find('input');
                const $icon = $(this).find('i');
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    if (typeof feather !== 'undefined') {
                        $icon.replaceWith(feather.icons['eye-off'].toSvg());
                    }
                } else {
                    $input.attr('type', 'password');
                    if (typeof feather !== 'undefined') {
                        $icon.replaceWith(feather.icons['eye'].toSvg());
                    }
                }
            });
        });
    </script>
@endpush