@extends('backend.app')

@section('title', 'Add Category')

@section('content')
<div class="app-content content ">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Add New Transport Type Category</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.transport_type_categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Ride Share, Delivery" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mt-2">
                    <button type="submit" class="btn btn-primary mr-1">Save</button>
                    <a href="{{ route('admin.transport_type_categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
