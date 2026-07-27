@extends('backend.app')
@section('title', 'Edit Category')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Category</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.categories.update', ['id' => $category->id]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') ?? $category->name }}" placeholder="Enter Category Name" required>
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label">Category Slug</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug') ?? $category->slug }}" required readonly>
                            @error('slug')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="form-label">Category Image</label>
                            <input type="file" name="image" class="form-control dropify" data-default-file="{{ asset($category->image) }}">
                            @error('image')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Update Category</button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script')
<script>
    $(document).ready(function() {
        if ($('.select2').length) {
            $('.select2').select2();
        }
        $('.dropify').dropify();
    });
</script>
@endpush
