@extends('backend.app')
@section('title', 'Add Delivery Option')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add Delivery Option</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.delivery_options.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Sub Text</label>
                            <input type="text" name="sub_text" class="form-control" value="{{ old('sub_text') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Image</label>
                            <input type="file" name="image" class="form-control dropify" accept="image/*">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Price ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Estimated Time (e.g. 30 mins)</label>
                            <input type="text" name="estimated_time" class="form-control" value="{{ old('estimated_time') }}" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Save</button>
                    <a href="{{ route('admin.delivery_options.index') }}" class="btn btn-secondary mt-3">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
