@extends('backend.app')

@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush

@section('title', 'Roles')

@section('content')
    <div class="app-content content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">All Roles</h3>
                <div>
                    <a href="{{ route('admin.role.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus"></i> Add Role
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable pt-0">
                    <table class="table table-hover" id="basic_tables">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $index => $role)
                                <tr id="role-{{ $role->id }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span class="fw-bold">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="{{ route('admin.role.edit', $role->id) }}" class="btn btn-sm btn-warning me-1" title="Edit Permissions">
                                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                            </a>
                                            @if(!in_array(strtolower($role->name), ['admin', 'super admin', 'super_admin']))
                                                <button onclick="deleteRole({{ $role->id }})" class="btn btn-sm btn-danger text-white" title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @else
                                                <span class="badge bg-light-primary text-primary ms-1">Pre-defined System Role</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="{{ asset('backend/assets/datatable/js/datatables.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            if (!$.fn.DataTable.isDataTable('#basic_tables')) {
                $('#basic_tables').DataTable({
                    order: [],
                    pageLength: 10,
                    responsive: true
                });
            }
        });

        function deleteRole(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/role/destroy/' + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}',
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#role-' + id).remove();
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Something went wrong. Please try again later.',
                                'error'
                            );
                        }
                    });
                }
            });
        }
    </script>
@endpush
