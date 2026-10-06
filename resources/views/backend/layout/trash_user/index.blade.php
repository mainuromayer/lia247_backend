@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
    <style>
        .badge.bg-light-primary {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
        }
        .badge.bg-light-success {
            background-color: #dcfce7 !important;
            color: #15803d !important;
        }
        .badge.bg-light-warning {
            background-color: #fef9c3 !important;
            color: #a16207 !important;
        }
        .badge.bg-light-info {
            background-color: #e0e7ff !important;
            color: #4338ca !important;
        }
    </style>
@endpush
@section('title', 'Trash Users')
@section('content')
    <div class="app-content content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Trash Users Management</h3>
                    <small class="text-muted">Soft-deleted users archived for data retention. You can restore or permanently delete them.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" onclick="bulkRestore()" class="btn btn-success bulk-btn d-none">
                        <i class="fa-solid fa-rotate-left"></i> Bulk Restore
                    </button>
                    <button type="button" onclick="bulkForceDelete()" class="btn btn-danger bulk-btn d-none">
                        <i class="fa-solid fa-trash"></i> Bulk Permanent Delete
                    </button>
                    <button type="button" onclick="$('#trash-table').DataTable().ajax.reload()" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-3 card-datatable pt-0">
                    <table class="table table-hover" id="trash-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <div class="form-checkbox">
                                        <input type="checkbox" class="form-check-input" id="select_all" onclick="select_all()">
                                        <label class="form-check-label" for="select_all"></label>
                                    </div>
                                </th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Replaced Email</th>
                                <th>Original Email</th>
                                <th>Role</th>
                                <th>Deleted At</th>
                                <th style="width: 200px;">Action</th>
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

                if (!$.fn.DataTable.isDataTable('#trash-table')) {
                    $('#trash-table').DataTable({
                        order: [[6, 'desc']],
                        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.trash_user.index') }}",
                            type: "GET",
                        },
                        columns: [
                            { data: 'bulk_check', name: 'bulk_check', orderable: false, searchable: false },
                            { data: 'account_id', name: 'id' },
                            { data: 'name', name: 'name' },
                            { data: 'email', name: 'email' },
                            { data: 'original_email_display', name: 'original_email' },
                            { data: 'user_type_badge', name: 'user_type' },
                            { data: 'deleted_date', name: 'deleted_at' },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ],
                        drawCallback: function() {
                            $('#select_all').prop('checked', false);
                            $('.bulk-btn').addClass('d-none');
                        }
                    });
                }
            });

            function select_all() {
                let checked = $('#select_all').is(':checked');
                $('.select_data').prop('checked', checked);
                toggleBulkButtons();
            }

            $(document).on('change', '.select_data', function() {
                toggleBulkButtons();
            });

            function toggleBulkButtons() {
                let count = $('.select_data:checked').length;
                if (count > 0) {
                    $('.bulk-btn').removeClass('d-none');
                } else {
                    $('.bulk-btn').addClass('d-none');
                }
            }

            // Restore Single User
            function restoreUser(id) {
                Swal.fire({
                    title: 'Restore User Account?',
                    text: 'This user will be restored from trash and set back to active status.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Restore User'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('admin/trash-users/restore') }}/" + id,
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Restored!', response.message, 'success');
                                    $('#trash-table').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('Error!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON?.message || 'Failed to restore user.';
                                Swal.fire('Error!', msg, 'error');
                            }
                        });
                    }
                });
            }

            // Permanent Force Delete Single User
            function forceDeleteUser(id) {
                Swal.fire({
                    title: 'Permanently Delete User?',
                    text: 'This action is IRREVERSIBLE. All records associated with this user will be purged from the database.',
                    icon: 'warning',
                    input: 'password',
                    inputPlaceholder: 'Enter admin password to confirm',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Permanently Delete',
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Admin password is required');
                        }
                        return password;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('admin/trash-users/force-delete') }}/" + id,
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                password: result.value
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', response.message, 'success');
                                    $('#trash-table').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('Error!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON?.message || 'Failed to permanently delete user.';
                                Swal.fire('Error!', msg, 'error');
                            }
                        });
                    }
                });
            }

            // Bulk Restore
            function bulkRestore() {
                var ids = [];
                $('.select_data:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length <= 0) {
                    Swal.fire('Warning', 'Please select at least one user', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Restore Selected Users?',
                    text: `Are you sure you want to restore ${ids.length} selected users?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Restore All'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.trash_user.bulk_restore') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                ids: ids
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Restored!', response.message, 'success');
                                    $('#trash-table').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('Error!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON?.message || 'Bulk restore failed.';
                                Swal.fire('Error!', msg, 'error');
                            }
                        });
                    }
                });
            }

            // Bulk Force Delete
            function bulkForceDelete() {
                var ids = [];
                $('.select_data:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length <= 0) {
                    Swal.fire('Warning', 'Please select at least one user', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Permanently Delete Selected Users?',
                    text: `This action will permanently purge ${ids.length} users. Enter admin password to confirm.`,
                    icon: 'warning',
                    input: 'password',
                    inputPlaceholder: 'Enter admin password',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Permanently Delete Selected',
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Admin password is required');
                        }
                        return password;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.trash_user.bulk_force_delete') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                ids: ids,
                                password: result.value
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', response.message, 'success');
                                    $('#trash-table').DataTable().ajax.reload();
                                } else {
                                    Swal.fire('Error!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON?.message || 'Bulk delete failed.';
                                Swal.fire('Error!', msg, 'error');
                            }
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection
