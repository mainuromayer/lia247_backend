@extends('backend.app')

@section('title', 'Add New Ad')

@push('style')
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
                                    <li class="breadcrumb-item active">Add New Ad</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-body">
                <form class="form" method="POST" action="{{ route('admin.ads.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card premium-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" for="url">Redirection URL (Optional)</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                                                <input type="url" name="url" id="url" class="form-control" value="{{ old('url') }}" placeholder="https://example.com/promo">
                                            </div>
                                            <small class="text-muted">Where the user will be taken when they click the ad.</small>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" for="is_active">Status</label>
                                            <select name="is_active" id="is_active" class="form-control">
                                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('is_active')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Ad Image <span class="text-danger">*</span></label>
                                            <input type="file" name="image" class="dropify" data-height="200" required data-allowed-file-extensions="jpg jpeg png webp" />
                                            <small class="text-muted">Best aspect ratio: 16:9 or 2:1. Max 5MB.</small>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Ad Video (Optional)</label>
                                            <input type="file" name="video" class="dropify" data-height="200" data-allowed-file-extensions="mp4 mov qt" />
                                            <small class="text-muted">Max 20MB. Short clips preferred.</small>
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary me-1">
                                            <i class="fa-solid fa-check me-1"></i> Save Advertisement
                                        </button>
                                        <a href="{{ route('admin.ads.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
