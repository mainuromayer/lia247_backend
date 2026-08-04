@extends('backend.app')

@section('title', 'Create Role')

@section('content')
    <main class="app-content content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Create Role & Assign Permissions</h4>
                            <a href="{{ route('admin.role.list') }}" class="btn btn-secondary btn-sm">Back</a>
                        </div>
                        <div class="card-body">
                            <form action="{{route('admin.role.store')}}" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="name" class="form-label font-weight-bold">Role Name</label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Order Manager" required>
                                    @error('name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold mb-2">Select Permissions for this Role</label>
                                    <div class="row">
                                        @foreach ($permissions as $permission)
                                            <div class="col-md-3 mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="perm_{{ $permission->id }}" name="permissions[]" value="{{ $permission->name }}">
                                                    <label class="custom-control-label" for="perm_{{ $permission->id }}">{{ ucwords(str_replace(['_', 'menu'], [' ', ' Menu'], $permission->name)) }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success">Save Role</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
@endsection
