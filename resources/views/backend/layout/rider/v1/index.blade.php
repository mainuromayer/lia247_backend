@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Rideshare Drivers')
@section('content')
    <div class="app-content content ">
        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Rideshare Driver List</h3>
                <div>
                    <button type="button" onclick="bulkDelete()" class="btn btn-danger delete_btn d-none">
                        <i class="fa-solid fa-trash-can"></i> Bulk Delete
                    </button>
                    <a href="{{ route('admin.rider.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus"></i> Add Rideshare Driver
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable table-responsive pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-checkbox">
                                        <input type="checkbox" class="form-check-input" id="select_all"
                                            onclick="select_all()">
                                        <label class="form-check-label" for="select_all"></label>
                                    </div>
                                </th>
                                <th>Name</th>
                                <th>Email</th>
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
                    $('#data-table').DataTable({
                        order: [],
                        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.rider.index') }}",
                            type: "get",
                        },
                        columns: [
                            { data: 'bulk_check', name: 'bulk_check', orderable: false, searchable: false },
                            { data: 'name', name: 'name' },
                            { data: 'email', name: 'email' },
                            { data: 'status', name: 'status', orderable: false, searchable: false },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ],
                        drawCallback: function() {
                            $('#select_all').prop('checked', false);
                            $('.delete_btn').addClass('d-none');
                        }
                    });
                }
            });



            function bulkDelete() {
                var ids = [];
                $('.select_data:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Please select at least one item' });
                    return;
                }

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
                        $.post("{{ route('admin.rider.bulk-delete') }}", { ids: ids }, function(resp) {
                            if (resp.success) {
                                Swal.fire({ icon: 'success', title: resp.message, showConfirmButton: false, timer: 1500 });
                                $('#data-table').DataTable().ajax.reload();
                                $('#select_all').prop('checked', false);
                            } else {
                                Swal.fire({ icon: 'error', title: resp.message });
                            }
                        });
                    }
                });
            }

            function showStatusChangeAlert(id) {
                event.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to update the status?',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                }).then((result) => {
                    if (result.isConfirmed) {
                        statusChange(id);
                    }
                });
            }

            function statusChange(id) {
                let url = "{{ route('admin.rider.status', ':id') }}";
                $.ajax({
                    type: "GET",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        Swal.fire({ icon: resp.success ? "success" : "error", title: resp.message, showConfirmButton: false, timer: 800 });
                        $('#data-table').DataTable().ajax.reload();
                    }
                })
            }

            async function deleteUser(id) {
                const { value: password } = await Swal.fire({
                    title: "Delete this account?",
                    input: "password",
                    inputLabel: "Enter your password",
                    showCancelButton: true,
                });

                if (password) {
                    $.post("{{ route('admin.rider.destroy') }}", { id: id, password: password, _token: '{{ csrf_token() }}' }, function(response) {
                        if (response.success) {
                            Swal.fire({ icon: 'success', title: response.message, showConfirmButton: false, timer: 2000 });
                            $('#data-table').DataTable().ajax.reload();
                        } else {
                            Swal.fire({ icon: 'error', title: response.message });
                        }
                    }).fail(xhr => Swal.fire({ icon: 'error', title: xhr.responseJSON?.message || 'Something Went Wrong' }));
                }
            }

            function accessAccount(id) {
                let url = "{{ route('admin.rider.activate', ':id') }}";
                Swal.fire({
                    title: 'Send Access Email?',
                    text: "Account will be activated and an email will be sent to the user.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Send',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "GET",
                            url: url.replace(':id', id),
                            success: function(resp) {
                                Swal.fire({ icon: resp.success ? "success" : "error", title: resp.message, showConfirmButton: false, timer: 1500 });
                                $('#data-table').DataTable().ajax.reload();
                            }
                        })
                    }
                });
            }
        </script>
    @endpush
@endsection
