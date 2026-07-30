@extends('backend.app')

@push('style')
    <style>
        .premium-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1); transition: all 0.3s ease; background: #fff; }
        .form-label { font-weight: 600; color: #5e5873; font-size: 0.9rem; }
    </style>
@endpush

@section('title', 'Edit Ad')

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Edit Ad</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.ads.index') }}">Ads Management</a></li>
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
                        <div class="col-12">
                            <div class="card premium-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" for="url">Redirection URL (Optional)</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                                                <input type="url" name="url" id="url" class="form-control" value="{{ old('url', $ad->url) }}" placeholder="https://example.com/promo">
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" for="is_active">Status</label>
                                            <select name="is_active" id="is_active" class="form-control">
                                                <option value="1" {{ old('is_active', $ad->is_active) ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ !old('is_active', $ad->is_active) ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            @error('is_active')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
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
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
