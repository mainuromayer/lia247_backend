@extends('backend.app')

@section('title', 'Edit Permissions')

@section('content')
    <main class="app-content content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Edit Role Permissions: <span class="text-primary">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span></h4>
                            <a href="{{ route('admin.role.list') }}" class="btn btn-secondary btn-sm">Back</a>
                        </div>
                        <div class="card-body">
                            <form action="{{route('admin.role.update',$role->id)}}" method="post">
                                @csrf
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold mb-2">Manage Permissions</label>
                                    <div class="row">
                                        @foreach ($permissions as $permission)
                                            <div class="col-md-3 mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="perm_{{ $permission->id }}" name="permissions[]" value="{{ $permission->name }}"
                                                        {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="perm_{{ $permission->id }}">{{ ucwords(str_replace(['_', 'menu'], [' ', ' Menu'], $permission->name)) }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success">Update Permissions</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
@endsection
