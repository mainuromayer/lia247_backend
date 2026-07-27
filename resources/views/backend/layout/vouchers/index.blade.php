@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Vouchers')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">{{ request()->query('type') == 'ride' ? 'Ride' : 'Food' }} Vouchers</h3>
                <div class="d-flex align-items-center" style="gap:15px">
                    <select id="filter-owner" class="form-select form-select-sm" style="width:180px">
                        <option value="">All Owners</option>
                        <option value="admin">Admin Only</option>
                        <option value="vendor">Vendor Only</option>
                    </select>
                    <a href="{{ route('admin.vouchers.create', ['type' => request()->query('type')]) }}" class="btn btn-primary px-4 btn-sm">
                        <i class="fa-solid fa-plus"></i> Add Voucher
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Min. Order</th>
                                <th>Type</th>
                                <th>Owner</th>
                                <th>Validity</th>
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
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.vouchers.index') }}",
                            type: "get",
                            data: function(d) {
                                d.type = new URLSearchParams(window.location.search).get('type');
                                d.owner_role = $('#filter-owner').val();
                            }
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'code', name: 'code' },
                            { data: 'discount', name: 'discount' },
                            { data: 'min_order_amount', name: 'min_order_amount' },
                            { data: 'type', name: 'type' },
                            { data: 'owner', name: 'owner', searchable: false },
                            { data: 'valid_until', name: 'valid_until' },
                            { data: 'status', name: 'status', orderable: false, searchable: false },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ]
                    });
                }

                $('#filter-owner').on('change', function() {
                    $('#data-table').DataTable().ajax.reload();
                });
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
                let url = "{{ route('admin.vouchers.status', ':id') }}";
                $.ajax({
                    type: "GET",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        Swal.fire({ icon: resp.success ? "success" : "error", title: resp.message, showConfirmButton: false, timer: 800 });
                        $('#data-table').DataTable().ajax.reload();
                    }
                });
            }

            async function deleteVoucher(id) {
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
                        $.post("{{ route('admin.vouchers.destroy') }}", { id: id, _token: '{{ csrf_token() }}' }, function(response) {
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
