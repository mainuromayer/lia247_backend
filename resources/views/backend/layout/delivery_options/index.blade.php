@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Delivery Options')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Delivery Options</h3>
                <div>
                    <a href="{{ route('admin.delivery_options.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus"></i> Add Option
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Sub Text</th>
                                <th>Price</th>
                                <th>Est. Time</th>
                                <th>Default</th>
                                <th>Active</th>
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
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.delivery_options.index') }}",
                            type: "get",
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'image', name: 'image', orderable: false, searchable: false },
                            { data: 'name', name: 'name' },
                            { data: 'sub_text', name: 'sub_text' },
                            { data: 'price', name: 'price' },
                            { data: 'estimated_time', name: 'estimated_time' },
                            { data: 'is_default', name: 'is_default', orderable: false, searchable: false },
                            { data: 'status', name: 'status', orderable: false, searchable: false },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ]
                    });
                }
            });

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
                    } else {
                        $('#data-table').DataTable().ajax.reload();
                    }
                });
            }

            function statusChange(id) {
                let url = "{{ route('admin.delivery_options.status', ':id') }}";
                $.ajax({
                    type: "GET",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        Swal.fire({ icon: resp.success ? "success" : "error", title: resp.message, showConfirmButton: false, timer: 800 });
                        $('#data-table').DataTable().ajax.reload();
                    }
                });
            }

            function setDefault(id) {
                let url = "{{ route('admin.delivery_options.set_default', ':id') }}";
                $.ajax({
                    type: "GET",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        Swal.fire({ icon: resp.success ? "success" : "error", title: resp.message, showConfirmButton: false, timer: 800 });
                        $('#data-table').DataTable().ajax.reload();
                    }
                });
            }

            async function deleteOption(id) {
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
                        $.post("{{ route('admin.delivery_options.destroy') }}", { id: id, _token: '{{ csrf_token() }}' }, function(response) {
                            if (response.success) {
                                Swal.fire({ icon: 'success', title: response.message, showConfirmButton: false, timer: 1500 });
                                $('#data-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire({ icon: 'error', title: response.message });
                            }
                        }).fail(xhr => Swal.fire({ icon: 'error', title: 'Something Went Wrong' }));
                    }
                });
            }
        </script>
    @endpush
@endsection
