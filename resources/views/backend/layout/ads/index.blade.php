@extends('backend.app')
@push('style')
    <link rel="stylesheet" href="{{ asset('backend/assets/datatable/css/datatables.min.css') }}">
@endpush
@section('title', 'Ads Management')
@section('content')
    <div class="app-content content ">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Ads Management</h3>
                <a href="{{ route('admin.ads.create') }}" class="btn btn-primary px-4">
                    <i class="fa-solid fa-plus"></i> Add New Ad
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive mt-4 p-4 card-datatable table-responsive pt-0">
                    <table class="table table-hover" id="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>URL</th>
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
                            url: "{{ route('admin.ads.index') }}",
                            type: "get",
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'image', name: 'image', orderable: false, searchable: false },
                            { data: 'url', name: 'url' },
                            { data: 'status', name: 'status', orderable: false, searchable: false },
                            { data: 'action', name: 'action', orderable: false, searchable: false },
                        ]
                    });
                }
            });

            function showStatusChangeAlert(id) {
                Swal.fire({
                    title: 'Update Status?',
                    text: 'Are you sure you want to change the visibility of this ad?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Update'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('admin.ads.status', ':id') }}".replace(':id', id), function(resp) {
                            if(resp.success) {
                                Swal.fire({ icon: 'success', title: resp.message, showConfirmButton: false, timer: 1500 });
                                $('#data-table').DataTable().ajax.reload();
                            }
                        });
                    }
                });
            }

            function deleteAd(id) {
                Swal.fire({
                    title: 'Delete Ad?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('admin.ads.delete', ':id') }}".replace(':id', id), function(resp) {
                            if(resp.success) {
                                Swal.fire({ icon: 'success', title: resp.message, showConfirmButton: false, timer: 1500 });
                                $('#data-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire({ icon: 'error', title: resp.message });
                            }
                        });
                    }
                });
            }

            function sendPromoEmail(id) {
                Swal.fire({
                    title: 'Send Promotion Email?',
                    text: 'This will send this ad/promotion email to all registered active customers.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#28c76f',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Send it!',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.post("{{ route('admin.ads.sendMail', ':id') }}".replace(':id', id))
                            .then(response => {
                                if (!response.success) {
                                    throw new Error(response.message || 'Something went wrong');
                                }
                                return response;
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`Request failed: ${error.responseJSON ? error.responseJSON.message : (error.responseText || error.message || error)}`);
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Dispatched Successfully!',
                            text: result.value.message || 'Emails are being sent to all active customers.',
                            showConfirmButton: true
                        });
                    }
                });
            }
        </script>
    @endpush
@endsection
