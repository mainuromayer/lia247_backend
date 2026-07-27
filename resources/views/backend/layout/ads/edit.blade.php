@extends('backend.app')

@section('title', 'Edit Ad')

@push('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css" />
    <style>
        .premium-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1); transition: all 0.3s ease; background: #fff; }
        .form-label { font-weight: 600; color: #5e5873; font-size: 0.9rem; }
    </style>
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
                            <h2 class="content-header-title float-start mb-0">Ads Management</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.ads.index') }}">Ads</a></li>
                                    <li class="breadcrumb-item active">Edit Ad</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-body">
                <form class="form" method="POST" action="{{ route('admin.ads.update', ['id' => $ad->id]) }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card premium-card border-top-info border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-info">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Advertisement
                                    </h4>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="row">
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label" for="url">Redirection URL (Optional)</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                                                <input type="url" name="url" id="url" class="form-control" value="{{ old('url', $ad->url) }}" placeholder="https://example.com/promo">
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Ad Image</label>
                                            <input type="file" name="image" class="dropify" data-height="200" data-default-file="{{ $ad->image }}" data-allowed-file-extensions="jpg jpeg png webp" />
                                            <small class="text-muted">Leave empty to keep the current image.</small>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Ad Video</label>
                                            <input type="file" name="video" class="dropify" data-height="200" data-default-file="{{ $ad->video }}" data-allowed-file-extensions="mp4 mov qt" />
                                            <small class="text-muted">Leave empty to keep the current video (if any).</small>
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-info me-1">
                                            <i class="fa-solid fa-save me-1"></i> Update Advertisement
                                        </button>
                                        <a href="{{ route('admin.ads.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card premium-card border-top-warning border-3">
                                <div class="card-header bg-transparent border-bottom">
                                    <h4 class="card-title text-warning">
                                        <i class="fa-solid fa-circle-info me-1"></i> Status info
                                    </h4>
                                </div>
                                <div class="card-body pt-2 text-center">
                                    <p>Current Status:</p>
                                    @if($ad->is_active)
                                        <span class="badge bg-success py-1 px-2">Active</span>
                                    @else
                                        <span class="badge bg-danger py-1 px-2">Inactive</span>
                                    @endif
                                    <p class="mt-2 text-muted small">To change the status, please use the toggle switch in the Ads list.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.dropify').dropify();
        });
    </script>
@endpush
@endsection
