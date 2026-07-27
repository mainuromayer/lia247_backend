@extends('backend.app')

@section('title', 'Dashboard')

@push('style')
<style>
    .stats-card { border-radius: 15px; overflow: hidden; transition: all 0.3s ease; border: none; }
    .stats-card:hover { transform: translateY(-5px); }
    .card-title-premium { font-weight: 700; color: #5e5873; margin-bottom: 1.5rem; }
    .table-premium thead th { background: #f8f8f8; border-top: none; font-weight: 600; font-size: 0.85rem; color: #5e5873; }
    .table-premium tbody td { vertical-align: middle; font-size: 0.9rem; color: #6e6b7b; }
    .period-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
    .chart-period-select { width: 120px; border-radius: 8px; font-size: 0.85rem; }
</style>
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-body">
            
            <!-- Welcome Header -->
            <div class="row match-height mb-2 mt-2">
                <div class="col-12">
                    <div class="card bg-primary text-white" style="border-radius: 15px;">
                        <div class="card-body d-flex justify-content-between align-items-center py-2">
                            <div>
                                <h2 class="text-white fw-bold mb-0">{{ $greeting['message'] }}, {{ $user->name }}!</h2>
                                <p class="mb-0">Your platform performance at a glance.</p>
                            </div>
                            <div class="d-none d-md-block">
                                <img src="{{ asset('backend/app-assets/images/illustration/email.svg') }}" alt="welcome" width="80">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Stats -->
            <div class="row">
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card stats-card shadow-sm mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="fw-bolder mb-0 text-primary">${{ number_format($stats['total_revenue'], 2) }}</h3>
                                    <p class="card-text text-muted mb-0">Total Revenue</p>
                                    <small class="text-success fw-bold">+${{ number_format($stats['today_revenue'], 2) }} today</small>
                                </div>
                                <div class="avatar bg-light-primary p-50 align-self-center">
                                    <div class="avatar-content"><i data-feather="dollar-sign" class="font-medium-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card stats-card shadow-sm mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="fw-bolder mb-0 text-success">{{ number_format($stats['total_orders']) }}</h3>
                                    <p class="card-text text-muted mb-0">Total Orders</p>
                                    <small class="text-info fw-bold">{{ $stats['today_orders'] }} new today</small>
                                </div>
                                <div class="avatar bg-light-success p-50 align-self-center">
                                    <div class="avatar-content"><i data-feather="shopping-bag" class="font-medium-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card stats-card shadow-sm mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="fw-bolder mb-0 text-info">{{ number_format($stats['total_customers']) }}</h3>
                                    <p class="card-text text-muted mb-0">Customers</p>
                                    <small class="text-muted">Total registered</small>
                                </div>
                                <div class="avatar bg-light-info p-50 align-self-center">
                                    <div class="avatar-content"><i data-feather="users" class="font-medium-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card stats-card shadow-sm mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="fw-bolder mb-0 text-warning">{{ number_format($stats['total_vendors']) }}</h3>
                                    <p class="card-text text-muted mb-0">Vendors</p>
                                    <small class="text-muted">Active partners</small>
                                </div>
                                <div class="avatar bg-light-warning p-50 align-self-center">
                                    <div class="avatar-content"><i data-feather="home" class="font-medium-5"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row match-height">
                <!-- Revenue Chart -->
                <div class="col-lg-8 col-12">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Revenue Analytics</h4>
                            <select id="period-select" class="form-select chart-period-select">
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="card-body"><div id="revenue-chart"></div></div>
                    </div>
                </div>
                <!-- Order Summary -->
                <div class="col-lg-4 col-12">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-header"><h4 class="card-title">Order Status Summary</h4></div>
                        <div class="card-body">
                            @foreach($orderSummary as $status => $count)
                                <div class="mb-1">
                                    <div class="d-flex justify-content-between align-items-center mb-50">
                                        <span class="fw-bold">{{ ucfirst($status === 'accepted' ? 'distribution' : $status) }}</span>
                                        <span>{{ $count }}</span>
                                    </div>
                                    @php
                                        $percent = $stats['total_orders'] > 0 ? ($count / $stats['total_orders']) * 100 : 0;
                                        $color = match($status) {
                                            'pending' => 'warning',
                                            'accepted', 'preparing', 'ready' => 'info',
                                            'delivered', 'completed' => 'success',
                                            'canceled' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <div class="progress progress-bar-{{ $color }}" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-7 col-12">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-header border-bottom d-flex justify-content-between">
                            <h4 class="card-title">Recent Orders</h4>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-premium table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order No</th>
                                            <th>Customer</th>
                                            <th>Vendor</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentOrders as $order)
                                            <tr>
                                                <td><a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold text-primary">{{ $order->order_no }}</a></td>
                                                <td>{{ $order->user->name ?? 'N/A' }}</td>
                                                <td>{{ $order->vendor->name ?? 'N/A' }}</td>
                                                <td>${{ number_format($order->total_amount, 2) }}</td>
                                                <td>
                                                    @php
                                                        $badgeColor = match($order->status) {
                                                            'pending' => 'warning',
                                                            'accepted', 'preparing', 'ready' => 'info',
                                                            'delivered', 'completed' => 'success',
                                                            'canceled' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge rounded-pill bg-light-{{ $badgeColor }}">
                                                        {{ ucfirst($order->status === 'accepted' ? 'distribution' : $order->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center py-2 text-muted">No orders found</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="col-lg-5 col-12">
                    <div class="card shadow-sm" style="border-radius: 15px;">
                        <div class="card-header border-bottom"><h4 class="card-title">Recent Wallet Activities</h4></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-premium table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Amount</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentTransactions as $tx)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar bg-light-primary me-1"><div class="avatar-content">{{ strtoupper(substr($tx->wallet->user->name ?? 'U', 0, 1)) }}</div></div>
                                                        <div><div class="fw-bold">{{ $tx->wallet->user->name ?? 'N/A' }}</div><small class="text-muted">{{ $tx->created_at->diffForHumans() }}</small></div>
                                                    </div>
                                                </td>
                                                <td class="fw-bold {{ $tx->type == 'credit' ? 'text-success' : 'text-danger' }}">
                                                    {{ $tx->type == 'credit' ? '+' : '-' }}${{ number_format($tx->amount, 2) }}
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-light-{{ $tx->type == 'credit' ? 'success' : 'danger' }}">{{ ucfirst($tx->type) }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center py-2 text-muted">No activities found</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const allChartData = {!! $allChartData !!};
        const periodSelect = document.getElementById('period-select');
        
        const getSeries = (period) => [{ 
            name: 'Revenue', 
            data: allChartData[period].map(item => item.value) 
        }];
        
        const getCategories = (period) => allChartData[period].map(item => item.label);

        const options = {
            series: getSeries('monthly'),
            chart: { height: 350, type: 'area', toolbar: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            colors: ['#7367f0'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2, stops: [0, 90, 100] } },
            xaxis: { categories: getCategories('monthly') },
            yaxis: { labels: { formatter: function (val) { return "$" + val.toFixed(0); } } },
            tooltip: { y: { formatter: function (val) { return "$" + val.toFixed(2); } } }
        };

        const chart = new ApexCharts(document.querySelector("#revenue-chart"), options);
        chart.render();

        periodSelect.addEventListener('change', function() {
            const period = this.value;
            chart.updateOptions({
                xaxis: { categories: getCategories(period) }
            });
            chart.updateSeries(getSeries(period));
        });
    });
</script>
@endpush