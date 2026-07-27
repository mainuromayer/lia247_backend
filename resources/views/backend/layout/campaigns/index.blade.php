@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Free Delivery Campaigns')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Free Delivery Campaigns</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable table-responsive pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Business Name</th>
                                <th>Vendor</th>
                                <th>Campaign Type</th>
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
                            url: "{{ route('admin.campaigns.index') }}",
                            type: "get",
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'business_name', name: 'business_name' },
                            { data: 'vendor_name', name: 'vendor_name' },
                            { data: 'type', name: 'type' },
                            { data: 'status', name: 'status', orderable: false, searchable: false },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ]
                    });
                }
            });

            function toggleFreeDelivery(id) {
                let url = "{{ route('admin.campaigns.status', ':id') }}";
                $.ajax({
                    type: "POST",
                    url: url.replace(':id', id),
                    success: function(resp) {
                        if(resp.success) {
                            toastr.success(resp.message);
                        } else {
                            toastr.error(resp.message);
                        }
                        $('#data-table').DataTable().ajax.reload();
                    }
                })
            }
        </script>
    @endpush
@endsection
