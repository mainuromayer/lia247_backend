@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Sub Admins')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Sub Admin List</h3>
                <a href="{{ route('admin.sub_admin.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> Add Sub Admin
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable table-responsive pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="{{ asset('backend/assets/datatable/js/datatables.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            $(document).ready(function() {
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    }
                });
                if (!$.fn.DataTable.isDataTable('#data-table')) {
                    let dTable = $('#data-table').DataTable({
                        order: [],
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.sub_admin.index') }}",
                            type: "get",
                        },
                        columns: [
                            {
                                data: 'DT_RowIndex',
                                name: 'DT_RowIndex',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'email',
                                name: 'email'
                            },
                            {
                                data: 'role',
                                name: 'role'
                            },
                            {
                                data: 'status',
                                name: 'status',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'action',
                                name: 'action',
                                orderable: false,
                                searchable: false
                            },
                        ],
                    });
                }
            });

            // Status Change Confirm Alert
            function showStatusChangeAlert(id) {
                event.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to update the status?',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        statusChange(id);
                    }
                });
            }

            // Status Change
            function statusChange(id) {
                let url = '{{ route('admin.sub_admin.status', ':id') }}';
                $.ajax({
                    type: "GET",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({
                                icon: "success",
                                title: resp.message,
                                showConfirmButton: false,
                                timer: 800
                            });
                        } else if (resp.errors) {
                            Swal.fire({
                                icon: "error",
                                title: resp.errors[0],
                                showConfirmButton: false,
                                timer: 800
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: resp.message,
                                showConfirmButton: false,
                                timer: 800
                            });
                        }
                        $('#data-table').DataTable().ajax.reload();
                    },
                    error: function(error) {
                        // location.reload();
                    }
                })
            }
        </script>

        <script>
            // Assuming Toast is defined as toastr (add this if not already: toastr.options = { closeButton: true, progressBar: true }; var Toast = toastr;)
            function deleteSubAdmin(id) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/sub-admins/destroy/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', response.message, 'success');
                                    $('#data-table').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('Error!', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Something went wrong.', 'error');
                            }
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection