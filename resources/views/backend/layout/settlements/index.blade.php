@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Payment Settlements')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Payment Settlements</h3>
                <div>
                    <a href="{{ route('admin.settlements.pending_users') }}" class="btn btn-primary px-4">
                        <i class="fa-solid fa-plus"></i> New Settlement
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Settled At</th>
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
                            url: "{{ route('admin.settlements.index') }}",
                            type: "get",
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'user', name: 'user' },
                            { data: 'amount', name: 'amount' },
                            { data: 'status', name: 'status' },
                            { data: 'settled_at', name: 'settled_at' },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ]
                    });
                }
            });

            function processSettlement(id) {
                Swal.fire({
                    title: 'Process Settlement?',
                    text: 'This will move the money from pending to liquid balance.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Settle'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('admin.settlements.process', ':id') }}".replace(':id', id), function(res) {
                            if (res.success) {
                                Swal.fire('Success', res.message, 'success');
                                $('#data-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection
