@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
    <style>
        .filter-bar { gap: 10px; }
        .status-filter .btn { border-radius: 20px; font-size: 0.8rem; }
        .status-filter .btn.active { font-weight: 600; }
    </style>
@endpush
@section('title', 'Order Tracking')
@section('content')
    <div class="app-content content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px">
                <h3 class="card-title mb-0">Order Tracking</h3>
                <div class="d-flex flex-wrap status-filter" style="gap:6px">
                    <button class="btn btn-sm btn-outline-secondary active" onclick="filterStatus('all', this)">All</button>
                    <button class="btn btn-sm btn-outline-warning" onclick="filterStatus('pending', this)">Pending</button>
                    <button class="btn btn-sm btn-outline-info" onclick="filterStatus('accepted', this)">Distribution</button>
                    <button class="btn btn-sm btn-outline-primary" onclick="filterStatus('preparing', this)">Preparing</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="filterStatus('ready', this)">Ready</button>
                    <button class="btn btn-sm btn-outline-dark" onclick="filterStatus('picked', this)">Picked</button>
                    <button class="btn btn-sm btn-outline-success" onclick="filterStatus('delivered', this)">Delivered</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="filterStatus('canceled', this)">Canceled</button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive mt-3 p-2 card-datatable pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>Vendor</th>
                                <th>Items</th>
                                <th>Delivery Driver</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
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

        <script>
            let currentStatus = 'all';
            let table;

            $(document).ready(function () {
                table = $('#data-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('admin.orders.index') }}",
                        type: "GET",
                        data: function (d) {
                            d.status = currentStatus;
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'order_no', name: 'order_no' },
                        { data: 'customer', name: 'customer' },
                        { data: 'vendor', name: 'vendor' },
                        { data: 'items_summary', name: 'items_summary', orderable: false, searchable: false },
                        { data: 'delivery_man', name: 'delivery_man', orderable: false },
                        { data: 'total', name: 'total_amount' },
                        { data: 'payment_gateway', name: 'payment_gateway' },
                        { data: 'status', name: 'status' },
                        { data: 'created_at', name: 'created_at' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ]
                });
            });

            function filterStatus(status, btn) {
                currentStatus = status;
                // Update button active state
                document.querySelectorAll('.status-filter .btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                // Reload table
                table.ajax.reload();
            }
        </script>
    @endpush
@endsection
