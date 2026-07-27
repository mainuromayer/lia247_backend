@extends('backend.app')

@section('title', 'Add Transport Type')

@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Add New Transport Type</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.transport_types.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="transport_type_category_id">Category</label>
                                <select name="transport_type_category_id" id="transport_type_category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Uber X, Pathao Bike" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="icon">Icon/Image</label>
                                <input type="file" name="icon" id="icon" class="form-control dropify">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="price_per_km">Price Per KM</label>
                                <input type="number" step="0.01" name="price_per_km" id="price_per_km" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="min_balance">Min Balance (to accept ride)</label>
                                <input type="number" step="0.01" name="min_balance" id="min_balance" class="form-control" value="0" required>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary mr-1">Save</button>
                            <a href="{{ route('admin.transport_types.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
