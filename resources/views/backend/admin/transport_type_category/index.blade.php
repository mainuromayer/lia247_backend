@extends('backend.app')

@section('title', 'Transport Type Categories')

@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush

@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Transport Type Category List</h3>
                    <a href="{{ route('admin.transport_type_categories.create') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus"></i> Add Category
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive mt-4 p-4 card-datatable pt-0">
                        <table class="table table-hover" id="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
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
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('backend/assets/datatable/js/datatables.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#data-table')) {
            $('#data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.transport_type_categories.index') }}",
                    type: "get",
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
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
        let url = "{{ route('admin.transport_type_categories.status', ':id') }}";
        $.ajax({
            type: "GET",
            url: url.replace(':id', id),
            success: function(resp) {
                Swal.fire({ 
                    icon: resp.success ? "success" : "error", 
                    title: resp.message, 
                    showConfirmButton: false, 
                    timer: 800 
                });
                $('#data-table').DataTable().ajax.reload();
            },
            error: function() {
                Swal.fire({ icon: "error", title: "Something went wrong", showConfirmButton: false, timer: 800 });
                $('#data-table').DataTable().ajax.reload();
            }
        })
    }

    function deleteCategory(id) {
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
                    type: "POST",
                    url: "{{ route('admin.transport_type_categories.destroy') }}",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({ icon: 'success', title: resp.message, showConfirmButton: false, timer: 1500 });
                            $('#data-table').DataTable().ajax.reload();
                        } else {
                            Swal.fire({ icon: 'error', title: resp.message });
                        }
                    }
                });
            }
        });
    }
</script>
@endpush
