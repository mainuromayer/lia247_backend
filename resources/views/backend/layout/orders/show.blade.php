@extends('backend.app')
@section('title', 'Order Detail - ' . $order->order_no)
@section('content')
<div class="app-content content">
    <div class="d-flex align-items-center mb-3" style="gap:12px">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <h4 class="mb-0">Order Detail &nbsp;<strong>{{ $order->order_no }}</strong></h4>
    </div>

    <div class="row">
        {{-- LEFT: Order Info --}}
        <div class="col-md-8">

            {{-- Order Items --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Order Items</h5></div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Variant</th>
                                <th>Toppings</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->variant?->name ?? '—' }}</td>
                                <td>
                                    @if($item->selected_toppings)
                                        @php $toppings = is_string($item->selected_toppings) ? json_decode($item->selected_toppings, true) : $item->selected_toppings; @endphp
                                        @foreach($toppings ?? [] as $t)
                                            <span class="badge bg-secondary">{{ $t['name'] ?? '' }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->price, 2) }}</td>
                                <td>${{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted">No items found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Financial Summary --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Financial Summary</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless" style="max-width:400px">
                        <tr><td>Subtotal</td><td class="text-end fw-bold">${{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td>Discount ({{ $order->discount_type ? ucfirst(str_replace('_',' ',$order->discount_type)) : '—' }})</td><td class="text-end text-success">- ${{ number_format($order->discount_amount, 2) }}</td></tr>
                        <tr><td>VAT</td><td class="text-end">${{ number_format($order->vat_amount, 2) }}</td></tr>
                        <tr><td>Delivery Fee</td><td class="text-end">${{ number_format($order->delivery_fee, 2) }}</td></tr>
                        <tr><td>Tips</td><td class="text-end">${{ number_format($order->tip_amount ?? 0, 2) }}</td></tr>
                        <tr class="border-top"><td><strong>Grand Total</strong></td><td class="text-end"><strong>${{ number_format($order->total_amount, 2) }}</strong></td></tr>
                    </table>
                </div>
            </div>

        </div>

        {{-- RIGHT: Status & Parties --}}
        <div class="col-md-4">

            {{-- Status Update --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Order Status</h5></div>
                <div class="card-body">
                    @php
                        $statusBadges = [
                            'pending'   => 'warning',   'accepted' => 'info',
                            'preparing' => 'primary',   'ready'    => 'secondary',
                            'picked'    => 'dark',      'delivered'=> 'success',
                            'completed' => 'success',   'canceled' => 'danger',
                        ];
                        $badge = $statusBadges[$order->status] ?? 'secondary';
                    @endphp
                    <div class="mb-3 text-center">
                        <span class="badge bg-{{ $badge }} fs-6 px-4 py-2">{{ $order->status === 'accepted' ? 'Distribution' : ucfirst($order->status) }}</span>
                    </div>

                    {{-- Status Update Form (Admin override) --}}
                    <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Update Status</label>
                            <select name="status" class="form-select form-select-sm">
                                @foreach(['pending','accepted','preparing','ready','picked','delivered','completed','canceled'] as $s)
                                    <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ $s === 'accepted' ? 'Distribution' : ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Update Status</button>
                    </form>
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Customer (Receiver)</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $order->user?->name ?? 'N/A' }}</strong></p>
                    <p class="mb-1 text-muted"><i class="fa-solid fa-envelope me-1"></i>{{ $order->user?->email ?? '' }}</p>
                    <p class="mb-1 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $order->user?->phone ?? '' }}</p>
                    <hr class="my-2">
                    <p class="mb-1"><strong>Receiver Details:</strong></p>
                    <p class="mb-1"><small class="text-muted">Name:</small> {{ $order->receiver_name ?? '—' }}</p>
                    <p class="mb-1"><small class="text-muted">Phone:</small> {{ $order->receiver_phone ?? '—' }}</p>
                    <p class="mb-1"><small class="text-muted">Address:</small> {{ $order->receiver_address ?? '—' }}</p>
                    @if($order->latitude && $order->longitude)
                        <p class="mb-0"><small class="text-muted">Location:</small> 
                            <a href="https://www.google.com/maps?q={{ $order->latitude }},{{ $order->longitude }}" target="_blank" class="badge bg-light-primary text-primary">
                                <i class="fa-solid fa-location-dot me-1"></i>View on Map ({{ $order->latitude }}, {{ $order->longitude }})
                            </a>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Vendor Info --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Vendor (Sender)</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $order->vendor->business->business_name ?? $order->vendor->name ?? 'N/A' }}</strong></p>
                    <p class="mb-1 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $order->vendor->business->business_phone ?? $order->vendor->phone ?? 'N/A' }}</p>
                    <p class="mb-1 text-muted"><i class="fa-solid fa-location-arrow me-1"></i>{{ $order->vendor->business->business_address ?? $order->vendor->address ?? 'N/A' }}</p>
                    @php
                        $vLat = $order->vendor->business->latitude ?? $order->vendor->latitude;
                        $vLng = $order->vendor->business->longitude ?? $order->vendor->longitude;
                    @endphp
                    @if($vLat && $vLng)
                        <p class="mb-0">
                            <a href="https://www.google.com/maps?q={{ $vLat }},{{ $vLng }}" target="_blank" class="badge bg-light-info text-info">
                                <i class="fa-solid fa-map me-1"></i>Vendor Location ({{ $vLat }}, {{ $vLng }})
                            </a>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Delivery Man --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Delivery Driver</h5></div>
                <div class="card-body">
                    @if($order->deliveryMan)
                        <p class="mb-1"><strong>{{ $order->deliveryMan->name }}</strong></p>
                        <p class="mb-1 text-muted"><i class="fa-solid fa-envelope me-1"></i>{{ $order->deliveryMan->email }}</p>
                        <p class="mb-1 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $order->deliveryMan->phone }}</p>
                        @if($order->delivery_man_address)
                            <hr class="my-2">
                            <p class="mb-1"><strong>Recorded Location:</strong></p>
                            <p class="mb-1 text-muted"><i class="fa-solid fa-map-pin me-1"></i>{{ $order->delivery_man_address }}</p>
                            @if($order->delivery_man_lat && $order->delivery_man_lng)
                                <p class="mb-0">
                                    <a href="https://www.google.com/maps?q={{ $order->delivery_man_lat }},{{ $order->delivery_man_lng }}" target="_blank" class="badge bg-light-secondary text-secondary">
                                        <i class="fa-solid fa-location-arrow me-1"></i>View on Map ({{ $order->delivery_man_lat }}, {{ $order->delivery_man_lng }})
                                    </a>
                                </p>
                            @endif
                        @endif
                    @else
                        <p class="mb-0 text-muted">Not assigned yet</p>
                    @endif
                </div>
            </div>

            {{-- Order Meta --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Order Info</h5></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td>Type</td><td class="fw-bold">{{ ucfirst($order->order_type) }}</td></tr>
                        <tr><td>Gateway</td><td class="fw-bold">{{ ucfirst($order->payment_gateway ?? '—') }}</td></tr>
                        <tr><td>Voucher</td><td class="fw-bold">{{ $order->voucher_id ? '#'.$order->voucher_id : '—' }}</td></tr>
                        <tr><td>Notes</td><td>{{ $order->notes ?? '—' }}</td></tr>
                        <tr><td>Placed At</td><td>{{ $order->created_at->format('d M Y, h:i A') }}</td></tr>
                        <tr><td>Updated At</td><td>{{ $order->updated_at->format('d M Y, h:i A') }}</td></tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

@push('script')
<script>
    @if(session('success'))
        alert("{{ session('success') }}");
    @endif
</script>
@endpush
@endsection
